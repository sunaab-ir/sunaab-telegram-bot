<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\County;
use App\Models\teAd;
use App\Services\bot\botService;
use Illuminate\Http\Request;

class admin extends Controller
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

    function admin ($entry = null)
    {


        $sub_process = $this->botUser->currentProcess->pivot->sub_process;
        $options['text'] = '';
        $send = false;
        $back = true;

        if ($entry) {
            switch ($entry['entry']) {
                case BOT_PROCESS__ADMIN_SEND_AD:
                {
                    $options['text'] .= '✅ آگهی با موفقیت ذخیره شد و آماده ارسال است، برای ارسال آگهی میتوانید به قسمت آگهی های من مراجعه نمایید

';
                    $this->botService->removeChatHistory([
                        ['meta_data', 'like', '%"sub_process":"' . BOT_PROCESS__ADMIN_SEND_AD . '"%']
                    ]);
                    break;
                }
            }
        }else {
            $fullName = $this->botUser->profile->full_name;
            $options['text'] .= "$fullName عزیز 🙂

به پنل مدیریت خوش آمدید

";
        }
        switch ($sub_process) {
            default:
            {
                $options['text'] .= "با استفاده از منوی زیر از بخش مدیریت استفاده کنید 🔰";


                $keyboard = [
                    [
                        [
                            'text' => "ثبت آگهی 📃",
                            'callback_data' => json_encode([
                                'process_id' => BOT_PROCESS__ADMIN_SEND_AD
                            ], JSON_UNESCAPED_UNICODE)
                        ]
                    ]
                ];

                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => $keyboard
                ], JSON_UNESCAPED_UNICODE);
                $send = true;

                break;
            }
        }


        if ($send)
            $this->botService->send('editMessageText', $options, $back);
    }

    function sendAd ($entry = null)
    {
        $sub_process = $this->botUser->currentProcess->pivot->sub_process;
        $options['text'] = '';
        $send = false;
        $back = false;
        $dontDeleteMessage = [
            'meta_data' => json_encode([
                'sub_process' => BOT_PROCESS__ADMIN_SEND_AD
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

- متن آگهی باید حداقل دارای 50 کاراکتر و حداکثر 2000 کاراکتر باشد.
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
                        strlen($update->getMessage()->text) >= 50 &&
                        strlen($update->getMessage()->text) <= 2000
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
                            'message' => '⭕️ متن آگهی باید حداقل دارای 50 کاراکتر و حداکثر 2000 کاراکتر باشد.

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
                    ],[
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
                    'entry' => BOT_PROCESS__ADMIN_SEND_AD
                ]);
                break;
            }
        }

        if ($cancelButton) {
            $options = $this->botService->appendInlineKeyboardButton($options, [[
                'text' => '❌ انصراف',
                'callback_data' => json_encode([
                    'process_id' => BOT_PROCESS__NAME__ADMIN_PANEL,
                    'ent' => BOT_PROCESS__ADMIN_SEND_AD
                ])
            ]]);
        }
        if ($send)
            $this->botService->send('editMessageText', $options, $back, $dontDeleteMessage, $hold);
    }
}
