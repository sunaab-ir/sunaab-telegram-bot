<?php

namespace App\Http\Middleware;

use App\Models\telUser;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

class telBotUpdateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle (Request $request, Closure $next)
    {
        $user = $this->getTelUser($request);
        $request->botUser = $user;
        $request->botUpdate = Telegram::getWebhookUpdates();
        return $next($request);
    }

    function getTelUser (Request $request)
    {
        $user_id = $request['message']['from']['id'];
        $last_user_message_id =  $request['message']['message_id'];
        $user = telUser::with('currentProcess')->find($user_id);
        $maxRetrySave = 3;
        if (!$user) {
            $chat_id = $request['message']['chat']['id'];
            $first_name =  $request['message']['from']['first_name'];
            $last_name =  $request['message']['from']['last_name'] ?? "";
            $username =  $request['message']['from']['username'] ?? "";
            $isBot =  $request['message']['from']['is_bot'] ?? "";
            $user = new telUser();
            $user->user_id = $user_id;
            $user->chat_id = $chat_id;
            $user->first_name = $first_name;
            $user->last_name = $last_name;
            $user->username = $username;
            $user->is_bot = $isBot;
            $user->last_user_message_id = $last_user_message_id;
            retrySaveUser:
            if ($user->save()) {
                $user->user_id = $user_id;
                $user->currentProcess()->sync([1]);
                return $user;
            }
            if (--$maxRetrySave > 0)
                goto retrySaveUser;
        }
        $user->last_user_message_id = $last_user_message_id;
        $user->save();
        return $user;
    }
}
