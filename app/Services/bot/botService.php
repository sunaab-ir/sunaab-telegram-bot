<?php


namespace App\Services\bot;


use App\Models\telProcess;
use App\Models\telUser;

class botService
{
    function handleProcess ($Process,telUser $User, $Params = null)
    {
        if (gettype($Process) == "integer") {
            $Process = telProcess::find($Process);
            if (!$Process)
                abort(404, 'the process not found!');
            $User->currentProcess()->sync([$Process->id]);
        }else {
            $Process = $Process->first();
            $User->currentProcess()->sync([$Process->id]);
        }
        $controller = "App\\Http\\Controllers\\bot\\processControllers\\" . $Process->process_controller;
        $action = $Process->process_action;
        $processController = new $controller();
        if ($Params)
            $processController->$action($Params);
        else
            $processController->$action();
    }
}
