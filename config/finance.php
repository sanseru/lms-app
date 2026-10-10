<?php

return [
    // Rekening tujuan transfer yang ditampilkan ke orang tua.
    'bank' => [
        'name' => env('FINANCE_BANK_NAME', 'BCA'),
        'account_number' => env('FINANCE_BANK_ACCOUNT', '1234567890'),
        'account_name' => env('FINANCE_BANK_HOLDER', 'PT Alprogmatics Edukasi'),
    ],
];
