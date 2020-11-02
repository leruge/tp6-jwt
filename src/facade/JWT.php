<?php

namespace leruge\facade;

use think\Facade;

/**
 * @title JWT的门面
 *
 * @method string builder(array $user) static 创建token
 * @method bool validate() static 是否认证通过
 * @method object auth() static 获取信息
 */
class JWT extends Facade
{
    protected static function getFacadeClass()
    {
        return \leruge\JWT::class;
    }
}