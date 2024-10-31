<?php

namespace Xwoole\Router;

use OpenSwoole\Http\Request;
use OutOfBoundsException;
use stdClass;

class RouterRequest extends Request
{
    
    public function __construct(private Request $request, private stdClass $bag)
    {
        
    }
    
    public function __isset($name)
    {
        return property_exists($this->bag, $name) || property_exists($this->request, $name);
    }
    
    public function __get($name)
    {
        if( property_exists($this->bag, $name) )
        {
            return $this->bag->{$name};
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
