<?php

namespace App\Http\Controllers;

use App\Payments\RefundPayouts;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RefundCallbackController extends Controller
{
    public function result(Request $request, string $reference, RefundPayouts $payouts): Response
    {
        if (app()->environment('local', 'testing')) {
            $payouts->handleResult($reference, $request->all());
        }

        return response()->noContent();
    }

    public function timeout(string $reference, RefundPayouts $payouts): Response
    {
        if (app()->environment('local', 'testing')) {
            $payouts->handleTimeout($reference);
        }

        return response()->noContent();
    }
}
