<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>BizzSoft Secure Checkout</title>

    <script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #222;
        }

        .checkout-status {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
        }

        .checkout-status__card {
            max-width: 520px;
            padding: 32px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>

<body>
    <main class="checkout-status">
        <div class="checkout-status__card">
            <h1>BizzSoft Secure Checkout</h1>

            <p id="checkout-message">
                Preparing your secure payment checkout...
            </p>
        </div>
    </main>

    <script>
        const paddleEnvironment = @json(config('paddle.environment'));
        const paddleClientToken = @json(config('paddle.client_token'));

        const message = document.getElementById('checkout-message');

        if (!paddleClientToken) {
            message.textContent =
                'Payment checkout is temporarily unavailable.';
        } else {
            if (paddleEnvironment === 'sandbox') {
                Paddle.Environment.set('sandbox');
            }

            Paddle.Initialize({
                token: paddleClientToken,
                checkout: {
                    settings: {
                        displayMode: 'overlay',
                        theme: 'light',
                        locale: 'en'
                    }
                }
            });
        }
    </script>
</body>
</html>