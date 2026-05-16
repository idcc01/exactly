<?php
session_start();

$appId     = 'wxde2ad02f02cb0df5';
$appSecret = 'f27a1e32177f425fe8c940dc8d063b32';

// 你的真实目标网站信息 - 请务必替换为真实值！！！
$targetDomain = 'ipp.noteflow.me'; // 例如：'example.com'
$targetPath   = 'home';   // 例如：'/oauth/callback'

// 1. 如果没有code，说明尚未授权，需要构造授权链接
if (!isset($_GET['code'])) {
    // 获取当前页面的所有查询参数（如 site, invite 等），用于后续处理
    $promoParams = array_diff_key($_GET, array_flip(['code', 'state']));
    // 将所有推广参数放入 state
    $state = base64_encode(json_encode($promoParams));

    // 回调地址，确保协议头和路径正确
    $redirectUri = urlencode('https://' . $targetDomain . $targetPath);

    // 构造微信授权链接
    $authUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appId .
               '&redirect_uri=' . $redirectUri .
               '&response_type=code&scope=snsapi_userinfo&state=' . urlencode($state) .
               '#wechat_redirect';

    header('Location: ' . $authUrl);
    exit;
}

// 2. 有code，开始处理用户授权信息
$code = $_GET['code'];

// 换取access_token和openid
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

// 拉取用户详细信息
$userInfoUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $accessToken .
               '&openid=' . $openId . '&lang=zh_CN';
$userJson = file_get_contents($userInfoUrl);
$userInfo = json_decode($userJson, true);

if (isset($userInfo['errcode'])) {
    die('获取用户信息失败：' . $userInfo['errmsg']);
}

// 3. 整理要传递给目标网站的参数
$userData = [
    'nickname'   => $userInfo['nickname'],
    'headimgurl' => $userInfo['headimgurl'],
    'sex'        => $userInfo['sex'],
    'openid'     => $openId,
];
if (!empty($unionId)) {
    $userData['unionid'] = $unionId;
}

// 4. 从 state 参数中获取并合并推广参数
$stateParam = $_GET['state'] ?? '';
$promoParams = [];
if (!empty($stateParam)) {
    $decoded = base64_decode($stateParam);
    if ($decoded !== false) {
        $promoParams = json_decode($decoded, true) ?: [];
    }
}

// 合并参数：推广参数与用户信息
$finalParams = array_merge($userData, $promoParams);

// 5. 构造最终跳转URL，传递所有参数
$finalRedirectUrl = 'https://' . $targetDomain . $targetPath . '?' . http_build_query($finalParams);
header('Location: ' . $finalRedirectUrl);
exit;
?>
