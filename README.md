# tp6-jwt
thinkphp6的jwt身份验证包。目前仅支持header传参验证。

## 安装
`composer require leruge/tp6-jwt`

## 说明
* 目前仅支持HS256算法加密。
* 准备支持如下三大类型加密方式：RSA,HASH,DSA。再各分256、384、512位。 默认是HS256，即hash 256位加密。
* 需要修改加密方式，请修改参数ALGO，参数选项：
    * HS256:hash256位
    * HS384:hash384位
    * HS512:hash512位
    * RS256:rsa256位
    * RS384:rsa384位
    * RS512:rsa512位
    * ES256:dsa256位
    * ES384:dsa384位
    * ES512:dsa512位
> 提示：RSA和DSA是非对称加密方式，除了修改ALGO参数外，需要配置public_key和private_key俩个参数。如果秘钥设置了密码，请配置好password参数。

## 使用方式
1. builder方法，生成token字符串
```php
$user = ['id' => 1];
$token = \leruge\facade\JWT::builder($user);
```
1. validate方法，如果通过返回true，失败抛出异常
```php
\leruge\facade\JWT::validate();
```
1. auth方法，返回一个对象，可以数组式访问，token有效的时候可以获取正常的数据，无效的时候获取的都是空
```php
$user = \leruge\facade\JWT::auth();
$uid = $user->id; // $user['id'];
```
1. 可以使用中间件JWT，如果成功就进行下一步，失败则返回 `['code' =>0, 'msg' => '失败信息']`
`\leruge\middleware\JWT::class`

## 传参方式
* 将token加入header，如下`Authorization:bearer token`值
