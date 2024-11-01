<?php

namespace Xwoole\Router;

use Exception;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

trait Routing
{
    private $routes = [
        "GET" => [],
        "POST" => [],
    ];
    
    private $epilogues;
    private $prologues;
    
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
    
    public function addPrologue(callable $callback)
    {
        $this->prologues[] = $callback;
    }
    
    public function removePrologue(callable $callback)
    {
        $i = array_search($callback, $this->prologues, true);
    
        if( false !== $i )
        {
            unset($this->prologues[$i]);
        }
    }
    
    public function addEpilogue(callable $callback)
    {
        $this->epilogues[] = $callback;
    }
    
    public function removeEpilogue(callable $callback)
    {
        $i = array_search($callback, $this->epilogues, true);
    
        if( false !== $i )
        {
            unset($this->epilogues[$i]);
        }
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
            
            $request = new RouterRequest($request);
            
            foreach( $this->prologues as $prologue )
            {
                call_user_func($prologue, $request, $response);
                
                if( ! $response->isWritable() )
                {
                    return;
                }
            }
            
            $args = [$request, $response, ...array_values($route->getArguments())];
            call_user_func_array($route->getHanlder(), $args);
            
            if( ! $response->isWritable() )
            {
                return;
            }
            
            foreach( $this->epilogues as $epilogue )
            {
                call_user_func($epilogue, $request, $response);
                
                if( ! $response->isWritable() )
                {
                    return;
                }
            }
        };
    }
    
}
