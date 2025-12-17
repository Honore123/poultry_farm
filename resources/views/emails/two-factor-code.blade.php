<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
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
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #d97706;
            margin: 0;
            font-size: 24px;
        }
        .code-container {
            background-color: #fef3c7;
            border: 2px dashed #d97706;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #92400e;
            font-family: 'Courier New', monospace;
        }
        .message {
            text-align: center;
            color: #666;
        }
        .warning {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin-top: 30px;
            font-size: 14px;
            color: #991b1b;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Login Verification</h1>
        </div>
        
        <p class="message">
            Hello <strong>{{ $user->name }}</strong>,<br>
            Use the following code to complete your login:
        </p>
        
        <div class="code-container">
            <div class="code">{{ $code }}</div>
        </div>
        
        <p class="message">
            This code will expire in <strong>10 minutes</strong>.
        </p>
        
        <div class="warning">
            <strong>⚠️ Security Notice:</strong> If you did not attempt to log in, please ignore this email and consider changing your password immediately.
        </div>
        
        <div class="footer">
            <p>This is an automated message from the Poultry Farm Management System.</p>
        </div>
    </div>
</body>
</html>

