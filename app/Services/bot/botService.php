<?php


namespace App\Services\bot;


use App\Models\telProcess;
use App\Models\telUser;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\User;

class botService
{
    protected $botUser;
    protected $botUpdate;

    public function __construct ()
    {
        $this->botUser = \request()->botUser;
        $this->botUpdate = \request()->botUpdate;
    }

    function handleProcess ($Process,telUser $User, $Params = null)
    {
        if (gettype($Process) != "object") {
            $Process = telProcess::find(((integer)$Process));
            if (!$Process)
                abort(404, 'the process not found!');
        }
        $User->Process()->sync([$Process->id]);
        $controller = "App\\Http\\Controllers\\bot\\processControllers\\" . $Process->process_controller;
        $action = $Process->process_action;
        $processController = new $controller();
        if ($Params)
            $processController->$action($Params);
        else
            $processController->$action();
    }
    function updateProcessData($params) {
        if (!$params)
            return;
        $process = request()->botUser->Process();
        $currentProcess = request()->botUser->currentProcess;

        $process->updateExistingPivot($currentProcess->id, $params);
    }

    function send($type, $options) {
        if (!$type)
            return;
        $_options = [];
        $_options['chat_id'] = $this->botUser->chat_id;
        $_options = array_merge($_options, $options);
        $response = Telegram::$type($_options);

        print_r($response);
    }
}
