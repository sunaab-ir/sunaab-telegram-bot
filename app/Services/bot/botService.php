<?php


namespace App\Services\bot;


use App\Models\telProcess;
use App\Models\telUser;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\User;

class botService
{
    protected $botUser;
    protected $botUpdate;

    public function __construct ()
    {
        $this->botUser = request()->botUser;
        $this->botUpdate = request()->botUpdate;
    }

    function handleProcess ($Process, $Params = null, $processData = [])
    {
        $User = request()->botUser;
        $Update = request()->botUpdate;
        if (!$Process)
            $Process = $User->currentProcess;
        if (gettype($Process) != "object") {
            $Process = telProcess::find(((integer)$Process));
            if (!$Process)
                abort(404, 'the process not found!');
        }
        $User->Process()->sync([$Process->id]);
        if ($Update->detectType() == "callback_query") {
            $data = json_decode($Update->callbackQuery->data, true);
            print_r($data);
            if (isset($data['sub_process']))
                $processData['sub_process'] = $data['sub_process'];
        }
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
        if (gettype($params) != "array")
            return;
        $process = request()->botUser->Process();
        $currentProcess = request()->botUser->currentProcess;
        if (count($params))
            $process->updateExistingPivot($currentProcess->id, $params);
    }

    function send ($type, $options = [], $showBack = true, $showMain = true)
    {
        print_r($options);
        if (!$type)
            return;
        $currentProcess = $this->botUser->currentProcess;
        if ($currentProcess->parent && $showBack) {
            if ($currentProcess->parent != BOT_PROCESS__MAIN)
                $options = $this->appendInlineKeyboardButton($options, [
                    'text' => "بازگشت",
                    'callback_data' => json_encode([
                        'process_id' => $currentProcess->parent
                    ])
                ]);
            $options = $this->appendInlineKeyboardButton($options, [
                'text' => "منوی اصلی",
                'callback_data' => json_encode([
                    'process_id' => BOT_PROCESS__MAIN
                ])
            ]);
        }
        $options['chat_id'] = $this->botUser->chat_id;
        try {
            resend:
            if (in_array($type, ["editMessageText", "editMessageReplyMarkup"])) {
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
            Log::error($exception->getMessage());
            if (in_array($exception->getCode(), [29, 28]))
                goto resend;
        }
        if ($type != "editMessageText") {
            $this->botUser->last_bot_message_id = $response->messageId;
            $this->botUser->save();
        }
    }

    function appendInlineKeyboardButton ($options, $button)
    {
        $replyMarkup = [];
        $inlineKeyboard = [];
        if (isset($options['reply_markup'])) {
            $replyMarkup = json_decode($options['reply_markup'], true);
            if (isset($replyMarkup['inline_keyboard'])) {
                $inlineKeyboard = $replyMarkup['inline_keyboard'];
            }
        }
        $inlineKeyboard[][] = $button;
        $replyMarkup['inline_keyboard'] = $inlineKeyboard;
        $options['reply_markup'] = json_encode($replyMarkup);
        return $options;
    }
}
