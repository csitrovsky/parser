<?php


use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;


include_once __DIR__ . '/init.php';
$token = getenv('TELEGRAM_TOKEN') ?: $_ENV['TELEGRAM_TOKEN'];

if (!isset($_REQUEST['token']) || ($_REQUEST['token'] !== $token)) {
    die('Access denied');
}

try {
    $telegram = new Api($token);
    $telegram->addCommand(new Telegram\Bot\Commands\HelpCommand());
    
    $update = $telegram->getWebhookUpdate();
    if (isset($update['callback_query'])) {
        $telegram->triggerCommand(
            $update['callback_query']['data'],
            $update,
            [
                'offset' => 0,
                'length' => strlen($update['callback_query']['data']) + 1,
                'type' => 'callback_query',
            ]
        );
    } else {
        $telegram->processCommand($update);
    }
} catch (TelegramSDKException $e) {
    die("error: " . $e->getMessage());
}