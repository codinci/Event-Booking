<?php

namespace App\Enums;

enum RefundReason: string
{
    case LatePayment = 'late_payment';
    case CustomerCancelled = 'customer_cancelled';
    case EventCancelled = 'event_cancelled';
}
