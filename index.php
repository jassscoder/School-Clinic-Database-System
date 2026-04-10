<?php
session_start();

// If user is logged in, redirect to appropriate dashboard based on role
if (isset($_SESSION['user'])) {
    switch($_SESSION['user']['role']) {
        case 'admin':
            header("Location: dashboards/dashboard_admin.php");
            break;
        case 'nurse':
            header("Location: dashboards/nurse_dashboard.php");
            break;
        case 'staff':
            header("Location: dashboards/staff_dashboard.php");
            break;
        default:
            header("Location: dashboards/dashboard.php");
            break;
    }
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCHoRD | School Health & Record Database</title>
    <style>
        :root {
            --bg-1: #0f172a;
            --bg-2: #134e4a;
            --bg-3: #111827;
            --accent: #34d399;
            --accent-2: #22c55e;
            --text: #ecfdf5;
            --muted: rgba(236, 253, 245, 0.75);
            --card: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.12);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, "Segoe UI", system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(52, 211, 153, 0.24), transparent 35%),
                radial-gradient(circle at bottom right, rgba(34, 197, 94, 0.18), transparent 30%),
                linear-gradient(135deg, var(--bg-1) 0%, var(--bg-2) 55%, var(--bg-3) 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .shell {
            width: min(960px, 100%);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 36px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(14px);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 18px;
        }

        .mark {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #052e16;
            font-size: 22px;
            box-shadow: 0 12px 30px rgba(34, 197, 94, 0.3);
        }

        h1 {
            margin: 0 0 14px;
            font-size: clamp(2.4rem, 5vw, 4.6rem);
            line-height: 0.98;
            letter-spacing: -0.04em;
            max-width: 12ch;
        }

        p {
            margin: 0;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.7;
            max-width: 62ch;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 28px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 22px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .button.primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #052e16;
            box-shadow: 0 16px 32px rgba(34, 197, 94, 0.25);
        }

        .button.secondary {
            color: var(--text);
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.04);
        }

        .button:hover {
            transform: translateY(-2px);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-top: 34px;
        }

        .tile {
            padding: 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .tile strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #d1fae5;
        }

        .tile span {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 720px) {
            .shell { padding: 24px; border-radius: 22px; }
            .grid { grid-template-columns: 1fr; }
            .actions { flex-direction: column; }
            .button { width: 100%; }
        }
    </style>
</head>
<body>
    <main class="shell">
        <div class="brand"><span class="mark">🏥</span> SCHoRD</div>
        <h1>School Health &amp; Record Database</h1>
        <p>
            SCHoRD is the school clinic record system for student profiles, clinic visits,
            health records, and role-based dashboards.
        </p>

        <div class="actions">
            <a class="button primary" href="auth/login.php">Open Login</a>
            <a class="button secondary" href="docs/README.md">Read Docs</a>
        </div>

        <section class="grid" aria-label="System highlights">
            <div class="tile">
                <strong>Live Access</strong>
                <span>Public landing page is now available even while backend deployment is being verified.</span>
            </div>
            <div class="tile">
                <strong>Role Dashboards</strong>
                <span>Admin, nurse, and staff views are available after sign-in.</span>
            </div>
            <div class="tile">
                <strong>Database Driven</strong>
                <span>Student, visit, and record data are loaded from MySQL.</span>
            </div>
        </section>
    </main>
</body>
</html>
?>