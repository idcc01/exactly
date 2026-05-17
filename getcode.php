<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// ==================== 配置区域 ====================
$appId     = 'wxde2ad02f02cb0df5';
$appSecret = 'f27a1e32177f425fe8c940dc8d063b32';

// 主站域名（不带 http://、不带尾斜杠）
$targetDomain = 'noteflow.me';

// 主站登录落地页（vue-router 守卫会识别 query 自动登录）
$targetPath = '/oauth/callback';

// HMAC 共享密钥：必须跟主站总后台 wechat_bridge_secret 完全一致
// 开发期默认：'changeit-bridge-secret'
$bridgeSecret = 'changeit-bridge-secret';
// =================================================

$currentUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// 第一步：还没 code → 发起授权
if (!isset($_GET['code'])) {
    $promoParams = array_diff_key($_GET, array_flip(['code', 'state']));
    $state = base64_encode(json_encode($promoParams, JSON_UNESCAPED_UNICODE));

    $authUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appId .
               '&redirect_uri=' . urlencode($currentUrl) .
               '&response_type=code&scope=snsapi_userinfo&state=' . urlencode($state) .
               '#wechat_redirect';
    header('Location: ' . $authUrl);
    exit;
}

// 第二步：有 code → 用 code 换 access_token
$code = $_GET['code'];

$stateParam = $_GET['state'] ?? '';
$promoParams = [];
if ($stateParam !== '') {
    $decoded = base64_decode($stateParam);
    if ($decoded !== false) {
        $arr = json_decode($decoded, true);
        if (is_array($arr)) $promoParams = $arr;
    }
}

$tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appId .
            '&secret=' . $appSecret . '&code=' . $code . '&grant_type=authorization_code';
$tokenJson = file_get_contents($tokenUrl);
$tokenArr = json_decode($tokenJson, true);
if (!isset($tokenArr['access_token'])) {
    die('换取 access_token 失败：' . htmlspecialchars($tokenJson));
}

$accessToken = $tokenArr['access_token'];
$openId      = $tokenArr['openid'];
$unionId     = $tokenArr['unionid'] ?? '';

$userInfoUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $accessToken .
               '&openid=' . $openId . '&lang=zh_CN';
$userJson = file_get_contents($userInfoUrl);
$userInfo = json_decode($userJson, true);
if (isset($userInfo['errcode'])) {
    die('获取用户信息失败：' . htmlspecialchars($userInfo['errmsg'] ?? ''));
}

// 第三步：组装本站要的字段（注意：本站字段名是 avatar，不是 headimgurl）
$nickname = $userInfo['nickname']   ?? '';
$avatar   = $userInfo['headimgurl'] ?? '';

// 推广参数：本站只关心 site / invite
$site   = isset($promoParams['site'])   ? trim((string)$promoParams['site'])   : '';
$invite = isset($promoParams['invite']) ? trim((string)$promoParams['invite']) : '';

$ts = (string) time();

// 第四步：HMAC-SHA256 签名
// 规则：参与字段按 key 字母升序，URL-decoded 原值，空字段不参与
//      'k=v' 用 & 拼接（不要 url_encode！）
$fields = [
    'avatar'   => $avatar,
    'invite'   => $invite,
    'nickname' => $nickname,
    'openid'   => $openId,
    'site'     => $site,
    'ts'       => $ts,
    'unionid'  => $unionId,
];

$nonEmpty = [];
foreach ($fields as $k => $v) {
    if ($v !== '' && $v !== null) $nonEmpty[$k] = $v;
}
ksort($nonEmpty, SORT_STRING);

$signParts = [];
foreach ($nonEmpty as $k => $v) {
    $signParts[] = $k . '=' . $v;     // 重要：原值，不 urlencode
}
$signBase = implode('&', $signParts);
$sign = hash_hmac('sha256', $signBase, $bridgeSecret);  // 默认小写 hex

// 第五步：构造 URL，浏览器重定向到主站落地页
$query = $nonEmpty;
$query['sign'] = $sign;

$targetUrl = 'https://' . $targetDomain . $targetPath . '?' . http_build_query($query);
header('Location: ' . $targetUrl);
exit;
