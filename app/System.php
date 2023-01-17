<?php


namespace app;


use function spl_autoload_register;


class System extends Autoload
{
    
    /**
     * @param bool $prepend
     *
     * @return void
     */
    public function register(bool $prepend): void
    {
        
        spl_autoload_register(function ($namespace) {
            
            $this->includes($namespace);
        }, true, $prepend);
    }
}