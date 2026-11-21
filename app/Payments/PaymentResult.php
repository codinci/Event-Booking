<?php

namespace App\Payments;

enum PaymentResult
{
    case Pending;
    case Succeeded;
    case Failed;
}
