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
            $this->botService->handleProcess($request->botUpdate->callbackQuery->data, $request->botUser);
        }else
            $this->botService->handleProcess($request->botUser->currentProcess, $request->botUser);
    }
}
