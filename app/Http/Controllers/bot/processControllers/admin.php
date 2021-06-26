<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\County;
use App\Models\teAd;
use App\Models\telBotMessage;
use App\Models\telUser;
use App\Services\bot\botService;
use Illuminate\Http\Request;

class admin extends Controller
{
    protected $botUser;
    protected $botUpdate;
    protected $botService;

    public function __construct ()
    {
        $this->botUser = \request()->botUser;
        $this->botUpdate = \request()->botUpdate;
        $this->botService = new botService();
    }

    function admin ($entry = null)
    {


        $sub_process = $this->botUser->currentProcess->pivot->sub_process;
        $options['text'] = '';
        $send = false;
        $back = true;

        if ($entry) {
            switch ($entry['entry']) {
                case BOT_PROCESS__ADD_AD:
                {
                    if (isset($entry['s']) && $entry['s'])
                    $options['text'] .= '✅ آگهی با موفقیت ذخیره شد و آماده ارسال است، برای ارسال آگهی میتوانید به قسمت "آگهی ها" مراجعه نمایید

';
                    else
                        goto adminPanelDefaultText;
                    $this->botService->removeChatHistory([
                        ['meta_data', 'like', '%"sub_process":"' . BOT_PROCESS__ADD_AD . '"%']
                    ]);
                    break;
                }
                case 'custom_message':
                {
                    $options['text'] .= $entry['message'];
                    break;
                }
            }
        } else {
            adminPanelDefaultText:
            $fullName = $this->botUser->profile->full_name;
            $options['text'] .= "$fullName عزیز 🙂

به پنل مدیریت خوش آمدید

";
        }
        switch ($sub_process) {
            default:
            {
                $options['text'] .= "با استفاده از منوی زیر از بخش مدیریت استفاده کنید 🔰";


                $keyboard = [
                    [
                        [
                            'text' => "ثبت آگهی 📃",
                            'callback_data' => json_encode([
                                'process_id' => BOT_PROCESS__ADD_AD
                            ], JSON_UNESCAPED_UNICODE)
                        ],
                        [
                            'text' => "📃 آگهی ها",
                            'callback_data' => json_encode([
                                'process_id' => BOT_PROCESS__ADS
                            ], JSON_UNESCAPED_UNICODE)
                        ]
                    ],
                    [
                        [
                            'text' => 'ارسال پیام به تمام اعضای ربات',
                            'callback_data' => json_encode([
                                'process_id' => BOT_PROCESS__ADMIN__SEND_MESSAGE_TO_ALL
                            ])
                        ]
                    ],
                    [
                        [
                            'text' => 'ارسال پیام یکی از اعضای ربات',
                            'callback_data' => json_encode([
                                'process_id' => BOT_PROCESS__ADMIN__SEND_MESSAGE_TO_SINGLE
                            ])
                        ]
                    ]
                ];

                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => $keyboard
                ], JSON_UNESCAPED_UNICODE);
                $send = true;

                break;
            }
        }


        if ($send)
            $this->botService->send('editMessageText', $options, $back);
    }

    function sendMessageToAll($entry = null) {
        $sub_process = $this->botUser->currentProcess->pivot->sub_process;
        $options = [];
        $send = false;
        $back = true;
        switch ($sub_process) {
            default: {
                $options['text'] = "لطفا پیام را ارسال کنید (فقط متنی)";
                $send = true;
                $this->botService->updateProcessData([
                    'sub_process' => 'message_input'
                ]);
                break;
            }
            case 'message_input': {
                if ($this->botUpdate->detectType() == 'message' && $this->botUpdate->getMessage()->detectType() == 'text') {
                    $users = telUser::where([
                        [
                            'is_bot', 0
                        ],
                        [
                            'user_id','<>', $this->botUser->user_id
                        ]
                    ])->get();
                    $options['text'] = "📩 پیغام از طرف ربات:\n\n" . $this->botUpdate->getMessage()->text;
                    $count = 0;
                    foreach ($users as $user) {
                        $options['chat_id'] = $user->chat_id;
                        if ($response = $this->botService->sendBase('sendMessage', $options)) {
                         $count++;
                        $messageLog = new telBotMessage();
                        $messageLog->chat_id = $user->chat_id;
                        $messageLog->message_id = $response->messageId;
                        $messageLog->message_type = "directMessageAll";
                        $messageLog->time = time();
                        $messageLog->meta_data = json_encode([
                            'message' => $options['text']
                        ]);
                        $messageLog->save();
                        }

                    }
                    $options['text'] = "پیام شما با موفقیت به $count کاربر ارسال شد";
                    $options['chat_id'] = $this->botUser->chat_id;
                    $this->botService->sendBase('sendMessage', $options);
                    $this->botService->handleProcess(BOT_PROCESS__NAME__ADMIN_PANEL);
                }
                break;
            }
        }
        if ($send)
            $this->botService->send('editMessageText', $options, $back);
    }
    function sendMessageToSingle($entry = null) {
        $sub_process = $this->botUser->currentProcess->pivot->sub_process;
        $options = [];
        $send = false;
        $back = true;
        switch ($sub_process) {
            default: {
                $options['text'] = "لطفا آی دی کاربر را ارسال کنید";
                $send = true;
                $this->botService->updateProcessData([
                    'sub_process' => 'getId_input'
                ]);
                break;
            }
            case 'getId_input': {
                if ($this->botUpdate->detectType() == 'message' && $this->botUpdate->getMessage()->detectType() == 'text') {
                    $this->botService->updateProcessData([
                        'tmp_data' => $this->botService->addJsonDataset(
                            $this->botUser->currentProcess->pivot->tmp_data,
                            'user_id',
                            $this->botUpdate->getMessage()->text
                        )
                    ]);
                    $this->botService->handleProcess(null, null, [
                        'sub_process' => 'message'
                    ]);
                }
                break;
            }
            case 'message': {
                $options['text'] = "لطفا پیام را ارسال کنید (فقط متنی)";
                $send = true;
                $this->botService->updateProcessData([
                    'sub_process' => 'message_input'
                ]);
                break;
            }
            case 'message_input': {
                if ($this->botUpdate->detectType() == 'message' && $this->botUpdate->getMessage()->detectType() == 'text') {
                    $tmpData = json_decode($this->botUser->currentProcess->pivot->tmp_data, true);
                    $options['text'] = "📩 پیغام از طرف ربات:\n\n" . $this->botUpdate->getMessage()->text;
                    $options['chat_id'] = $tmpData['user_id'];
                    if($response = $this->botService->sendBase('sendMessage', $options)) {
                        $messageLog = new telBotMessage();
                        $messageLog->chat_id = $tmpData['user_id'];
                        $messageLog->message_id = $response->messageId;
                        $messageLog->message_type = "directMessage";
                        $messageLog->time = time();
                        $messageLog->meta_data = json_encode([
                            'message' => $options['text']
                        ]);
                        $messageLog->save();
                    }
                    $options['text'] = "پیام شما با موفقیت به کاربر ارسال شد";
                    $options['chat_id'] = $this->botUser->chat_id;
                    $this->botService->sendBase('sendMessage', $options);
                    $this->botService->handleProcess(BOT_PROCESS__NAME__ADMIN_PANEL);
                }
                break;
            }
        }
        if ($send)
            $this->botService->send('editMessageText', $options, $back);
    }
}
