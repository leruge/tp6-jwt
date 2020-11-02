<?php


namespace leruge\middleware;


use leruge\exception\JWTException;

class JWT
{
    public function handle($request, \Closure $next)
    {
        try {
            (new \leruge\JWT())->validate();
            return $next($request);
        } catch (JWTException $e) {
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
    }
}