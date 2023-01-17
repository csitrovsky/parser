<?php


namespace app\src\telegram;


use app\src\helpers\ArrayHelper;
use Closure;
use TelegramBot\Api\Events\Event;
use TelegramBot\Api\Types\Update;


class EventCollection
{
    
    /**
     * @var array
     */
    protected $events;
    
    /**
     * @param          $name
     * @param          $description
     * @param \Closure $event
     * @param          $checker
     *
     * @return $this
     */
    public function add($name, $description, Closure $event, $checker = null): self
    {
        
        $this->events[] = [
            'handler' => !is_null($checker)
                ? new Event($event, $checker)
                : new Event($event, static function () { }),
            'name' => $name,
            'description' => $description,
        ];
        
        return $this;
    }
    
    /**
     * @param \TelegramBot\Api\Types\Update $update
     *
     * @return void
     */
    public function handle(Update $update): void
    {
        
        foreach ($this->events as $event) {
            /* @var Event $event */
            if ($event['handler']->executeChecker($update) === true) {
                if (false === $event['handler']->executeAction($update)) {
                    break;
                }
            }
        }
    }
    
    /**
     * @return array
     */
    public function getEvents(): array
    {
        
        return ArrayHelper::map($this->events, 'name', 'description');
    }
}