<?php

namespace Xwoole\Router;

use Exception;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use stdClass;

trait Routing
{
    private $routes = [
        "GET" => [],
        "POST" => [],
    ];
    
    private $prologue;
    
    private function resolveHandler(callable|string $handler): callable
    {
        if( is_string($handler) )
        {
            $path = realpath($handler);
            
            if( false === $path )
            {
                throw new Exception("\"$handler\" file not found");
            }
            
            if( is_file($path) )
            {
                $handler = function($_, Response $response) use ($path)
                {
                    $response->sendfile($path);
                };
            }
            elseif( is_dir($path) )
            {
                $handler = function(Request $request, Response $response) use ($path)
                {
                    $path .= $request->server["request_uri"];
                    
                    if( is_file($path) )
                    {
                        $response->sendfile($path);
                    }
                    else
                    {
                        $response->status(404);
                    }
                };
            }
        }
        
        return $handler;
    }
    
    public function get(string $pattern, callable|string $handler)
    {
        $this->routes["GET"][$pattern] = new Route($pattern, $this->resolveHandler($handler));
    }
    
    public function post(string $pattern, callable|string $handler)
    {
        $this->routes["POST"][$pattern] = new Route($pattern, $this->resolveHandler($handler));
    }
    
    public function any(string $pattern, callable|string $handler)
    {
        $route = new Route($pattern, $this->resolveHandler($handler));
        $this->routes["GET"][$pattern] = $route;
        $this->routes["POST"][$pattern] = $route;
    }
    
    public function match(Request $request): ?Route
    {
        $path = $request->server["request_uri"];
        
        foreach( $this->routes[$request->getMethod()] as $route )
        {
            if( $route->match($path) )
            {
                return $route;
            }
        }
        
        return null;
    }
    
    public function setPrologue(callable $callback)
    {
        $this->prologue = $callback;
    }
    
    public function generateOpenswooleHttpServerRequestHandler(): callable
    {
        return function(Request $request, Response $response)
        {
            $route = $this->match($request);
            
            if( null === $route )
            {
                $response->status(404);
                return;
            }
            
            if( is_callable($this->prologue) )
            {
                $properties = new stdClass;
                call_user_func($this->prologue, $properties);
                $request = new RouterRequest($request, $properties);
            }
            
            $args = [$request, $response, ...array_values($route->getArguments())];
            call_user_func_array($route->getHanlder(), $args);
        };
    }
    
}
