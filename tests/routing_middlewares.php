<?php

/* [TODO]

- test handling prologue middleware
- test handling epilogue middleware

*/

use OpenSwoole\Coroutine\Http\Client;
use OpenSwoole\Http\Response;
use OpenSwoole\Http\Server;
use Xwoole\Router\Router;
use Xwoole\Router\RouterRequest;

require_once __DIR__ . "/../vendor/autoload.php";

$router = new Router();
$server = new Server("localhost");

$router->addPrologue(function(RouterRequest $request)
{
    $request->key = "value";
});

$router->get("/", function(RouterRequest $request, Response $response)
{
    dump("[Test] handling prologue middleware");
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
