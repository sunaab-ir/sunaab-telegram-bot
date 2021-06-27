<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
use App\Services\bot\botService;
use Illuminate\Http\Request;

class farmer extends Controller
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


    function farmerPanel($entry = null) {
        $sub_process = $this->botUser->currentProcess->pivot->sub_process;
        $options['text'] = '';
        $send = false;
        $back = false;
        $dontDeleteMessage = false;
        $hold = false;
        $cancelButton = true;

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
                $farmerFullName = $this->botUser->profile->full_name;
                $options['text'] .= "کشاورز محترم، $farmerFullName ، به پنل کشاورز خوش آمدید\n\nلطفا از منوی زیر استفاده کنید";

                $options['reply_markup'] = json_encode([
                   'inline_keyboard' => [
                       [
                           [
                               'text' => 'ثبت آگهی کار',
                               'callback_data' => json_encode([
                                   'process_id' => BOT_PROCESS__ADD_AD,
                                   'ent' => 'farmer'
                               ])
                           ],
                           [
                               'text' => 'آگهی های من',
                               'callback_data' => json_encode([
                                   'process_id' => BOT_PROCESS__FARMER_ADS
                               ])
                           ]
                       ]
                   ]
                ]);


                $cancelButton = false;
                $send = true;
                $back = true;
                break;
            }
        }


        if ($cancelButton) {
            if (isset($tmpData['user_type']) && $tmpData['user_type'] == 'farmer') {
                $callback_data = [
                    'process_id' => BOT_PROCESS__MAIN,
                ];
            } else
                $callback_data = [
                    'process_id' => BOT_PROCESS__NAME__ADMIN_PANEL,
                    'ent' => BOT_PROCESS__ADD_AD,
                    's' => false
                ];
            print_r($callback_data);
            $options = $this->botService->appendInlineKeyboardButton($options, [[
                'text' => '❌ انصراف',
                'callback_data' => json_encode($callback_data)
            ]]);
        }
        if ($send)
            $this->botService->send('editMessageText', $options, $back, $dontDeleteMessage, $hold);
    }
}
