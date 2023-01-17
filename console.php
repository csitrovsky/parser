#!/usr/bin/env php
<?php

include_once __DIR__ . '/init.php';

$console = (new \Symfony\Component\Console\Application('...', '1.0.0'));
$commands = include INC_ROOT . '/app/config/commands.php';

foreach ($commands as $commandName) {
    $console->add(new $commandName());
}

try {
    $console->run();
} catch (Exception $e) {
    die((new \app\src\Error())->output($e->getMessage(), 500));
}