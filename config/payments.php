<?php

return [
    'hold_minutes' => max(1, (int) env('PAYMENT_HOLD_MINUTES', 5)),
    'review_minutes' => max(1, (int) env('PAYMENT_REVIEW_MINUTES', 15)),
    // Daraja sandbox by default; set DARAJA_BASE_URL to https://api.safaricom.co.ke for live payments.
    'daraja_base_url' => env('DARAJA_BASE_URL', 'https://sandbox.safaricom.co.ke'),
    'daraja' => [
        'key' => env('DARAJA_CONSUMER_KEY'),
        'secret' => env('DARAJA_CONSUMER_SECRET'),
        'shortcode' => env('DARAJA_BUSINESS_SHORTCODE'),
        'passkey' => env('DARAJA_PASSKEY'),
    ],
    'daraja_b2c' => [
        'shortcode' => env('DARAJA_B2C_SHORTCODE'),
        'initiator_name' => env('DARAJA_B2C_INITIATOR_NAME'),
        'security_credential' => env('DARAJA_B2C_SECURITY_CREDENTIAL'),
    ],
    'daraja_callback_url' => env('DARAJA_CALLBACK_URL'),
    // A sent B2C refund with no result callback after this long moves to review.
    'refund_result_minutes' => max(1, (int) env('REFUND_RESULT_MINUTES', 15)),
    // Customers can cancel a paid booking up to this many hours before the event.
    'cancellation_cutoff_hours' => max(0, (int) env('BOOKING_CANCELLATION_CUTOFF_HOURS', 24)),
    'sms' => [
        'username' => env('AFRICASTALKING_USERNAME', 'sandbox'),
        'key' => env('AFRICASTALKING_API_KEY'),
    ],
];
