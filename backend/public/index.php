<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Юникод24 в MAX</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,600;0,700;1,400&family=Syne:wght@600;700;800&display=swap');

        :root {
            --bg: #f2f5fb;
            --card: #fff;
            --text: #111827;
            --muted: #667085;
            --accent: #6f6dff;
            --accent-2: #8b5cf6;
            --border: #e8ebf3;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Instrument Sans", system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 1rem;
        }
        .wrap {
            max-width: 760px;
            margin: 0 auto;
        }
        .hero {
            background: linear-gradient(140deg, #ffffff, #f7f7ff);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 1.2rem;
            box-shadow: 0 16px 32px rgba(53, 65, 89, 0.06);
        }
        .top { display:flex; justify-content:space-between; align-items:center; gap:.7rem; flex-wrap:wrap; }
        .brand {
            display: flex;
            align-items: center;
            gap: .8rem;
        }
        .brand img { width: 42px; height: 42px; object-fit: contain; }
        .brand h1 {
            margin: 0;
            font-family: "Syne", sans-serif;
            font-size: clamp(1.45rem, 6.2vw, 2rem);
        }
        .badges { display:flex; gap:.5rem; flex-wrap:wrap; }
        .badge {
            border: 1px solid var(--border);
            background: #fff;
            border-radius: 999px;
            padding: .35rem .7rem;
            font-size: .86rem;
            color: var(--muted);
        }
        .subtitle { color: var(--muted); margin:.8rem 0 1rem; font-size:1rem; }
        .grid { display:grid; grid-template-columns: 1fr; gap:.7rem; }
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: .9rem;
        }
        .card h3 {
            margin: 0 0 .45rem;
            font-size: 1rem;
            font-family: "Syne", sans-serif;
            display: flex;
            gap: .45rem;
            align-items: center;
        }
        .card p { margin:0; color: var(--muted); font-size: .93rem; }
        .actions { margin-top:1rem; display:grid; gap:.55rem; }
        .btn {
            text-decoration: none;
            border-radius: 11px;
            padding: .58rem .9rem;
            font-weight: 600;
            font-size: .95rem;
            text-align: center;
        }
        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
        }
        .btn-light {
            color: #344054;
            border: 1px solid var(--border);
            background: #fff;
        }
    </style>
</head>
<body>
<main class="wrap" role="main" aria-label="Информация для клиента в MAX">
    <section class="hero">
        <div class="top">
            <div class="brand">
                <img src="/assets/logo.png" alt="">
            </div>
            <div class="badges">
                <span class="badge"><i class="bi bi-lightning-charge"></i> Быстрые ответы</span>
                <span class="badge"><i class="bi bi-phone"></i> Удобно с телефона</span>
            </div>
        </div>
        <p class="subtitle">Это официальный бот Юникод24: вы отправляете заявку с сайта и сразу получаете уведомление в MAX. Внутри мини‑приложения можно открыть детали заявки в пару касаний.</p>
        <div class="grid">
            <article class="card">
                <h3><i class="bi bi-bell"></i> Оперативные уведомления</h3>
                <p>Новые заявки и заказы приходят в чат почти мгновенно, без почты и лишних вкладок.</p>
            </article>
            <article class="card">
                <h3><i class="bi bi-card-checklist"></i> Все заявки в одном месте</h3>
                <p>Кнопка «Открыть» ведет в мини‑приложение со списком и карточкой каждой заявки.</p>
            </article>
            <article class="card">
                <h3><i class="bi bi-shield-check"></i> Удобно и безопасно</h3>
                <p>Привязка по секретному ключу защищает доступ к данным и исключает чужие уведомления.</p>
            </article>
        </div>
    </section>
</main>
</body>
</html>
