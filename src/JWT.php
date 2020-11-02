<?php


namespace leruge;


use http\Exception\BadMethodCallException;
use leruge\exception\BadMethodException;
use leruge\exception\JWTAlgException;
use leruge\exception\JWTBadMethodCallException;
use leruge\exception\JWTConfigException;
use leruge\exception\JWTException;
use leruge\exception\JWTTokenInvalidException;
use leruge\exception\JWTTokenNotFoundException;
use leruge\exception\JWTUserException;

class JWT
{
    // 默认配置
    protected $config = [
        // JWT加密算法
        'alg'        => 'HS256',
        'secret'      => 'leruge',
        // 非对称需要配置
        'public_key'  => '',
        'private_key' => '',
        'password'    => '',
        // JWT有效时间
        'ttl'         => 3600 * 24 * 365,
    ];

    // 标准声明
    protected $standardClaim = [
        'iss' => null,
        'sub' => null,
        'aud' => null,
        'exp' => null,
        'nbf' => null,
        'iat' => null,
        'jti' => null,
    ];

    // 公共声明
    protected $publicClaim = [];

    // 私有声明，暂时留空
    protected $privateClaim = [];

    // 算法类型
    protected $algorithm = [
        'HS256',
        'HS384',
        'HS512',
        'RS256',
        'RS384',
        'RS512',
        'ES256',
        'ES384',
        'ES512'
    ];

    // header部分
    protected $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];

    // payload部分
    protected $payload = [];

    // 签证信息
    protected $signature = [];

    public function __construct()
    {
        $this->config = array_merge($this->config, config('jwt.'));
    }

    /**
     * @title 生成token
     *
     * @param array $user 用户信息
     * @return string token字符串
     * @throws JWTAlgException
     * @throws JWTUserException
     * @throws JWTConfigException
     */
    public function builder(array $user)
    {
        $this->publicClaim = $user;
        $header = $this->getHeader();
        $payload = $this->getPayLoad($user);
        $signature = $this->getSignature($header, $payload);
        return $header . '.' . $payload . '.' . $signature;
    }

    /**
     * @title 获取header部分
     *
     * @return string header
     * @throws JWTAlgException
     */
    protected function getHeader()
    {
        if (!in_array(strtoupper($this->config['alg']), $this->algorithm)) {
            throw new JWTAlgException("Algorithm [{$this->config['alg']}] does not exist.");
        }
        // 目前仅支持hs256
        if (strtoupper($this->config['alg']) != 'HS256') {
            throw new JWTAlgException("only HS256 algorithm is supported.");
        }
        $this->header['alg'] = strtoupper($this->config['alg']);
        return base64_encode(json_encode($this->header));
    }

    /**
     * @title 获取payload部分
     *
     * @param array $user 用户信息
     *
     * @return string payload
     * @throws JWTUserException
     * @throws JWTConfigException
     */
    protected function getPayLoad($user)
    {
        if (!is_array($user)) {
            throw new JWTUserException("user info type is not array.");
        }

        $userKeyArray = array_keys($user);
        $standardClaimKeyArray = array_keys($this->standardClaim);
        $intersect = array_intersect($userKeyArray, $standardClaimKeyArray);
        if ($intersect) {
            $intersectString = implode(',', $intersect);
            throw new JWTUserException("user info [{$intersectString}] does not allowed to use.");
        }

        if (!is_numeric($this->config['ttl'])) {
            throw new JWTConfigException("jwt config [ttl] invalid.");
        }
        $this->standardClaim['exp'] = time() + $this->config['ttl'];
        $payloadArray = array_merge($this->standardClaim, $user);
        return base64_encode(json_encode($payloadArray));
    }

    /**
     * @title 获取签证部分
     *
     * @param string $header header部分
     * @param string $payload payload部分
     *
     * @return string signature
     */
    protected function getSignature($header, $payload)
    {
        $signature = $header . '.' . $payload;
        $signature = hash_hmac('sha256', $signature, $this->config['secret']);
        return $signature;
    }

    /**
     * @title 验证token是否有效（仅验证时间和加密方式）
     *
     * @return bool 布尔值
     * @throws JWTTokenNotFoundException
     * @throws JWTTokenInvalidException
     */
    public function validate()
    {
        $tokenBearer = app('request')->header('authorization');
        if (!$tokenBearer) {
            throw new JWTTokenNotFoundException('token is must.');
        }
        $token = substr($tokenBearer, 7);
        if (!$token) {
            throw new JWTTokenNotFoundException('token is required.');
        }

        $tokenArray = explode('.', $token);
        if (count($tokenArray) != 3) {
            throw new JWTTokenInvalidException('token needs two dots.');
        }

        $signature = hash_hmac('sha256', $tokenArray[0] . '.' . $tokenArray[1], $this->config['secret']);
        if ($signature != $tokenArray[2]) {
            throw new JWTTokenInvalidException('token is invalid.');
        }
        $payloadArray = json_decode(base64_decode($tokenArray[1]), true);
        if ($payloadArray['exp'] < time()) {
            throw new JWTTokenInvalidException('token is expired.');
        }
        return true;
    }

    /**
     * @title 获取信息对象
     *
     * @return object 信息对象
     */
    public function auth()
    {
        $user = new class() implements \ArrayAccess {
            public function offsetExists($offset)
            {
                return isset($this->$offset);
            }

            public function offsetGet($offset)
            {
                return $this->$offset;
            }

            public function offsetSet($offset, $value)
            {
                $this->$offset = $value;
            }

            public function offsetUnset($offset)
            {
                unset($this->$offset);
            }

            public function __get($name)
            {
                return null;
            }
        };

        try {
            $this->validate();
            $tokenBearer = app('request')->header('authorization');
            $token = substr($tokenBearer, 7);
            $tokenArray = explode('.', $token);
            $payloadArray = json_decode(base64_decode($tokenArray[1]), true);
            foreach ($payloadArray as $k => $v) {
                $user->$k = $v;
            }
            return $user;
        } catch (JWTException $e) {
            $user->_error_msg = $e->getMessage();
            return $user;
        }
    }

    /**
     * @title 调用没有的方法触发
     *
     * @param $name 方法名
     * @param $arguments 方法参数
     *
     * @throws JWTBadMethodCallException
     */
    public function __call($name, $arguments)
    {
        throw new JWTBadMethodCallException("Method [$name] does not exist.");
    }

    /**
     * @title 安装完成以后复制swagger文件
     */
    public static function copySwagger()
    {
        $sourceFile = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $targetFile = config_path() . DIRECTORY_SEPARATOR . 'kk.php';
        copy($sourceFile, $targetFile);
        echo 2;
    }

    /**
     * @title 卸载扩展包以后删除配置文件
     */
    public static function deleteConfig()
    {
        $configFile = config_path() . DIRECTORY_SEPARATOR . 'jwt.php';
        if (file_exists($configFile)) {
            echo 1;
            unlink($configFile);
        }
    }
}