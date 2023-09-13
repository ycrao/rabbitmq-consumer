RabbitMQ-Consumer
-----------------

### 直面问题

`php/php-cli` 在常驻和任务调度上面存在天然的不应性，这是语言自身限制的。虽然我们可以借助于 `pctl` 等扩展能解决部分问题，但在复杂框架依赖（如 `Laravel/Symfony`）且更长时间常驻运行等情况下，还是会出现内存泄露、僵尸进程等问题。对于消息队列消费端来说，尤其需要考虑此问题。那如何保障消息队列消费端正常无偏差的投递呢？

### 解决方案

#### 方案一 基于 `pctl` 和 `posix` 实现常驻服务

此方案治标不治本，只是延迟常驻发生内存泄露的时间，但是，在一般业务场景下，已经够用了。

参考文档：

- [PHP 实现守护进程](https://learnku.com/articles/32320)
- [php-resque](https://github.com/chrisboulton/php-resque)

#### 方案二 基于 `supervisor/k8s-docker` 重启拉起机制

此方案通俗点讲，在终端消费完队列消息后空闲的情况，特意等待一段时间后自行退出，然后 `supervisor` 进程管理软件会重新拉起该消费脚本并执行它。此方案需要特别注意，脚本进程不能退出过于密集，否则会被 `supervisor` 标记为频繁退出不予启动。如果是 `k8s-docker` 环境的话，将消费脚本作为入口进程，我们可以选择在运行一段时间后且较为空闲情况下（一般选择在晚上无消息投递空闲时候）自动退出，然后 `k8s` 调度器会自动拉起新的 `POD` 继续跑消费脚本。

#### 方案三 第三方进程调度器

>   借助于其它高级语言实现的进程调度器来平衡。目前，找到2个实现方案：一个基于 [rust 实现](https://github.com/facile-it/rabbitmq-consumer) 的 ，一个基于 [golang 实现](https://github.com/corvus-ch/rabbitmq-cli-consumer) 的。

参考文档：

- [Common problems faced by PHP developers in consuming an AMQP message](https://engineering.facile.it/blog/eng/common-problems-faced-by-php-developers-in-consuming-an-ampq-message/)
- [Keeping RabbitMQ connections alive in PHP](https://blog.mollie.com/keeping-rabbitmq-connections-alive-in-php-b11cb657d5fb)

#### 方案四 使用其它成熟语言解决方案（如JAVA）

基于 `Java` 语言实现消费端方案，就不过多赘述，`GitHub` 上应该拥有不少教程示例（基本上无差别选用 `Spring` 方案）。该方案需要使用者有一定 `Java` 语言功底。还有一个更贴近 `PHP` 生态，借助于 `Swoole` （基于 `c` 语言编写的）扩展，实现消费调度（此方案发生内存泄露概率会大大降低，但是不保证完全不会）。

参考文档：

- [Messaging with RabbitMQ](https://spring.io/guides/gs/messaging-rabbitmq/)
- [Swoole](https://www.swoole.com/)
- [Hyperf AMQP](https://hyperf.wiki/2.2/#/zh-cn/amqp)