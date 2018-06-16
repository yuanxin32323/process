<?php

namespace Lisao\Process;

class Process {

    /**
     * 线程数
     * @var int
     */
    public $process_count = 1;

    /**
     * 进程id保存路径
     * @var int
     */
    public $process_save;

    /**
     * 配置初始化
     * @param type $config 配置参数，process_count=运行进程数,process_save=进程id保存路径
     * @throws \Exception
     */
    public function __construct($config = []) {
        //仅允许 cli 运行
        if (php_sapi_name() != "cli") {
            throw new \Exception("请在 CLI 模式下启动");
        }
        $this->process_count = $config['process_count'] ?: 1;
        $this->process_save = $config['process_save'];
    }

    /**
     * 启动多进程
     * @param object $func_class 可以是闭包，也可以是类名
     * @param string $action 如果是以类的方式运行，需要传入要执行的方法名
     * @throws \Exception
     */
    public function start($func_class, $action = '') {
        if (!$this->process_save) {
            throw new \Exception("未设置进程文件保存路径");
        }
        if ($this->process_count < 1) {
            throw new \Exception("进程数必须大于等于1");
        }
        if (!($func_class instanceof \Closure) && !is_object($func_class)) {

            throw new \Exception("请设置要执行的方法或函数");
        }
        if (!($func_class instanceof \Closure)) {
            if (!method_exists($func_class, $action)) {
                throw new \Exception("需要执行的方法不存在");
            }
        }


        if (file_exists($this->process_save)) {
            throw new \Exception("程序运行中");
        }

        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new \Exception('fork子进程失败');
        } elseif ($pid > 0) {
            //父进程退出,子进程不是进程组长，以便接下来顺利创建新会话  
            exit(0);
        }
        //创建独立会话
        posix_setsid();
        usleep(500000);
        //修改工作目录
        //chdir('/');
        //开始创建多进程
        $this->create_process($this->process_count, $func_class, $action);
    }

    /**
     * 创建进程
     */
    private function create_process($process_count, $func_class, $action) {
        $son = [];
        //循环创建进程
        for ($i = 0; $i < $process_count; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                throw new \Exception("进程创建失败");
            } elseif ($pid == 0) {
                //当前是子进程 
                // 由于守护进程用不到标准输入输出，关闭标准输入，输出，错误输出描述符  
                fclose(STDIN);
                //fclose(STDOUT);
                //fclose(STDERR);
                //启动任务
                if (!($func_class instanceof \Closure)) {
                    $func_class->$action($i);
                } else {
                    $func_class($i);
                }

                break;
            } else {
                //当前是主进程
                $son[] = $pid;
                //写入pid文件
                if ($process_count - $i == 1) {
                    file_put_contents($this->process_save, json_encode($son));
                }
            }
            //延迟20ms
            usleep(1000 * 20);
        }
    }

    /**
     * 停止进程
     * @return boolean
     * @throws \Exception
     */
    public function stop() {
        if (!$this->process_save) {
            throw new \Exception("未设置进程文件保存路径");
        }
        $proce_file = @file_get_contents($this->process_save);
        if (!$proce_file) {
            throw new \Exception("程序未运行");
        }
        $arr = json_decode($proce_file, true);
        if (!$arr) {
            unlink($this->process_save);
            throw new \Exception("程序未运行");
        }
        foreach ($arr as $val) {
            //发送终止进程信号
            posix_kill($val, SIGTERM);
        }
        // posix_kill($arr['master'], SIGTERM);
        unlink($this->process_save);
        return TRUE;
    }

}
