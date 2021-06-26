<?php


namespace App\Services\bot;


use App\Models\telBotMessage;
use App\Models\telProcess;
use App\Models\telUser;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\User;

class botService
{
    protected $botUser;
    protected $botUpdate;

    public function __construct ($user = null, $update = null)
    {
        $this->botUser = $user ?? request()->botUser;
        $this->botUpdate = $update ?? request()->botUpdate;
    }

    function handleProcess ($Process = null, $Params = null, $processData = [])
    {
        $User = request()->botUser;
        $Update = request()->botUpdate;
        if (!$Process)
            $Process = $User->currentProcess;
        if (gettype($Process) != "object") {
            $Process = telProcess::where('id', ((integer)$Process))->orWhere('process_name', $Process)->first();
            if (!$Process)
                abort(404, 'the process not found!');
        }
        if (!$User->commandProcess)
            $User->Process()->attach(BOT_PROCESS__COMMAND, [
                'process_type' => 'command'
            ]);
        $User->Process()->sync([$Process->id, $User->commandProcess->id => [
            'process_type' => 'command'
        ]]);
        if ($Update->detectType() == "callback_query" && !isset($processData['sub_process'])) {
            $data = json_decode($Update->callbackQuery->data, true);
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

    function handleCommandProcess ($Process, $entry = null, $processData = [])
    {
        $User = request()->botUser;
        $Update = request()->botUpdate;
        if (!$Process)
            $Process = $User->commandProcess;
        if (gettype($Process) != "object") {
            $Process = telProcess::where('id', ((integer)$Process))->orWhere('process_name', $Process)->first();
            if (!$Process)
                abort(404, 'the process not found!');
        }
        if (!$User->commandProcess)
            $User->Process()->attach(BOT_PROCESS__COMMAND, [
                'process_type' => 'command'
            ]);
        $User->Process()->sync([$Process->id => [
            'process_type' => 'command'
        ], $User->currentProcess->id]);
        if ($Update->detectType() == "callback_query") {
            $data = json_decode($Update->callbackQuery->data, true);
            if (isset($data['sub_process']))
                $processData['sub_process'] = $data['sub_process'];
        }
        $this->updateProcessData($processData, true);

        $controller = "App\\Http\\Controllers\\bot\\processControllers\\" . $Process->process_controller;
        $action = $Process->process_action;
        $processController = new $controller();
        if ($entry)
            $processController->$action($entry);
        else
            $processController->$action();
    }

    function updateProcessData ($params, $isCommand = false)
    {
        if (gettype($params) != "array")
            return;
        $process = request()->botUser->Process();
        if (!$isCommand) {
            $TargetProcess = request()->botUser->currentProcess;
        } else {
            $params['process_type'] = 'command';
            $TargetProcess = request()->botUser->commandProcess;
        }
        if (count($params)) {
            $process->updateExistingPivot($TargetProcess->id, $params);
        }
    }

    function send ($type, $options = [], $showBack = true, $deleteMessages = null, $hold = null)
    {
        if (!$type)
            return;
        $currentProcess = $this->botUser->currentProcess;
        if ($currentProcess->parent && $showBack) {
            if ($currentProcess->parent != BOT_PROCESS__NAME__MAIN)
                $options = $this->appendInlineKeyboardButton($options, [[
                    'text' => "🔙 بازگشت",
                    'callback_data' => json_encode([
                        'process_id' => $currentProcess->parent
                    ])
                ]]);
            $options = $this->appendInlineKeyboardButton($options, [
                [
                    'text' => "🚩 منوی اصلی",
                    'callback_data' => json_encode([
                        'process_id' => BOT_PROCESS__MAIN
                    ])
                ]]);
        }
//        if ($this->botUpdate->getMessage()->chat->type == "private")
        $options['chat_id'] = $this->botUser->chat_id;
//        else {
//            echo "from channel";
//            $type = 'sendMessage';
//            $options['chat_id'] = $this->botUpdate->getMessage()->chat->id;
//        }
        try {
            resendToTelegram:
            if (in_array($type, ["editMessageText", "editMessageReplyMarkup"])) {
                $options['message_id'] = $this->botUser->last_bot_message_id ?? 0;
                if ($options['message_id'] < $this->botUser->last_user_message_id) {
                    unset($options['message_id']);
                    $type = 'sendMessage';
                }
            }
            $response = Telegram::$type($options);
        } catch (ConnectException $e) {
            Log::emergency('VPN Dont Work!');
            goto resendToTelegram;
        } catch (TelegramResponseException $exception) {
            Log::error($exception->getCode() . " -------------- " . $exception->getMessage());
            echo $exception->getCode() . " ------\n-------- " . $exception->getMessage() . "\n\n";
            if ($exception->getCode() === 400) {
                unset($options['message_id']);
                $type = 'sendMessage';
                goto resendToTelegram;
            }
            if (in_array($exception->getCode(), [29, 28])) {
                goto resendToTelegram;
            }
            return false;
        }
        if (!isset($response)) {
            return false;
        }
        if (in_array($type, ["sendMessage"]) && isset($response['message_id']) || (isset($response['message_id']) && $hold)) {
            if ($this->botUser->last_bot_message_id && !$deleteMessages && (time() - ($this->botUser->last_bot_message_date ?? time() - 1000)) < 172800) {
                $this->sendBase('deleteMessage', [
                    'chat_id' => $this->botUser->chat_id,
                    'message_id' => $this->botUser->last_bot_message_id
                ]);
                $this->botUser->last_bot_message_id = null;
                $this->botUser->save();
            } else if ($this->botUser->last_bot_message_id && $deleteMessages) {
                if (gettype($deleteMessages) != 'array')
                    $deleteMessages = [];
                if (!isset($deleteMessages['message_type']))
                    $deleteMessages['message_type'] = 'bot_message_dont_delete';
                $deleteMessages['message_id'] = $this->botUser->last_bot_message_id;
                $deleteMessages['chat_id'] = $this->botUser->chat_id;
                $deleteMessages['time'] = time();
                telBotMessage::create($deleteMessages);
            }
            if ($this->botUser->last_user_message_id && !$deleteMessages && (time() - ($this->botUser->last_user_message_date ?? time() - 1000)) < 172800) {
                $this->sendBase('deleteMessage', [
                    'chat_id' => $this->botUser->chat_id,
                    'message_id' => $this->botUser->last_user_message_id
                ]);
                $this->botUser->last_user_message_id = null;
                $this->botUser->save();
            } else if ($this->botUser->last_user_message_id && $deleteMessages) {
                if (gettype($deleteMessages) != 'array')
                    $deleteMessages = [];
                if (!isset($deleteMessages['message_type']))
                    $deleteMessages['message_type'] = 'user_message_dont_delete';
                $deleteMessages['message_id'] = $this->botUser->last_user_message_id;
                $deleteMessages['chat_id'] = $this->botUser->chat_id;
                $deleteMessages['time'] = time();
                telBotMessage::create($deleteMessages);
            }
            if ($hold) {
                if (gettype($hold) != 'array')
                    $hold = [];
                if (!isset($hold['message_type']))
                    $hold['message_type'] = 'hold';
                $hold['message_id'] = $response->messageId;
                $hold['chat_id'] = $this->botUser->chat_id;
                $hold['time'] = time();
                telBotMessage::create($hold);
                $this->botUser->last_bot_message_id = null;
                $this->botUser->save();
            } else {
                $this->botUser->last_bot_message_id = $response->messageId;
                $this->botUser->last_bot_message_date = time();
                $this->botUser->save();
            }
            return $response;
        }
        return $response;
    }

    function sendBase ($type, $options = [], $async = false, $reset = false)
    {
        if (!$type)
            return;
        print_r($options);
        $response = null;
        try {
            resendSendBaseToTelegram:
            if ($async)
                $response = Telegram::setAsyncRequest(true)->$type($options);
            else
                $response = Telegram::$type($options);
        } catch (TelegramResponseException $exception) {
            Log::error($exception->getMessage());
            if ($exception->getMessage() == BOT_ERROR__FORBIDDEN_BLOCKED_BY_USER)
                return;
            if ($type == 'sendPhoto') {
                $type = 'sendMessage';
                goto resendSendBaseToTelegram;
            }else {
                return false;
            }
        }
        if ($reset) {
            $this->botUser->last_bot_message_id = 0;
            $this->botUser->save();
        }

        return $response;
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
        $inlineKeyboard[] = $button;
        $replyMarkup['inline_keyboard'] = $inlineKeyboard;
        $options['reply_markup'] = json_encode($replyMarkup);
        return $options;
    }

    function addJsonDataset ($dataset, $key, $value)
    {
        $ADataset = json_decode($dataset, true);
        $ADataset[$key] = $value;
        return json_encode($ADataset, JSON_UNESCAPED_UNICODE);
    }

    function removeJsonDataset ($dataset, $key)
    {
        $ADataset = json_decode($dataset, true);
        unset($ADataset[$key]);
        return json_encode($ADataset, JSON_UNESCAPED_UNICODE);
    }

    function removeChatHistory ($where = [])
    {
        $where['chat_id'] = $this->botUser->chat_id;
        $messages = telBotMessage::where($where)->get();

        foreach ($messages as $message) {
            if ((time() - $message->time) < 172800) {
                try {
                    if ($this->sendBase('deleteMessage', [
                        'chat_id' => $message->chat_id,
                        'message_id' => $message->message_id
                    ])) {
                        $message->delete();
                    }
                } catch (\Exception $exception) {
                    echo $exception->getMessage() . "\n";
                }
            }
        }
    }
}
