<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// ==================== 配置区域 ====================
$appId     = 'wxde2ad02f02cb0df5';
$appSecret = 'f27a1e32177f425fe8c940dc8d063b32';
$targetDomain = 'ipp.noteflow.me';
$targetPath = '/oauth/callback';
$bridgeSecret = 'changeit-bridge-secret';
// =================================================

$currentUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// 第一步：还没 code → 发起静默授权（snsapi_base，不弹任何授权页）
if (!isset($_GET['code'])) {
    $promoParams = array_diff_key($_GET, array_flip(['code', 'state']));
    $state = base64_encode(json_encode($promoParams, JSON_UNESCAPED_UNICODE));

    $authUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appId .
               '&redirect_uri=' . urlencode($currentUrl) .
               '&response_type=code&scope=snsapi_base&state=' . urlencode($state) .
               '#wechat_redirect';
    header('Location: ' . $authUrl);
    exit;
}

// 第二步：有 code → 用 code 换 access_token + openid（snsapi_base 一步到位）
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
if (!isset($tokenArr['openid'])) {
    die('换取 openid 失败：' . htmlspecialchars($tokenJson));
}

$openId  = $tokenArr['openid'];
$unionId = $tokenArr['unionid'] ?? '';   // 公众号绑开放平台才会返回；没绑则空

// snsapi_base 拿不到昵称头像，留空
$nickname = '';
$avatar   = '';

// 推广参数
$site   = isset($promoParams['site'])   ? trim((string)$promoParams['site'])   : '';
$invite = isset($promoParams['invite']) ? trim((string)$promoParams['invite']) : '';

$ts = (string) time();

// 第三步：HMAC-SHA256 签名（空字段不参与 → 与原逻辑一致）
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
    $signParts[] = $k . '=' . $v;
}
$signBase = implode('&', $signParts);
$sign = hash_hmac('sha256', $signBase, $bridgeSecret);

// 第四步：跳主站落地
$query = $nonEmpty;
$query['sign'] = $sign;

$targetUrl = 'https://' . $targetDomain . $targetPath . '?' . http_build_query($query);
header('Location: ' . $targetUrl);
exit;

