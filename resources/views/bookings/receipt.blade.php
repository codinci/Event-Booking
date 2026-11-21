<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $attempt->receipt_number }}</title>
</head>
<body>
    <main>
        <h1>Sandbox payment receipt</h1>
        <dl>
            <dt>Receipt</dt><dd>{{ $attempt->receipt_number }}</dd>
            <dt>Booking</dt><dd>{{ $booking->reference }}</dd>
            <dt>Event</dt><dd>{{ $booking->event->title }}</dd>
            <dt>Amount</dt><dd>{{ $attempt->currency }} {{ number_format($attempt->amount / 100, 2) }}</dd>
        </dl>
    </main>
</body>
</html>
