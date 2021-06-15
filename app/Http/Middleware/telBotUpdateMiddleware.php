<?php

namespace App\Http\Middleware;

use App\Models\telUser;
use App\Models\telUserTrack;
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
        if (in_array($request->botUpdate->detectType(), [
            'message',
            'edited_message',
            'inline_query',
            'chosen_inline_result',
            'callback_query',
        ])) {
            $user = $this->getTelUser($request);
            $request->botUser = $user;
            $this->setTrack($request);
        }
        return $next($request);
    }

    function getTelUser (Request $request)
    {
        if ($request->botUpdate->detectType() == 'callback_query')
            $from = $request->botUpdate->callbackQuery->from;
        else
            $from = $request->botUpdate->getMessage()->getFrom();
        $isBot =  $from->isBot;
        if ($isBot)
            $user_id = $request->botUpdate->getMessage()->getChat()->id;
        else
            $user_id = $from->id;

        if ($request->botUpdate->detectType() != 'callback_query')
            $last_user_message_id =  $request->botUpdate->getMessage()->messageId;
        $user = telUser::with(['Process', 'Profile', 'Tracks'])->find($user_id);
        $maxRetrySave = 3;
        if (!$user) {
            $chat_id = $from->id;
            if ($isBot && $request->botUpdate->detectType() != 'callback_query') {
                $first_name = $request->botUpdate->getMessage()->getChat()->firstName;
                $last_name = $request->botUpdate->getMessage()->getChat()->lastName ?? "";
                $username = $request->botUpdate->getMessage()->getChat()->username ?? "";
            }else {
                $first_name = $from->firstName;
                $last_name = $from->lastName ?? "";
                $username = $from->username ?? "";
            }
            $user = new telUser();
            $user->user_id = $user_id;
            $user->chat_id = $chat_id;
            $user->first_name = $first_name;
            $user->last_name = $last_name;
            $user->username = $username;
            $user->process_type = 'normal';
            $user->is_bot = $isBot;
            if ($request->botUpdate->detectType() != 'callback_query') {
                $user->last_user_message_id = $last_user_message_id;
                $user->last_user_message_date = time();
            }
            retrySaveUser:
            if ($user->save()) {
                $user->user_id = $user_id;
                $user->Process()->sync([BOT_PROCESS__COMMAND => [
                    'process_type' => 'command'
                ], BOT_PROCESS__MAIN]);
//                die;
//                $user->Process()->attach(BOT_PROCESS__COMMAND , [
//                    'process_type' => 'command'
//                ]);
                return $user;
            }
            if (--$maxRetrySave > 0)
                goto retrySaveUser;
        }
        if ($request->botUpdate->detectType() != 'callback_query') {
            $user->last_user_message_id = $last_user_message_id;
            $user->last_user_message_date = time();
        }
        $user->save();
        return $user;
    }

    function setTrack(Request $request) {
        $user = $request->botUser;
        $update = $request->botUpdate;
        $track = new telUserTrack();
        $track->tel_process_id = $user->currentProcess->id;
        $track->process_state = $user->currentProcess->pivot->process_state;
        $track->sub_process = $user->currentProcess->pivot->sub_process;
        $track->type = "in";
        $track->entry_type = $update->detectType();
        if ($update->detectType() == "callback_query")
            $track->user_input = $update->callbackQuery->data;
        else
            $track->user_input = $update->getMessage()->text;
        $user->Tracks()->save($track);
    }
}
