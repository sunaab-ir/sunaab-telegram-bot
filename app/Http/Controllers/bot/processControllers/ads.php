<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\County;
use App\Models\teAd;
use App\Models\telUser;
use App\Models\Village;
use App\Services\bot\botService;
use Illuminate\Http\Request;

class ads extends Controller
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

    function addAd ($entry = null)
    {
        $sub_process = $this->botUser->currentProcess->pivot->sub_process;
        $options['text'] = '';
        $send = false;
        $back = false;
        $dontDeleteMessage = [
            'meta_data' => json_encode([
                'sub_process' => BOT_PROCESS__ADMIN_ADD_AD
            ])
        ];;
        $hold = false;
        $cancelButton = true;

        if ($entry && isset($entry['entry'])) {
            switch ($entry['entry']) {
                case 'custom_message':
                {
                    $options['text'] .= $entry['message'];
                    break;
                }
                default:
                {
                    $options['text'] .= constant('BOT_MESSAGE__ENTRY__' . strtoupper($entry['entry']));
                    break;
                }
            }
        }
        switch ($sub_process) {
            default:
            {
                $options['text'] .= '🖼 لطفا اگر آگهی دارای تصویر است تصویر آگهی را ارسال کنید
در غیر این صورت روی دکمه "تصویر ندارد" ضربه بزنید';

                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'تصویر ندارد',
                                'callback_data' => json_encode([
                                    'sub_process' => 'ad_title'
                                ], JSON_UNESCAPED_UNICODE)
                            ]
                        ]
                    ]
                ]);
                $send = true;
                $this->botService->updateProcessData([
                    'sub_process' => 'ad_photo_take'
                ]);
                break;
            }
            case 'ad_photo_take':
            {
                if ($this->botUpdate->detectType() == 'message' && $this->botUpdate->getMessage()->detectType() == 'photo') {
                    $imageFileId = $this->botUpdate->getMessage()->photo[count($this->botUpdate->getMessage()->photo) - 1]['file_id'];
                    $this->botService->updateProcessData([
                        'tmp_data' => $this->botService->addJsonDataset(
                            $this->botUser->currentProcess->pivot->tmp_data,
                            'ad_image',
                            $imageFileId
                        )
                    ]);
                    $this->botService->handleProcess(null, [
                        'entry' => 'custom_message',
                        'message' => 'تصویر آگهی دریافت شد

'
                    ], [
                        'sub_process' => 'ad_title'
                    ]);
                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'custom_message',
                        'message' => "⭕️ مقدار ارسالی معتبر نیست

لطفا دقت نمایید تصویری که ارسال میکنید، از نوع فشرده شده باشد، به عبارتی دیگر به صورت فایل ارسال نشود

"
                    ], [
                        'sub_process' => ''
                    ]);
                }
                break;
            }
            case 'ad_title':
            {
                $options['text'] .= 'لطفا عنوان آگهی را ارسال کنید 🔰

⚠️ اگر مایل به قراردادن عنوان برای آگهی نیستید، روی دکمه "عنوان ندارد" ضربه بزنید';
                $send = true;
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'عنوان ندارد',
                                'callback_data' => json_encode([
                                    'sub_process' => 'ad_body'
                                ])
                            ]
                        ]
                    ]
                ]);
                $this->botService->updateProcessData([
                    'sub_process' => 'ad_title_input'
                ]);
                break;
            }
            case 'ad_title_input':
            {
                $update = $this->botUpdate;
                if ($update->detectType() == 'message' && $update->getMessage()->detectType() == 'text') {
                    if (strlen($update->getMessage()->text) > 10) {
                        $this->botService->updateProcessData([
                            'tmp_data' => $this->botService->addJsonDataset(
                                $this->botUser->currentProcess->pivot->tmp_data,
                                'ad_title',
                                $update->getMessage()->text
                            )
                        ]);
                        $this->botService->handleProcess(null, [
                            'entry' => 'custom_message',
                            'message' => 'عنوان دریافت شد

                            '
                        ], [
                            'sub_process' => 'ad_body'
                        ]);
                    } else {
                        $this->botService->handleProcess(null, [
                            'entry' => 'custom_message',
                            'message' => '⭕️ طول عنوان آگهی حداقل باید 10 کاراکتر باشد
'
                        ], [
                            'sub_process' => 'ad_title'
                        ]);
                    }
                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ], [
                        'sub_process' => 'ad_title'
                    ]);
                }
                break;
            }
            case 'ad_body':
            {
                $options['text'] .= '📃 لطفا متن آگهی را ارسال کنید

لطفا به این نکات دقت نمایید:

- متن آگهی باید حداقل دارای حداکثر 900 کاراکتر باشد.
- اگر نیاز به مشخص کردن تعداد افراد است، در متن آگهی ذکر شود.
- از قرار دادن شماره موبایل خودداری گردد.
- در صورت نیاز به ذکر آدرس، حتما آدرس را در متن آگهی ذکر کنید.';

                $send = true;
                $this->botService->updateProcessData([
                    'sub_process' => 'ad_body_input'
                ]);
                break;
            }
            case 'ad_body_input':
            {
                $update = $this->botUpdate;
                if (
                    $update->detectType() == 'message' &&
                    $update->getMessage()->detectType() == 'text'
                ) {
                    if (
                        strlen($update->getMessage()->text) <= 900
                    ) {
                        $this->botService->updateProcessData([
                            'tmp_data' => $this->botService->addJsonDataset(
                                $this->botUser->currentProcess->pivot->tmp_data,
                                'ad_body',
                                $update->getMessage()->text
                            )
                        ]);
                        $this->botService->handleProcess(null, [
                            'entry' => 'custom_message',
                            'message' => 'متن آگهی دریافت شد

                                '
                        ], [
                            'sub_process' => 'target_sex'
                        ]);
                    } else {
                        $this->botService->handleProcess(null, [
                            'entry' => 'custom_message',
                            'message' => '⭕️ متن آگهی باید حداقل دارای حداکثر 900 کاراکتر باشد.

'
                        ],
                            [
                                'sub_process' => 'ad_body'
                            ]);
                    }
                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ],
                        [
                            'sub_process' => 'ad_body'
                        ]);
                }
                break;
            }
            case 'target_sex':
            {
                $options['text'] .= '🙎🏻‍♂️🙍🏻‍♀️ لطفا جنسیت افراد منتخب آگهی را مشخص کنید

در صورتی که میخواهید این آگهی به هردو نوع جنسیت ارسال شود، روی "هر دو" ضربه بزنید';

                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🙎🏻‍♂️ مرد',
                                'callback_data' => json_encode([
                                    'sex' => 'man'
                                ])
                            ],
                            [
                                'text' => '🙍🏻‍♀️زن',
                                'callback_data' => json_encode([
                                    'sex' => 'woman'
                                ])
                            ]
                        ],
                        [
                            [
                                'text' => '🙎🏻‍♂️🙍🏻‍♀️هر دو',
                                'callback_data' => json_encode([
                                    'sex' => 'all'
                                ])
                            ]
                        ]
                    ]
                ]);
                $send = true;
                $this->botService->updateProcessData([
                    'sub_process' => 'target_sex_select'
                ]);
                break;
            }
            case 'target_sex_select':
            {
                if (
                    $this->botUpdate->detectType() == 'callback_query'
                ) {
                    $callback_data = json_decode($this->botUpdate->callbackQuery->data, true);
                    $this->botService->updateProcessData([
                        'tmp_data' => $this->botService->addJsonDataset(
                            $this->botUser->currentProcess->pivot->tmp_data,
                            'sex',
                            $callback_data['sex']
                        )
                    ]);
                    $this->botService->handleProcess(null, [
                        'entry' => 'custom_message',
                        'message' => 'جنسیت انتخاب شد

'
                    ], [
                        'sub_process' => 'ad_county'
                    ]);
                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ], [
                        'sub_process' => 'target_sex'
                    ]);
                }
                break;
            }
            case 'ad_county':
            {
                $options['text'] .= '🏘 لطفا شهر نمایش آگهی را انتخاب کنید';
                $cities = City::whereIn('id', [240, 555, 1110, 1174, 160])->get();
                $citiesKeyboard = [];
                foreach ($cities as $city) {
                    $citiesKeyboard[] = ['text' => $city['name'], 'callback_data' => json_encode([
                        'cid' => $city->county->id,
                        'city_id' => $city->id
                    ])];
                }
                $keyboardLayout = array_values(array_chunk($citiesKeyboard, 2));
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => $keyboardLayout
                ]);
                $send = true;
                $this->botService->updateProcessData([
                    'sub_process' => 'ad_county_select'
                ]);
                break;
            }
            case 'ad_county_select':
            {
                if (
                    $this->botUpdate->detectType() == 'callback_query'
                ) {
                    $callback_data = json_decode($this->botUpdate->callbackQuery->data, true);
                    $this->botService->updateProcessData([
                        'tmp_data' => $this->botService->addJsonDataset(
                            $this->botUser->currentProcess->pivot->tmp_data,
                            'ad_county',
                            $callback_data['cid']
                        )
                    ]);
                    $this->botService->updateProcessData([
                        'tmp_data' => $this->botService->addJsonDataset(
                            $this->botUser->currentProcess->pivot->tmp_data,
                            'ad_city',
                            $callback_data['city_id']
                        )
                    ]);
                    $this->botService->handleProcess(null, [
                        'entry' => 'custom_message',
                        'message' => 'شهرانتخاب شد

'
                    ], [
                        'sub_process' => 'ad_village_ask'
                    ]);
                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ], [
                        'sub_process' => 'ad_county'
                    ]);
                }
                break;
            }
            case 'ad_village_ask':
            {
                $options['text'] .= '⁉️ آیا میخواهید محدوده نمایش آگهی را به روستا محدود کنید ؟';
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'بله',
                                'callback_data' => json_encode([
                                    'limit' => true
                                ])
                            ],
                            [
                                'text' => 'خیر',
                                'callback_data' => json_encode([
                                    'limit' => false
                                ])
                            ]
                        ]
                    ]
                ]);
                $send = true;
                $this->botService->updateProcessData([
                    'sub_process' => 'ad_village_ask_select'
                ]);
                break;
            }
            case 'ad_village_ask_select':
            {
                if (
                    $this->botUpdate->detectType() == 'callback_query'
                ) {
                    $callback_data = json_decode($this->botUpdate->callbackQuery->data, true);
                    if ($callback_data['limit']) {
                        $this->botService->handleProcess(null, [
                            'entry' => 'custom_message',
                            'message' => 'محدود کردن به روستا
'
                        ], [
                            'sub_process' => 'ad_village'
                        ]);
                    } else {
                        $this->botService->handleProcess(null, null, [
                            'sub_process' => 'valid_time'
                        ]);
                    }
                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ], [
                        'sub_process' => 'ad_village_ask'
                    ]);
                }
                break;
            }
            case 'ad_village':
            {
                $countyId = json_decode($this->botUser->currentProcess->pivot->tmp_data, true)['ad_county'];
                $selectedCounty = County::find($countyId);
                $villages = [22472, 68190, 2748, 7291, 53470, 69601, 71997, 58434];
                $villages = $selectedCounty->villages()->whereIn('id', $villages)->get();
                $villagesKeyboard = [];
                foreach ($villages as $village) {
                    $villagesKeyboard[] = ['text' => $village['name'], 'callback_data' => json_encode([
                        'village_id' => $village['id']
                    ])];
                }
                $keyboardLayout = array_values(array_chunk($villagesKeyboard, 2));
                $options['text'] .= '🏡 لطفا روستای نمایش آگهی را انتخاب کنید';
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => $keyboardLayout

                ]);
                $send = true;
                $this->botService->updateProcessData([
                    'sub_process' => 'ad_village_select'
                ]);
                break;
            }
            case 'ad_village_select':
            {
                if (
                    $this->botUpdate->detectType() == 'callback_query'
                ) {
                    $callback_data = json_decode($this->botUpdate->callbackQuery->data, true);
                    $this->botService->updateProcessData([
                        'tmp_data' => $this->botService->addJsonDataset(
                            $this->botUser->currentProcess->pivot->tmp_data,
                            'ad_village',
                            $callback_data['village_id']
                        )
                    ]);
                    $this->botService->handleProcess(null, [
                        'entry' => 'custom_message',
                        'message' => 'روستا انتخاب شد

'
                    ], [
                        'sub_process' => 'valid_time'
                    ]);
                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ], [
                        'sub_process' => 'ad_village_ask'
                    ]);
                }
                break;
            }
            case 'valid_time':
            {
                $options['text'] .= '🕒 لطفا مدت اعتبار آگهی را به ساعت مشخص نمایید

مثال:
برای 10 ساعت لطفا مقدار "10" ارسال نمایید';
                $this->botService->updateProcessData([
                    'sub_process' => 'valid_time_input'
                ]);
                $send = true;
                break;
            }
            case 'valid_time_input':
            {
                if (
                    $this->botUpdate->detectType() == 'message' &&
                    $this->botUpdate->getMessage()->detectType() == 'text' &&
                    preg_match("/^[0-9]{1,3}$/", $this->botUpdate->getMessage()->text)
                ) {
                    $this->botService->updateProcessData([
                        'tmp_data' => $this->botService->addJsonDataset(
                            $this->botUser->currentProcess->pivot->tmp_data,
                            'ad_valid_time',
                            intval($this->botUpdate->getMessage()->text) * 60 * 60
                        )
                    ]);
                    $this->botService->handleProcess(null, [
                        'entry' => 'custom_message',
                        'message' => 'ساعت اعتبار مشخص شد

'
                    ], [
                        'sub_process' => 'finish'
                    ]);
                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ], [
                        'sub_process' => 'valid_time'
                    ]);
                }
                break;
            }
            case 'finish':
            {
                $ad_data = json_decode($this->botUser->currentProcess->pivot->tmp_data, true);
                $tel_ad = new teAd();
                $tel_ad->creator_user_id = $this->botUser->user_id;
                $tel_ad->title = $ad_data['ad_title'] ?? null;
                $tel_ad->photo_file_id = $ad_data['ad_image'] ?? null;
                $tel_ad->ad_text = $ad_data['ad_body'];
                $tel_ad->county_id = $ad_data['ad_county'];
                $tel_ad->city_id = $ad_data['ad_city'];
                $tel_ad->village_id = $ad_data['ad_village'] ?? null;
                $tel_ad->target_sex = $ad_data['sex'];
                $tel_ad->valid_time = $ad_data['ad_valid_time'];
                $tel_ad->save();
                $this->botService->handleProcess(BOT_PROCESS__NAME__ADMIN_PANEL, [
                    'entry' => BOT_PROCESS__ADMIN_ADD_AD,
                    's' => true
                ]);
                break;
            }
        }

        if ($cancelButton) {
            $options = $this->botService->appendInlineKeyboardButton($options, [[
                'text' => '❌ انصراف',
                'callback_data' => json_encode([
                    'process_id' => BOT_PROCESS__NAME__ADMIN_PANEL,
                    'ent' => BOT_PROCESS__ADMIN_ADD_AD,
                    's' => false
                ])
            ]]);
        }
        if ($send)
            $this->botService->send('editMessageText', $options, $back, $dontDeleteMessage, $hold);
    }

    function ads ($entry = null)
    {
        $subProcess = $this->botUser->currentProcess->pivot->sub_process;
        $options['text'] = '';
        $send = false;
        $back = false;
        $dontDeleteMessage = false;
        $hold = false;
        $cancelButton = false;

        if ($entry && isset($entry['entry'])) {
            switch ($entry['entry']) {
                case 'custom_message':
                {
                    $options['text'] .= $entry['message'];
                    break;
                }
                default:
                {
                    $options['text'] .= constant('BOT_MESSAGE__ENTRY__' . strtoupper($entry['entry']));
                    break;
                }
            }
        }
        switch ($subProcess) {
            default:
            {
                $options['text'] .= "📃 لطفا انتخاب کنید قصد مشاهده کدام دسته از آگهی ها را دارید 🔰";

                $reservedCount = count(teAd::where('state', '=', BOT__AD__STATE__RESERVED)->get());
                $confirmedCount = count(teAd::where('state', '=', BOT__AD__STATE__CONFIRMED)->get());
                $sentCount = count(teAd::where('state', BOT__AD__STATE__SENT)->get());
                $promisedCount = count(teAd::where('state', BOT__AD__STATE__PROMISED)->get());
                $expiredCount = count(teAd::where('state', BOT__AD__STATE__EXPIRED)->get());
                $rejectedCount = count(teAd::where('state', BOT__AD__STATE__REJECTED)->get());

                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => "رزرو شده ها ($reservedCount)",
                                'callback_data' => json_encode([
                                    'process_id' => 'admin_ads_' . BOT__AD__STATE__RESERVED
                                ])
                            ],
                            [
                                'text' => "رد شده ها ($rejectedCount)",
                                'callback_data' => json_encode([
                                    'process_id' => 'admin_ads_' . BOT__AD__STATE__REJECTED
                                ])
                            ]
                        ],
                        [
                            [
                                'text' => "تأیید شده ها ($confirmedCount)",
                                'callback_data' => json_encode([
                                    'process_id' => 'admin_ads_' . BOT__AD__STATE__CONFIRMED
                                ])
                            ],
                            [
                                'text' => "منقضی شده ها ($expiredCount)",
                                'callback_data' => json_encode([
                                    'process_id' => 'admin_ads_' . BOT__AD__STATE__EXPIRED
                                ])
                            ]
                        ],
                        [
                            [
                                'text' => "ارسال شده ها ($sentCount)",
                                'callback_data' => json_encode([
                                    'process_id' => 'admin_ads_' . BOT__AD__STATE__SENT
                                ])
                            ],
                            [
                                'text' => "پذیرفته شده ها ($promisedCount)",
                                'callback_data' => json_encode([
                                    'process_id' => 'admin_ads_' . BOT__AD__STATE__PROMISED
                                ])
                            ]
                        ]
                    ]
                ]);
                $send = true;
                $back = true;
                break;
            }
        }


        if ($cancelButton) {
            $options = $this->botService->appendInlineKeyboardButton($options, [[
                'text' => '❌ انصراف',
                'callback_data' => json_encode([
                    'process_id' => BOT_PROCESS__NAME__ADMIN_PANEL,
                    'ent' => BOT_PROCESS__ADMIN_ADD_AD
                ])
            ]]);
        }
        if ($send)
            $this->botService->send('editMessageText', $options, $back);
    }

    function ads_1 ($entry = [])
    {
        $subProcess = $this->botUser->currentProcess->pivot->sub_process;
        $tmpData = json_decode($this->botUser->currentProcess->pivot->tmp_data, true);
        $options['text'] = '';
        $send = false;
        $back = false;
        $dontDeleteMessage = false;
        $hold = false;
        $cancelButton = false;
        $this->botService->removeChatHistory([
            ['message_type', '=', 'ad_display']
        ]);
        if ($entry && isset($entry['entry'])) {
            switch ($entry['entry']) {
                case 'custom_message':
                {
                    $options['text'] .= $entry['message'];
                    break;
                }
                default:
                {
                    $options['text'] .= constant('BOT_MESSAGE__ENTRY__' . strtoupper($entry['entry']));
                    break;
                }
            }
        }
        switch ($subProcess) {
            default:
            {
                $this->botService->removeChatHistory([
                    ['message_type', '=', 'ad_display_ad']
                ]);
                if (!isset($entry['page']))
                    $entry['page'] = $tmpData['ads_page'] ?? 0;
                $page = $entry['page'];
                $adsCount = count(teAd::where('state', BOT__AD__STATE__RESERVED)->get());
                $pagesCount = floor($adsCount / 10);
                if ($adsCount % 10 != 0)
                    $pagesCount++;
                $where = [
                  ['state', '=', BOT__AD__STATE__RESERVED]
                ];
                if (isset($entry['srch'])) {
                    $page = 0;
                    $search = $entry['srch'];
                    $where[] = ['title', 'like', "%$search%"];
                    $ads = teAd::where($where)->orWhere('id', $search)->skip($page * 10)->take(10)->get();
                }else
                    $ads = teAd::where($where)->skip($page * 10)->take(10)->get();
                $this->botService->updateProcessData([
                    'tmp_data' => $this->botService->addJsonDataset(
                        $this->botUser->currentProcess->pivot->tmp_data,
                        'ads_page',
                        $page
                    )
                ]);
                $keyboard = [];
                if (!count($ads)) {
                    $options['text'] = '❌ آگهی ای با این عنوان یا شناسه یافت نشد، لطفا مجدد سعی کنید';
                    $options['reply_markup'] = json_encode([
                       'inline_keyboard' => [
                           [
                               [
                                   'text' => 'بازگشت به آگهی ها',
                                   'callback_data' => json_encode([
                                       'sub_process' => ''
                                   ])
                               ]
                           ]
                       ]
                    ]);
                    goto ads_1_default_skipToSend;
                }
                foreach ($ads as $ad) {
                    $text = $ad->title ?? 'آگهی کد: ' . $ad->id;
                    $keyboard[] = [
                        'text' => '📃 ' . $text,
                        'callback_data' => json_encode([
                            'ty' => 'ad',
                            'aid' => $ad->id
                        ])
                    ];
                }
                $keyboard = array_chunk($keyboard, 2);

                $options['text'] .= "📃 لطفا آگهی مورد نظر را از لیست زیر انتخاب کنید\n\nهمچنین می توانید با ارسال عنوان یا شناسه آگهی، آگهی مورد نظر را جستجو کنید";

                $paginationButtons = [];
                if ($page) {
                    $paginationButtons[] = [
                        'text' => 'صفحه قبل ⬅️',
                        'callback_data' => json_encode([
                            'ty' => 'pg',
                            'dir' => 'back'
                        ])
                    ];
                }
                $keyboard[][] = [
                    'text' => "🗒 صفحه " . strval($page + 1) . " از " . $pagesCount . ' 🗒',
                    'callback_data' => 'null'
                ];
                if ($page + 1 < $pagesCount) {
                    $paginationButtons[] = [
                        'text' => '➡️ صفحه بعد',
                        'callback_data' => json_encode([
                            'ty' => 'pg',
                            'dir' => 'next'
                        ])
                    ];
                }
                $keyboard = array_merge($keyboard, [
                    $paginationButtons
                ]);

                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => $keyboard
                ]);
                ads_1_default_skipToSend:
                $back = true;
                $cancelButton = false;
                $send = true;
                $this->botService->updateProcessData([
                    'sub_process' => 'ads_select'
                ]);
                break;
            }
            case 'ads_select':
            {
                if ($this->botUpdate->detectType() == 'callback_query') {
                    $callbackData = json_decode($this->botUpdate->callbackQuery->data, true);
                    if ($callbackData['ty'] == 'ad') {
                        $ad = teAd::find($callbackData['aid']);
                        $creatorName = (telUser::find($ad->creator_user_id))->profile->full_name ?? 'برون نام';
                        $adTitle = $ad->title ?? 'بدون عنوان';
                        $adBody = $ad->ad_text;
                        switch ($ad->target_sex) {
                            case 'all':
                            {
                                $sex = '🙎🏻‍♂️🙍🏻‍♀️ فرقی نمی کند';
                                break;
                            }
                            case 'man':
                            {
                                $sex = '🙎🏻‍♂️ آقا';
                                break;
                            }
                            case 'woman':
                            {
                                $sex = '🙍🏻‍♀️ خانم';
                                break;
                            }
                            default:
                            {
                                $sex = 'نا مشخص';
                                break;
                            }
                        }
                        $city = (City::find($ad->city_id))->name;
                        if ($ad->village_id) {
                            $village = (Village::find($ad->village_id))->name;
                        }
                        $validTime = $ad->valid_time / 60 / 60;
                        $options['caption'] = "سازنده آگهی: $creatorName\n";
                        $options['caption'] .= "عنوان آگهی: $adTitle\n";
                        $options['caption'] .= "جنسیت : $sex\n";
                        $options['caption'] .= "شهر : $city\n";
                        if (isset($village))
                            $options['caption'] .= "روستا : $village\n";
                        $options['caption'] .= "مدت اعتبار آگهی : $validTime ساعت\n\n";
                        $options['caption'] .= "متن آگهی : $adBody\n";
                        $options['reply_markup'] = json_encode([
                            'inline_keyboard' => [
                                [
                                    [
                                        'text' => '✅ تأیید آگهی',
                                        'callback_data' => json_encode([
                                            'ty' => 'c',
                                            'aid' => $ad->id
                                        ])
                                    ],
                                    [
                                        'text' => '✅📣 تأیید و ارسال آگهی',
                                        'callback_data' => json_encode([
                                            'ty' => 'cs',
                                            'aid' => $ad->id
                                        ])
                                    ]
                                ],
                                [
                                    [
                                        'text' => '⛔️ رد آگهی',
                                        'callback_data' => json_encode([
                                            'ty' => 'r',
                                            'aid' => $ad->id
                                        ])
                                    ]
                                ],
                                [
                                    [
                                        'text' => 'بازگشت به آگهی ها',
                                        'callback_data' => json_encode([
                                            'sub_process' => ''
                                        ])
                                    ]
                                ]
                            ]
                        ], JSON_UNESCAPED_UNICODE);
                        $hold = [
                          'message_type' => 'ad_display_ad'
                        ];
                        $options['text'] .= $options['caption'];
                        if (strlen($options['caption']) <= 1010 && $ad->photo_file_id) {
                            $options['photo'] = $ad->photo_file_id;
                            $this->botService->send('sendPhoto', $options, $back, $dontDeleteMessage, $hold);
                        }else {
                            $options['text'] .= $options['caption'];
                            $send = true;
                            $cancelButton = false;
                        }
                        $this->botService->updateProcessData([
                            'sub_process' => 'ad_actions'
                        ]);

                    } else {
                        switch ($callbackData['dir']) {
                            case 'next':
                            {
                                $this->botService->handleProcess(null, [
                                    'page' => ($tmpData['ads_page'] + 1)
                                ], [
                                    'sub_process' => ''
                                ]);
                                break;
                            }
                            case 'back':
                            {
                                $this->botService->handleProcess(null, [
                                    'page' => ($tmpData['ads_page'] - 1)
                                ], [
                                    'sub_process' => ''
                                ]);
                                break;
                            }
                        }
                    }
                }
                elseif ($this->botUpdate->detectType() == 'message' && $this->botUpdate->getMessage()->detectType() == 'text') {
                    $this->botService->handleProcess(null, [
                        'srch' => $this->botUpdate->getMessage()->text
                    ], [
                        'sub_process' => ''
                    ]);
                }
                else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ], [
                        'sub_process' => ''
                    ]);
                }
                break;
            }
            case 'ad_actions': {
                if ($this->botUpdate->detectType() == 'callback_query') {

                }else {
                    $options['text'] = '🚫 مقدار ارسالی معتبر نیست، لطفا از دکمه های آگهی استفاده کنید';
                    $options['reply_markup'] = json_encode([
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => 'بازگشت به آگهی ها',
                                    'callback_data' => json_encode([
                                        'sub_process' => ''
                                    ])
                                ]
                            ]
                        ]
                    ]);
                    $dontDeleteMessage = [
                        'message_type' => 'ad_display'
                    ];
                    $send = true;
                    goto ads_1_skipToSend;
                }
                break;
            }
        }
        ads_1_skipToSend:
        if ($cancelButton) {
            $options = $this->botService->appendInlineKeyboardButton($options, [[
                'text' => '❌ انصراف',
                'callback_data' => json_encode([
                    'process_id' => BOT_PROCESS__NAME__ADMIN_PANEL,
                    'ent' => BOT_PROCESS__ADMIN_ADD_AD
                ])
            ]]);
        }
        if ($send)
            $this->botService->send('editMessageText', $options, $back, $dontDeleteMessage, $hold);
    }

    function ads_2 ($entry = null)
    {
        echo "state2";
    }

    function ads_3 ($entry = null)
    {

    }

    function ads_4 ($entry = null)
    {

    }

    function ads_5 ($entry = null)
    {

    }

    function ads_6 ($entry = null)
    {

    }
}
