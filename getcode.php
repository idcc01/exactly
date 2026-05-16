<?php
// 获取公众号配置
$appId = 'wxde2ad02f02cb0df5'; // 替换成你的AppID
$appSecret = 'f27a1e32177f425fe8c940dc8d063b32'; // 替换成你的AppSecret

// 1. 如果URL中没有code参数，代表用户尚未授权，需要引导用户跳转到授权页面
if (!isset($_GET['code'])) {
    // 当前页面完整的URL，将被作为回调地址，并需要进行URL编码
    $redirectUri = urlencode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
    // 构造微信授权URL，使用snsapi_userinfo以获得用户详细信息
    $authUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appId}&redirect_uri={$redirectUri}&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
    // 执行跳转，引导用户进入授权页面
    header("Location: " . $authUrl);
    exit;
}

// 2. 用户已授权，URL中包含code参数，则用来换取access_token
$code = $_GET['code'];
$tokenUrl = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appId}&secret={$appSecret}&code={$code}&grant_type=authorization_code";
$tokenJson = file_get_contents($tokenUrl);
$tokenArr = json_decode($tokenJson, true);

// 检查是否成功获取access_token
if (isset($tokenArr['access_token'])) {
    $accessToken = $tokenArr['access_token'];
    $openId = $tokenArr['openid'];

    // 3. 拿着access_token和openid去拉取用户信息
    $userInfoUrl = "https://api.weixin.qq.com/sns/userinfo?access_token={$accessToken}&openid={$openId}&lang=zh_CN";
    $userJson = file_get_contents($userInfoUrl);
    $userInfo = json_decode($userJson, true);

    // 检查是否成功获取用户信息
    if (!isset($userInfo['errcode'])) {
        // 成功！现在$userInfo数组里就包含了用户的openid, nickname, headimgurl等信息
        echo "<pre>";
        print_r($userInfo);
        echo "</pre>";
        // 你可以在这里将用户信息存入数据库，或进行其他业务逻辑处理
    } else {
        echo "获取用户信息失败: " . $userInfo['errmsg'];
    }
} else {
    echo "换取access_token失败: " . $tokenJson;
}
?>
