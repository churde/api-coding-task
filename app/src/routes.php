<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Controllers\CharacterController;
use App\Models\Character;
use OpenApi\Annotations as OA;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Services\Cache;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response as SlimResponse;
use App\Services\Auth;
use App\Middleware\RateLimitMiddleware;
use App\Services\CharacterService;
use DI\Container;
use App\Interfaces\CacheInterface;
use App\Interfaces\CharacterServiceInterface;

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

// Load settings
$settings = require __DIR__ . '/../config/app.php';
$container->set('settings', $settings);

// Set up container definitions
$container->set('db', function () {
    return new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
});

$container->set(CacheInterface::class, function () {
    return new Cache();
});


$container->set('tokenManager', function () {
    $secretKey = getenv('JWT_SECRET_KEY') ?: 'your-secret-key';
    return new \App\Services\TokenManager($secretKey);
});

$container->set('permissionChecker', function ($c) {
    return new \App\Services\PermissionChecker($c->get(CacheInterface::class), $c->get('db'));
});

$container->set('auth', function ($c) {
    return new Auth(
        new \App\Services\AuthenticationService($c->get('tokenManager')),
        new \App\Services\AuthorizationService($c->get('permissionChecker'))
    );
});

$container->set('settings', function () {
    return require __DIR__ . '/../config/app.php';
});


$container->set('characterModel', function ($c) {
    return new Character();
});

$container->set('characterRepository', function ($c) {
    return new \App\Repositories\CharacterRepository(
        $c->get('db'),
        $c->get('characterModel'),
        $c->get(CacheInterface::class),
        $c->get('settings')['cache']
    );
});

$container->set('characterRepositoryInterface', function ($c) {
    return $c->get('characterRepository');
});

$container->set('log', function () {
    $log = new Logger('api');
    $log->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
    return $log;
});

$container->set('characterValidator', function ($c) {
    return new \App\Validators\CharacterValidator($c->get('characterRepositoryInterface'));
});

// Update the container configuration for CharacterService
$container->set(CharacterServiceInterface::class, function ($c) {
    return new CharacterService(
        $c->get('auth'),
        $c->get('characterRepositoryInterface'),
        $c->get('log'),
        $c->get('characterValidator')
    );
});

// Update the CharacterController configuration to use CharacterServiceInterface
$container->set('characterController', function ($c) {
    return new CharacterController(
        $c->get(CharacterServiceInterface::class),
        $c->get('characterValidator')
    );
});

// Add these new container definitions
$container->set('equipmentModel', function ($c) {
    return new \App\Models\Equipment();
});

$container->set('equipmentRepository', function ($c) {
    return new \App\Repositories\EquipmentRepository(
        $c->get('db'),
        $c->get('equipmentModel'),
        $c->get(CacheInterface::class),
        $c->get('settings')['cache']
    );
});

$container->set('equipmentRepositoryInterface', function ($c) {
    return $c->get('equipmentRepository');
});

$container->set('equipmentValidator', function ($c) {
    return new \App\Validators\EquipmentValidator($c->get('equipmentRepositoryInterface'));
});

$container->set(\App\Interfaces\EquipmentServiceInterface::class, function ($c) {
    return new \App\Services\EquipmentService(
        $c->get('auth'),
        $c->get('equipmentRepositoryInterface'),
        $c->get('log'),
        $c->get('equipmentValidator')
    );
});

$container->set('equipmentController', function ($c) {
    return new \App\Controllers\EquipmentController(
        $c->get(\App\Interfaces\EquipmentServiceInterface::class),
        $c->get('equipmentValidator')
    );
});

// Add these new container definitions for Faction
$container->set('factionModel', function ($c) {
    return new \App\Models\Faction();
});

$container->set('factionRepository', function ($c) {
    return new \App\Repositories\FactionRepository(
        $c->get('db'),
        $c->get('factionModel'),
        $c->get(CacheInterface::class),
        $c->get('settings')['cache']
    );
});

$container->set('factionRepositoryInterface', function ($c) {
    return $c->get('factionRepository');
});

$container->set('factionValidator', function ($c) {
    return new \App\Validators\FactionValidator($c->get('factionRepositoryInterface'));
});

$container->set(\App\Interfaces\FactionServiceInterface::class, function ($c) {
    return new \App\Services\FactionService(
        $c->get('auth'),
        $c->get('factionRepositoryInterface'),
        $c->get('log'),
        $c->get('factionValidator')
    );
});

$container->set('factionController', function ($c) {
    return new \App\Controllers\FactionController(
        $c->get(\App\Interfaces\FactionServiceInterface::class),
        $c->get('factionValidator')
    );
});

// Create the Slim app
$app = AppFactory::createFromContainer($container);

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// Create middleware
$authMiddleware = new class($container) {
    private $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function __invoke($request, $handler) {
        $auth = $this->container->get('auth');
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
    }
};

$rateLimitMiddleware = new RateLimitMiddleware(
    $container->get(CacheInterface::class),
    $container->get('settings')['rate_limit']
);

// Group routes for version 1
$app->group('/v1', function ($group) use ($container) {
    // Characters routes
    $group->get('/characters', function (Request $request, Response $response) use ($container) {
        return $container->get('characterController')->getAllCharacters($request, $response);
    });

    $group->post('/characters', function (Request $request, Response $response) use ($container) {
        return $container->get('characterController')->createCharacter($request, $response);
    });

    $group->get('/characters/{id}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get('characterController')->getCharacterById($request, $response, $args);
    });

    $group->put('/characters/{id}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get('characterController')->updateCharacter($request, $response, $args);
    });

    $group->delete('/characters/{id}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get('characterController')->deleteCharacter($request, $response, $args);
    });

    // Equipment routes
    $group->get('/equipment', function (Request $request, Response $response) use ($container) {
        return $container->get('equipmentController')->getAllEquipment($request, $response);
    });

    $group->post('/equipment', function (Request $request, Response $response) use ($container) {
        return $container->get('equipmentController')->createEquipment($request, $response);
    });

    $group->get('/equipment/{id}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get('equipmentController')->getEquipmentById($request, $response, $args);
    });

    $group->put('/equipment/{id}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get('equipmentController')->updateEquipment($request, $response, $args);
    });

    $group->delete('/equipment/{id}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get('equipmentController')->deleteEquipment($request, $response, $args);
    });

    // Faction routes
    $group->get('/factions', function (Request $request, Response $response) use ($container) {
        return $container->get('factionController')->getAllFactions($request, $response);
    });

    $group->post('/factions', function (Request $request, Response $response) use ($container) {
        return $container->get('factionController')->createFaction($request, $response);
    });

    $group->get('/factions/{id}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get('factionController')->getFactionById($request, $response, $args);
    });

    $group->put('/factions/{id}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get('factionController')->updateFaction($request, $response, $args);
    });

    $group->delete('/factions/{id}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get('factionController')->deleteFaction($request, $response, $args);
    });
})->add($authMiddleware)->add($rateLimitMiddleware);

// Update OpenAPI annotations for versioned routes
/**
 * @OA\Server(url="/v1")
 */

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
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search term for filtering characters by name, kingdom, equipment name, or faction name",
 *         required=false,
 *         @OA\Schema(type="string")
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

/**
 * @OA\Post(
 *     path="/characters",
 *     summary="Create a new character",
 *     tags={"Characters"},
 *     security={{"bearerAuth": {}}},
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

// Add OpenAPI annotations for equipment routes
/**
 * @OA\Schema(
 *     schema="Equipment",
 *     required={"name", "type", "made_by"},
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="type", type="string"),
 *     @OA\Property(property="made_by", type="string")
 * )
 */

/**
 * @OA\Get(
 *     path="/equipment",
 *     summary="Get all equipment",
 *     tags={"Equipment"},
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
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search term for filtering equipment by name or type",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Equipment")),
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

/**
 * @OA\Post(
 *     path="/equipment",
 *     summary="Create a new equipment",
 *     tags={"Equipment"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "type"},
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="type", type="string"),
 *             @OA\Property(property="made_by", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Equipment created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Equipment")
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input"
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/equipment/{id}",
 *     summary="Get an equipment by ID",
 *     tags={"Equipment"},
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
 *             @OA\Property(property="data", ref="#/components/schemas/Equipment"),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="cache_used", type="boolean")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Equipment not found"
 *     )
 * )
 */

/**
 * @OA\Put(
 *     path="/equipment/{id}",
 *     summary="Update an equipment",
 *     tags={"Equipment"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/Equipment")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Equipment updated successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Equipment")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Equipment not found"
 *     )
 * )
 */

/**
 * @OA\Delete(
 *     path="/equipment/{id}",
 *     summary="Delete an equipment",
 *     tags={"Equipment"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=204,
 *         description="Equipment deleted successfully"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Equipment not found"
 *     )
 * )
 */

// Add OpenAPI annotations for faction routes
/**
 * @OA\Schema(
 *     schema="Faction",
 *     required={"faction_name", "description"},
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="faction_name", type="string"),
 *     @OA\Property(property="description", type="string")
 * )
 */

/**
 * @OA\Get(
 *     path="/factions",
 *     summary="Get all factions",
 *     tags={"Factions"},
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
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search term for filtering factions by name or description",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Faction")),
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

/**
 * @OA\Post(
 *     path="/factions",
 *     summary="Create a new faction",
 *     tags={"Factions"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"faction_name", "description"},
 *             @OA\Property(property="faction_name", type="string"),
 *             @OA\Property(property="description", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Faction created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Faction")
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input"
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/factions/{id}",
 *     summary="Get a faction by ID",
 *     tags={"Factions"},
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
 *             @OA\Property(property="data", ref="#/components/schemas/Faction"),
 *             @OA\Property(property="meta", type="object",
 *                 @OA\Property(property="cache_used", type="boolean")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Faction not found"
 *     )
 * )
 */

/**
 * @OA\Put(
 *     path="/factions/{id}",
 *     summary="Update a faction",
 *     tags={"Factions"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/Faction")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Faction updated successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Faction")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Faction not found"
 *     )
 * )
 */

/**
 * @OA\Delete(
 *     path="/factions/{id}",
 *     summary="Delete a faction",
 *     tags={"Factions"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=204,
 *         description="Faction deleted successfully"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Faction not found"
 *     )
 * )
 */