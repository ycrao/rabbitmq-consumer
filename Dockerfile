FROM hub.hayitech.com/infrastructure/runtime:raw-php7.4-fpm-ngx-v1.1
COPY solution /var/www/app
RUN mkdir -p /srv/logs && chmod +x /var/www/app/bin/calc_sum_consumer.php
WORKDIR /var/www/app

# 这里仅以 求和 消费者为演示
CMD ["/usr/bin/php", "bin/calc_sum_consumer.php"]