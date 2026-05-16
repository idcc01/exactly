<?php
// --------------------- 你只需要改这 2 个信息 ---------------------
$appid       = "wxde2ad02f02cb0df5";       // 你的公众号APPID
$appsecret   = "f27a1e32177f425fe8c940dc8d063b32";     // 你的APPSECRET
$target_url  = "https://ipp.noteflow.me/home"; // 最终要去的网站
// ----------------------------------------------------------------

// 1. 获取微信返回的 code 和 state
$code  = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (empty($code)) {
    header("Location: $target_url");
    exit;
}

// 2. 用 code 换取 openid
$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecret&code=$code&grant_type=authorization_code";
$res = file_get_contents($url);
$arr = json_decode($res, true);

$openid = $arr['openid'] ?? '';

// 3. 拼接参数，跳转到你的最终网站
$params = http_build_query([
    'code'    => $code,
    'openid'  => $openid,
    'state'   => $state
]);

$go = $target_url . '?' . $params;

// 4. 自动跳转（带所有参数）
header("Location: $go");
exit;
?>
