<?php


namespace App\Services\bot;


use App\Models\City;
use App\Models\sentAd;
use App\Models\teAd;
use App\Models\telBotMessage;
use App\Models\telProcess;
use App\Models\telUser;
use App\Models\Village;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\User;
use function GuzzleHttp\Psr7\get_message_body_summary;

class adService
{
    protected $botUser;
    protected $botUpdate;
    protected $botService;

    public function __construct ()
    {
        $this->botUser = request()->botUser;
        $this->botUpdate = request()->botUpdate;
        $this->botService = new botService();
    }

    public function sendAd ($adId)
    {
        $ad = teAd::find($adId);
        if (!$ad)
            return;
        $adCreatorFullName = $ad->creator->profile->full_name;
        $profile_where = [
            ['county_id', '=', $ad->county_id],
            ['user_id', '<>', $this->botUser->user_id],
            ['is_manual_worker', '=', 1],
            ['work_category', '=', $ad->work_category]
        ];
        $setting_where = [
            ['receive_ad', '=', 1]
        ];
        if ($ad->city_id)
            $profile_where[] = ['city_id', '=', $ad->city_id];
        if ($ad->village_id) {
            $profile_where[] = ['village_id', '=', $ad->village_id];
        } else {
            $setting_where[] = ['receive_village', '<>', 1];
        }
        if ($ad->target_sex != 'all')
            $profile_where[] = ['sex', '=', $ad->target_sex];

        try {
            $receiverUsers = telUser::whereHas('profile', function ($query) use ($profile_where) {
                return $query->where($profile_where);
            })->whereHas('setting', function ($query) use ($setting_where) {
                return $query->where($setting_where);
            })->get();
            $adId = $ad->id;
            $options['caption'] = "📣 آگهی جدید\n";
            $options['caption'] .= "📄از طرف: $adCreatorFullName\n";
            $options['caption'] .= "📄 کد آگهی: $adId\n";
            $adTitle = $ad->title ?? 'بدون عنوان';
            $adBody = $ad->ad_text;
            $options['caption'] .= "\n📄 $adTitle\n\n";
            $options['caption'] .= "📃 متن آگهی:\n $adBody\n";
            $options['text'] = $options['caption'];
            $options['reply_markup'] = json_encode([
                'inline_keyboard' => [
                    [

                        [
                            'text' => '⭕️ رد میکنم',
                            'callback_data' => json_encode([
                                'src' => 'ad', // type ad
                                'a' => 'ur', //user reject
                                'aid' => $ad->id // ad id
                            ])
                        ],
                        [
                            'text' => '✅ قبول میکنم',
                            'callback_data' => json_encode([
                                'src' => 'ad', // type ad
                                'a' => 'ua', //user agree
                                'aid' => $ad->id // ad id
                            ])
                        ]
                    ]
                ]
            ]);
            foreach ($receiverUsers as $user) {
                $options['chat_id'] = $user->chat_id;
                if (strlen($options['caption']) <= 1010 && $ad->photo_file_id) {
                    echo "send\n";
                    $options['photo'] = $ad->photo_file_id;
                    if ($result = $this->sendAdToTelegram($options)) {
                        $sentRecord = new sentAd();
                        $sentRecord->ad_id = $ad->id;
                        $sentRecord->user_id = $user->user_id;
                        $sentRecord->chat_id = $user->chat_id;
                        $sentRecord->message_id = $result['response']->messageId;
                        $sentRecord->sent_time = $result['response']->date;
                        if ($result['type'] == 'sendPhoto')
                            $sentRecord->type = 'media';
                        $sentRecord->save();
                    } else {
                        Log::debug(
                            "ad: " . $ad->id . "\n for user: " . $user->user_id . " sent failed"
                        );
                    }
                } else {
                    if ($result = $this->sendAdToTelegram($options, false)) {
                        $sentRecord = new sentAd();
                        $sentRecord->ad_id = $ad->id;
                        $sentRecord->user_id = $user->user_id;
                        $sentRecord->chat_id = $user->chat_id;
                        $sentRecord->message_id = $result['response']->messageId;
                        $sentRecord->sent_time = $result['response']->date;
                        $sentRecord->save();
                    } else {
                        Log::debug(
                            "ad: " . $ad->id . "\n for user: " . $user->user_id . " sent failed"
                        );
                    }
                }
            }
            return true;

        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    function sendAdToTelegram($options = [], $media = true) {
        $type = 'sendPhoto';
        if (!$media) {
            $type = 'sendMessage';
            $options['text'] = $options['caption'];
        }
        try {
            resendAdToTelegram:
            $response = Telegram::$type($options);
        } catch (TelegramResponseException $exception) {
            Log::debug($exception->getMessage());
            if ($exception->getMessage() == BOT_ERROR__FORBIDDEN_BLOCKED_BY_USER)
                return;
            $type = 'sendMessage';
            $options['text'] = $options['caption'];
            goto resendAdToTelegram;
        }
        if (!isset($response)) {
            return false;
        }
        $this->botUser->last_bot_message_id = 0;
        $this->botUser->save();
        $result['response'] = $response;
        $result['type'] = $type;
        return $result;
    }

    function absoluteDelAd($adId) {
        $ad = teAd::find($adId);
        if (!$ad)
            return false;

        foreach ($ad->sents as $sent) {
            $options['chat_id'] = $sent->chat_id;
            if (time() - $sent->sent_time < 172800) {
                $options['message_id'] = $sent->message_id;
                $this->botService->sendBase('deleteMessage', $options);
            }
            $receiverFullName = $sent->user->profile->full_name;
            $adTitle = $ad->title ?? get_message_body_summary($ad->ad_text, 10);
            $senderName = $this->botUser->profile->full_name;
            $options['text'] = "کاربر محترم، $receiverFullName\n\nآگهی کاری '$adTitle' توسط ارسال کننده آن $senderName حذف شد و این به معنی لغو شدن کار است، لطفا از بین آگهی های دریافت شده یکی دیگر را انتخاب کنید\n\nموفق باشید 🌹";
            $this->botService->sendBase('sendMessage', $options);
            $sent->delete();
        }

        $ad->delete();

        return true;
    }
}
