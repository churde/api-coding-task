<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Controllers\CharacterController;
use App\Models\Character;
use OpenApi\Annotations as OA;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Cache;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response as SlimResponse;
use App\Services\Auth;
use App\Middleware\RateLimitMiddleware;
use App\Services\CharacterService;
use DI\Container;

// Add this near the top of your file, with the other OpenAPI annotations

/**
 * @OA\Info(
 *     title="LOTR API",
 *     version="1.0.0",
 *     description="API for Lord of the Rings characters with authentication"
 * )
 */

/**
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * )
 */

/**
 * @OA\Schema(
 *     schema="Character",
 *     required={"name", "birth_date", "kingdom"},
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="birth_date", type="string", format="date"),
 *     @OA\Property(property="kingdom", type="string"),
 *     @OA\Property(property="equipment_id", type="integer"),
 *     @OA\Property(property="faction_id", type="integer")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Error",
 *     @OA\Property(property="error", type="string")
 * )
 */

// Create Container
$container = new Container();

// Set up container definitions
$container->set('db', function () {
    return new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
});

$container->set('cache', function () {
    return new Cache();
});

$container->set('cacheConfig', function () {
    return require __DIR__ . '/../config/cache_config.php';
});

$container->set('tokenManager', function () {
    $secretKey = getenv('JWT_SECRET_KEY') ?: 'your-secret-key';
    return new \App\Services\TokenManager($secretKey);
});

$container->set('permissionChecker', function ($c) {
    return new \App\Services\PermissionChecker($c->get('cache'), $c->get('db'));
});

$container->set('auth', function ($c) {
    return new Auth(
        new \App\Services\AuthenticationService($c->get('tokenManager')),
        new \App\Services\AuthorizationService($c->get('permissionChecker'))
    );
});

$container->set('characterModel', function ($c) {
    return new Character($c->get('db'));
});

$container->set('characterRepository', function ($c) {
    return new \App\Repositories\CharacterRepository($c->get('db'), $c->get('characterModel'));
});

$container->set('characterRepositoryInterface', function ($c) {
    return $c->get('characterRepository');
});


$container->set('log', function () {
    $log = new Logger('api');
    $log->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
    return $log;
});

$container->set('characterController', function ($c) {
    return new CharacterController(
        new CharacterService(
            $c->get('auth'),
            $c->get('cache'),
            $c->get('cacheConfig'),
            $c->get('characterRepositoryInterface'),
            $c->get('log')
        )
    );
});

// Create the Slim app
$app = AppFactory::createFromContainer($container);

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// Update the API key middleware to use JWT
$authMiddleware = function (Request $request, RequestHandler $handler) use ($container) {
    $auth = $container->get('auth');
    $token = $request->getHeaderLine('Authorization');
    $token = str_replace('Bearer ', '', $token);
    if (!$auth->validateToken($token)) {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
    return $handler->handle($request);
};

// Add the auth middleware
$app->add($authMiddleware);

// Add rate limiting middleware
$rateLimitMiddleware = new RateLimitMiddleware($container->get(Cache::class), 100, 3600);
$app->add($rateLimitMiddleware);

/**
 * @OA\Get(
 *     path="/characters",
 *     summary="Get all characters",
 *     tags={"Characters"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number",
 *         required=false,
 *         @OA\Schema(type="integer", default=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Number of items per page",
 *         required=false,
 *         @OA\Schema(type="integer", default=10)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Character")),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="current_page", type="integer"),
 *                 @OA\Property(property="per_page", type="integer"),
 *                 @OA\Property(property="total_count", type="integer"),
 *                 @OA\Property(property="total_pages", type="integer"),
 *                 @OA\Property(property="cache_used", type="boolean")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     )
 * )
 */
$app->get('/characters', function (Request $request, Response $response) use ($container) {
    return $container->get('characterController')->getAllCharacters($request, $response);
});

/**
 * @OA\Post(
 *     path="/characters",
 *     summary="Create a new character",
 *     tags={"Characters"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "birth_date", "kingdom"},
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="birth_date", type="string", format="date"),
 *             @OA\Property(property="kingdom", type="string"),
 *             @OA\Property(property="equipment_id", type="integer"),
 *             @OA\Property(property="faction_id", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Character created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Character")
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input"
 *     )
 * )
 */
$app->post('/characters', function (Request $request, Response $response) use ($container) {
    return $container->get('characterController')->createCharacter($request, $response);
});

/**
 * @OA\Get(
 *     path="/characters/{id}",
 *     summary="Get a character by ID",
 *     tags={"Characters"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="data", ref="#/components/schemas/Character"),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="cache_used", type="boolean")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Character not found"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     )
 * )
 */
$app->get('/characters/{id}', function (Request $request, Response $response, $args) use ($container) {
    return $container->get('characterController')->getCharacterById($request, $response, $args);
});

/**
 * @OA\Put(
 *     path="/characters/{id}",
 *     summary="Update a character",
 *     tags={"Characters"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/Character")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Character updated successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Character")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Character not found"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     )
 * )
 */
$app->put('/characters/{id}', function (Request $request, Response $response, $args) use ($container) {
    return $container->get('characterController')->updateCharacter($request, $response, $args);
});

/**
 * @OA\Delete(
 *     path="/characters/{id}",
 *     summary="Delete a character",
 *     tags={"Characters"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=204,
 *         description="Character deleted successfully"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Character not found"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     )
 * )
 */
$app->delete('/characters/{id}', function (Request $request, Response $response, $args) use ($container) {
    return $container->get('characterController')->deleteCharacter($request, $response, $args);
});
