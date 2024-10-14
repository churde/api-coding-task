<?php namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;
use App\Services\Cache;

class RateLimitMiddleware
{
    private $cache;
    private $limit;
    private $window;

    public function __construct(Cache $cache, int $limit = 100, int $window = 3600)
    {
        $this->cache = $cache;
        $this->limit = $limit;
        $this->window = $window;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $token = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            $responseFactory = new ResponseFactory();
            $response = $responseFactory->createResponse(401);
            $response->getBody()->write(json_encode(['error' => 'Unauthorized: No token provided']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $key = "rate_limit:{$token}";

        $current = $this->cache->get($key);

        if ($current !== false && intval($current) >= $this->limit) {
            $responseFactory = new ResponseFactory();
            $response = $responseFactory->createResponse(429);
            $response->getBody()->write(json_encode(['error' => 'Rate limit exceeded']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        if ($current === false) {
            $this->cache->set($key, 1, $this->window);
        } else {
            $this->cache->set($key, $current + 1, $this->window);
        }

        return $handler->handle($request);
    }
}