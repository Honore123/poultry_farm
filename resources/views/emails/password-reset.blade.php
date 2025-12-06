<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
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
            background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%);
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
            margin-bottom: 30px;
        }
        
        .alert-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            padding: 15px 20px;
            border-radius: 0 12px 12px 0;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-box span {
            font-size: 24px;
        }
        
        .alert-box p {
            color: #92400e;
            font-size: 14px;
            font-weight: 500;
        }
        
        .button-container {
            text-align: center;
            margin: 35px 0;
        }
        
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 10px 30px rgba(5, 150, 105, 0.3);
            transition: all 0.3s ease;
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(5, 150, 105, 0.4);
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
            margin: 30px 0;
        }
        
        .features {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .feature {
            text-align: center;
            flex: 1;
            min-width: 100px;
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 24px;
        }
        
        .feature p {
            color: #6b7280;
            font-size: 12px;
            font-weight: 500;
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
            color: #059669;
            text-decoration: none;
        }
        
        .link-text {
            background: #f3f4f6;
            padding: 12px 15px;
            border-radius: 8px;
            margin-top: 20px;
            word-break: break-all;
            font-size: 11px;
            color: #6b7280;
        }
        
        .expire-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fef2f2;
            color: #dc2626;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <div class="logo">
                    <span>🐔</span>
                </div>
                <h1>{{ config('app.name') }}</h1>
                <p>Poultry Farm Management System</p>
            </div>
        </div>
        
        <div class="content">
            <p class="greeting">Hello {{ $user->name }}! 👋</p>
            
            <p class="message">
                We received a request to reset the password for your account. 
                No worries – it happens to the best of us! Click the button below 
                to create a new password and get back to managing your farm.
            </p>
            
            <div class="alert-box">
                <span>🔐</span>
                <p>For your security, this link will expire in <strong>60 minutes</strong>.</p>
            </div>
            
            <div class="button-container">
                <a href="{{ $resetUrl }}" class="button">
                    🔑 Reset My Password
                </a>
            </div>
            
            <div class="divider"></div>
            
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">🥚</div>
                    <p>Track Production</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">🌾</div>
                    <p>Manage Feed</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">📊</div>
                    <p>View Reports</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">💰</div>
                    <p>Track Sales</p>
                </div>
            </div>
            
            <p class="message" style="font-size: 13px; color: #9ca3af; text-align: center;">
                If you didn't request this password reset, you can safely ignore this email. 
                Your password will remain unchanged.
            </p>
        </div>
        
        <div class="footer">
            <p>
                Having trouble with the button? Copy and paste this link into your browser:
            </p>
            <div class="link-text">
                {{ $resetUrl }}
            </div>
            <p style="margin-top: 20px;">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                <a href="{{ url('/') }}">Visit our website</a>
            </p>
        </div>
    </div>
</body>
</html>

