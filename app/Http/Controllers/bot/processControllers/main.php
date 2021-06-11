<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\County;
use App\Models\telUserProfile;
use App\Models\Village;
use App\Services\bot\botService;
use Illuminate\Http\Request;
use Telegram\Bot\Exceptions\TelegramOtherException;

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

    function main ($params = null)
    {
        if (!$this->botUser->profile) {
            $this->botUser->Profile()->save(new telUserProfile());
            $this->botService->handleProcess(BOT_PROCESS__PROFILE, [
                'entry' => 'newUser'
            ], [
                'sub_process' => 'get_fullName'
            ]);
        } else {
            $options = [];
            $options['text'] = '';
            if (isset($params) || isset($params['entry'])) {
                switch ($params['entry']) {
                    case 'newUserCompleteProfile':
                    {
                        $this->botService->send('editMessageText', [
                            'text' => 'اطلاعات  کاربری شما با موفقیت تکمیل شد ✅ ',
                            'reply_markup' => json_encode([
                                'remove_keyboard' => true
                            ])
                        ]);
                        $options['text'] .= 'خب حالا که پروفایلت رو تکمیل کردی وقتشه که بریم سراغ قسمت های باحال 😃😉
                        ';
                    }
                }
            }

            $options['text'] .= "از منوی زیر چیزی که میخوای رو انتخاب کن 🔰";
            $options['reply_markup'] = json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => '📄 مشخصات من',
                            'callback_data' => json_encode([
                                'process_id' => BOT_PROCESS__PROFILE,
                                'sub_process' => ''
                            ])
                        ]
                    ],
                    [
                        [
                            'text' => '❔ درباره ساناب',
                            'callback_data' => json_encode([
                                'process_id' => BOT_PROCESS__ABOUT
                            ])
                        ],
                        [
                            'text' => '📲 تماس با ساناب',
                            'callback_data' => json_encode([
                                'process_id' => BOT_PROCESS__CONTACT
                            ])
                        ]
                    ]
                ]
            ]);

            $this->botService->send('editMessageText', $options);
        }
    }

    function profile ($params = null)
    {
        $currentSubProcess = $this->botUser->currentProcess->pivot->sub_process;
        $options = [];
        $options['text'] = "";
        if ($params) {
            if (isset($params['entry'])) {
                switch ($params['entry']) {
                    case 'newUser':
                    {
                        $options['text'] .= 'سلام ' . $this->botUser->first_name . ' 😃✋

به ربات ساناب خوش اومدی 🌳

قبل از استفاده از ربات نیازه که پروفایلت رو تکمیل کنی 🙂 بیا با هم سریع تکمیلش کنیم 😉 🙏🏻';
                        break;
                    }
                    case "invalid":
                    {
                        $options['text'] .= BOT_MESSAGE__ENTRY__INVALID;
                        break;
                    }
                    case 'saved':
                    {
                        $options['text'] .= BOT_MESSAGE__ENTRY__SAVED;
                        break;
                    }
                    case 'custom_message':
                    {
                        $options['text'] .= $params['message'];
                    }
                }
            }
        }
        switch ($currentSubProcess) {
            default:
            {
                $fullName = $this->botUser->profile->full_name;
                $userType = $this->botUser->profile->is_manual_worker ? "👷🏻‍♂️ کارگر" : "👨🏻‍🌾 کشاورز";
                $cityName = (City::find($this->botUser->profile->city_id))->name;
                $isUserRural = !empty($this->botUser->profile->village_id);
                $villageName = "";
                if ($isUserRural) {
                    $villageName = (Village::find($this->botUser->profile->village_id))->name;
                }
                $isRural = !$isUserRural ? 'خیر' : 'بله';

                $options['text'] .= "🔴 نام و نام خانوادگی: $fullName

🟠 نوع کاربر: $userType

🟡 نام شهر: $cityName

🟢 روستایی: $isRural";
                if ($isUserRural) {
                    $options['text'] .= "

🔵 نام روستا: $villageName";
                }

                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '📝 ویرایش مشخصات',
                                'callback_data' => json_encode([
                                    'process_id' => BOT_PROCESS__EDIT_PROFILE
                                ])
                            ]
                        ]
                    ]
                ]);
                $this->botService->send('editMessageText', $options);
                break;
            }
            case 'get_fullName':
            {
                $options['text'] .= "
                لطفا نام و نام خانوادگیت رو ارسال کن";
                $this->botService->send('sendMessage', $options, false);
                $this->botService->updateProcessData([
                    'sub_process' => 'get_fullName_input'
                ]);
                break;
            }
            case 'get_fullName_input':
            {
                if ($this->botUpdate->detectType() == 'message') {
                    if (strlen($this->botUpdate->getMessage()->text) > 5) {
                        $this->botUser->profile->full_name = $this->botUpdate->getMessage()->text;
                        $this->botUser->profile->save();
                        $this->botService->handleProcess(null, [
                            'entry' => 'saved'
                        ], [
                            'sub_process' => 'is_manual_worker'
                        ]);
                    } else
                        goto invalidFullNameData;
                } else {
                    invalidFullNameData:
                    $this->botService->send('sendMessage', [
                        'text' => '❌ مقدار ارسالی نا معتبره، لطفا دقت کن
مثال: مهدی باقری'
                    ]);
                }
                break;
            }
            case 'is_manual_worker':
            {
                $options['text'] .= 'خب، حالا مشخص کن 👷🏻‍♂️ کارگر هستی یا 👨🏻‍🌾 کشاورز 😊';
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '👷🏻‍♂️ کارگر', 'callback_data' => json_encode([
                                'is_manual_worker' => true
                            ])],
                            ['text' => '👨🏻‍🌾 کشاورز', 'callback_data' => json_encode([
                                'is_manual_worker' => false
                            ])]
                        ]
                    ]
                ]);
                $this->botService->send('sendMessage', $options, false);
                $this->botService->updateProcessData([
                    'sub_process' => 'is_manual_worker_select'
                ]);
                break;
            }
            case 'is_manual_worker_select':
            {
                if ($this->botUpdate->detectType() == 'callback_query') {
                    $isWorker = (json_decode($this->botUpdate->callbackQuery->data, true))['is_manual_worker'];
                    $this->botUser->profile->is_manual_worker = $isWorker;
                    $this->botUser->profile->save();
                    $this->botService->handleProcess(null, [
                        'entry' => 'custom_message',
                        'message' => 'تا اینجا حله 😄
                        '
                    ], [
                        'sub_process' => 'get_county'
                    ]);
                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ],
                        [
                            'sub_process' => 'is_manual_worker'
                        ]);
                }
                break;
            }
            case 'get_county':
            {
                $options['text'] .= BOT_MESSAGE__PLEASE_SELECT_CITY;
                $cities = City::whereIn('id', [240, 555, 1110, 1174, 160])->get();
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => $cities[0]->name, 'callback_data' => json_encode([
                                'county_id' => $cities[0]->county->id,
                                'city_id' => $cities[0]->id
                            ])],
                            ['text' => $cities[1]->name, 'callback_data' => json_encode([
                                'county_id' => $cities[1]->county->id,
                                'city_id' => $cities[1]->id
                            ])]
                        ],
                        [
                            ['text' => $cities[2]->name, 'callback_data' => json_encode([
                                'county_id' => $cities[2]->county->id,
                                'city_id' => $cities[2]->id
                            ])],
                            ['text' => $cities[3]->name, 'callback_data' => json_encode([
                                'county_id' => $cities[3]->county->id,
                                'city_id' => $cities[3]->id
                            ])]
                        ],
                        [
                            ['text' => "سایر", 'callback_data' => json_encode([
                                'county_id' => 'other'
                            ])],
                            ['text' => $cities[4]->name, 'callback_data' => json_encode([
                                'county_id' => $cities[4]->county->id,
                                'city_id' => $cities[4]->id
                            ])]
                        ]
                    ]
                ]);
                $this->botService->updateProcessData([
                    'sub_process' => 'get_county_select'
                ]);
                $this->botService->send('editMessageText', $options, false);
                break;
            }
            case 'get_county_select':
            {
                if ($this->botUpdate->detectType() == 'callback_query') {
                    $data = json_decode($this->botUpdate->callbackQuery->data, true);
                    if ($data['county_id'] == 'other') {
                        $this->botService->send('editMessageText', [
                            'text' => 'در بروز رسانی های بعدی شهر های بیشتری در اختیار شما قرار می گیرد 😊

                            در حال بازگشت به منو...'
                        ]);
                        sleep(2);
                        $this->botService->handleProcess(null, null,
                            [
                                'sub_process' => 'get_county'
                            ]);
                    } else {
                        $this->botUser->profile->county_id = $data['county_id'];
                        $this->botUser->profile->city_id = $data['city_id'];
                        $this->botUser->profile->save();
                        $this->botService->handleProcess(null, [
                            'entry' => 'custom_message',
                            'message' => '🤏دیگه چیزی نمونده
                            '
                        ],
                            [
                                'sub_process' => 'check_rural'
                            ]);
                    }
                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ],
                        [
                            'sub_process' => 'get_county'
                        ]);
                }
                break;
            }
            case 'check_rural':
            {
                $options['text'] .= "خب حالا مشخص کن توی روستا زندگی میکنی یا شهر 🧐";
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '🏡 روستا', 'callback_data' => json_encode([
                                'is_village' => true
                            ])],
                            ['text' => '🏘 شهر', 'callback_data' => json_encode([
                                'is_village' => false
                            ])]
                        ]
                    ]
                ]);
                $this->botService->send('editMessageText', $options, false);
                $this->botService->updateProcessData([
                    'sub_process' => 'check_rural_select'
                ]);
                break;
            }
            case 'check_rural_select' :
            {
                if ($this->botUpdate->detectType() == 'callback_query') {
                    $data = json_decode($this->botUpdate->callbackQuery->data, true);
                    if ($data['is_village']) {
                        $this->botService->handleProcess(null, null, [
                            'sub_process' => 'get_village'
                        ]);
                    } else {
                        $this->botService->handleProcess(null, [
                            'entry' => 'saved'
                        ], [
                            'sub_process' => 'get_mobile'
                        ]);
                    }

                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ],
                        [
                            'sub_process' => 'check_rural'
                        ]);
                }
                break;
            }
            case 'get_village':
            {
                $selectedCounty = County::find($this->botUser->profile->county_id);
                $villages = [22472, 68190, 2748, 7291, 53470, 69601, 71997, 58434];
                $villages = $selectedCounty->villages()->whereIn('id', $villages)->get();
                $villagesKeyboard = [];
                foreach ($villages as $village) {
                    $villagesKeyboard[] = ['text' => $village['name'], 'callback_data' => json_encode([
                        'type' => 'item',
                        'village_id' => $village['id']
                    ])];
                }
                $keyboardLayout = array_values(array_chunk($villagesKeyboard, 2));
                $options['text'] .= 'لطفا روستای محل زندگیت رو انتخاب کن

⚠️ اگه روستای محل زندگیت توی این لیست نیست لطفا از روی متن آبی زیر بزن و اسم روستا رو بنویس و بفرست
/profile_village_send_name';
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' =>
                        array_merge($keyboardLayout, [[
                                ['text' => 'انتخاب مجدد شهر', 'callback_data' => json_encode([
                                    'type' => 'navigate',
                                    'sub_process' => 'get_county'
                                ])
                                ]]]
                        )

                ], JSON_UNESCAPED_UNICODE);
                $this->botService->send('editMessageText', $options, false);
                $this->botService->updateProcessData([
                    'sub_process' => 'get_village_select'
                ]);
                break;
            }
            case "get_village_select":
            {
                if ($this->botUpdate->detectType() == 'callback_query') {
                    $data = json_decode($this->botUpdate->callbackQuery->data, true);
                    if ($data['type'] == 'item') {
                        if (isset($data['village_id'])) {
                            $this->botUser->profile->village_id = $data['village_id'];
                            $this->botUser->profile->save();
                            $this->botService->handleProcess(null, [
                                'entry' => 'saved'
                            ], [
                                'sub_process' => 'get_mobile'
                            ]);
                        } else
                            goto isInvalidSelectedVillage;
                    } else if ($data['type'] == 'navigate') {
                        $this->botService->handleProcess(null, [
                            'entry' => 'custom_message',
                            'message' => 'انتخاب مجدد شهر
                            '
                        ], [
                            'sub_process' => 'get_county'
                        ]);
                    }
                } else {
                    isInvalidSelectedVillage:
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ],
                        [
                            'sub_process' => 'get_village'
                        ]);
                }
                break;
            }
            case "get_mobile":
            {
                $options['text'] .= '📱جهت بهره مندی از خدمات و مزایا، لطفا  شماره خود را از طریق دکمه پایین صفحه ارسال نمایید';
                $options['reply_markup'] = json_encode([
                    'keyboard' => [
                        [
                            [
                                'text' => '📱 ارسال شماره',
                                'request_contact' => true
                            ]
                        ]
                    ],
                    'resize_keyboard' => true
                ]);
                $this->botService->send('editMessageText', $options, false);
                $this->botService->updateProcessData([
                    'sub_process' => 'get_mobile_input'
                ]);
                break;
            }
            case 'get_mobile_input':
            {
                if ($this->botUpdate->getMessage()->detectType() == 'contact') {
                    $this->botUser->profile->mobile_number = $this->botUpdate->getMessage()->contact->phoneNumber;
                    $this->botUser->profile->save();
                    $this->botService->handleProcess(BOT_PROCESS__MAIN, [
                        'entry' => 'newUserCompleteProfile'
                    ]);
                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ],
                        [
                            'sub_process' => 'get_mobile'
                        ]);
                }
                break;
            }
        }
    }

    function edit_profile ($params = null)
    {
        $sub_process = $this->botUser->currentProcess->pivot->sub_process;
        $send = false;
        $back = false;
        $main = false;
        $options = [];
        $options['text'] = "";
        $cancelButton = true;


        if ($params && isset($params['entry'])) {
            $entryName = strtoupper($params['entry']);
            $options['text'] .= constant("BOT_MESSAGE__ENTRY__$entryName");
        }

        switch ($sub_process) {
            default:
            {
                $options['text'] .= 'لطفا یکی از مشخصه هایی که میخوای ویرایش کنی رو از منوی زیر انتخاب کن، توی روند ویرایش اگه خواستی انصراف بدی فقط کافیه روی دکمه "❌ انصراف" بزنی 🙂';
                $cancelButton = false;
                $send = true;
                $back = true;
                $main = true;
                $keyboard[][] = [
                    'text' => '✏️ ویرایش نام و نام خانوادگی',
                    'callback_data' => json_encode([
                        'sub_process' => 'fullName'
                    ])
                ];
                $keyboard[][] = [
                    'text' => '✏️ ویرایش نوع کاربری (کشاورز یا کارگر)',
                    'callback_data' => json_encode([
                        'sub_process' => 'manualWorker'
                    ])
                ];
                $keyboard[][] = [
                    'text' => '✏️ ویرایش موقعیت مکانی',
                    'callback_data' => json_encode([
                        'sub_process' => 'location'
                    ])
                ];
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => $keyboard
                ]);
                break;
            }
            case "location":
            {
                $options['text'] .= "🏠 ویرایش موقعیت مکانی

لطفا از لیست زیر شهرت رو انتخاب کن 🔰";
                $cities = City::whereIn('id', [240, 555, 1110, 1174, 160])->get();
                $citiesKeyboard = [];
                foreach ($cities as $city) {
                    $citiesKeyboard[] = ['text' => $city['name'], 'callback_data' => json_encode([
                        'county_id' => $city->county->id,
                        'city_id' => $city->id
                    ])];
                }
                $keyboardLayout = array_values(array_chunk($citiesKeyboard, 2));
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => $keyboardLayout
                ]);
                $this->botService->updateProcessData([
                    'sub_process' => 'get_county_select'
                ]);
                $send = true;
                break;
            }
            case "get_county_select":
            {
                if ($this->botUpdate->detectType() == 'callback_query') {
                    $data = json_decode($this->botUpdate->callbackQuery->data, true);
                    if ($data['county_id'] == 'other') {
                        $this->botService->send('editMessageText', [
                            'text' => 'در بروز رسانی های بعدی شهر های بیشتری در اختیار شما قرار می گیرد 😊

                            در حال بازگشت به منو...'
                        ]);
                        sleep(2);
                        $this->botService->handleProcess(null, null,
                            [
                                'sub_process' => 'location'
                            ]);
                    } else {
                        $this->botUser->profile->county_id = $data['county_id'];
                        $this->botUser->profile->city_id = $data['city_id'];
                        $this->botUser->profile->save();
                        $this->botService->handleProcess(null, [
                            'entry' => 'custom_message',
                            'message' => '🤏دیگه چیزی نمونده
                            '
                        ],
                            [
                                'sub_process' => 'check_rural'
                            ]);
                    }
                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ],
                        [
                            'sub_process' => 'location'
                        ]);
                }
                break;
            }
            case 'check_rural':
            {
                $options['text'] .= "خب حالا مشخص کن توی روستا زندگی میکنی یا شهر 🧐";
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '🏡 روستا', 'callback_data' => json_encode([
                                'is_village' => true
                            ])],
                            ['text' => '🏘 شهر', 'callback_data' => json_encode([
                                'is_village' => false
                            ])]
                        ]
                    ]
                ]);
                $this->botService->updateProcessData([
                    'sub_process' => 'check_rural_select'
                ]);
                $send = true;
                $cancelButton = false;
                break;
            }
            case 'check_rural_select' :
            {
                if ($this->botUpdate->detectType() == 'callback_query') {
                    $data = json_decode($this->botUpdate->callbackQuery->data, true);
                    if ($data['is_village']) {
                        $this->botService->handleProcess(null, null, [
                            'sub_process' => 'get_village'
                        ]);
                    } else {
                        $this->botUser->profile->village_id = null;
                        $this->botUser->profile->save();
                        $this->botService->handleProcess(null, [
                            'entry' => 'saved'
                        ], [
                            'sub_process' => ''
                        ]);
                    }

                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ],
                        [
                            'sub_process' => 'check_rural'
                        ]);
                }
                break;
            }
            case 'get_village':
            {
                $selectedCounty = County::find($this->botUser->profile->county_id);
                $villages = [22472, 68190, 2748, 7291, 53470, 69601, 71997, 58434];
                $villages = $selectedCounty->villages()->whereIn('id', $villages)->get();
                $villagesKeyboard = [];
                foreach ($villages as $village) {
                    $villagesKeyboard[] = ['text' => $village['name'], 'callback_data' => json_encode([
                        'type' => 'item',
                        'village_id' => $village['id']
                    ])];
                }
                $keyboardLayout = array_values(array_chunk($villagesKeyboard, 2));
                $options['text'] .= 'لطفا روستای محل زندگیت رو انتخاب کن

⚠️ اگه روستای محل زندگیت توی این لیست نیست لطفا از روی متن آبی زیر بزن و اسم روستا رو بنویس و بفرست
/profile_village_send_name';
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' =>
                        array_merge($keyboardLayout, [[
                                ['text' => 'انتخاب مجدد شهر', 'callback_data' => json_encode([
                                    'type' => 'navigate',
                                    'sub_process' => 'get_county'
                                ])
                                ]]]
                        )

                ]);
                $send = true;
                $cancelButton = false;
                $this->botService->updateProcessData([
                    'sub_process' => 'get_village_select'
                ]);
                break;
            }
            case "get_village_select":
            {
                if ($this->botUpdate->detectType() == 'callback_query') {
                    $data = json_decode($this->botUpdate->callbackQuery->data, true);
                    if ($data['type'] == 'item') {
                        if (isset($data['village_id'])) {
                            $this->botUser->profile->village_id = $data['village_id'];
                            $this->botUser->profile->save();
                            $this->botService->handleProcess(null, [
                                'entry' => 'saved'
                            ], [
                                'sub_process' => ''
                            ]);
                        } else
                            goto isInvalidSelectedVillage;
                    } else if ($data['type'] == 'navigate') {
                        $this->botService->handleProcess(null, [
                            'entry' => 'custom_message',
                            'message' => 'انتخاب مجدد شهر
                            '
                        ], [
                            'sub_process' => 'get_county'
                        ]);
                    }
                } else {
                    isInvalidSelectedVillage:
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ],
                        [
                            'sub_process' => 'get_village'
                        ]);
                }
                break;
            }
            case 'fullName': {
                $options['text'] .= "
                لطفا نام و نام خانوادگیت رو ارسال کن";
                $this->botService->updateProcessData([
                    'sub_process' => 'get_fullName_input'
                ]);
                $send = true;
                break;
            }
            case 'get_fullName_input':
            {
                if ($this->botUpdate->detectType() == 'message') {
                    if (strlen($this->botUpdate->getMessage()->text) > 5) {
                        $this->botUser->profile->full_name = $this->botUpdate->getMessage()->text;
                        $this->botUser->profile->save();
                        $this->botService->handleProcess(null, [
                            'entry' => 'saved'
                        ], [
                            'sub_process' => ''
                        ]);
                    } else
                        goto invalidFullNameData;
                } else {
                    invalidFullNameData:
                    $this->botService->send('sendMessage', [
                        'text' => '❌ مقدار ارسالی نا معتبره، لطفا دقت کن
مثال: مهدی باقری'
                    ]);
                }
                break;
            }
            case 'manualWorker':
            {
                $options['text'] .= 'خب، حالا مشخص کن 👷🏻‍♂️ کارگر هستی یا 👨🏻‍🌾 کشاورز 😊';
                $options['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '👷🏻‍♂️ کارگر', 'callback_data' => json_encode([
                                'is_manual_worker' => true
                            ])],
                            ['text' => '👨🏻‍🌾 کشاورز', 'callback_data' => json_encode([
                                'is_manual_worker' => false
                            ])]
                        ]
                    ]
                ]);
                $this->botService->updateProcessData([
                    'sub_process' => 'is_manual_worker_select'
                ]);
                $send = true;
                break;
            }
            case 'is_manual_worker_select':
            {
                if ($this->botUpdate->detectType() == 'callback_query') {
                    $isWorker = (json_decode($this->botUpdate->callbackQuery->data, true))['is_manual_worker'];
                    $this->botUser->profile->is_manual_worker = $isWorker;
                    $this->botUser->profile->save();
                    $this->botService->handleProcess(null, [
                        'entry' => 'saved'
                    ], [
                        'sub_process' => ''
                    ]);
                } else {
                    $this->botService->handleProcess(null, [
                        'entry' => 'invalid'
                    ],
                        [
                            'sub_process' => 'manualWorker'
                        ]);
                }
                break;
            }
        }
        if ($cancelButton) {
            $options = $this->botService->appendInlineKeyboardButton($options, [
                'text' => '❌ انصراف',
                'callback_data' => json_encode([
                    'sub_process' => ""
                ])
            ]);
        }
        if ($send)
            $this->botService->send('editMessageText', $options, $back, $main);
    }

    function about ()
    {
        $this->botService->send('editMessageText', [
            'text' => BOT_MESSAGE__ABOUT_SUNAAB
        ]);
    }

    function contact ()
    {
        $this->botService->send('editMessageText', [
            'text' => BOT_MESSAGE__CONTACT_SUNAAB
        ]);
    }
}
