<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\County;
use App\Models\teAd;
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


}
