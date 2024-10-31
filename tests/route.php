<?php

use Xwoole\Router\Route;

require_once __DIR__ . "/../vendor/autoload.php";


dump("[Test] capturing parameter");
$uid = rand(100, 999);
$route = new Route("/user/{uid}", fn() => null);
assert(true === $route->match("/user/{$uid}"), "failed to match");
assert(array_intersect($route->getArguments(), ["uid" => "$uid"]), "failed to capture parameter");


dump("[Test] capturing wildcard");
$route = new Route("/parent/*", fn() => null);
assert(true === $route->match("/parent/child"), "failed to match");
assert(false === $route->match("/parent/child/"), "failed to unmatch");


dump("[Test] capturing doubled wildcard");
$route = new Route("/cloud/**", fn() => null);
assert(true === $route->match("/cloud/"), "failed to match");
assert(true === $route->match("/cloud/file"), "failed to match");
assert(false === $route->match("/cloud"), "failed to unmatch");
