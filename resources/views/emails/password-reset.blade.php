<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 30px;
            border: 1px solid #e0e0e0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4A5568;
            margin: 0;
        }
        .content {
            background-color: white;
            padding: 25px;
            border-radius: 6px;
        }
        .button {
            display: inline-block;
            background-color: #48BB78;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #38A169;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #718096;
        }
        .warning {
            background-color: #FFFAF0;
            border-left: 4px solid #F6AD55;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .code-box {
            background-color: #F7FAFC;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            text-align: center;
            font-size: 18px;
            letter-spacing: 2px;
            margin: 20px 0;
        }
        .security-notice {
            background-color: #EBF8FF;
            border-left: 4px solid #4299E1;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>
        
        <div class="content">
            <h2>Hello {{ $user->name }},</h2>
            
            <p>We received a request to reset your password. Click the button below to create a new password:</p>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">Reset Password</a>
            </div>
            
            <p>Or copy and paste this link into your browser:</p>
            <div class="code-box">
                {{ $resetUrl }}
            </div>
            
            <div class="warning">
                <strong>⏰ Time Sensitive:</strong> This password reset link will expire in 60 minutes.
            </div>
            
            <div class="security-notice">
                <strong>🔒 Security Tips:</strong>
                <ul style="margin: 10px 0;">
                    <li>Never share your password with anyone</li>
                    <li>Use a strong, unique password</li>
                    <li>Enable two-factor authentication if available</li>
                </ul>
            </div>
            
            <p><strong>If you didn't request a password reset,</strong> please ignore this email and your password will remain unchanged. Your account is secure.</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Your Company. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>