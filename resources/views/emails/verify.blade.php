<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify Your Email</title>
</head>
<body>
    <h2>Hi {{ $user->name }},</h2>
    <p>Thank you for registering! Please verify your email by clicking the link below:</p>
    <!-- <p><a href="{{ $verifyUrl }}">Verify Email</a></p> -->
    <p>This link will expire in 10 minutes.</p>
</body>
</html>
