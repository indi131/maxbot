<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MAX | Заглушка</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #06080f;
            --panel: rgba(16, 20, 33, 0.75);
            --border: rgba(99, 115, 255, 0.35);
            --text: #f2f5ff;
            --muted: #8f9cc6;
            --accent: #7b8cff;
            --accent-2: #35d7ff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: "Segoe UI", "Inter", system-ui, -apple-system, sans-serif;
            background:
                radial-gradient(1200px 700px at 10% -10%, rgba(123, 140, 255, 0.25), transparent 55%),
                radial-gradient(900px 500px at 100% 100%, rgba(53, 215, 255, 0.2), transparent 60%),
                var(--bg);
            color: var(--text);
            padding: 24px;
        }

        .card {
            width: min(560px, 100%);
            border: 1px solid var(--border);
            background: var(--panel);
            border-radius: 24px;
            padding: 34px 28px;
            box-shadow: 0 18px 60px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(8px);
            text-align: center;
        }

        .logo img {
            max-width: 200px;
            max-width: 0px auto;
        }

        .brand {
            margin: 0;
            font-size: clamp(32px, 8vw, 46px);
            line-height: 1;
            letter-spacing: 0.14em;
            font-weight: 800;
        }

        .text {
            margin: 14px 0 0;
            font-size: clamp(16px, 3.8vw, 20px);
            font-weight: 500;
            color: var(--muted);
            text-transform: lowercase;
            letter-spacing: 0.08em;
        }

        .line {
            width: 120px;
            height: 2px;
            margin: 20px auto 0;
            background: linear-gradient(90deg, transparent, var(--accent), var(--accent-2), transparent);
            opacity: 0.9;
        }
    </style>
</head>
<body>
<main class="card" role="main" aria-label="MAX заглушка">
<div class="logo" aria-hidden="true">
    <img src="/assets/logo.png">
    </div>
    <h1 class="brand">MAX БОТ</h1>
    <div class="line" aria-hidden="true"></div>
</main>
</body>
</html>
