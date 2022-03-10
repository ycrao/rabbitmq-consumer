<?php

class Deamon {

    /*
     * @var pid file
     */
    protected $pidFile;

    public function __construct() {
        $this->pidFile = __DIR__.'/daemon.pid';
        $this->checkExt();
    }

    /**
     * 检查是否已安装必要的扩展
     */
    private function checkExt() {
        !function_exists('pcntl_signal') && die('Error:Need PHP Pcntl extension!');
    }

    /**
     * 常驻服务
     */
    private function daemon() {
        if (php_sapi_name() != 'cli') {
            die('Should run in CLI');
        }
        $pid = pcntl_fork();
        if ($pid < 0) {
            exit('fork failed');
        } else if ($pid > 0) {
            // 退出父进程
            exit(0);
        }

        // 设置新的会员
        // setsid 有几个注意点
        // 不能是进程组的组长调用
        // 对于进程组组员调用会产生新的会话和进程组，并成为该进程组的唯一成员，调用的进程将脱离终端
        if (posix_setsid() < 0) {
            exit('set sid failed');
        }

        // 重置文件掩码
        umask(0);
        // 切换工作目录
        // chdir('/');

        $fp = fopen($this->pidFile, 'w') or die("Cannot create pid file");
        fwrite($fp, posix_getpid());
        fclose($fp);

        // 关闭标准输入输出

        @fclose(STDIN);
        @fclose(STDOUT);
        @fclose(STDERR);


        $this->job();
        return;
    }

    /**
     * 守护进程任务
     */
    private function job() {
        while (true) {
            // passthru('php bin/calc_avg_consumer.php');
            shell_exec('php bin/calc_avg_consumer.php > bin/calc.log');
            sleep(5);
        }
    }

    /*
     * 格式化 debug 输出消息
     *
     * @param string $message
     */
    private function debug($message) {
        // 获取进程ID
        // var_dump(posix_getpid());
        // 获取进程组ID
        // var_dump(posix_getpgid(posix_getpid()));
        // 获取进程会话ID
        // var_dump(posix_getsid(posix_getpid()));
        printf("%d %d %d %d %s" . PHP_EOL,
            posix_getpid(),
            posix_getppid(),
            posix_getpgid(posix_getpid()),
            posix_getsid(posix_getpid()),
            $message
        );
    }

    /**
     * 获取守护进程 id
     * @return int
     */
    private function getPid(){
        // 判断存放守护进程id的文件是否存在
        if (!file_exists($this->pidFile)) {
            return 0;
        }
        $pid = intval(file_get_contents($this->pidFile));
        if (posix_kill($pid, SIG_DFL)) {  //判断该进程是否正常运行中
            return $pid;
        } else {
            unlink($this->pidFile);
            return 0;
        }
    }

    /**
     * 开启守护进程
     */
    private function start() {
        if ($this->getPid() > 0) {
            $this->debug('');
            echo 'Running'.PHP_EOL;
        } else {
            $this->daemon();
            $this->debug('');
            echo 'Start'.PHP_EOL;
        }
    }

    /**
     * 停止守护进程
     */
    private function stop() {
        $pid = $this->getPid();
        if ($pid > 0) {
            // 通过向进程id发送终止信号来停止进程
            posix_kill($pid, SIGTERM);
            unlink($this->pidFile);
            echo 'Stopped'. PHP_EOL;
        } else {
            echo 'Not Running'.PHP_EOL;
        }
    }

    /**
     * 获取运行状态
     */
    private function status() {
        if ($this->getPid() > 0) {
            echo 'Is Running'.PHP_EOL;
            $this->debug('');
        } else {
            echo 'Not Running'.PHP_EOL;
        }
    }

    /**
     * 入口方法
     *
     * @param array $argv
     */
    public function run($argv) {
        $param = is_array($argv) && count($argv) == 2 ? $argv[1] : null;
        switch ($param) {
            case 'start':
                $this->start();
                break;
            case 'stop':
                $this->stop();
                break;
            case 'status':
                $this->status();
                break;
            default:
                echo "php bin/daemon.php start|stop|status " . PHP_EOL;
                break;
        }
    }
}

$daemon = new \Deamon();
$daemon->run($argv);