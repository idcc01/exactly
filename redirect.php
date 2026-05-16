<?php
require 'config.php';

$code = $_GET['code'] ?? '';
$params = $_GET;
unset($params['code'], $params['state']);

if (empty($code)) {
    $query = http_build_query($params);
    header("Location: " . TARGET_URL . "?$query");
    exit;
}

// 获取 token
$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . APPID . "&secret=" . SECRET . "&code=$code&grant_type=authorization_code";
$res = @file_get_contents($url);
$data = json_decode($res, true);

$access_token = $data['access_token'] ?? '';
$openid = $data['openid'] ?? '';
$unionid = $data['unionid'] ?? '';

if (!$access_token || !$openid) {
    $query = http_build_query($params);
    header("Location: " . TARGET_URL . "?$query");
    exit;
}

// 获取用户信息
$userUrl = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
$userRes = @file_get_contents($userUrl);
$user = json_decode($userRes, true);

$nickname = $user['nickname'] ?? '';
$avatar = $user['headimgurl'] ?? '';
$ts = time();

// 组装参数
$base = [
    'openid' => $openid,
    'unionid' => $unionid,
    'nickname' => $nickname,
    'avatar' => $avatar,
    'ts' => $ts,
];

// 合并传入参数
if (!empty($params['site'])) $base['site'] = $params['site'];
if (!empty($params['invite'])) $base['invite'] = $params['invite'];

// 按 key 升序
ksort($base);

// 生成签名字符串（URL decoded）
$signStr = http_build_query($base);
$sign = strtolower(hash_hmac('sha256', $signStr, HMAC_KEY));

// 最终跳转
$final = array_merge($base, ['sign' => $sign]);
$query = http_build_query($final);

header("Location: " . TARGET_URL . "?$query");
exit;
?>
