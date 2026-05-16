# 使用官方 PHP 7.3 Apache 镜像
FROM php:7.3-apache

# 复制项目所有文件到 Apache 的默认网页目录
COPY . /var/www/html/

# 修改 Apache 监听端口为 8080
RUN sed -i 's/Listen 80/Listen 8080/g' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:8080>/g' /etc/apache2/sites-available/000-default.conf

# 启用 mod_rewrite
RUN a2enmod rewrite

# 使用开发模式 php.ini
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# 声明容器监听端口
EXPOSE 8080

# 启动 Apache
CMD ["apache2-foreground"]
