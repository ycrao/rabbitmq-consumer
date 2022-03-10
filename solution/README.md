读我
---

### 运行

```bash
cd solution
# 安装依赖
composer install -vvv
# 修改 rabbitmq 配置
cp bin/config.php.example bin/config.php
# 生产者推送测试消息到 rabbitmq 中
php bin/producer.php
# 求和值消费者
php bin/calc_sum_consumer.php
# 求平均值消费者
php bin/calc_avg_consumer.php
# 使用 `php` 编写的基于 `pctl` 和 `posix` 常驻管理脚本
# php bin/daemon.php start|stop|status
php bin/daemon.php start
```

### `Supervisor` 重启方案

参考 `conf/calc-sum-rabbitmq-worker.ini.example` 配置示例。

### `K8S-Docker` 方案

参考根目录下 `Dockerfile` 示例。

### 方案三和四

略去，请自行调研与学习。

