<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Farm Daily Report</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #d97706;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .date {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 25px;
            padding: 15px;
            border-radius: 6px;
        }
        .section-danger {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
        }
        .section-warning {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
        }
        .section-info {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
        }
        .section-success {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
        }
        .section h2 {
            font-size: 16px;
            margin: 0 0 10px 0;
        }
        .section-danger h2 { color: #dc2626; }
        .section-warning h2 { color: #d97706; }
        .section-info h2 { color: #2563eb; }
        .section-success h2 { color: #16a34a; }
        ul {
            margin: 0;
            padding-left: 20px;
        }
        li {
            margin-bottom: 8px;
        }
        .batch-code {
            font-weight: 600;
            color: #1f2937;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-danger {
            background: #fee2e2;
            color: #dc2626;
        }
        .badge-warning {
            background: #fef3c7;
            color: #d97706;
        }
        .badge-info {
            background: #dbeafe;
            color: #2563eb;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .cta-button {
            display: inline-block;
            background: #d97706;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }
        .cta-button:hover {
            background: #b45309;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐔 Farm Daily Report</h1>
        <p class="date">{{ $date->format('l, F j, Y') }}</p>

        @if(count($overdueVaccinations) > 0)
            <div class="section section-danger">
                <h2>🚨 Overdue Vaccinations</h2>
                <ul>
                    @foreach($overdueVaccinations as $data)
                        @foreach($data['vaccinations'] as $vax)
                            <li>
                                <span class="batch-code">{{ $data['batch']->code }}</span>: 
                                {{ $vax['name'] }}
                                <span class="badge badge-danger">{{ $vax['days_overdue'] }} days overdue</span>
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        @endif

        @if(count($missingEntries) > 0)
            <div class="section section-warning">
                <h2>⚠️ Missing Daily Entries</h2>
                <ul>
                    @foreach($missingEntries as $data)
                        <li>
                            <span class="batch-code">{{ $data['batch']->code }}</span> 
                            ({{ $data['batch']->farm->name ?? 'N/A' }}):
                            <br>
                            @foreach($data['missing'] as $entry)
                                <span class="badge badge-warning">{{ $entry }}</span>
                            @endforeach
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(count($upcomingVaccinations) > 0)
            <div class="section section-info">
                <h2>📅 Upcoming Vaccinations (Next 7 Days)</h2>
                <ul>
                    @foreach($upcomingVaccinations as $data)
                        @foreach($data['vaccinations'] as $vax)
                            <li>
                                <span class="batch-code">{{ $data['batch']->code }}</span>: 
                                {{ $vax['name'] }}
                                <span class="badge badge-info">
                                    @if($vax['days_until'] == 0)
                                        TODAY
                                    @else
                                        in {{ $vax['days_until'] }} days ({{ $vax['due_date']->format('M d') }})
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        @endif

        @if(count($overdueVaccinations) == 0 && count($missingEntries) == 0 && count($upcomingVaccinations) == 0)
            <div class="section section-success">
                <h2>✅ All Good!</h2>
                <p>All daily entries are complete and no pending vaccinations.</p>
            </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ url('/admin') }}" class="cta-button">Open Farm Dashboard</a>
        </div>

        <div class="footer">
            <p>This is an automated message from your Poultry Farm Management System.</p>
            <p>Generated at {{ now()->format('H:i') }}</p>
        </div>
    </div>
</body>
</html>

