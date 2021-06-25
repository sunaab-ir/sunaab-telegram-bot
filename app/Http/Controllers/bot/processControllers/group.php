<?php

namespace App\Http\Controllers\bot\processControllers;

use App\Http\Controllers\Controller;
use App\Models\m118;
use App\Services\bot\botService;
use Illuminate\Http\Request;

class group extends Controller
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

    function handleGroupUpdate ()
    {
        switch ($this->botUpdate->detectType()) {
            case 'message':
                $this->handleMessageUpdate();
                break;
        }
    }

    ////// Message Update
    function handleMessageUpdate ()
    {
        switch ($this->botUpdate->message->detectType()) {
            case 'text':
                $this->handleMessageTextUpdate();
                break;
            case 'contact':
                $this->handleMessageContactUpdate();
                break;
        }
    }

    function handleMessageTextUpdate ()
    {
        if ($this->botUpdate->message->hasCommand()) {
            $this->handleMessageCommandUpdate();
        } else {
            echo "no command";
        }
    }

    function handleMessageContactUpdate ()
    {
        print_r($this->botUpdate);
    }

    function handleMessageCommandUpdate ()
    {
        $command = $this->botUpdate->getMessage()->entities[0];
        $commandName = trim(substr($this->botUpdate->getMessage()->text, $command['offset'] + 1, $command['length']));
        $commandValue = trim(substr($this->botUpdate->getMessage()->text, $command['length'], strlen($this->botUpdate->getMessage()->text)));
        switch ($commandName) {
            case '118':
            {
                if ($this->botUser->profile) {
                    if ($commandValue) {
                        if (strpos($this->botUpdate->message->text, "\n")) {
                            $detail = explode("\n", $commandValue);

                            if (strlen(@$detail[0]) > 3 && preg_match("/^[+\u0600-\u06FF\s0-9]+\w{10}$/", @$detail[1])) {
                                $full_name = $detail[0];
                                $mobile = $detail[1];
                                $contact = m118::where("full_name", $full_name)->first();
                                if (!$contact) {
                                    $contact = new m118();
                                    $contact->full_name = $full_name;
                                    $contact->number = $mobile;
                                    if ($contact->save()) {
                                        $options['text'] = "مخاطب، " . $full_name . " با شماره " . $mobile . " با موفقیت در بخش 118  ربات ساناب ثبت شد\n\n بعدا برای دریافت شماره این مخاطب تنها نیاز است دقیقا متن زیر را بفرستید:\n\n/118 $full_name";
                                        $options['chat_id'] = $this->botUpdate->message->chat->id;
                                        $options['disable_notification'] = true;
                                        $options['reply_to_message_id'] = $this->botUpdate->message->messageId;
                                        $this->botService->sendBase('sendMessage', $options);
                                        if ($this->botUpdate->message->replyToMessage) {
                                            $options = [];
                                            $options['reply_to_message_id'] = $this->botUpdate->message->replyToMessage->messageId;
                                            $options['chat_id'] = $this->botUpdate->message->chat->id;
                                            $options['vcard'] = "شماره تماس " . $commandValue;
                                            $options['phone_number'] = $contact->number;
                                            $options['disable_notification'] = true;
                                            $options['first_name'] = $contact->full_name;
                                            $this->botService->sendBase('sendContact', $options);
                                        }
                                    }
                                } else {
                                    $options['text'] = "این مخاطب در بخش 118 ربات ساناب وجود دارد، برای دریافت شماره، کافیست متن زیر را به تنهایی ارسال کنید\n\n" . "/118 " . $full_name;
                                    $options['chat_id'] = $this->botUpdate->message->chat->id;
                                    $options['disable_notification'] = true;
                                    $options['reply_to_message_id'] = $this->botUpdate->message->messageId;
                                    $this->botService->sendBase('sendMessage', $options);
                                }
                            } else {

                                $options['chat_id'] = $this->botUpdate->message->chat->id;
                                $options['reply_to_message_id'] = $this->botUpdate->message->messageId;
                                $options['disable_notification'] = true;
                                $options['text'] = "کاربر محترم، " . $this->botUser->first_name . "\n\n";
                                $options['text'] .= "فرمت ارسالی شما برای ثبت در 118 روستای جابوز صحیح نیست
مثال:

‏/118
ابوالفضل اکبرزاده
09033292307‌‏

.";
                                $this->botService->sendBase('sendMessage', $options);
                            }
                        } else {
                            if (strlen($commandValue) > 4) {
                                $contact118 = m118::where("full_name", "like", "%$commandValue%")->get();
                                if ($contact118) {
                                    if (count($contact118) == 1) {
                                        $contact['chat_id'] = $this->botUpdate->message->chat->id;
                                        if ($this->botUpdate->message->replyToMessage)
                                            $contact['reply_to_message_id'] = $this->botUpdate->message->replyToMessage->messageId;
                                        else
                                            $contact['reply_to_message_id'] = $this->botUpdate->message->messageId;
                                        $contact['vcard'] = "شماره تماس " . $commandValue;
                                        $contact['phone_number'] = $contact118[0]->number;
                                        $contact['first_name'] = $commandValue;
                                        $this->botService->sendBase('sendContact', $contact);
                                    } else {
                                        $contacts['text'] = "نتیجه شما چند مخاطب دارد، لطفا مخاطب مد نظر را انتخاب کنید\n\n";
                                        foreach ($contact118 as $item) {
                                            $full_name = $item->full_name;
                                            $number = $item->number;
                                            $contacts['text'] .= "👤 نام مخاطب: $full_name\n📲 شماره مخاطب: $number\n\n";
                                        }
                                        $contacts['text'] .= "اگر شماره مد نظر در لیست وجود ندارد، به این معناست که در 118 ربات ساناب ثبت نشده، برای ثبت مخاطب در 118 ربات ساناب، باید نام و نام خانوادگی و شماره را به فرمت زیر بفرستید\n\nمثال:

‏/118
ابوالفضل اکبرزاده
09033292307‌‏

.";
                                        $contacts['chat_id'] = $this->botUpdate->message->chat->id;
                                        if (isset($this->botUpdate->message->replyToMessage))
                                            $contacts['reply_to_message_id'] = $this->botUpdate->message->replyToMessage->messageId;
                                        else
                                            $contacts['reply_to_message_id'] = $this->botUpdate->message->messageId;
                                        $this->botService->sendBase('sendMessage', $contacts);
                                    }
                                }
                            } else {
                                $options['text'] = 'لطفا نام مخاطب را جهت جستجو به درستی وارد کنید';
                                $options['reply_to_message_id'] = $this->botUpdate->message->messageId;
                                $options['chat_id'] = $this->botUpdate->message->chat->id;
                                $options['disable_notification'] = true;
                                $this->botService->sendBase('sendMessage', $options);
                            }
                        }
                    } else {
                        $options['text'] = "☎️ بخش 118 ربات ساناب روستای جابوز\n\nجهت استفاده از 118، از دستور العمل زیر استفاده کنید\n\nدریافت شماره مخاطب:  🔰\n\n1- /118 نام و نام خانوادگی مخاطب\n2- ارسال\n\nثبت مخاطب در 118: 🔰\n\n1- /118‏\nنام و نام خانوادگی\nشماره مخاطب\n2- ارسال";
                        $options['reply_to_message_id'] = $this->botUpdate->message->messageId;
                        $options['disable_notification'] = true;
                        $options['chat_id'] = $this->botUpdate->message->chat->id;
                        $this->botService->sendBase('sendMessage', $options);
                    }
                }else {
                    $options['text'] = "⛔️ برای استفاده از 118 جابوز ربات ساناب، باید در ربات ثبت نام کامل انجلم دهید، برای ثبت نام لطفا از لینک زیر اقدام کنید\nhttps://t.me/sunaab_bot?start=12";
                    $options['reply_to_message_id'] = $this->botUpdate->message->messageId;
                    $options['chat_id'] = $this->botUpdate->message->chat->id;
                    $options['disable_notification'] = true;
                    $this->botService->sendBase('sendMessage', $options);
                }
                break;
            }
        }
    }
    ////// Message Update</>
}
