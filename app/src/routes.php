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

