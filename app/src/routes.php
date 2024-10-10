<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Character;

$app->get('/characters', function (Request $request, Response $response) {
    
    $pdo = new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $characterModel = new Character($pdo);
    $characters = $characterModel->getAllCharacters();

    $response->getBody()->write(json_encode($characters));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/characters', function (Request $request, Response $response) {
    // Logic to create a new character
    return $response;
});

$app->put('/characters/{id}', function (Request $request, Response $response, $args) {
    // Logic to update a character
    return $response;
});

$app->delete('/characters/{id}', function (Request $request, Response $response, $args) {
    // Logic to delete a character
    return $response;
});