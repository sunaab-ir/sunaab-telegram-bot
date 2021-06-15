<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
use App\Models\telUserSetting;
use App\Services\bot\botService;
use Illuminate\Http\Request;

class worker extends Controller
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

    function worker ($params = null)
    {
        $sub_process = $this->botUser->currentProcess->pivot->sub_process;
        $options['text'] = '';
        $send = false;
        $back = true;
//        die;
        switch ($sub_process) {
            default: {
                if (!$this->botUser->setting){
                    $this->botUser->setting()->save((new telUserSetting()));
                }
                $options = $this->getMainMenu($options);
                $send = true;
                break;
            }
            case 'toggle_receive_ad': {
                if ($this->botUpdate->detectType() == 'callback_query') {
                    $callback_data = json_decode($this->botUpdate->callbackQuery->data);
                    $this->botUser->setting->receive_ad = !$callback_data->v;
                    $this->botUser->setting->save();
                    $this->botService->handleProcess(null, null, [
                        'sub_process' => ''
                    ]);
                }
                break;
            }
            case 'toggle_receive_village_ad': {
                if ($this->botUpdate->detectType() == 'callback_query') {
                    $callback_data = json_decode($this->botUpdate->callbackQuery->data);
                    $this->botUser->setting->receive_village = !$callback_data->v;
                    $this->botUser->setting->save();
                    $this->botService->handleProcess(null, null, [
                        'sub_process' => ''
                    ]);
                }
                break;
            }
        }

        if ($send)
            $this->botService->send('editMessageText', $options, $back);
    }

    function getMainMenu($options) {
        $fullName = $this->botUser->profile->full_name;
        $options['text'] .= "$fullName عزیز 🙂

با توجه به تنظیماتی که در زیر مشاهده می کنید، آگهی کار برای شما ارسال میگردد.

برای تغییر هر یک از این تنظیمات، میتوانید با کلیک روی مقدار تنظیم شده، وضعیت تنظیم را تغییر دهید ⚙️

مثال:
برای تغییر دریافت آگهی ، روی مقدار فعال یا غیر فعال بودن ضربه بزنید ✅";

        $receiveState = $this->botUser->setting->receive_ad;
        $receiveVillageState = $this->botUser->setting->receive_village;
        $keyboard = [
            [
                [
                    'text' => ($receiveState ? 'فعال ✅' : 'غیر فعال ❌'),
                    'callback_data' => json_encode([
                        'sub_process' => 'toggle_receive_ad',
                        'v' => $receiveState
                    ])
                ],
                [
                    'text' => 'دریافت آگهی :',
                    'callback_data' => 'null'
                ]
            ]
        ];
        if ($this->botUser->profile->village_id)
            $keyboard[] = [
                [
                    'text' => ($receiveVillageState ? 'فعال ✅' : 'غیر فعال ❌'),
                    'callback_data' => json_encode([
                        'sub_process' => 'toggle_receive_village_ad',
                        'v' => $receiveVillageState
                    ])
                ],
                [
                    'text' => 'دریافت آگهی فقط از روستای خودم :',
                    'callback_data' => 'null'
                ]
            ];
        $options['reply_markup'] = json_encode([
           'inline_keyboard' => $keyboard
        ]);

        return $options;
    }
}
