<?php
// 启用错误显示（调试用，上线后可注释）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// ==================== 配置区域 ====================
$appId     = 'wxde2ad02f02cb0df5';           // 微信公众号 AppID
$appSecret = 'f27a1e32177f425fe8c940dc8d063b32';       // 微信公众号 AppSecret

// 主站 API 地址（用于后端登录）
$mainApiUrl = 'https://ipp.noteflow.me/api/auth/wechat/bridge';

// 主站首页地址（登录成功后跳转）
$homeUrl = 'https://ipp.noteflow.me/home';

// 签名密钥（与主站约定，用于验签）
$secret = 'your-pre-shared-secret-key';   // 请修改为与主站一致的密钥
// =================================================

// 当前脚本的完整 URL（用于微信回调）
$currentUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// 1. 如果没有 code 参数，跳转到微信授权页
if (!isset($_GET['code'])) {
    // 获取所有查询参数（推广参数），并排除微信可能会用的 code、state
    $promoParams = array_diff_key($_GET, array_flip(['code', 'state']));
    // 将推广参数编码后放入 state（base64 + json）
    $state = base64_encode(json_encode($promoParams));
    
    // 回调地址：当前脚本的完整 URL
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

// 3. 整理要传递给主站 API 的用户数据
$userData = [
    'nickname'   => $userInfo['nickname'],
    'avatar'     => $userInfo['headimgurl'],
    'sex'        => $userInfo['sex'] ?? 0,
    'openid'     => $openId,
];
if (!empty($unionId)) {
    $userData['unionid'] = $unionId;
}

// 合并推广参数
$userData = array_merge($userData, $promoParams);

// 4. 生成签名（防止参数被篡改）
ksort($userData);
$userData['ts'] = time();
$signStr = http_build_query($userData);
$userData['sign'] = hash_hmac('sha256', $signStr, $secret);

// 5. 调用主站后端 API 完成登录（POST 请求）
$postData = http_build_query($userData);
$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => $postData,
        'timeout' => 10,
    ],
];
$context = stream_context_create($options);
$result = file_get_contents($mainApiUrl, false, $context);

if ($result === false) {
    die('调用主站 API 失败，请稍后重试');
}

$response = json_decode($result, true);
if ($response && isset($response['code']) && $response['code'] === 200) {
    // 登录成功，重定向到主站首页
    header('Location: ' . $homeUrl);
    exit;
} else {
    $errorMsg = $response['msg'] ?? '未知错误';
    die('登录失败：' . $errorMsg);
}
