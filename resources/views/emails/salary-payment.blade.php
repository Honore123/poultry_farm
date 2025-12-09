<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Payment Confirmation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 50%, #155e75 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-50%, -50%); }
        }
        
        .logo-container {
            position: relative;
            z-index: 1;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .logo span {
            font-size: 40px;
        }
        
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }
        
        .header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            position: relative;
            z-index: 1;
        }
        
        .payment-banner {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            padding: 25px 30px;
            text-align: center;
            border-bottom: 1px solid #6ee7b7;
        }
        
        .payment-banner h2 {
            color: #065f46;
            font-size: 22px;
            margin-bottom: 5px;
        }
        
        .payment-banner p {
            color: #047857;
            font-size: 14px;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .message {
            color: #4b5563;
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 25px;
        }
        
        .payment-summary {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 25px;
        }
        
        .payment-summary-header {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .payment-details {
            padding: 0;
        }
        
        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .payment-row:last-child {
            border-bottom: none;
        }
        
        .payment-row.highlight {
            background: #ecfdf5;
        }
        
        .payment-row .label {
            color: #6b7280;
            font-size: 14px;
        }
        
        .payment-row .value {
            color: #1f2937;
            font-weight: 600;
            font-size: 14px;
        }
        
        .payment-row.bonus .value {
            color: #059669;
        }
        
        .payment-row.deduction .value {
            color: #dc2626;
        }
        
        .payment-row.total {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        .payment-row.total .label,
        .payment-row.total .value {
            color: white;
            font-size: 16px;
            font-weight: 700;
        }
        
        .method-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #e0f2fe;
            color: #0369a1;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        
        .method-badge span {
            font-size: 20px;
        }
        
        .method-badge p {
            font-size: 14px;
        }
        
        .method-badge strong {
            color: #0284c7;
        }
        
        .period-info {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            padding: 15px 20px;
            border-radius: 0 12px 12px 0;
            margin-bottom: 25px;
        }
        
        .period-info p {
            color: #92400e;
            font-size: 14px;
        }
        
        .period-info strong {
            color: #b45309;
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
            margin: 30px 0;
        }
        
        .reference-box {
            background: #f3f4f6;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        
        .reference-box p {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 5px;
        }
        
        .reference-box .ref-number {
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }
        
        .footer {
            background: #f9fafb;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer p {
            color: #9ca3af;
            font-size: 12px;
            line-height: 1.6;
        }
        
        .footer a {
            color: #0891b2;
            text-decoration: none;
        }
        
        .contact-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }
        
        .contact-info p {
            color: #6b7280;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <div class="logo">
                    <span>💰</span>
                </div>
                <h1>{{ config('app.name') }}</h1>
                <p>Salary Payment Notification</p>
            </div>
        </div>
        
        <div class="payment-banner">
            <h2>✅ Payment Confirmed!</h2>
            <p>Your salary for {{ $payment->payment_period }} has been processed</p>
        </div>
        
        <div class="content">
            <p class="greeting">Hello {{ $employee->employee_name }}! 👋</p>
            
            <p class="message">
                Great news! Your salary payment has been processed and confirmed. 
                Below are the details of your payment for this period.
            </p>
            
            <div class="period-info">
                <p>📅 Payment Period: <strong>{{ $payment->payment_period }}</strong></p>
            </div>
            
            <div class="payment-summary">
                <div class="payment-summary-header">
                    💵 Payment Breakdown
                </div>
                <div class="payment-details">
                    <div class="payment-row">
                        <span class="label">Base Salary</span>
                        <span class="value">RWF {{ number_format($payment->base_salary, 0) }}</span>
                    </div>
                    
                    @if($payment->bonus > 0)
                        <div class="payment-row bonus highlight">
                            <span class="label">➕ Bonus</span>
                            <span class="value">+ RWF {{ number_format($payment->bonus, 0) }}</span>
                        </div>
                    @endif
                    
                    @if($payment->deductions > 0)
                        <div class="payment-row deduction">
                            <span class="label">➖ Deductions</span>
                            <span class="value">- RWF {{ number_format($payment->deductions, 0) }}</span>
                        </div>
                    @endif
                    
                    <div class="payment-row total">
                        <span class="label">NET PAYMENT</span>
                        <span class="value">RWF {{ number_format($payment->net_amount, 0) }}</span>
                    </div>
                </div>
            </div>
            
            <div class="method-badge">
                <span>
                    @if($payment->payment_method === 'cash')
                        💵
                    @elseif($payment->payment_method === 'bank_transfer')
                        🏦
                    @elseif($payment->payment_method === 'mobile_money')
                        📱
                    @else
                        💳
                    @endif
                </span>
                <p>Payment Method: <strong>{{ $paymentMethod }}</strong></p>
            </div>
            
            @if($payment->reference)
                <div class="reference-box">
                    <p>Transaction Reference:</p>
                    <span class="ref-number">{{ $payment->reference }}</span>
                </div>
            @endif
            
            <div class="divider"></div>
            
            <p class="message" style="font-size: 14px;">
                <strong>📋 Payment Date:</strong> {{ $payment->payment_date->format('F d, Y') }}
            </p>
            
            @if($payment->notes)
                <div class="reference-box" style="margin-top: 15px;">
                    <p>📝 Notes:</p>
                    <span class="ref-number">{{ $payment->notes }}</span>
                </div>
            @endif
            
            <p class="message" style="font-size: 13px; color: #9ca3af; text-align: center; margin-top: 25px;">
                If you have any questions about this payment, please contact the HR/Admin department.
            </p>
        </div>
        
        <div class="footer">
            <p>
                This is an automated payment notification from {{ config('app.name') }}.
            </p>
            <div class="contact-info">
                <p>
                    You can view your complete payment history by logging into your account.
                </p>
            </div>
            <p style="margin-top: 20px;">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                <a href="{{ url('/admin/my-salary') }}">View My Salary History</a>
            </p>
        </div>
    </div>
</body>
</html>

