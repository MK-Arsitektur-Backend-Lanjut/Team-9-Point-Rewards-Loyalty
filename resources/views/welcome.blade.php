<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loyalty Backend Dashboard</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f3f4f6;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --accent: #2563eb;
            --border: #e2e8f0;
        }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }
        .hero, .panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 16px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }
        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            margin-top: 18px;
        }
        .stat {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            background: #f8fafc;
        }
        .stat .label {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 6px;
        }
        .stat .value {
            font-weight: 700;
            font-size: 24px;
        }
        .links a {
            display: inline-block;
            margin: 8px 10px 0 0;
            padding: 10px 12px;
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid var(--border);
            color: var(--accent);
            font-weight: 600;
            background: #eff6ff;
        }
        .footer {
            font-size: 12px;
            color: var(--muted);
            margin-top: 8px;
        }
    </style>
</head>
<body>
<div class="container">
    <section class="hero">
        <h1>Loyalty Backend - Modul 1</h1>
        <p>Dashboard sederhana untuk melihat status modul Activity Rules and Rewards.</p>

        <div class="grid">
            <div class="stat">
                <div class="label">Total Activity Rules</div>
                <div class="value">{{ number_format($stats['activity_rules']) }}</div>
            </div>
            <div class="stat">
                <div class="label">Total Rewards</div>
                <div class="value">{{ number_format($stats['rewards']) }}</div>
            </div>
            <div class="stat">
                <div class="label">Point Activity Logs</div>
                <div class="value">{{ number_format($stats['activity_logs']) }}</div>
            </div>
        </div>
    </section>

    <section class="panel">
        <h2>Quick API Links</h2>
        <p>Akses endpoint utama modul langsung dari browser.</p>
        <div class="links">
            <a href="/api/activity-rules" target="_blank">GET Activity Rules</a>
            <a href="/api/rewards" target="_blank">GET Rewards</a>
        </div>
    </section>

    <div class="footer">
        Laravel v{{ Illuminate\Foundation\Application::VERSION }} | PHP v{{ PHP_VERSION }}
    </div>
</div>
</body>
</html>
