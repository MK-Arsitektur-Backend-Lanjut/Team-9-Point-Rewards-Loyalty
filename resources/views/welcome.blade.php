<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loyalty Dashboard - Module 4 Playground</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --ink: #172033;
            --muted: #5a667f;
            --panel: #ffffff;
            --line: #d8e0ef;
            --brand: #1363df;
            --brand-soft: #e8f1ff;
            --ok: #0f9d58;
            --warn: #b45f06;
            --radius: 14px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 0% 0%, #e9f2ff 0, transparent 40%),
                radial-gradient(circle at 100% 0%, #fef3e8 0, transparent 34%),
                var(--bg);
        }
        .wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }
        .hero {
            background: linear-gradient(120deg, #ffffff, #f5f9ff);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 22px;
            margin-bottom: 16px;
        }
        h1 {
            margin: 0 0 6px;
            font-size: 30px;
        }
        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
        }
        .stats {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
        }
        .stat {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
        }
        .stat small {
            display: block;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .stat strong {
            font-size: 22px;
        }
        .grid {
            display: grid;
            gap: 12px;
            grid-template-columns: 1.2fr 1fr;
            margin-bottom: 12px;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 16px;
        }
        .panel h2 {
            margin: 0 0 10px;
            font-size: 20px;
        }
        .table-wrap { overflow: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th, td {
            border-bottom: 1px solid var(--line);
            text-align: left;
            padding: 8px 6px;
            white-space: nowrap;
        }
        th { color: var(--muted); font-weight: 600; }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 12px;
        }
        .action {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
            background: #fbfdff;
        }
        .action h3 {
            margin: 0 0 8px;
            font-size: 16px;
        }
        form { display: grid; gap: 8px; }
        input, select, button, textarea {
            width: 100%;
            border: 1px solid #cbd6eb;
            border-radius: 9px;
            padding: 9px 10px;
            font: inherit;
            background: #fff;
        }
        button {
            cursor: pointer;
            font-weight: 600;
            background: var(--brand);
            color: #fff;
            border-color: var(--brand);
        }
        .ghost {
            background: var(--brand-soft);
            color: #0d3f8f;
            border-color: #b7d0fb;
        }
        .response {
            margin-top: 12px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #0c1424;
            color: #d4e2ff;
            padding: 12px;
            font-size: 12px;
            white-space: pre-wrap;
            min-height: 110px;
        }
        .hint {
            color: var(--warn);
            font-size: 12px;
            margin-top: 6px;
        }
        .ok {
            color: var(--ok);
            font-size: 12px;
            margin-top: 6px;
        }
        @media (max-width: 980px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <section class="hero">
        <h1>Module 4 Testing Dashboard</h1>
        <p>Semua action penting Membership Tiering, Referral, Multiplier, dan Redeem bisa langsung dites dari sini tanpa Postman.</p>
        <div class="stats">
            <div class="stat"><small>Users</small><strong>{{ number_format($stats['users']) }}</strong></div>
            <div class="stat"><small>Activity Rules</small><strong>{{ number_format($stats['activity_rules']) }}</strong></div>
            <div class="stat"><small>Rewards</small><strong>{{ number_format($stats['rewards']) }}</strong></div>
            <div class="stat"><small>Activity Logs</small><strong>{{ number_format($stats['activity_logs']) }}</strong></div>
            <div class="stat"><small>Tiers</small><strong>{{ number_format($stats['tiers']) }}</strong></div>
            <div class="stat"><small>Referrals</small><strong>{{ number_format($stats['referrals']) }}</strong></div>
            <div class="stat"><small>Redemptions</small><strong>{{ number_format($stats['redemptions']) }}</strong></div>
        </div>
    </section>

    <section class="grid">
        <div class="panel">
            <h2>Data Helper</h2>
            <p>Pakai user_id, reward_id, dan activity_code dari tabel ini saat submit action.</p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>User ID</th><th>Name</th><th>Points</th><th>Tier ID</th><th>Referral Code</th></tr>
                    </thead>
                    <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->points }}</td>
                            <td>{{ $user->membership_tier_id ?? '-' }}</td>
                            <td>{{ $user->referral_code ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Belum ada user.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <br>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr><th>Reward ID</th><th>Name</th><th>Points Required</th><th>Stock</th><th>Physical</th></tr>
                    </thead>
                    <tbody>
                    @forelse($rewards as $reward)
                        <tr>
                            <td>{{ $reward->id }}</td>
                            <td>{{ $reward->name }}</td>
                            <td>{{ $reward->points_required }}</td>
                            <td>{{ $reward->stock }}</td>
                            <td>{{ $reward->is_physical ? 'yes' : 'no' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Belum ada reward.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <br>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr><th>Activity Rule ID</th><th>Activity Code</th><th>Point Value</th><th>Active</th></tr>
                    </thead>
                    <tbody>
                    @forelse($activityRules as $rule)
                        <tr>
                            <td>{{ $rule->id }}</td>
                            <td>{{ $rule->activity_code }}</td>
                            <td>{{ $rule->point_value }}</td>
                            <td>{{ $rule->is_active ? 'yes' : 'no' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Belum ada activity rule.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <h2>Tier & Log Snapshot</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Tier ID</th><th>Code</th><th>Name</th><th>Range</th><th>Multiplier</th></tr></thead>
                    <tbody>
                    @forelse($tiers as $tier)
                        <tr>
                            <td>{{ $tier->id }}</td>
                            <td>{{ $tier->code }}</td>
                            <td>{{ $tier->name }}</td>
                            <td>{{ $tier->min_points }} - {{ $tier->max_points ?? 'INF' }}</td>
                            <td>{{ $tier->point_multiplier }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Tier belum dibuat.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <br>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Log ID</th><th>User</th><th>Code</th><th>Points</th><th>At</th></tr></thead>
                    <tbody>
                    @forelse($recentLogs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->user_id }}</td>
                            <td>{{ $log->activity_code }}</td>
                            <td>{{ $log->points_earned }}</td>
                            <td>{{ $log->earned_at }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Belum ada log terbaru.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <p class="hint">Tip: refresh halaman untuk update tabel snapshot setelah submit action.</p>
        </div>
    </section>

    <section class="panel">
        <h2>Module 4 Actions</h2>
        <div class="action-grid">
            <div class="action">
                <h3>Create Tier</h3>
                <form id="form-create-tier">
                    <input name="code" placeholder="Code (ex: GOLD)" required>
                    <input name="name" placeholder="Name" required>
                    <input name="min_points" type="number" min="0" placeholder="Min Points" required>
                    <input name="max_points" type="number" min="0" placeholder="Max Points (optional)">
                    <input name="point_multiplier" type="number" min="1" step="0.01" placeholder="Multiplier" required>
                    <button type="submit">POST /api/membership/tiers</button>
                </form>
            </div>

            <div class="action">
                <h3>Recalculate User Tier</h3>
                <form id="form-recalc-tier">
                    <input name="user_id" type="number" min="1" placeholder="User ID" required>
                    <button type="submit">POST /api/membership/tiers/recalculate</button>
                </form>
                <h3 style="margin-top:12px;">Generate Referral</h3>
                <form id="form-generate-referral">
                    <input name="user_id" type="number" min="1" placeholder="User ID" required>
                    <button type="submit">POST /api/membership/referrals/generate</button>
                </form>
            </div>

            <div class="action">
                <h3>Apply Referral</h3>
                <form id="form-apply-referral">
                    <input name="user_id" type="number" min="1" placeholder="Referee User ID" required>
                    <input name="referral_code" placeholder="Referral Code" required>
                    <button type="submit">POST /api/membership/referrals/apply</button>
                </form>
            </div>

            <div class="action">
                <h3>Trigger Activity with Multiplier</h3>
                <form id="form-trigger-activity">
                    <input name="user_id" type="number" min="1" placeholder="User ID" required>
                    <input name="activity_code" placeholder="Activity Code (ex: DAILY_LOGIN)" required>
                    <button type="submit">POST /api/membership/activity/trigger</button>
                </form>
            </div>

            <div class="action">
                <h3>Redeem Reward</h3>
                <form id="form-redeem">
                    <input name="user_id" type="number" min="1" placeholder="User ID" required>
                    <input name="reward_id" type="number" min="1" placeholder="Reward ID" required>
                    <input name="quantity" type="number" min="1" value="1" required>
                    <button type="submit">POST /api/membership/rewards/{id}/redeem</button>
                </form>
            </div>

            <div class="action">
                <h3>Quick Read</h3>
                <form id="form-read-tiers">
                    <button class="ghost" type="submit">GET /api/membership/tiers</button>
                </form>
                <form id="form-read-rules" style="margin-top:8px;">
                    <button class="ghost" type="submit">GET /api/activity-rules</button>
                </form>
                <form id="form-read-rewards" style="margin-top:8px;">
                    <button class="ghost" type="submit">GET /api/rewards</button>
                </form>
                <div class="ok">Request dijalankan langsung dari browser ke API app ini.</div>
            </div>
        </div>

        <div id="response" class="response">Response output akan muncul di sini...</div>
    </section>
</div>

<script>
    const responseBox = document.getElementById('response');

    async function apiCall(method, path, payload = null) {
        responseBox.textContent = 'Loading...';

        const options = {
            method,
            headers: { 'Accept': 'application/json' }
        };

        if (payload !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(payload);
        }

        try {
            const res = await fetch(path, options);
            let data;
            try {
                data = await res.json();
            } catch (_) {
                data = { message: await res.text() };
            }

            responseBox.textContent = JSON.stringify({
                status: res.status,
                ok: res.ok,
                path,
                data
            }, null, 2);
        } catch (error) {
            responseBox.textContent = JSON.stringify({ error: error.message }, null, 2);
        }
    }

    document.getElementById('form-create-tier').addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        apiCall('POST', '/api/membership/tiers', {
            code: fd.get('code'),
            name: fd.get('name'),
            min_points: Number(fd.get('min_points')),
            max_points: fd.get('max_points') ? Number(fd.get('max_points')) : null,
            point_multiplier: Number(fd.get('point_multiplier')),
            is_active: true
        });
    });

    document.getElementById('form-recalc-tier').addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        apiCall('POST', '/api/membership/tiers/recalculate', {
            user_id: Number(fd.get('user_id'))
        });
    });

    document.getElementById('form-generate-referral').addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        apiCall('POST', '/api/membership/referrals/generate', {
            user_id: Number(fd.get('user_id'))
        });
    });

    document.getElementById('form-apply-referral').addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        apiCall('POST', '/api/membership/referrals/apply', {
            user_id: Number(fd.get('user_id')),
            referral_code: fd.get('referral_code')
        });
    });

    document.getElementById('form-trigger-activity').addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        apiCall('POST', '/api/membership/activity/trigger', {
            user_id: Number(fd.get('user_id')),
            activity_code: fd.get('activity_code')
        });
    });

    document.getElementById('form-redeem').addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        const rewardId = Number(fd.get('reward_id'));
        apiCall('POST', '/api/membership/rewards/' + rewardId + '/redeem', {
            user_id: Number(fd.get('user_id')),
            quantity: Number(fd.get('quantity'))
        });
    });

    document.getElementById('form-read-tiers').addEventListener('submit', function (e) {
        e.preventDefault();
        apiCall('GET', '/api/membership/tiers');
    });

    document.getElementById('form-read-rules').addEventListener('submit', function (e) {
        e.preventDefault();
        apiCall('GET', '/api/activity-rules');
    });

    document.getElementById('form-read-rewards').addEventListener('submit', function (e) {
        e.preventDefault();
        apiCall('GET', '/api/rewards');
    });
</script>
</body>
</html>
