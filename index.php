<?php
/**
 * 中转跳转页面 - 显示美观过渡页，然后自动跳转到目标地址
 * 
 * 功能：获取当前请求的所有查询参数，构建目标URL，展示友好等待页面，
 *       3秒后通过JS（或Meta刷新）自动跳转，同时保留原始参数。
 */

// 获取当前请求的所有查询参数（如 ?site=xa016&invite=xxx）
$queryString = $_SERVER['QUERY_STRING'] ?? '';

// 构造重定向目标（保留原参数）
$redirectUrl = 'https://zt.xiaoyuwangluo.vip/getcode.php';
if ($queryString !== '') {
    $redirectUrl .= '?' . $queryString;
}

// 跳转延迟时间（秒）
$delaySeconds = 3;

// 对URL进行安全编码，用于HTML属性、JS变量和Meta刷新
$safeRedirectUrl = htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8');
$jsSafeRedirectUrl = json_encode($redirectUrl); // 用于JS，自动转义

// 以下是美观过渡页面
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- 禁止缓存，确保每次都是最新状态 -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <!-- Meta 刷新后备方案：如果JS被禁用，delaySeconds秒后自动跳转 -->
    <meta http-equiv="refresh" content="<?php echo $delaySeconds; ?>;url=<?php echo $safeRedirectUrl; ?>">
    <title>安全跳转中...</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', 'PingFang SC', Roboto, 'Helvetica Neue', system-ui, -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* 动态背景装饰 */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, rgba(0,0,0,0.1) 100%);
            pointer-events: none;
        }

        /* 主卡片容器 */
        .card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(0px);
            border-radius: 42px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255,255,255,0.2);
            width: 100%;
            max-width: 500px;
            padding: 40px 32px 48px;
            text-align: center;
            transition: transform 0.3s ease;
            animation: fadeInUp 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 图标 + 加载动画区域 */
        .icon-wrapper {
            margin-bottom: 28px;
            position: relative;
            display: inline-block;
        }

        .loading-spinner {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            position: relative;
        }

        .loading-spinner svg {
            width: 100%;
            height: 100%;
            animation: rotate 1.8s linear infinite;
            transform-origin: center;
        }

        @keyframes rotate {
            100% { transform: rotate(360deg); }
        }

        .spinner-circle {
            stroke: #764ba2;
            stroke-width: 5;
            stroke-linecap: round;
            stroke-dasharray: 180;
            stroke-dashoffset: 80;
            animation: dash 1.2s ease-in-out infinite;
            fill: none;
        }

        @keyframes dash {
            0% { stroke-dashoffset: 180; }
            50% { stroke-dashoffset: 40; }
            100% { stroke-dashoffset: 180; }
        }

        /* 标题文字 */
        .title {
            font-size: 28px;
            font-weight: 700;
            color: #1e1e2f;
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }

        .subtitle {
            font-size: 16px;
            color: #5a5a72;
            margin-bottom: 32px;
            line-height: 1.4;
            font-weight: 500;
        }

        /* 倒计时圆环区域 (简洁数字) */
        .countdown-container {
            background: #f0eff5;
            border-radius: 60px;
            padding: 14px 20px;
            display: inline-flex;
            align-items: baseline;
            gap: 6px;
            margin-bottom: 32px;
            font-weight: 600;
            backdrop-filter: blur(4px);
        }

        .countdown-label {
            font-size: 15px;
            color: #4a4a60;
        }

        .countdown-number {
            font-size: 32px;
            font-weight: 800;
            color: #764ba2;
            line-height: 1;
            font-family: monospace;
            min-width: 48px;
            text-align: center;
        }

        .countdown-unit {
            font-size: 15px;
            color: #4a4a60;
        }

        /* 手动跳转链接 */
        .manual-link {
            display: inline-block;
            margin-top: 16px;
            padding: 10px 24px;
            background: rgba(118, 75, 162, 0.08);
            color: #5e3a8e;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.2s ease;
            border: 1px solid rgba(118, 75, 162, 0.2);
        }

        .manual-link:hover {
            background: rgba(118, 75, 162, 0.15);
            border-color: rgba(118, 75, 162, 0.4);
            transform: scale(0.97);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        /* 提示脚注 */
        .footer-note {
            margin-top: 32px;
            font-size: 12px;
            color: #8e8ea8;
            border-top: 1px solid #ececf0;
            padding-top: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .footer-note span {
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #b0b0c4;
            border-radius: 50%;
        }

        /* 目标域名提示（轻量级） */
        .target-hint {
            background: #f7f6fc;
            border-radius: 28px;
            padding: 8px 16px;
            font-size: 13px;
            color: #6b6b85;
            margin-top: 20px;
            word-break: break-all;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            backdrop-filter: blur(2px);
        }

        .target-hint::before {
            content: "🔒";
            font-size: 12px;
            opacity: 0.7;
        }

        /* 响应式调整 */
        @media (max-width: 550px) {
            .card {
                padding: 32px 24px 40px;
            }
            .title {
                font-size: 24px;
            }
            .countdown-number {
                font-size: 28px;
            }
        }

        /* 无脚本友好 */
        noscript {
            display: block;
            margin-top: 20px;
            background: #ffeedd;
            padding: 12px;
            border-radius: 36px;
            color: #b45b0a;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrapper">
            <div class="loading-spinner">
                <svg viewBox="0 0 50 50">
                    <circle class="spinner-circle" cx="25" cy="25" r="20"></circle>
                </svg>
            </div>
        </div>
        
        <h1 class="title">即将跳转</h1>
        <p class="subtitle">正在安全地将您引导至目标服务</p>
        
        <!-- 动态倒计时 -->
        <div class="countdown-container">
            <span class="countdown-label">剩余</span>
            <span class="countdown-number" id="countdownNum"><?php echo $delaySeconds; ?></span>
            <span class="countdown-unit">秒后自动跳转</span>
        </div>
        
        <!-- 手动跳转链接，保留所有参数 -->
        <a href="<?php echo $safeRedirectUrl; ?>" class="manual-link" id="manualLink">立即跳转 →</a>
        
        <!-- 轻量显示目标域名，增加信任 -->
        <div class="target-hint">
            <?php 
            // 只展示主域名，不影响美观
            $host = parse_url($redirectUrl, PHP_URL_HOST);
            echo htmlspecialchars($host ?: '目标站点', ENT_QUOTES, 'UTF-8'); 
            ?>
        </div>
        
        <div class="footer-note">
            <span></span> 页面自动跳转无需操作 <span></span>
        </div>
        
        <noscript>
            ⚡ 您的浏览器未启用JavaScript，页面将在 <?php echo $delaySeconds; ?> 秒后自动跳转。<br>
            若未跳转，请<a href="<?php echo $safeRedirectUrl; ?>">点击此处</a>。
        </noscript>
    </div>

    <script>
        (function() {
            // 延迟时间 (与PHP保持一致)
            let secondsLeft = <?php echo (int)$delaySeconds; ?>;
            const countdownElement = document.getElementById('countdownNum');
            const redirectUrl = <?php echo $jsSafeRedirectUrl; ?>;
            
            // 更新时间显示
            function updateDisplay() {
                if (countdownElement) {
                    countdownElement.innerText = secondsLeft;
                }
            }
            
            // 执行跳转
            function performRedirect() {
                // 清除可能存在的定时器
                if (window.redirectTimer) clearTimeout(window.redirectTimer);
                if (window.countdownInterval) clearInterval(window.countdownInterval);
                window.location.href = redirectUrl;
            }
            
            // 倒计时更新器
            if (secondsLeft > 0) {
                // 先立即更新显示
                updateDisplay();
                
                // 设置倒计时定时器
                window.countdownInterval = setInterval(function() {
                    secondsLeft--;
                    updateDisplay();
                    
                    if (secondsLeft <= 0) {
                        clearInterval(window.countdownInterval);
                        performRedirect();
                    }
                }, 1000);
                
                // 设置后备强制跳转 (确保无论如何都会跳转，比实际倒计时多0.2秒保护)
                window.redirectTimer = setTimeout(function() {
                    if (secondsLeft > 0) {
                        clearInterval(window.countdownInterval);
                        performRedirect();
                    }
                }, (<?php echo (int)$delaySeconds; ?> + 1) * 1000);
            } else {
                // 如果延迟为0，立即跳转
                performRedirect();
            }
            
            // 手动链接点击时，阻止默认行为但要清除计时器然后跳转（但不与meta冲突，提升体验）
            const manualLink = document.getElementById('manualLink');
            if (manualLink) {
                manualLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    // 清除所有自动跳转计时器，避免多次跳转
                    if (window.redirectTimer) clearTimeout(window.redirectTimer);
                    if (window.countdownInterval) clearInterval(window.countdownInterval);
                    // 立即执行跳转
                    window.location.href = redirectUrl;
                });
            }
        })();
    </script>
</body>
</html>
<?php
// 确保不再执行任何额外输出
exit;
