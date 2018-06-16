# What's This?
这是一个为方便初级 phper 使用 php 进行多进程开发的库。
<br/>
用不到十行的代码，实现一个以守护进程方式启动的多进程程序。  
<br/>
一般用于消息队列的消费者端、多进程爬虫等场景

### 初始化运行库
```php
require './vendor/autoload.php';

$app = new \Lisao\Process\Process([
    'process_count' => 4 , //运行的线程数
    'process_save' => './process.pid' //线程 id 保存路径，用于停止进程时使用
]);
```


### 闭包运行
```php
/*
 * $work_id 为工作id，从0开始，根据线程数顺序递增。
 * 用于区分进程，指派任务
 */
$app->start(function($work_id){
    echo "我启动了，工作ID：{$work_id} \n";
});
```


### 类方法运行
```php
class obj {
    public function test($work_id) {
        echo "我启动了，工作ID：{$work_id} \n";
    }
}

$obj = new obj();
$app->start($obj, 'test');
```


### 停止运行
```php
$app->stop();
```
