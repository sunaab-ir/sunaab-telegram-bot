<?php

namespace App\Http\Controllers\bot;

use App\Http\Controllers\Controller;
use App\Models\telUser;
use App\Services\bot\botService;
use Illuminate\Http\Request;

class updateController extends Controller
{
    protected $botService;

    public function __construct ()
    {
        $this->botService = new botService();
    }

    public function update(Request $request) {
        if ($request->botUpdate->detectType() == 'callback_query') {
            $callbackData = json_decode($request->botUpdate->callbackQuery->data, true);
            if (isset($callbackData['process_id']))
                $this->botService->handleProcess($callbackData['process_id']);
            else
               goto handleCurrentProcess;
        }else {
            handleCurrentProcess:
            $this->botService->handleProcess($request->botUser->currentProcess);
        }
    }
}
