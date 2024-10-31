<?php

namespace Xwoole\Router;

use Exception;

class Route
{
    
    private $arguments = [];
    private $subtitutions = [
        "~(/\\\\\*(\\\\\*)+)+~" => "(?:/.*?)",
        "~\\\\\*~" => "[^/]+?",
        "~\\\\{([\da-z][\w_]*?)\\\\}~i" => "(?<$1>[^/]+?)"
    ];
    
    readonly string $pattern;
    private $handler;
    
    public function __construct(
        string $pattern,
        callable $handler
    )
    {
        $pattern = preg_replace(
            array_keys($this->subtitutions),
            array_values($this->subtitutions),
            preg_quote($pattern, "~")
        );
        
        if( null === $pattern )
        {
            throw new Exception("invalid route pattern");
        }
        
        $this->handler = $handler;
        $this->pattern = "~^$pattern$~";
    }
    
    public function match(string $path): bool
    {
        $result = preg_match($this->pattern, $path, $matches);
        $this->arguments = array_filter($matches, fn($k) => is_string($k), ARRAY_FILTER_USE_KEY);
        return $result;
    }
    
    public function getHanlder(): callable
    {
        return $this->handler;
    }
    
    public function getArguments(): array
    {
        return $this->arguments;
    }
}

