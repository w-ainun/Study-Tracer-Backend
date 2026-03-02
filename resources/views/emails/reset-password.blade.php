<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #3C5759 0%, #2e4344 100%); padding: 32px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.8); margin: 8px 0 0; font-size: 13px; }
        .body { padding: 32px; }
        .body p { color: #475569; font-size: 14px; line-height: 1.7; margin: 0 0 16px; }
        .token-box { background: #f1f5f9; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0; }
        .token-box .token { font-size: 32px; font-weight: 800; letter-spacing: 8px; color: #3C5759; font-family: monospace; }
        .token-box .hint { font-size: 12px; color: #94a3b8; margin-top: 8px; }
        .btn { display: inline-block; background: #3C5759; color: #ffffff; padding: 14px 32px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; }
        .footer { background: #f8fafc; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #94a3b8; font-size: 11px; margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Alumni Tracer Study</h1>
            <p>SMK Negeri 1 Gondang</p>
        </div>
        <div class="body">
            <p>Halo,</p>
            <p>Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda. Gunakan kode OTP di bawah ini untuk melanjutkan proses reset password:</p>

            <div class="token-box">
                <div class="token">{{ $resetToken }}</div>
                <div class="hint">Kode berlaku selama 60 menit</div>
            </div>

            <p>Jika Anda tidak meminta reset password, abaikan email ini. Akun Anda tetap aman.</p>
            <p>Terima kasih,<br><strong>Tim Alumni Tracer Study</strong></p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Alumni Tracer Study - SMK Negeri 1 Gondang. Hak cipta dilindungi.</p>
        </div>
    </div>
</body>
</html>
