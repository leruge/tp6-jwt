<?php
/**
 * @title jwt配置
 * @author Leruge
 * @email leruge@163.com
 */

return [
    // JWT加密算法
    'alg'        => env('JWT_ALG', 'HS256'),
    'secret'      => env('JWT_SECRET', 'leruge'),
    // 非对称加密需要配置
    'public_key'  => env('JWT_PUBLIC_KEY'),
    'private_key' => env('JWT_PRIVATE_KEY'),
    'password'    => env('JWT_PASSWORD'),
    // JWT有效期
    'ttl'         => env('JWT_TTL', 3600 * 24 * 365),
];
