<?php


namespace App\Services\bot;


use App\Models\teAd;
use App\Models\telBotMessage;
use App\Models\telProcess;
use App\Models\telUser;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\User;

class adService
{
    protected $botUser;
    protected $botUpdate;

    public function __construct ()
    {
        $this->botUser = request()->botUser;
        $this->botUpdate = request()->botUpdate;
    }

    public function sendAd($adId) {
        $ad = teAd::find($adId);
        if (!$ad)
            return;

        $where = [
          ['county_id','=', $ad->county_id]
        ];
        if ($ad->city_id)
            $where[] = ['city_id', '=', $ad->city_id];
        if ($ad->village_id)
            $where[] = ['village_id', '=', $ad->village_id];
        if ($ad->target_sex != 'all')
            $where[] = ['sex', '=', $ad->target_sex];

        try {
            $receiverUsers = telUser::whereHas('profile', function ($query) use ($where) {
                return $query->where($where);
            })->get();

        }catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
