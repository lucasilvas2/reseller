<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Creation Invitation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .panel {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 16px;
            margin: 16px 0;
            font-weight: bold;
            text-align: center;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 16px 0;
        }
        .button-container {
            text-align: center;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Welcome!</h1>
</div>

<p>Hi, {{ $username }},</p>

<p>You have been registered by <strong>{{ $storeName }}</strong>.</p>

<p>Your temporary password for first access is:</p>

<div class="panel">
    {{ $rawPassword }}
</div>

<p>Please use the button below to log in for the first time and set your new password:</p>

<div class="button-container">
    <a href="{{ $loginUrl }}" class="button">First Login</a>
</div>

<p>If you did not expect this email, please ignore it.</p>

<p>Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>
