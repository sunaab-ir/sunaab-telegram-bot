<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class drivers extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $drivers = [
            [
                'name' => "عباس موسوی",
                'type' => 'پیکان وانت',
                'mobile' => '09158711347',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "محمد پاسبان",
                'type' => 'ثبت نشده',
                'mobile' => '09156948540',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "هادی میزبان",
                'type' => 'مزدا',
                'mobile' => '09159317102',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "حسین رمضانی",
                'type' => 'پیکان وانت',
                'mobile' => '09159306542',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "رضا جبرائیلی",
                'other' => 'فقط تربت حیدریه',
                'type' => 'ثبت نشده',
                'mobile' => '09158017523',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "حجت پاسبان",
                'type' => 'پیکان وانت',
                'mobile' => '09159334329',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "ایمان رفیعی",
                'type' => 'پیکان وانت',
                'mobile' => '09158524403',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "علی هوشمند",
                'type' => 'نیسان',
                'mobile' => '09151368600',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "جهان ارا",
                'type' => 'پیکان وانت',
                'mobile' => '09030298797',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "رضا رجایی",
                'type' => 'نیسان',
                'mobile' => '09153332638',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "حسین کارگر",
                'type' => 'نیسان',
                'mobile' => '09153332581',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "محمد منیری",
                'type' => 'پیکان وانت',
                'mobile' => '09159302478',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "محمد توکلی",
                'type' => 'پیکان وانت',
                'mobile' => '09355237582',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "امیر جمالی",
                'type' => 'پیکان وانت',
                'mobile' => '09106833995 - 09359394262',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "رضا  یوسفیان",
                'type' => 'نیسان',
                'mobile' => '09156252900',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "سعید پهلوانی",
                'type' => 'پیکان وانت',
                'mobile' => '09159636735',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "جواد عاکف",
                'type' => 'نیسان',
                'mobile' => '09156284497',
                'data_type' => 'load_driver'
            ],
            [
                'name' => "حسین احمدیان",
                'type' => 'پیکان وانت',
                'mobile' => '09306329737',
                'data_type' => 'load_driver'
            ]
        ];
        foreach ($drivers as $driver) {
            DB::table('other_data')->insert([
                'name' => $driver['name'],
                'data' => json_encode($driver, JSON_UNESCAPED_UNICODE)
            ]);
        }
    }
}
