<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $attempt->receipt_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; padding: 2.5rem 1rem; background: #fff7ed; color: #111827; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { max-width: 42rem; margin: 0 auto; }
        .back { display: inline-flex; align-items: center; gap: .4rem; margin-bottom: 1rem; padding: .5rem 1rem; border: 1px solid #d6d3d1; border-radius: .5rem; background: #fff; color: #44403c; font-size: .875rem; font-weight: 600; text-decoration: none; }
        .back:hover { background: #fafaf9; }
        .card { overflow: hidden; border-radius: .75rem; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
        .head { padding: 2rem 1.5rem; border-bottom: 1px solid #f3f4f6; text-align: center; }
        .badge { display: inline-flex; width: 4rem; height: 4rem; align-items: center; justify-content: center; border-radius: 9999px; background: #ffedd5; color: #ea580c; font-size: 1.5rem; }
        h1 { margin: 1.25rem 0 0; font-size: 1.75rem; }
        dl { display: grid; grid-template-columns: max-content 1fr; gap: .75rem 1.5rem; margin: 0; padding: 1.5rem; }
        dt { color: #6b7280; font-size: .875rem; }
        dd { margin: 0; font-weight: 600; text-align: right; }
        .actions { display: flex; gap: .75rem; padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6; }
        .btn { padding: .5rem 1rem; border: 0; border-radius: .5rem; background: #f97316; color: #fff; font-size: .875rem; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #ea580c; }
        @media print { .back, .actions { display: none; } body { background: #fff; padding: 0; } }
    </style>
</head>
<body>
    <main>
        @isset($backUrl)
            <a href="{{ $backUrl }}" class="back">&larr; Back to booking</a>
        @endisset

        <div class="card">
            <div class="head">
                <div class="badge">&#10003;</div>
                <h1>Sandbox payment receipt</h1>
            </div>
            <dl>
                <dt>Receipt</dt><dd>{{ $attempt->receipt_number }}</dd>
                <dt>Booking</dt><dd>{{ $booking->reference }}</dd>
                <dt>Event</dt><dd>{{ $booking->event->title }}</dd>
                <dt>Amount</dt><dd>{{ $attempt->currency }} {{ number_format($attempt->amount / 100, 2) }}</dd>
            </dl>
            @isset($backUrl)
                <div class="actions">
                    <button type="button" class="btn" onclick="window.print()">Print receipt</button>
                </div>
            @endisset
        </div>
    </main>
</body>
</html>
