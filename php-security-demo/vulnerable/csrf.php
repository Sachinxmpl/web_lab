<?php
/**
 * VULNERABLE CSRF DEMO
 * ---------------------------------------------------------------
 * This page is INTENTIONALLY INSECURE for educational purposes.
 * It processes state-changing requests without CSRF token validation.
 * NEVER write code like this in production.
 * ---------------------------------------------------------------
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = get_db();

// Simulate a logged-in user (no real auth in this demo)
if (!isset($_SESSION['csrf_demo_user'])) {
    $_SESSION['csrf_demo_user'] = 'alice';
}
$logged_in_user = $_SESSION['csrf_demo_user'];

$message = '';
$error   = '';

// VULNERABLE: No CSRF token check — any site can trigger this POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to_user = $_POST['to_user'] ?? '';
    $amount  = (float) ($_POST['amount'] ?? 0);

    // Fetch sender's balance
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE username = ?");
    $stmt->execute([$logged_in_user]);
    $sender = $stmt->fetch();

    if ($sender && $amount > 0 && $sender['balance'] >= $amount) {
        // VULNERABLE: No CSRF verification — any site can post this form and trigger a transfer
        $pdo->prepare("UPDATE users SET balance = balance - ? WHERE username = ?")->execute([$amount, $logged_in_user]);
        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE username = ?")->execute([$amount, $to_user]);
        $pdo->prepare("INSERT INTO transfers (from_user, to_user, amount) VALUES (?, ?, ?)")->execute([$logged_in_user, $to_user, $amount]);
        $message = "✅ Transferred \${$amount} to {$to_user}.";
    } else {
        $error = '❌ Transfer failed — insufficient balance or invalid amount.';
    }
}

// Fetch current balances
$users = $pdo->query("SELECT username, balance FROM users ORDER BY username")->fetchAll();

// Fetch transfer history
$transfers = $pdo->query("SELECT * FROM transfers ORDER BY created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSRF – Vulnerable Demo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php render_demo_header(
    'Cross-Site Request Forgery (CSRF)',
    'vulnerable',
    'CSRF',
    'No CSRF token is validated — any malicious site can submit this form on behalf of a logged-in user.'
); ?>

<div class="demo-container">

    <div class="panel danger-panel">
        <div class="panel-header"><span>⚠</span><h3>How the Attack Works</h3></div>
        <div class="panel-body">
            <p style="color:var(--muted);margin-bottom:14px">A malicious website (e.g. <code>evil.com</code>) could host this hidden form. When an authenticated user visits that site, the transfer fires automatically:</p>
            <pre><code>&lt;!-- On evil.com — auto-submits when victim visits --&gt;
&lt;form method="POST" action="https://bank.com/transfer" id="x"&gt;
    &lt;input type="hidden" name="to_user" value="hacker"&gt;
    &lt;input type="hidden" name="amount" value="500"&gt;
&lt;/form&gt;
&lt;script&gt;document.getElementById('x').submit();&lt;/script&gt;</code></pre>
            <p style="color:var(--muted);margin-top:12px">The victim's browser sends the request with their session cookie attached. The server has no way to tell it wasn't initiated by the user.</p>
        </div>
    </div>

    <div class="alert alert-info">
        Simulated logged-in user: <strong><?= htmlspecialchars($logged_in_user) ?></strong>
        &nbsp;—&nbsp;
        <a href="?switch=bob" style="color:var(--accent)">Switch to bob</a> |
        <a href="?switch=alice" style="color:var(--accent)">Switch to alice</a>
    </div>

    <?php
    // Simple user switcher for demo
    if (isset($_GET['switch'])) {
        $_SESSION['csrf_demo_user'] = in_array($_GET['switch'], ['alice','bob','charlie','admin'])
            ? $_GET['switch'] : 'alice';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    ?>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="two-col">
        <div class="panel">
            <div class="panel-header"><span>💸</span><h3>Transfer Funds (No CSRF Protection)</h3></div>
            <div class="panel-body">
                <!-- VULNERABLE: No CSRF token in form -->
                <form method="POST">
                    <div class="form-group">
                        <label>Transfer To</label>
                        <select name="to_user">
                            <?php foreach ($users as $u): ?>
                                <?php if ($u['username'] !== $logged_in_user): ?>
                                    <option value="<?= htmlspecialchars($u['username']) ?>">
                                        <?= htmlspecialchars($u['username']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount ($)</label>
                        <input type="number" name="amount" min="1" max="10000" value="100">
                    </div>
                    <!-- VULNERABLE: No hidden CSRF token field here -->
                    <button type="submit" class="btn-submit">Transfer (No CSRF Token)</button>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><span>💰</span><h3>Account Balances</h3></div>
            <div class="panel-body">
                <table>
                    <thead><tr><th>User</th><th>Balance</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($u['username']) ?>
                                <?= $u['username'] === $logged_in_user ? ' 👤' : '' ?>
                            </td>
                            <td>$<?= number_format($u['balance'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($transfers)): ?>
    <div class="panel">
        <div class="panel-header"><span>📋</span><h3>Transfer History</h3></div>
        <div class="panel-body">
            <table>
                <thead><tr><th>From</th><th>To</th><th>Amount</th><th>Time</th></tr></thead>
                <tbody>
                <?php foreach ($transfers as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['from_user']) ?></td>
                        <td><?= htmlspecialchars($t['to_user']) ?></td>
                        <td>$<?= number_format($t['amount'], 2) ?></td>
                        <td style="color:var(--muted)"><?= htmlspecialchars($t['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-header"><span>📄</span><h3>The Vulnerable Code</h3></div>
        <div class="panel-body">
<pre><code><span class="php-comment">// ❌ VULNERABLE — No CSRF token generated or checked</span>
<span class="php-keyword">if</span> (<span class="php-variable">$_SERVER</span>[<span class="php-string">'REQUEST_METHOD'</span>] === <span class="php-string">'POST'</span>) {
    <span class="php-variable">$to_user</span> = <span class="php-variable">$_POST</span>[<span class="php-string">'to_user'</span>];
    <span class="php-variable">$amount</span>  = <span class="php-variable">$_POST</span>[<span class="php-string">'amount'</span>];
    <span class="php-comment">// Immediately processes — no origin verification!</span>
    transferFunds(<span class="php-variable">$logged_in_user</span>, <span class="php-variable">$to_user</span>, <span class="php-variable">$amount</span>);
}

<span class="php-comment">// ❌ VULNERABLE form — no hidden token</span>
<span class="php-string">&lt;form method="POST"&gt;
    &lt;input name="to_user" value="hacker"&gt;
    &lt;input name="amount" value="500"&gt;
    &lt;button&gt;Transfer&lt;/button&gt;
&lt;/form&gt;</span></code></pre>
        </div>
    </div>

    <a href="../secure/csrf.php" class="btn btn-safe" style="display:inline-block;margin-top:8px">→ See the Secure Version</a>
</div>
</body>
</html>
