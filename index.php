<?php
/**
 * 极简美观跳转页面 - 只显示转圈动画，无任何文字，自动跳转
 */

// 获取查询参数并构造目标URL
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$redirectUrl = 'https://sq.buliuming.me/getcode.php';
if ($queryString !== '') {
    $redirectUrl .= '?' . $queryString;
}

// 跳转延迟（秒）- 只控制自动跳转，界面上不显示任何数字
$delaySeconds = 2;

// 对URL进行安全编码，用于JS跳转
$jsRedirectUrl = json_encode($redirectUrl);

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Loading...</title>
    <style>
        /* 彻底重置边距，确保全屏覆盖 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: radial-gradient(circle at 30% 10%, rgba(20, 30, 48, 1) 0%, rgba(8, 12, 24, 1) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* 动态光晕背景层 - 增加深邃感 */
        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            top: -50%;
            left: -50%;
            background: radial-gradient(ellipse at center, rgba(80, 140, 210, 0.15) 0%, rgba(0,0,0,0) 70%);
            animation: rotateBg 20s linear infinite;
            pointer-events: none;
        }

        @keyframes rotateBg {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* 主容器 - 只放圆圈 */
        .loader-container {
            position: relative;
            z-index: 2;
        }

        /* 主要圆环 - 多圈层设计，更美观 */
        .spinner {
            width: 90px;
            height: 90px;
            position: relative;
        }

        /* 外圈 */
        .spinner:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(from 0deg, #6c5ce7, #a363d9, #ff8c42, #6c5ce7);
            mask: radial-gradient(farthest-side, transparent calc(100% - 10px), #000 calc(100% - 8px));
            -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 10px), #000 calc(100% - 8px));
            animation: rotate 1.2s linear infinite;
        }

        /* 内圈装饰小圆点 - 增加层次感 */
        .spinner:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 16px;
            height: 16px;
            background: #ffb347;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 12px #ffb347, 0 0 6px #ff8c42;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.6; transform: translate(-50%, -50%) scale(0.8); }
            50% { opacity: 1; transform: translate(-50%, -50%) scale(1.2); }
        }

        /* 可选：增加细微的粒子效果，提升视觉但不影响简洁 */
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 4s infinite ease-in-out;
            pointer-events: none;
        }

        @keyframes float {
            0% { transform: translateY(0px) scale(1); opacity: 0; }
            50% { opacity: 0.8; }
            100% { transform: translateY(-80px) scale(0); opacity: 0; }
        }

        /* 无任何文字，保证完全纯净 */
    </style>
</head>
<body>
    <div class="loader-container">
        <div class="spinner"></div>
    </div>

    <!-- 动态添加随机粒子（仅装饰） -->
    <script>
        (function() {
            // 创建少量漂浮粒子，更灵动（不影响跳转核心）
            const body = document.body;
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.bottom = '-20px';
                particle.style.animationDelay = Math.random() * 5 + 's';
                particle.style.animationDuration = 3 + Math.random() * 4 + 's';
                particle.style.width = Math.random() * 6 + 2 + 'px';
                particle.style.height = particle.style.width;
                particle.style.backgroundColor = `hsla(${Math.random() * 60 + 200}, 70%, 60%, 0.4)`;
                body.appendChild(particle);
            }

            // 自动跳转
            let delay = <?php echo (int)$delaySeconds; ?> * 0;
            setTimeout(function() {
                window.location.href = <?php echo $jsRedirectUrl; ?>;
            }, delay);
        })();
    </script>
</body>
</html>
<?php
// 确保结束，无额外输出
exit;
