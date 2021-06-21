<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
use App\Services\bot\botService;
use Illuminate\Http\Request;

class command extends Controller
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

    function command($entry = null) {
        switch ($this->botUser->commandProcess->pivot->sub_process) {
            case BOT__COMMAND__SUB_PROCESS_PROFILE_VILLAGE_SEND_NAME: {
                if ($this->botUser->currentProcess->pivot->tel_process_id == BOT_PROCESS__PROFILE && $this->botUser->current_process->pivot->sub_process == 'get_village_select'){

                }else {
                    $this->botService->send('sendMessage', [
                        'text' => '⛔️ دستور غیر مجاز است'
                    ]);
                }
                break;
            }
            case BOT__COMMAND__SUB_PROCESS_PROFILE_VILLAGE_SEND_NAME . "_input": {

                break;
            }
        }
    }

    function start() {
        $this->botService->handleProcess(BOT_PROCESS__MAIN, null, [
            'sub_process' => ''
        ]);
    }
}
