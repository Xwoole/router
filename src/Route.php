<?php

namespace Xwoole\Router;

use Exception;

class Route
{
    
    private $handler;
    
    private $arguments = [];
    
    private $middlewares = [];
    
    private $excludedMiddlewares = [];
    
    private $subtitutions = [
        "~(/\\\\\*(\\\\\*)+)+~" => "(?:/.*?)",
        "~\\\\\*~" => "[^/]+?",
        "~\\\\{([\da-z][\w_]*?)\\\\}~i" => "(?<$1>[^/]+?)"
    ];
    
    readonly string $regex;
    
    public function __construct(
        readonly string $pattern,
        callable $handler
    )
    {
        $regex = preg_replace(
            array_keys($this->subtitutions),
            array_values($this->subtitutions),
            preg_quote($pattern, "~")
        );
        
        if( null === $regex )
        {
            throw new Exception("invalid route pattern");
        }
        
        $this->handler = $handler;
        $this->regex = "~^$regex$~";
    }
    
    public function withMiddleware(callable $callback): self
    {
        if( ! in_array($callback, $this->excludedMiddlewares) && ! in_array($callback, $this->middlewares) )
        {
            $this->middlewares[] = $callback;
        }
        
        return $this;
    }
    
    public function excludeMiddleware(callable $callback): self
    {
        if( false !== ($key = array_search($callback, $this->middlewares)) )
        {
            unset($this->middlewares[$key]);
        }
        
        if( ! in_array($callback, $this->excludedMiddlewares) )
        {
            $this->excludedMiddlewares[] = $callback;
        }
        
        return $this;
    }
    
    public function getHanlder(): callable
    {
        return $this->handler;
    }
    
    public function getArguments(): array
    {
        return $this->arguments;
    }
    
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
    
    public function match(string $path): bool
    {
        $result = preg_match($this->regex, $path, $matches);
        $this->arguments = array_filter($matches, fn($k) => is_string($k), ARRAY_FILTER_USE_KEY);
        return $result;
    }
}

