<?php
require __DIR__ . '/../vendor/autoload.php';

use OpenApi\Annotations as OA;
use OpenApi\Generator;

// Generate the OpenAPI documentation
$openapi = Generator::scan([dirname(__DIR__) . '/src']);

// If the request is for the JSON, return it
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo $openapi->toJson();
    exit;
}

// Otherwise, serve the Swagger UI
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>LOTR API - Swagger UI</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3/swagger-ui.css" />
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@3/swagger-ui-bundle.js" charset="UTF-8"></script>
    <script>
        window.onload = function() {
            window.ui = SwaggerUIBundle({
                url: "swagger.php?json=1",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout"
            });
        };
    </script>
</body>
</html>