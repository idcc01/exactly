<?php
// 目标网站
$target = "https://ipp.noteflow.me/home";

// 获取所有推广参数
$query = $_SERVER['QUERY_STRING'];

// 直接跳转！不做任何微信登录操作！
if (!empty($query)) {
    header("Location: $target?$query");
} else {
    header("Location: $target");
}
exit;
?>
