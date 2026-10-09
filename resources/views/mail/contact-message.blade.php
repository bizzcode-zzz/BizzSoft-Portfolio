<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BizzSoft Contact Message</title>
</head>
<body style="margin: 0; padding: 24px; background: #f3f4f6; color: #111827; font-family: Arial, sans-serif;">
    <div style="max-width: 680px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;">
        <div style="padding: 24px 28px; background: #02050c; color: #ffffff;">
            <p style="margin: 0 0 6px; font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; color: #67e8f9;">
                BizzSoft Buyer Support
            </p>

            <h1 style="margin: 0; font-size: 24px;">
                New contact message
            </h1>
        </div>

        <div style="padding: 28px;">
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px;">
                <tr>
                    <td style="padding: 8px 0; width: 110px; font-weight: 700; vertical-align: top;">
                        Name
                    </td>
                    <td style="padding: 8px 0;">
                        {{ $customerName }}
                    </td>
                </tr>

                <tr>
                    <td style="padding: 8px 0; font-weight: 700; vertical-align: top;">
                        Email
                    </td>
                    <td style="padding: 8px 0;">
                        <a href="mailto:{{ $customerEmail }}">
                            {{ $customerEmail }}
                        </a>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 8px 0; font-weight: 700; vertical-align: top;">
                        Subject
                    </td>
                    <td style="padding: 8px 0;">
                        {{ $contactSubject }}
                    </td>
                </tr>
            </table>

            <div style="border-top: 1px solid #e5e7eb; padding-top: 24px;">
                <h2 style="margin: 0 0 12px; font-size: 16px;">
                    Message
                </h2>

                <div style="line-height: 1.7; white-space: pre-wrap;">{{ $contactMessage }}</div>
            </div>

            <p style="margin: 28px 0 0; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 12px; line-height: 1.6; color: #6b7280;">
                This message was submitted through the public BizzSoft contact form.
                Replying to this email will reply directly to the sender.
            </p>
        </div>
    </div>
</body>
</html>