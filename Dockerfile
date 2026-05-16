# 使用官方 PHP 7.3 Apache 镜像
FROM php:7.3-apache
# 将代码复制到容器内的默认网站目录
COPY . /var/www/html/
# 监听 8080 端口
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
