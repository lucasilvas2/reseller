<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Invitation</title>
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
            background-color: #e8f4fd;
            border: 1px solid #b8daff;
            border-radius: 4px;
            padding: 16px;
            margin: 16px 0;
        }
        .button {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 16px 0;
        }
        .button-container {
            text-align: center;
        }
        .store-info {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 12px;
            margin: 16px 0;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>You've Been Invited!</h1>
</div>

<p>Hi, {{ $username }},</p>

<p>You have been invited to join as a client by:</p>

<div class="store-info">
    <strong>{{ $storeName }}</strong>
</div>

<div class="panel">
    <p>By accepting this invitation, you'll have access to exclusive services and offers from this store.</p>
</div>

<p>Click the button below to accept the invitation and create your account:</p>

<div class="button-container">
    <a href="{{ $invitationUrl }}" class="button">Accept Invitation</a>
</div>

<p>If you don't want to accept this invitation, you can safely ignore this email.</p>

<p>Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>
