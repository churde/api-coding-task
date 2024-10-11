<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Models\Character;
use OpenApi\Annotations as OA;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Cache;
use Predis\Connection\ConnectionException;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response as SlimResponse;
use App\Auth;

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

// Create a log channel
$log = new Logger('api');
$log->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));

$cache = new Cache();

// Create Auth instance
$auth = new Auth($cache);

// Load cache configuration
$cacheConfig = require __DIR__ . '/../config/cache_config.php';

// Update the API key middleware to use JWT
$authMiddleware = function (Request $request, RequestHandler $handler) use ($auth) {
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

// Create the Slim app
$app = AppFactory::create();
// Add body parsing middleware to handle JSON request bodies
// This allows us to easily access parsed request body data in our route handlers
$app->addBodyParsingMiddleware();

// Add the auth middleware
$app->add($authMiddleware);

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
$app->get('/characters', function (Request $request, Response $response) use ($log, $cache, $auth, $cacheConfig) {
    $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
    if (!$auth->hasPermission($token, 'read', 'character')) {
        $response->getBody()->write(json_encode(['error' => 'Forbidden']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }

    $queryParams = $request->getQueryParams();
    $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
    $perPage = isset($queryParams['per_page']) ? (int)$queryParams['per_page'] : 10;

    $cacheKey = "all_characters_with_relations_page_{$page}_perPage_{$perPage}";
    $cachedData = null;
    $cacheUsed = false;

    if ($cacheConfig['enable_cache']['get_all_characters']) {
        $cachedData = $cache->get($cacheKey);
    }

    if ($cachedData) {
        $log->info('Returning cached characters with relations');
        $result = $cachedData;
        $cacheUsed = true;
    } else {
        $log->info('Fetching all characters with relations from database');

        $pdo = new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $characterModel = new Character($pdo);
        $result = $characterModel->getAllCharactersWithRelations($page, $perPage);

        if ($cacheConfig['enable_cache']['get_all_characters']) {
            $cache->set($cacheKey, $result, $cacheConfig['cache_ttl']);
            $log->info('Cached characters with relations for page ' . $page);
        }
    }

    $result['meta']['cache_used'] = $cacheUsed;

    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * @OA\Post(
 *     path="/characters",
 *     summary="Create a new character",
 *     tags={"Characters"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/Character")
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Character created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Character")
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
$app->post('/characters', function (Request $request, Response $response) use ($log, $cache, $auth, $cacheConfig) {
    $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
    if (!$auth->hasPermission($token, 'create', 'character')) {
        $response->getBody()->write(json_encode(['error' => 'Forbidden']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }

    $log->info('Creating a new character');

    $data = $request->getParsedBody();
    $pdo = new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $characterModel = new Character($pdo);
    $newCharacter = $characterModel->createCharacter($data);

    $log->info('Created character with ID: ' . $newCharacter['id']);
    $response->getBody()->write(json_encode($newCharacter));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

    if ($cacheConfig['enable_cache']['get_all_characters']) {
        $cache->delete('all_characters_with_relations');
    }
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
$app->get('/characters/{id}', function (Request $request, Response $response, $args) use ($log, $cache, $auth, $cacheConfig) {
    $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
    if (!$auth->hasPermission($token, 'read', 'character')) {
        $response->getBody()->write(json_encode(['error' => 'Forbidden']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }

    $cacheKey = 'character_with_relations_' . $args['id'];
    $cachedData = null;
    $cacheUsed = false;

    if ($cacheConfig['enable_cache']['get_character_by_id']) {
        $cachedData = $cache->get($cacheKey);
    }

    if ($cachedData) {
        $log->info('Returning cached character with relations, ID: ' . $args['id']);
        $character = $cachedData;
        $cacheUsed = true;
    } else {
        $log->info('Fetching character with relations, ID: ' . $args['id']);

        $pdo = new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $characterModel = new Character($pdo);
        $character = $characterModel->getCharacterWithRelations($args['id']);

        if ($character && $cacheConfig['enable_cache']['get_character_by_id']) {
            $cache->set($cacheKey, $character, $cacheConfig['cache_ttl']);
            $log->info('Cached character with relations, ID: ' . $args['id']);
        }
    }

    if (!$character) {
        $log->warning('Character not found with ID: ' . $args['id']);
        return $response->withStatus(404);
    }

    $result = [
        'data' => $character,
        'meta' => [
            'cache_used' => $cacheUsed
        ]
    ];

    $log->info('Returned character with relations, ID: ' . $args['id']);
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
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
$app->put('/characters/{id}', function (Request $request, Response $response, $args) use ($log, $cache, $auth, $cacheConfig) {
    $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
    if (!$auth->hasPermission($token, 'update', 'character')) {
        $response->getBody()->write(json_encode(['error' => 'Forbidden']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }

    $log->info('Updating character with ID: ' . $args['id']);

    $data = $request->getParsedBody();

    $pdo = new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $characterModel = new Character($pdo);
    $updatedCharacter = $characterModel->updateCharacter($args['id'], $data);

    if (!$updatedCharacter) {
        $log->warning('Character not found for update with ID: ' . $args['id']);
        return $response->withStatus(404);
    }

    $cache->delete('all_characters_with_relations');
    $cache->delete('character_with_relations_' . $args['id']);

    $log->info('Updated character with ID: ' . $args['id']);
    $response->getBody()->write(json_encode($updatedCharacter));
    return $response->withHeader('Content-Type', 'application/json');
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
$app->delete('/characters/{id}', function (Request $request, Response $response, $args) use ($log, $cache, $auth, $cacheConfig) {
    $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
    if (!$auth->hasPermission($token, 'delete', 'character')) {
        $response->getBody()->write(json_encode(['error' => 'Forbidden']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }

    $log->info('Deleting character with ID: ' . $args['id']);

    $pdo = new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $characterModel = new Character($pdo);
    $result = $characterModel->deleteCharacter($args['id']);

    if (!$result) {
        $log->warning('Character not found for deletion with ID: ' . $args['id']);
        return $response->withStatus(404);
    }

    // Invalidate cache
    $cache->delete('all_characters_with_relations');
    $cache->delete('character_with_relations_' . $args['id']);

    $log->info('Deleted character with ID: ' . $args['id']);
    return $response->withStatus(204);
});