<?php

namespace Xwoole\Router;

use OpenSwoole\Http\Server;

class Router
{
    use Routing;
    
    public function register(Server $server)
    {
        $server->on("request", $this->generateOpenswooleHttpServerRequestHandler());
    }
    
}
