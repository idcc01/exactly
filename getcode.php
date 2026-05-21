<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// ==================== 配置区域 ====================
$appId        = 'wxde2ad02f02cb0df5';
$appSecret    = 'f27a1e32177f425fe8c940dc8d063b32';
$targetDomain = 'ipp.noteflow.me';
$targetPath   = '/oauth/callback';
$bridgeSecret = 'changeit-bridge-secret';
// =================================================

$currentUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// 第一步：还没 code → 发起授权（snsapi_userinfo，可拿昵称头像）
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

// 第二步：有 code → 换 access_token + openid
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
if (!isset($tokenArr['openid']) || !isset($tokenArr['access_token'])) {
    die('换取 access_token 失败：' . htmlspecialchars($tokenJson));
}

$accessToken = $tokenArr['access_token'];
$openId      = $tokenArr['openid'];
$unionId     = $tokenArr['unionid'] ?? '';

// 第三步：用 access_token 拉 userinfo（昵称 + 头像）
$nickname = '';
$avatar   = '';
$userInfoUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $accessToken .
               '&openid=' . $openId . '&lang=zh_CN';
$userJson = @file_get_contents($userInfoUrl);
if ($userJson !== false) {
    $userInfo = json_decode($userJson, true);
    if (is_array($userInfo) && !isset($userInfo['errcode'])) {
        $nickname = $userInfo['nickname']   ?? '';
        $avatar   = $userInfo['headimgurl'] ?? '';
        if (!$unionId && !empty($userInfo['unionid'])) {
            $unionId = $userInfo['unionid'];
        }
    }
    // 拿不到不致命，至少有 openid 能登录，前端会显示「未拉到昵称」提示
}

// 推广参数
$site   = isset($promoParams['site'])   ? trim((string)$promoParams['site'])   : '';
$invite = isset($promoParams['invite']) ? trim((string)$promoParams['invite']) : '';
$ts     = (string) time();

// 第四步：HMAC-SHA256 签名（空字段不参与）
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
    $signParts[] = $k . '=' . $v;     // 重要：原值不 urlencode
}
$signBase = implode('&', $signParts);
$sign = hash_hmac('sha256', $signBase, $bridgeSecret);

// 第五步：跳主站落地
$query = $nonEmpty;
$query['sign'] = $sign;

$targetUrl = 'https://' . $targetDomain . $targetPath . '?' . http_build_query($query);
header('Location: ' . $targetUrl);
exit;
