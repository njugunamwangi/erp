<?php

declare(strict_types=1);

return [
    'username' => env('AFRICASTALKING_USERNAME', 'sandbox'),
    'api-key' => env('AFRICASTALKING_API_KEY'),
    'sms' => [
        'from' => env('AFRICASTALKING_FROM'),
    ],
    'payment' => [
        'product-name' => env('AFRICASTALKING_PAYMENT_PRODUCT'),
    ],
    'voice' => [
        'from' => env('AFRICASTALKING_VOICE_PHONE_NUMBER'),
    ]
];
