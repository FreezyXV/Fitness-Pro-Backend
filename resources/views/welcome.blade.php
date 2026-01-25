<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Pro Backend</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #667eea;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .container {
            text-align: center;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            max-width: 600px;
            margin: 2rem;
        }

        .title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #ffffff;
        }

        .message {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .highlight {
            color: #fbbf24;
            font-weight: bold;
        }

        .api-info {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3rem;
            border-left: 3rem solid #fbbf24;
        }

        .api-info h3 {
            color: #fbbf24;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .api-info p {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(34, 197, 94, 0.2);
            border: 2px solid #22c55e;
            border-radius: 25px;
            margin: 1rem 0;
            font-weight: bold;
            color: #22c55e;
        }

        .footer {
            margin-top: 2rem;
            font-size: 0.9rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="title">🏋️ Fitness Pro</h1>
        <div class="badge">✅ Backend Active</div>
        <p class="message">
            Hello! Your <span class="highlight">Fitness Pro Backend</span> is working perfectly.
            <br><span class="highlight">Congratulations!</span> 🎉
        </p>

        <div class="api-info">
            <h3>🚀 API Information</h3>
            <p><strong>Base URL:</strong> {{ config('app.url') }}</p>
            <p><strong>Environment:</strong> {{ app()->environment() }}</p>
            <p><strong>Laravel Version:</strong> {{ app()->version() }}</p>
            <p><strong>PHP Version:</strong> {{ PHP_VERSION }}</p>
        </div>

        <div class="footer">
            <p>Your fitness journey starts here! 💪</p>
            <p>API endpoints are ready for your Angular frontend.</p>
        </div>
    </div>
</body>
</html>
