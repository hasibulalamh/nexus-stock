<?php

return [
    'allow_negative' => false,
    'approval' => [
        'allow_self_approval' => env('STOCK_ALLOW_SELF_APPROVAL', true),
    ],
    'notifications' => [
        'mail' => env('STOCK_MAIL_NOTIFICATIONS', false),
    ],
    'documents' => [
        'transfer_prefix' => 'TRF',
        'purchase_prefix' => 'PO',
        'receipt_prefix' => 'GRN',
        'adjustment_prefix' => 'ADJ',
        'count_prefix' => 'CNT',
    ],
    'purchases' => [
        // When enabled, purchase orders must be approved before ordering.
        'require_approval' => env('STOCK_PO_REQUIRE_APPROVAL', false),
        // Over-receipt beyond the ordered quantity is rejected unless enabled.
        'allow_over_receipt' => env('STOCK_ALLOW_OVER_RECEIPT', false),
        // off | last | weighted_average
        'cost_update' => env('STOCK_PO_COST_UPDATE', 'off'),
    ],
    'counts' => [
        // Require an explicit acknowledgement when stock moved after the snapshot.
        'require_drift_ack' => true,
    ],
];
