<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
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

    function admin() {
        $sub_process = $this->botUser->currentProcess->pivot->sub_process;
        $options['text'] = '';
        $send = false;
        $back = true;

        switch ($sub_process) {
            default: {
                $fullName = $this->botUser->profile->full_name;
                $options['text'] .= "$fullName عزیز 🙂

به پنل مدیریت خوش آمدید

با استفاده از منوی زیر از بخش مدیریت استفاده کنید 🔰";


                $keyboard = [
                    [
                        [
                            'text' => "ارسال آگهی 📃",
                            'callback_data' => json_encode([
                                'process_id' => BOT_PROCESS__ADMIN_SEND_AD
                            ], JSON_UNESCAPED_UNICODE)
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

    function sendAd() {
        $sub_process = $this->botUser->currentProcess->pivot->sub_process;
        $options['text'] = '';
        $send = false;
        $back = false;
        $cancelButton = true;

        /// make new ad record in database



        /// make new ad record in database</>

        switch ($sub_process) {
            default: {

                break;
            }
        }

        if ($cancelButton) {
            $options = $this->botService->appendInlineKeyboardButton($options, [[
                'text' => '❌ انصراف',
                'callback_data' => json_encode([
                    'sub_process' => ""
                ])
            ]]);
        }
        if ($send)
            $this->botService->send('editMessageText', $options, $back);
    }
}
