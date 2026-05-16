<?php
// 启动 session（用于保存用户登录状态）
session_start();

// ==================== 配置区域 ====================
$appId     = 'wxde2ad02f02cb0df5';          // 请替换为真实的 AppID
$appSecret = 'f27a1e32177f425fe8c940dc8d063b32';      // 请替换为真实的 AppSecret

// 回调地址（当前脚本的完整URL，建议写死更安全）
$redirectUri = 'https://zt.xiaoyuwangluo.vip/getcode.php';

// 定义 state 防止 CSRF（可选，这里生成随机串）
$state = md5(uniqid(rand(), true));
// =================================================

// 1. 如果没有 code 参数，说明还未授权，需要引导用户跳转到微信授权页面
if (!isset($_GET['code'])) {
    // 构造微信 OAuth2 授权链接（使用 snsapi_userinfo 获取头像昵称）
    $authUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appId .
              '&redirect_uri=' . urlencode($redirectUri) .
              '&response_type=code&scope=snsapi_userinfo&state=' . $state .
              '#wechat_redirect';
    // 执行跳转
    header('Location: ' . $authUrl);
    exit;
}

// 2. 有 code 参数，说明用户已授权，需要换取 access_token
$code = $_GET['code'];

// 请求 access_token 的接口
$tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appId .
            '&secret=' . $appSecret . '&code=' . $code . '&grant_type=authorization_code';

$tokenJson = file_get_contents($tokenUrl);
$tokenArr = json_decode($tokenJson, true);

// 检查是否获取到 access_token
if (isset($tokenArr['access_token'])) {
    $accessToken = $tokenArr['access_token'];
    $openId      = $tokenArr['openid'];

    // 3. 拉取用户信息（头像、昵称等）
    $userInfoUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $accessToken .
                   '&openid=' . $openId . '&lang=zh_CN';
    $userJson = file_get_contents($userInfoUrl);
    $userInfo = json_decode($userJson, true);

    // 检查用户信息是否获取成功
    if (!isset($userInfo['errcode'])) {
        // 将用户信息存入 session 中，以便在其他页面使用
        $_SESSION['wechat_user'] = $userInfo;
        // 可选：设置登录过期时间（例如30分钟）
        $_SESSION['login_time'] = time();

        // 授权成功，跳转到首页（index.html）
        $homeUrl = 'https://zt.xiaoyuwangluo.vip/index.html';
        header('Location: ' . $homeUrl);
        exit;
    } else {
        // 拉取用户信息失败（通常是 access_token 无效或过期）
        die('获取用户信息失败：' . $userInfo['errmsg']);
    }
} else {
    // 换取 access_token 失败
    die('换取 access_token 失败：' . $tokenJson);
}
?>
