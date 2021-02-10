<?php

if (!isset($_GET['accesskey'])) {
    echo 'Access Key Missing.';
    die;
}

if(!array_key_exists('MAUTIC_COMMAND_ACCESS_KEY', $_ENV)) {
    echo 'MAUTIC_COMMAND_ACCESS_KEY was not set in environment variables';
    die;
}

if(strcmp($_GET['accesskey'], $_ENV['MAUTIC_COMMAND_ACCESS_KEY']) !== 0){
    echo 'Wrong Key';
    die;
}

$link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$allowedTasks = array(
    'cache:clear',
    'mautic:segments:update',
    'mautic:import',
    'mautic:campaigns:rebuild',
    'mautic:campaigns:trigger',
    'mautic:messages:send',
    'mautic:emails:send',
    'mautic:email:fetch',
    'mautic:social:monitoring',
    'mautic:webhooks:process',
    'mautic:broadcasts:send',
    'mautic:maintenance:cleanup',
    'mautic:iplookup:download',
    'mautic:reports:scheduler',
    'mautic:unusedip:delete',
    'mautic:iplookup:download',
);

if (!isset($_GET['task'])) {
    echo 'Specify what task to run. You can run these:';
    foreach ($allowedTasks as $task) {
        $href = $link . '&task=' . urlencode($task);
        echo '<br><a href="' . $href . '">' . $href . '</a>';
    }
    echo '<br><a href="https://www.mautic.org/docs/setup/index.html">Read more</a>';
    echo '<br><b style="color:red">Please, backup your database before executing the doctrine commands!</b>';
    die;
}

$task = urldecode($_GET['task']);

if (!in_array($task, $allowedTasks)) {
    echo 'Task ' . $task . ' is not allowed.';
    die;
}

$fullCommand = explode(' ', $task);
$command = $fullCommand[0];

$argsCount = count($fullCommand) - 1;
$args = array('console', $command);

if ($argsCount) {
    for ($i = 1; $i <= $argsCount; $i++) {
        $args[] = $fullCommand[$i];
    }
}

echo '<h3>Executing ' . implode(' ', $args) . '</h3>';

require_once __DIR__.'/app/autoload.php';
require_once __DIR__.'/app/AppKernel.php';
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

if (function_exists('set_time_limit')) {
    set_time_limit(0);
}
defined('IN_MAUTIC_CONSOLE') or define('IN_MAUTIC_CONSOLE', 1);

try {
    $input  = new ArgvInput($args);
    $output = new BufferedOutput();
    $kernel = new AppKernel('prod', false);
    $app    = new Application($kernel);
    $app->setAutoExit(false);
    $result = $app->run($input, $output);
    echo "<pre>\n".$output->fetch().'</pre>';
} catch (\Exception $exception) {
    echo $exception->getMessage();
}
