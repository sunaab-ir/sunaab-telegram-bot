<?php

namespace App\Http\Controllers\bot;

use App\Http\Controllers\bot\processControllers\ads;
use App\Http\Controllers\bot\processControllers\command;
use App\Http\Controllers\bot\processControllers\group;
use App\Http\Controllers\Controller;
use App\Models\telUser;
use App\Services\bot\botService;
use Illuminate\Http\Request;

class updateController extends Controller
{
    protected $botService;
    protected $botUpdate;
    protected $botUser;

    public function __construct ()
    {
        $this->botService = new botService();
    }

    public function update (Request $request)
    {
        $this->botUpdate = $request->botUpdate;
        $this->botUser = $request->botUser;
        if ($this->botUpdate->detectType()) {
            if ($this->botUpdate->getMessage()->getChat()->type == 'private') {
                if ($this->botUpdate->hasCommand() && $this->botUpdate->getMessage()->entities[0]['offset'] == 0) {
                    $this->handleCommands($request);
                } else {
                    if ($this->botUser->process_type != 'normal')
                        $this->handleCommands($request);
                    $this->handleNormalUpdate($request);
                }
            }else if (in_array($this->botUpdate->getMessage()->getChat()->type, ['group', 'supergroup'])) {
                $groupController = new group();
                $groupController->handleGroupUpdate();
            }
        }
    }

    function handleNormalUpdate ($request)
    {
        if (in_array($request->botUpdate->detectType(), ['message', 'edited_message', 'callback_query'])) {
            if ($request->botUpdate->detectType() == 'callback_query') {
                $this->handleCallbackQuery();
            } else {
                $this->botService->handleProcess($request->botUser->currentProcess);
            }
        } else {
            switch ($request->botUpdate->detectType()) {
                case 'inline_query':
                {
                    $this->botService->sendBase('answerInlineQuery', [
                        'inline_query_id' => $request->botUpdate->inlineQuery->id,
                        'results' => json_encode([
                            [
                                'type' => 'contact',
                                'id' => 1,
                                'phone_number' => "+989219871833",
                                'first_name' => "ابوالفضل"
                            ]
                        ])
                    ]);
                    break;
                }
                case 'channel_post':
                {
                    $this->botService->sendBase('editMessageReplyMarkup', [
                        'chat_id' => $request->botUpdate->getMessage()->chat->id,
                        'message_id' => $request->botUpdate->getMessage()->messageId,
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [
                                    [
                                        'text' => BOT__VOTE__3[0],
                                        'callback_data' => json_encode([
                                            'source' => 'channel',
                                            'type' => BOT__VOTE_TYPE__POST_VOTE,
                                            'value' => BOT__VOTE__3[1]
                                        ])
                                    ],
                                    [
                                        'text' => BOT__VOTE__2[0],
                                        'callback_data' => json_encode([
                                            'source' => 'channel',
                                            'type' => BOT__VOTE_TYPE__POST_VOTE,
                                            'value' => BOT__VOTE__2[1]
                                        ])
                                    ],
                                    [
                                        'text' => BOT__VOTE__1[0],
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
                    break;
                }
            }
        }
    }

    function handleCallbackSource ()
    {
        $callbackData = json_decode(\request()->botUpdate->callbackQuery->data, true);

        $controller = "App\\Http\\Controllers\\bot\\processControllers\\" . $callbackData['source'];
        $action = $callbackData['type'];
        $processController = new $controller();
        $processController->$action($callbackData);
    }

    function handleCommands ($request)
    {
        $this->botUser->process_type = 'command';
        $this->botUser->save();
        $command = $request->botUpdate->getMessage()->entities[0];
        $commandName = trim(substr($request->botUpdate->getMessage()->text, $command['offset'] + 1, $command['length']));
        $commandValue = trim(substr($request->botUpdate->getMessage()->text, $command['length'], strlen($request->botUpdate->getMessage()->text)));
        if ($commandName == 'start') {
            $commandController = new command();
            $commandController->start();
        } else
            $this->botService->handleCommandProcess(null, $commandValue, [
                'sub_process' => $commandName
            ]);
    }


    function handleCallbackQuery ()
    {
        $callbackData = json_decode($this->botUpdate->callbackQuery->data, true);
        if (!$callbackData)
            return;
        if (isset($callbackData['src'])) {
            switch ($callbackData['src']) {
                case 'ad':
                {
                    if (isset($callbackData['a'])) {
                        $adController = new ads();
                        $adController->handleUserAdActions($callbackData['a']);
                    }
                    break;
                }
            }
        } else if (!isset($callbackData['source'])) {
            if (isset($callbackData['process_id']))
                $this->botService->handleProcess($callbackData['process_id'],
                    isset($callbackData['ent']) ? ['entry' => $callbackData['ent']] : null
                );
            else
                $this->botService->handleProcess();
        } else {
            $this->handleCallbackSource();
        }
    }
}
