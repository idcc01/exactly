<?php
session_start();

$appId     = 'wxde2ad02f02cb0df5';
$appSecret = 'f27a1e32177f425fe8c940dc8d063b32';

// 当前脚本的地址（用于微信回调）
$redirectUri = 'https://zt.xiaoyuwangluo.vip/getcode.php';

// 获取推广参数（例如 site, from 等），并保存到 session 中，以便授权完成后跳转时使用
$promoParams = [];
if (!empty($_GET)) {
    // 过滤掉微信可能会带的 code 和 state，只保留我们自己的参数
    $promoParams = array_diff_key($_GET, array_flip(['code', 'state']));
}
// 将推广参数序列化后存入 session（或者用 $_SESSION 数组保存每个参数）
$_SESSION['promo_params'] = $promoParams;

// 1. 如果没有 code，跳转到微信授权页面
if (!isset($_GET['code'])) {
    $state = json_encode($promoParams); // 可以将推广参数编码后放入 state（注意长度限制）
    $state = base64_encode($state);
    $authUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appId .
              '&redirect_uri=' . urlencode($redirectUri) .
              '&response_type=code&scope=snsapi_userinfo&state=' . urlencode($state) .
              '#wechat_redirect';
    header('Location: ' . $authUrl);
    exit;
}

// 2. 获取 code，换取 access_token
$code = $_GET['code'];
$tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appId .
            '&secret=' . $appSecret . '&code=' . $code . '&grant_type=authorization_code';
$tokenJson = file_get_contents($tokenUrl);
$tokenArr = json_decode($tokenJson, true);

if (!isset($tokenArr['access_token'])) {
    die('获取 access_token 失败：' . $tokenJson);
}

$accessToken = $tokenArr['access_token'];
$openId      = $tokenArr['openid'];

// 3. 拉取用户信息
$userInfoUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $accessToken .
               '&openid=' . $openId . '&lang=zh_CN';
$userJson = file_get_contents($userInfoUrl);
$userInfo = json_decode($userJson, true);

if (isset($userInfo['errcode'])) {
    die('获取用户信息失败：' . $userInfo['errmsg']);
}

// 4. 整理要传递给目标网站的用户数据
$userData = [];
// 优先使用 unionid（如果存在）
if (!empty($userInfo['unionid'])) {
    $userData['unionid'] = $userInfo['unionid'];
} else {
    $userData['openid'] = $userInfo['openid'];
}
$userData['nickname']   = $userInfo['nickname'];
$userData['headimgurl'] = $userInfo['headimgurl'];
// 可选：性别、省份等
$userData['sex']        = $userInfo['sex'] ?? 0;

// 5. 获取之前保存的推广参数
$promo = isset($_SESSION['promo_params']) ? $_SESSION['promo_params'] : [];
// 合并用户数据和推广参数（推广参数优先级高，避免覆盖用户关键字段）
$params = array_merge($userData, $promo);

// 6. 跳转到目标网站，携带参数（使用 URL 传递）
$targetBase = 'https://你的目标网站.com/oauth/callback';  // 目标网站接收回调的地址
$query = http_build_query($params);
$targetUrl = $targetBase . '?' . $query;

// 清除 session 中的临时数据（可选）
unset($_SESSION['promo_params']);

header('Location: ' . $targetUrl);
exit;
