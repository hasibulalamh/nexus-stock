<?php

return [
    'allow_negative' => false,
    'approval' => [
        'allow_self_approval' => env('STOCK_ALLOW_SELF_APPROVAL', true),
    ],
    'notifications' => [
        'mail' => env('STOCK_MAIL_NOTIFICATIONS', false),
    ],
];
