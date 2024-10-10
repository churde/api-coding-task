<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Character;
use OpenApi\Annotations as OA;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a log channel
$log = new Logger('api');
$log->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));

/**
 * @OA\Info(
 *     title="LOTR API",
 *     version="1.0.0",
 *     description="API for Lord of the Rings characters"
 * )
 */

/**
 * @OA\Get(
 *     path="/characters",
 *     summary="Get all characters",
 *     tags={"Characters"},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Character"))
 *     )
 * )
 */
$app->get('/characters', function (Request $request, Response $response) use ($log) {
    $log->info('Fetching all characters');
    
    $pdo = new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $characterModel = new Character($pdo);
    $characters = $characterModel->getAllCharacters();

    $log->info('Returned ' . count($characters) . ' characters');
    $response->getBody()->write(json_encode($characters));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * @OA\Post(
 *     path="/characters",
 *     summary="Create a new character",
 *     tags={"Characters"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/Character")
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Character created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Character")
 *     )
 * )
 */
$app->post('/characters', function (Request $request, Response $response) use ($log) {
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
});

/**
 * @OA\Get(
 *     path="/characters/{id}",
 *     summary="Get a character by ID",
 *     tags={"Characters"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(ref="#/components/schemas/Character")
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Character not found"
 *     )
 * )
 */
$app->get('/characters/{id}', function (Request $request, Response $response, $args) use ($log) {
    $log->info('Fetching character with ID: ' . $args['id']);
    
    $pdo = new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $characterModel = new Character($pdo);
    $character = $characterModel->getCharacterById($args['id']);

    if (!$character) {
        $log->warning('Character not found with ID: ' . $args['id']);
        return $response->withStatus(404);
    }

    $log->info('Returned character with ID: ' . $args['id']);
    $response->getBody()->write(json_encode($character));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * @OA\Put(
 *     path="/characters/{id}",
 *     summary="Update a character",
 *     tags={"Characters"},
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
 *     )
 * )
 */
$app->put('/characters/{id}', function (Request $request, Response $response, $args) use ($log) {
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
});

/**
 * @OA\Delete(
 *     path="/characters/{id}",
 *     summary="Delete a character",
 *     tags={"Characters"},
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
 *     )
 * )
 */
$app->delete('/characters/{id}', function (Request $request, Response $response, $args) use ($log) {
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
});

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

