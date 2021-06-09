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

    function handleProcess ($Process, $Params = null, $processData = null)
    {
        $User = request()->botUser;
        if (!$Process)
            $Process = $User->currentProcess;
        if (gettype($Process) != "object") {
            $Process = telProcess::find(((integer)$Process));
            if (!$Process)
                abort(404, 'the process not found!');
        }
        $User->Process()->sync([$Process->id]);
        $this->updateProcessData($processData);
        $controller = "App\\Http\\Controllers\\bot\\processControllers\\" . $Process->process_controller;
        $action = $Process->process_action;
        $processController = new $controller();
        if ($Params)
            $processController->$action($Params);
        else
            $processController->$action();
    }

    function updateProcessData ($params)
    {
        if (!$params)
            return;
        $process = request()->botUser->Process();
        $currentProcess = request()->botUser->currentProcess;

        $process->updateExistingPivot($currentProcess->id, $params);
    }

    function send ($type, $options = [])
    {
        if (!$type)
            return;
        $currentProcess = $this->botUser->currentProcess;
        if ($currentProcess->parent) {
            $replyMarkup = [];
            $inlineKeyboard = [];
            if (isset($options['reply_markup'])) {
                $replyMarkup = json_decode($options['reply_markup'], true);
                if (isset($replyMarkup['inline_keyboard'])) {
                    $inlineKeyboard = $replyMarkup['inline_keyboard'];
                }
            }
            $inlineKeyboard[][] = [
                'text' => "بازگشت",
                'callback_data' => json_encode([
                    'process_id' => $currentProcess->parent
                ])
            ];
            $replyMarkup['inline_keyboard'] = $inlineKeyboard;
            $options['reply_markup'] = json_encode($replyMarkup);
        }
        $options['chat_id'] = $this->botUser->chat_id;
        try {
            if ($type == "editMessageText") {
                $options['message_id'] = $this->botUser->last_bot_message_id;
                if ($this->botUser->last_bot_message_id < $this->botUser->last_user_message_id) {
                    unset($options['message_id']);
                    $type = 'sendMessage';
                }
            }
            $response = Telegram::$type($options);
        } catch (\Exception $exception) {
            if ($exception->getCode() == 400) {
                $type = 'sendMessage';
                unset($options['message_id']);
                $response = Telegram::$type($options);
            }
        }
        if ($type != "editMessageText") {
            $this->botUser->last_bot_message_id = $response->messageId;
            $this->botUser->save();
        }
    }
}
