# 使用官方 PHP 7.3 Apache 镜像
FROM php:7.3-apache

# 复制项目所有文件到 Apache 的默认网页目录
COPY . /var/www/html/

# 【关键修复】将 Apache 的监听端口从 80 改为 8080
# 修改 ports.conf 中的 Listen 指令
RUN sed -i 's/Listen 80/Listen 8080/g' /etc/apache2/ports.conf
# 修改虚拟主机配置中的端口
RUN sed -i 's/:80>/:8080>/g' /etc/apache2/sites-available/000-default.conf

# 可选：启用 mod_rewrite 模块（如果你需要 URL 重写）
RUN a2enmod rewrite

# 可选：将 PHP 环境设置为开发模式（便于调试，上线后可改为 production）
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# 启动 Apache，以前台模式运行
CMD ["apache2-foreground"]
