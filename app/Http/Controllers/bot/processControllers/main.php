<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
use App\Services\bot\botService;
use Illuminate\Http\Request;

class main extends Controller
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

    function main() {

    }
}
