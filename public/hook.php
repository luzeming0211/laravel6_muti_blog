<?php
die;
//访问秘钥
$keySecret = '123456';

//需要更新的项目目录
$wwwRoot = [
    '/www/wwwroot/laravel6_muti_blog',
//    '/www/wwwroot/test2',
];

//保存运行脚本的日志
$logFile = 'log/layuimini-githook.log';

//git执行命令
$gitCommand = 'git pull';

// 判断是否开启秘钥认证(已实现gitee和github)
if (isset($keySecret) && !empty($keySecret)) {
    list($headers, $gitType) = [[], null];
    foreach ($_SERVER as $key => $value) {
        'HTTP_' == substr($key, 0, 5) && $headers[str_replace('_', '-', substr($key, 5))] = $value;
        if (empty($gitType) && strpos($key, 'GITEE') !== false) {
            $gitType = 'GITEE';
        }
        if (empty($gitType) && strpos($key, 'GITHUB') !== false) {
            $gitType = 'GITHUB';
        }
    }
    if ($gitType == 'GITEE') {
        if (!isset($headers['X-GITEE-TOKEN']) || $headers['X-GITEE-TOKEN'] != $keySecret) {
            die('GITEE - 请求失败，秘钥有误');

        }
    } elseif ($gitType == 'GITHUB') {
        $json_content = file_get_contents('php://input');
        $signature = "sha1=" . hash_hmac('sha1', $json_content, $keySecret);
        if ($signature != $headers['X-HUB-SIGNATURE']) {
            die('GITHUB - 请求失败，秘钥有误');
        }
    } else {
        die('请求错误，未知git类型');
    }
}

!is_array($wwwRoot) && $wwwRoot = [$wwwRoot];
foreach ($wwwRoot as $vo) {
    $shell = sprintf("cd %s && git pull 2>&1", $vo);
    $output = shell_exec($shell);
    $log = sprintf("[%s] %s \n", date('Y-m-d H:i:s', time()) . ' - ' . $vo, $output);
    echo $log;
    file_put_contents($logFile, $log, FILE_APPEND);
}
