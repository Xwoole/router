<?php

use OpenSwoole\Coroutine\Http\Client;
use OpenSwoole\Http\Response;
use OpenSwoole\Http\Server;
use Xwoole\Router\Router;
use Xwoole\Router\RouterRequest;

require_once __DIR__ . "/../vendor/autoload.php";

$router = new Router();
$server = new Server("localhost");

$router->withMiddleware(function(RouterRequest $request, Response $response, $next)
{
    $request->key = "value";
    $next($request, $response);
});

$router->get("/", function(RouterRequest $request, Response $response)
{
    dump("[Test] handling global middleware");
    assert(isset($request->key));
    assert($request->key === "value");
    
    $response->end();
});

$router->register($server);

$server->after(100, function() use ($server)
{
    $client = new Client($server->host, $server->port);
    $client->get("/");
    $server->shutdown();
});

$server->start();
