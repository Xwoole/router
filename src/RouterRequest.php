<?php

namespace Xwoole\Router;

use Exception;
use OpenSwoole\Http\Request;
use OutOfBoundsException;

class RouterRequest extends Request
{
    
    private array $properties = [];
    
    public function __construct(private Request $request)
    {
        
    }
    
    public function __isset($name)
    {
        return array_key_exists($name, $this->properties) || property_exists($this->request, $name);
    }
    
    public function __set($name, $value)
    {
        if( property_exists($this->request, $name) )
        {
            throw new Exception();
        }
        
        $this->properties[$name] = $value;
    }
    
    public function __get($name)
    {
        if( array_key_exists($name, $this->properties) )
        {
            return $this->properties[$name];
        }
        
        if( property_exists($this->request, $name) )
        {
            return $this->request->{$name};
        }
        
        throw new OutOfBoundsException();
    }
    
    public function __call($name, $arguments)
    {
        if( method_exists($this->request, $name) )
        {
            return call_user_func_array([$this->request, $name], $arguments);
        }
        
        throw new OutOfBoundsException();
    }
    
}
