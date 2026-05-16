<?php
// 启用错误显示（调试用，上线后可注释或删除）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 启动 session（可选，用于临时存储）
session_start();

// ==================== 配置区域 ====================
$appId     = 'wxde2ad02f02cb0df5';          // 微信公众号 AppID
$appSecret = 'f27a1e32177f425fe8c940dc8d063b32';      // 微信公众号 AppSecret

// 目标网站配置（接收用户信息和推广参数）
$targetDomain = 'ipp.noteflow.me';   // 例如 'example.com'，不要带 http://
$targetPath   = '/home';    // 例如 '/oauth/callback'
// =================================================

// 当前脚本的完整 URL（用于微信回调）
$currentUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// 1. 如果没有 code 参数，跳转到微信授权页
if (!isset($_GET['code'])) {
    // 获取所有查询参数（推广参数），并排除微信可能会用的 code、state
    $promoParams = array_diff_key($_GET, array_flip(['code', 'state']));
    // 将推广参数编码后放入 state（base64 + json）
    $state = base64_encode(json_encode($promoParams));
    
    // 回调地址：当前脚本的完整 URL（必须与公众号后台配置的域名一致）
    $redirectUri = $currentUrl;
    
    // 构造微信授权链接（使用 snsapi_userinfo 获取头像昵称）
    $authUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appId .
               '&redirect_uri=' . urlencode($redirectUri) .
               '&response_type=code&scope=snsapi_userinfo&state=' . urlencode($state) .
               '#wechat_redirect';
    
    header('Location: ' . $authUrl);
    exit;
}

// 2. 有 code 参数，处理授权回调
$code = $_GET['code'];

// 获取 state 中的推广参数
$stateParam = $_GET['state'] ?? '';
$promoParams = [];
if (!empty($stateParam)) {
    $decoded = base64_decode($stateParam);
    if ($decoded !== false) {
        $promoParams = json_decode($decoded, true);
        if (!is_array($promoParams)) {
            $promoParams = [];
        }
    }
}

// 换取 access_token
$tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appId .
            '&secret=' . $appSecret . '&code=' . $code . '&grant_type=authorization_code';
$tokenJson = file_get_contents($tokenUrl);
$tokenArr = json_decode($tokenJson, true);

if (!isset($tokenArr['access_token'])) {
    die('换取 access_token 失败：' . $tokenJson);
}

$accessToken = $tokenArr['access_token'];
$openId      = $tokenArr['openid'];
$unionId     = $tokenArr['unionid'] ?? '';

// 拉取用户详细信息（昵称、头像等）
$userInfoUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $accessToken .
               '&openid=' . $openId . '&lang=zh_CN';
$userJson = file_get_contents($userInfoUrl);
$userInfo = json_decode($userJson, true);

if (isset($userInfo['errcode'])) {
    die('获取用户信息失败：' . $userInfo['errmsg']);
}

// 3. 整理要传递的用户数据
$userData = [
    'nickname'   => $userInfo['nickname'],
    'headimgurl' => $userInfo['headimgurl'],
    'sex'        => $userInfo['sex'] ?? 0,
    'openid'     => $openId,
];
if (!empty($unionId)) {
    $userData['unionid'] = $unionId;
}

// 4. 合并推广参数（推广参数优先级高于用户数据，但这里不会覆盖，因为字段名不同）
$finalParams = array_merge($userData, $promoParams);

// 5. 构造跳转到目标网站的 URL
$targetUrl = 'https://' . $targetDomain . $targetPath . '?' . http_build_query($finalParams);

// 可选：记录日志或存储到 session
$_SESSION['wechat_user'] = $userData;
$_SESSION['promo'] = $promoParams;

// 执行跳转
header('Location: ' . $targetUrl);
exit;
