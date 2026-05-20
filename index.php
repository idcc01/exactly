<?php
/**
 * 安全跳转页面 - 自动检测错误，显示过渡动画，3秒后跳转
 */

// 【调试模式】如果出现空白页，会强制输出错误（上线后可将下面两行注释）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 开启输出缓冲，防止 "headers already sent" 错误
ob_start();

// 获取查询参数
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$redirectUrl = 'https://zt.xiaoyuwangluo.vip/getcode.php';
if ($queryString !== '') {
    $redirectUrl .= '?' . $queryString;
}

$delaySeconds = 3; // 等待秒数

// 安全编码
$safeUrl = htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8');
$jsUrl = json_encode($redirectUrl);

// 清理输出缓冲区，确保没有任何前置输出
ob_end_clean();

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="<?php echo $delaySeconds; ?>;url=<?php echo $safeUrl; ?>">
    <title>正在跳转...</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: system-ui, -apple-system, 'Segoe UI', 'PingFang SC', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 48px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            max-width: 460px;
            width: 100%;
            padding: 40px 32px 48px;
            text-align: center;
            animation: fadeUp 0.5s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .spinner {
            width: 70px;
            height: 70px;
            margin: 0 auto 24px;
            border: 4px solid #e0d4f5;
            border-top-color: #764ba2;
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        h2 {
            font-size: 26px;
            color: #1e1e2f;
            margin-bottom: 10px;
        }
        p {
            color: #5a5a72;
            margin-bottom: 28px;
            font-size: 16px;
        }
        .countdown {
            background: #f3f0fa;
            display: inline-flex;
            align-items: baseline;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 60px;
            font-weight: 600;
            margin-bottom: 30px;
        }
        .countdown span:first-child {
            font-size: 28px;
            color: #764ba2;
            font-weight: 800;
            font-family: monospace;
            min-width: 46px;
        }
        .manual-link {
            display: inline-block;
            background: #764ba2;
            color: white;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 500;
            transition: 0.2s;
            margin-top: 8px;
        }
        .manual-link:hover {
            background: #5e3a8e;
            transform: scale(0.96);
        }
        .footer {
            margin-top: 32px;
            font-size: 12px;
            color: #8e8ea8;
        }
        @media (max-width: 500px) {
            .card { padding: 32px 24px; }
            h2 { font-size: 22px; }
        }
    </style>
</head>
<body>
<div class="card">
    <div class="spinner"></div>
    <h2>⏳ 安全跳转中</h2>
    <p>正在前往目标服务，请稍候</p>
    <div class="countdown">
        <span id="countdownNum"><?php echo $delaySeconds; ?></span>
        <span>秒后自动跳转</span>
    </div>
    <a href="<?php echo $safeUrl; ?>" class="manual-link">立即跳转 →</a>
    <div class="footer">
        <?php 
        $host = parse_url($redirectUrl, PHP_URL_HOST);
        echo htmlspecialchars($host ?: '目标站点', ENT_QUOTES); 
        ?>
    </div>
</div>

<script>
(function() {
    let seconds = <?php echo (int)$delaySeconds; ?>;
    const countEl = document.getElementById('countdownNum');
    function update() { if(countEl) countEl.innerText = seconds; }
    update();
    const interval = setInterval(() => {
        seconds--;
        update();
        if(seconds <= 0) {
            clearInterval(interval);
            window.location.href = <?php echo $jsUrl; ?>;
        }
    }, 1000);
    // 手动跳转拦截优雅处理
    const manual = document.querySelector('.manual-link');
    if(manual) {
        manual.addEventListener('click', (e) => {
            e.preventDefault();
            clearInterval(interval);
            window.location.href = <?php echo $jsUrl; ?>;
        });
    }
})();
</script>
</body>
</html>
<?php
// 保证脚本不再执行其他内容
exit;
