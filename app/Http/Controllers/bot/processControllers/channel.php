<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
use App\Models\postVote;
use App\Services\bot\botService;
use Illuminate\Http\Request;

class channel extends Controller
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

    function pvote($params = null) {
        $votes = (postVote::where([
            'chat_id' => $this->botUpdate->getMessage()->chat->id,
            'message_id' => $this->botUpdate->getMessage()->messageId,
            'vote_type' => $params['type']
        ]));
        $userVote = clone($votes);
        $userVote = $userVote->where('user_id', $this->botUser->user_id)->first();
        if ($userVote) {
            $userVote->vote = $params['value'];
        } else {
            $userVote = new postVote();
            $userVote->user_id = $this->botUser->user_id;
            $userVote->chat_id = $this->botUpdate->getMessage()->chat->id;
            $userVote->message_id = $this->botUpdate->getMessage()->messageId;
            $userVote->vote_type = $params['type'];
            $userVote->vote = $params['value'];
        }
        $userVote->save();
        $vote_1_count = clone($votes);
        $vote_1_count = count($vote_1_count->where('vote', BOT__VOTE__1[1])->get());
        $vote_2_count = clone($votes);
        $vote_2_count = count($vote_2_count->where('vote', BOT__VOTE__2[1])->get());
        $vote_3_count = clone($votes);
        $vote_3_count = count($vote_3_count->where('vote', BOT__VOTE__3[1])->get());
        $this->botService->sendBase('editMessageReplyMarkup', [
            'chat_id' => $this->botUpdate->getMessage()->chat->id,
            'message_id' => $this->botUpdate->getMessage()->messageId,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => BOT__VOTE__3[0] . ($vote_3_count ? " ( $vote_3_count )" : ""),
                            'callback_data' => json_encode([
                                'source' => 'channel',
                                'type' => BOT__VOTE_TYPE__POST_VOTE,
                                'value' => BOT__VOTE__3[1]
                            ])
                        ],
                        [
                            'text' => BOT__VOTE__2[0] . ($vote_2_count ? " ( $vote_2_count ) " : ""),
                            'callback_data' => json_encode([
                                'source' => 'channel',
                                'type' => BOT__VOTE_TYPE__POST_VOTE,
                                'value' => BOT__VOTE__2[1]
                            ])
                        ],
                        [
                            'text' => BOT__VOTE__1[0] . ($vote_1_count ? " ( $vote_1_count ) " : ""),
                            'callback_data' => json_encode([
                                'source' => 'channel',
                                'type' => BOT__VOTE_TYPE__POST_VOTE,
                                'value' => BOT__VOTE__1[1]
                            ])
                        ]
                    ]
                ]
            ], JSON_UNESCAPED_UNICODE)
        ]);
        $this->botService->sendBase('answerCallbackQuery', [
            'callback_query_id' => $this->botUpdate->callbackQuery->id,
            'text' => 'نظر شما در مورد این پست: ' . constant('BOT__VOTE__' . $params['value'])[0]
        ]);
    }
}
