<?php

use OpenSwoole\Coroutine\Http\Client;
use OpenSwoole\Http\Response;
use OpenSwoole\Http\Server;
use Xwoole\Router\Router;

require_once __DIR__ . "/../vendor/autoload.php";

$router = new Router();
$server = new Server("localhost");
$public_dir = __DIR__ . "/public";
$client_file = __DIR__ . "/client.html";


dump("[Test] registering a GET request handler");
try
{
    $router->get("/validGet", function($_, Response $response)
    {
        $response->end("");
    });
}
catch( Exception $e)
{
    throw new AssertionError(previous: $e);
}


dump("[Test] registering a POST request handler");
try
{
    $router->post("/validPost", function($_, Response $response)
    {
        $response->end("");
    });
}
catch( Exception $e)
{
    throw new AssertionError(previous: $e);
}


dump("[Test] registering a GET request handler that directs to an invalid path");
try
{
    $router->get("/file", "invalid_path");
    throw new AssertionError();
}
catch( Exception )
{
    
}


dump("[Test] registering a GET request handler that directs to a file");
try
{
    $router->get("/client", $client_file);
}
catch( Exception $e)
{
    throw new AssertionError(previous: $e);
}


dump("[Test] registering a GET request handler that directs to any file in a directory");
try
{
    $router->get("/**", $public_dir);
}
catch( Exception $e)
{
    throw new AssertionError(previous: $e);
}


dump("[Test] registering the server request handler");
$router->register($server);
assert($server->getCallback("request") !== null);


$server->after(100, function() use ($server, $client_file, $public_dir)
{
    $client = new Client($server->host, $server->port);
    
    
    dump("[Test] handling an invalid GET request");
    $client->get("/invalidGet");
    assert($client->getStatusCode() === 404);
    
    
    dump("[Test] handling a valid GET request");
    $client->get("/validGet");
    assert($client->getStatusCode() === 200);
    
    
    dump("[Test] handling an invalid POST request");
    $client->post("/invalidPost", []);
    assert($client->getStatusCode() === 404);
    
    
    dump("[Test] handling a valid POST request");
    $client->post("/validPost", []);
    assert($client->getStatusCode() === 200);
    
    
    dump("[Test] routing to a file");
    $client->get("/client");
    assert($client->getStatusCode() === 200);
    assert($client->getBody() === file_get_contents($client_file), "failed to match content");
    
    
    dump("[Test] prevent routing to a missing file in a directory");
    $client->get("/missing_file");
    assert($client->getStatusCode() === 404);
    
    
    dump("[Test] routing to an existing file in a directory");
    $client->get("/style.css");
    assert($client->getStatusCode() === 200);
    assert($client->getBody() === file_get_contents("$public_dir/style.css"), "failed to match content");
    
    
    $server->shutdown();
});

$server->start();
