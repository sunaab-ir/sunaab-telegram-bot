<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
use App\Models\otherData;
use App\Services\bot\botService;
use Illuminate\Http\Request;

class grapeSession extends Controller
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

    function grapeHarvestSession($entry = null) {
        $sub_process = $this->botUser->currentProcess->pivot->sub_process;
        $options['text'] = '';
        $send = false;
        $back = true;
        $dontDeleteMessage = false;
        $hold = false;
        $backButton = true;

        if ($entry && isset($entry['entry'])) {
            switch ($entry['entry']) {
                case 'custom_message':
                {
                    $options['text'] .= $entry['message'];
                    break;
                }
                default:
                {
                    $options['text'] .= constant('BOT_MESSAGE__ENTRY__' . strtoupper($entry['entry']));
                    break;
                }
            }
        }

        switch ($sub_process) {
            default: {
                $userFullName = $this->botUser->profile->full_name;
                $options['text'] = "کاربر عزیز، $userFullName\n\nبا توجه به فرا رسیدن فصل برداشت انگور 🍇، قسمت هایی را برای شما آماده کرده ایم 😃\n\nلطفا از منوی زیر استفاده کنید🔰";
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🚚 رانندگان بار 🚛',
                                'callback_data' => json_encode([
                                    'sub_process' => 'load_drivers'
                                ])
                            ]
                        ]
                    ]
                ]);
                $backButton = false;
                $send = true;
                break;
            }
            case 'load_drivers': {
                $options['text'] = "کاربر عزیز، در لیست زیر میتوانید لیست نام رانندگان، برای جابجایی محصول خود به مقصد مورد نظر را مشاهده کنید 🔰\n\n✳️ لطفا برای مشاهده نوع خودرو و شماره موبایل اسم شخص مورد نظر را انتخاب کنید";
                $drivers = otherData::where(
                    'data',
                    'like',
                    '%"data_type":"load_driver"%'
                )->get();
                $keyboard = [];
                foreach ($drivers as $driver) {
                    $keyboard[] = [
                        'text' => "🙍🏻‍♂️ " . $driver->name,
                        'callback_data' => json_encode([
                            'sub_process' => 'driver',
                            'did' => $driver->id
                        ])
                    ];
                }
                $keyboard = array_chunk($keyboard, 2);
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => $keyboard
                ]);
                $send = true;
                break;
            }
            case 'driver': {
                $callbackData = json_decode($this->botUpdate->callbackQuery->data, true);
                $driver = json_decode((otherData::find($callbackData['did']))->data, true);
                $driverName = $driver['name'];
                $driverCarType = $driver['type'];
                $driverMobile = $driver['mobile'];
                if (isset($driver['other']))
                    $driverOther = $driver['other'];
                $options['text'] .= "ـ➖➖➖➖🚛➖➖➖➖" . "\n";
                if (isset($driverOther))
                    $options['text'] .= "ـ⚠️ $driverOther\n\n";
                $options['text'] .= "ـ🙍🏻‍♂️ $driverName\n\n";
                $options['text'] .= "🚛 نوع خودرو: $driverCarType\n\n";
                $options['text'] .= "ـ📲 $driverMobile\n";
                $options['text'] .= "ـ➖➖➖➖🚚➖➖➖➖" . "\n\n";
                $options['text'] .= "برای بازگشت به لیست رانندگان دکمه بازگشت را بزنید";
                $backButton = false;
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🔶 بازگشت به لیست',
                                'callback_data' => json_encode([
                                    'sub_process' => 'load_drivers'
                                ])
                            ]
                        ]
                    ]
                ]);
                $send = true;
                break;
            }
        }
        if ($backButton) {
            $options = $this->botService->appendInlineKeyboardButton($options, [[
                'text' => '🔶 بازگشت به منو',
                'callback_data' => json_encode([
                    'sub_process' => ''
                ])
            ]]);
        }
        if ($send)
            $this->botService->send('editMessageText', $options, $back, $dontDeleteMessage, $hold);
    }
}
