<?php
// 获取当前请求的所有查询参数（如 ?site=xa016&invite=xxx）
$queryString = $_SERVER['QUERY_STRING'] ?? '';

// 构造重定向目标（保留原参数）
$redirectUrl = 'https://zt.xiaoyuwangluo.vip/getcode.php';
if ($queryString !== '') {
    $redirectUrl .= '?' . $queryString;
}

// 发送 302 临时重定向头（浏览器会立即跳转，不渲染任何内容）
header('Location: ' . $redirectUrl, true, 302);
exit;
