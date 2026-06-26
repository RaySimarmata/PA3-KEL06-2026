<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #1e3c72;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
            text-align: left;
        }
        .message {
            white-space: pre-wrap;
            background-color: white;
            padding: 20px;
            border-left: 4px solid #1e3c72;
            margin: 20px 0;
            text-align: left !important;
            font-family: Arial, sans-serif;
        }
        .message pre {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            white-space: pre-wrap;
            word-wrap: break-word;
            text-align: left;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Sistem Informasi GKM {{ $prodiKode }}</h2>
        <p style="margin: 5px 0;">Reminder Review Soal Ujian</p>
    </div>
    
    <div class="content">
        <div class="message"><pre style="margin: 0; padding: 0; font-family: Arial, sans-serif; white-space: pre-wrap; word-wrap: break-word; text-align: left;">{{ $messageContent }}</pre></div>
        
        <p style="margin-top: 30px; color: #666; font-size: 14px;">
            <strong>Catatan:</strong> Email ini dikirim secara otomatis oleh sistem. 
            Mohon tidak membalas email ini.
        </p>
    </div>
    
    <div class="footer">
        <p>© {{ date('Y') }} Sistem Informasi GKM & GJM - Program Studi {{ $prodiName }}</p>
        <p>Email ini dikirim kepada {{ $dosenName }}</p>
    </div>
</body>
</html>
