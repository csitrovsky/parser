<?php


namespace app\src\telegram;


use GuzzleHttp\Client;
use TelegramBot\Api\BotApi;


class Bot
{
    
    /**
     * @param       $message
     * @param array $options
     *
     * @return void
     */
    public static function message($message, array $options = []): void
    {
        
        $client = new Client();
        $client->post(
            'https://api.telegram.org/bot' . $_ENV['TELEGRAM_TOKEN'] . '/sendMessage',
            [
                'form_params' => array_merge(
                    [
                        'chat_id' => $_ENV['TELEGRAM_CHAT_ID'],
                        'parse_mode' => 'Markdown',
                    ],
                    $options,
                    ['text' => $message]
                ),
            ]
        );
    }
    
    /**
     * @param       $photo
     * @param array $options
     *
     * @return void
     * @throws \Exception
     */
    public static function photo($photo, array $options = []): void
    {
        
        sleep(random_int(3, 5));
        
        $options = array_merge([
            'chat_id' => $_ENV['TELEGRAM_CHAT_ID'],
        ], $options, [
            'photo' => $photo,
        ]);
        
        $options = array_map(static function ($key, $value) {
            
            return [
                'name' => $key,
                'contents' => $value,
            ];
        }, array_keys($options), array_values($options));
        
        $client = new Client();
        $client->post('https://api.telegram.org/bot' . $_ENV['TELEGRAM_TOKEN'] . '/sendPhoto', [
            'multipart' => $options,
        ]);
    }
    
    /**
     * @param             $document
     * @param string|null $filename
     * @param array       $options
     *
     * @return void
     * @throws \Exception
     */
    public static function document($document, string $filename = null, array $options = []): void
    {
        
        sleep(random_int(3, 5));
        
        $options = array_merge([
            'chat_id' => $_ENV['TELEGRAM_CHAT_ID'],
        ], $options);
        
        $options = array_map(static function ($key, $value) {
            
            return [
                'name' => $key,
                'contents' => $value,
            ];
        }, array_keys($options), array_values($options));
        $options[] = [
            'name' => 'document',
            'contents' => is_null($filename) ? fopen($document, 'rb') : $document,
            'filename' => is_null($filename) ? basename($document) : $filename,
        ];
        
        $client = new Client();
        $client->post('https://api.telegram.org/bot' . $_ENV['TELEGRAM_TOKEN'] . '/sendDocument', [
            'multipart' => $options,
        ]);
    }
    
    /**
     * @return \TelegramBot\Api\BotApi
     */
    public static function make(): BotApi
    {
        
        return new BotApi($_ENV('TELEGRAM_TOKEN'));
    }
}