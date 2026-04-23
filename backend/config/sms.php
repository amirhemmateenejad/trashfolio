<?php
return [

    'default' => env('SMS_DRIVER', 'melipyamak'),

    'drivers' => [
        'ghasedak' => [
            'api_key' => env('GHASEDAK_API_KEY'),
            'sender'  => env('GHASEDAK_SENDER'),
            'base_url' => 'https://api.ghasedaksms.com/v2',
            'group_id' => env('GHASEDAK_GROUP_ID'),
            'otp_template'=>env('GHASEDAK_OTP_TEMPLATE')
        ],
        'melipyamak'=>[
            'username' => env("MELIPAYAMAK_USERNAME"),
            'password' => env("MELIPAYAMAK_PASSWORD"),
            'group_id' => env('MELIPAYAMAK_GROUP_ID'),
            'otp_template'=> env('MELIPAYAMAK_OTP_TEMPLATE'),
            'from_number'=>env('MELIPAYAMAK_FROM')
        ]

    ],

];
