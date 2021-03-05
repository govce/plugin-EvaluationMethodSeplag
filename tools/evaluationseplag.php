<?php
set_time_limit(0);
ini_set('memory_limit', '8192M');
unset($_ENV['LOG_HOOK']);
require __DIR__ . './../../../bootstrap.php';

$app = MapasCulturais\App::i();

$config = $app->plugins['EvaluationMethodSeplag']->config;

if($config['log']){
    $app->log->debug('EVALUATION SEPLAG');
}

$app->plugins['EvaluationMethodSeplag']->cron();
$app->em->getConnection()->close();