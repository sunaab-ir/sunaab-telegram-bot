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
        $request->botUpdate = Telegram::getWebhookUpdates();
        $user = $this->getTelUser($request);
        $request->botUser = $user;
        return $next($request);
    }

    function getTelUser (Request $request)
    {
        $isBot =  $request->botUpdate->getMessage()->getFrom()->isBot;
        if ($isBot)
            $user_id = $request->botUpdate->getMessage()->getChat()->id;
        else
            $user_id = $request->botUpdate->getMessage()->getFrom()->id;
        $last_user_message_id =  $request->botUpdate->getMessage()->messageId;
        $user = telUser::with('Process')->find($user_id);
        $maxRetrySave = 3;
        if (!$user) {
            $chat_id = $request->botUpdate->getMessage()->getChat()->id;
            if ($isBot) {
                $first_name = $request->botUpdate->getMessage()->getChat()->firstName;
                $last_name = $request->botUpdate->getMessage()->getChat()->lastName ?? "";
                $username = $request->botUpdate->getMessage()->getChat()->userName ?? "";
            }else {
                $first_name = $request->botUpdate->getMessage()->getFrom()->firstName;
                $last_name = $request->botUpdate->getMessage()->getFrom()->lastName ?? "";
                $username = $request->botUpdate->getMessage()->getFrom()->userName ?? "";
            }
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
                $user->Process()->sync([1]);
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
