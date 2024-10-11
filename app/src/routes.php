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

// Add the auth middleware
$app->add($authMiddleware);

/**
 * @OA\Get(
 *     path="/characters",
 *     summary="Get all characters",
 *     tags={"Characters"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Character")),
 *             @OA\Property(property="meta", type="object",
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
$app->get('/characters', function (Request $request, Response $response) use ($log, $cache, $auth) {
    $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
    if (!$auth->hasPermission($token, 'read', 'character')) {
        return $response->withStatus(403)->withJson(['error' => 'Forbidden']);
    }

    $cacheKey = 'all_characters';
    $cachedData = $cache->get($cacheKey);
    $cacheUsed = false;

    if ($cachedData) {
        $log->info('Returning cached characters');
        $characters = $cachedData;
        $cacheUsed = true;
    } else {
        $log->info('Fetching all characters from database');
        
        $pdo = new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $characterModel = new Character($pdo);
        $characters = $characterModel->getAllCharacters();

        $cache->set($cacheKey, $characters, 3600); // Cache for 1 hour
        $log->info('Cached ' . count($characters) . ' characters');
    }

    $result = [
        'data' => $characters,
        'meta' => [
            'cache_used' => $cacheUsed
        ]
    ];

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
$app->post('/characters', function (Request $request, Response $response) use ($log, $cache, $auth) {
    $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
    if (!$auth->hasPermission($token, 'create', 'character')) {
        return $response->withStatus(403)->withJson(['error' => 'Forbidden']);
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
    
    $cache->delete('all_characters'); // Invalidate the cache
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
$app->get('/characters/{id}', function (Request $request, Response $response, $args) use ($log, $cache, $auth) {
    $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
    if (!$auth->hasPermission($token, 'read', 'character')) {
        return $response->withStatus(403)->withJson(['error' => 'Forbidden']);
    }

    $cacheKey = 'character_' . $args['id'];
    $cachedData = $cache->get($cacheKey);
    $cacheUsed = false;

    if ($cachedData) {
        $log->info('Returning cached character with ID: ' . $args['id']);
        $character = $cachedData;
        $cacheUsed = true;
    } else {
        $log->info('Fetching character with ID: ' . $args['id']);
        
        $pdo = new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $characterModel = new Character($pdo);
        $character = $characterModel->getCharacterById($args['id']);

        if ($character) {
            $cache->set($cacheKey, $character, 3600); // Cache for 1 hour
            $log->info('Cached character with ID: ' . $args['id']);
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

    $log->info('Returned character with ID: ' . $args['id']);
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
$app->put('/characters/{id}', function (Request $request, Response $response, $args) use ($log, $cache, $auth) {
    $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
    if (!$auth->hasPermission($token, 'update', 'character')) {
        return $response->withStatus(403)->withJson(['error' => 'Forbidden']);
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

    $log->info('Updated character with ID: ' . $args['id']);
    $response->getBody()->write(json_encode($updatedCharacter));
    return $response->withHeader('Content-Type', 'application/json');
    
    if ($updatedCharacter) {
        $cache->delete('all_characters'); // Invalidate the list cache
        $cache->set('character_' . $args['id'], $updatedCharacter, 3600);
    }
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
$app->delete('/characters/{id}', function (Request $request, Response $response, $args) use ($log, $cache, $auth) {
    $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
    if (!$auth->hasPermission($token, 'delete', 'character')) {
        return $response->withStatus(403)->withJson(['error' => 'Forbidden']);
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

    $log->info('Deleted character with ID: ' . $args['id']);
    return $response->withStatus(204);
    
    if ($result) {
        $cache->delete('all_characters'); // Invalidate the list cache
        $cache->delete('character_' . $args['id']); // Remove the individual character cache
    }
});