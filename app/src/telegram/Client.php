<?php


namespace app\src\telegram;


use Closure;
use TelegramBot\Api\BotApi;


class Client extends \TelegramBot\Api\Client
{
    
    /**
     * @param $token
     */
    public function __construct($token)
    {
        
        $this->api = new BotApi($token);
        $this->events = new EventCollection();
    }
    
    /**
     * @param          $name
     * @param \Closure $action
     * @param string   $description
     *
     * @return $this
     */
    public function command($name, Closure $action, string $description = ''): Client
    {
        
        $this->events->add($name, $description, self::getEvent($action), self::getChecker($name));
        
        return $this;
    }
    
    /**
     * @return \TelegramBot\Api\BotApi
     */
    public function getApi(): BotApi
    {
        
        return $this->api;
    }
    
    /**
     * @return \app\src\telegram\EventCollection
     */
    public function getEvents(): EventCollection
    {
        
        return $this->events;
    }
}