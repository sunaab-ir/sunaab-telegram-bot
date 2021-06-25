<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
use App\Models\otherData;
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
            case BOT__COMMAND__SUB_PROCESS__118: {

                break;
            }
        }
    }

    function start() {
        if ($this->botUser->process_type != 'normal') {
            $this->botUser->process_type = 'normal';
            $this->botUser->save();
        }
        if ($this->botUpdate->message->entities) {
            $otherData = new otherData();
            $otherData->name = "entry from source";
            $entry = $this->botUpdate->getMessage()->entities[0];
            $entryName = trim(substr($this->botUpdate->getMessage()->text, $entry['offset'] + 1, $entry['length']));
            $entryValue = trim(substr($this->botUpdate->getMessage()->text, $entry['length'], strlen($this->botUpdate->getMessage()->text)));
            switch ($entryValue) {
                case 12: {
                    $otherData->data = json_encode([
                        'entry_source' => "group 118 jabuz"
                    ]);
                }
            }
            $otherData->save();
        }
        $this->botService->handleProcess(BOT_PROCESS__MAIN, null, [
            'sub_process' => ''
        ]);
    }
}
