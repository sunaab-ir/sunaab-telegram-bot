<?php


namespace App\Commands;

use \Telegram\Bot\Commands\Command;

class StartCommand extends Command
{

    protected $name = 'start';

    /**
     * @inheritDoc
     */
    public function handle ()
    {
        print_r($this);

        die;
    }
}
