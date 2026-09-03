<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .card { max-width: 450px; margin: 0 auto; background: #ffffff; padding: 24px; border-radius: 8px; text-align: center; }
        .otp { font-size: 32px; font-weight: bold; color: #4f46e5; letter-spacing: 6px; margin: 20px 0; }
    </style>
</head>
<body style="background-color: #f4f6f9; padding: 20px;">

    <div class="card" style="max-width: 450px; margin: 0 auto; background: #ffffff; padding: 24px; border-radius: 8px;">
        <h2>🔒 Your OTP Number</h2>
        <p style="color: #4b5563;">Please use the OTP number below to continue verifying your account.</p>

        <div class="otp" style="font-size: 32px; font-weight: bold; color: #4f46e5; letter-spacing: 6px; margin: 20px 0;">
            {{ $otp }}
        </div>

        <p style="color: red; font-size: 12px;">* This OTP number is valid for 10 minutes only.</p>
    </div>

</body>
</html>
