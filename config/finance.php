<?php

return [
    // Transfer destination account shown to parents.
    'bank' => [
        'name' => env('FINANCE_BANK_NAME', 'BCA'),
        'account_number' => env('FINANCE_BANK_ACCOUNT', '1234567890'),
        'account_name' => env('FINANCE_BANK_HOLDER', 'PT Alprogmatics Edukasi'),
    ],
];
