<?php
/**
 * SECURE CSRF DEMO
 * ---------------------------------------------------------------
 * Demonstrates best practices to prevent Cross-Site Request Forgery:
 *  1. Generate a cryptographically random CSRF token per session
 *  2. Embed token in every state-changing form
 *  3. Validate token on every POST — reject if missing or mismatched
 *  4. Rotate token after use (optional but recommended)
 *  5. SameSite cookie attribute
 * ---------------------------------------------------------------
 */

session_start();

// BEST PRACTICE 5: Set session cookie with SameSite=Strict and Secure attributes
// (In production, also set Secure flag for HTTPS)
// session_set_cookie_params(['samesite' => 'Strict', 'secure' => true, 'httponly' => true]);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = get_db();

// Simulate logged-in user
if (!isset($_SESSION['csrf_secure_demo_user'])) {
    $_SESSION['csrf_secure_demo_user'] = 'alice';
}
$logged_in_user = $_SESSION['csrf_secure_demo_user'];

// BEST PRACTICE 1: Generate and store a CSRF token in the session (once per session)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // 64-char hex token
}
$csrf_token = $_SESSION['csrf_token'];

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // BEST PRACTICE 2: Validate the token before ANY processing
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrf_token, $submitted_token)) {
        // BEST PRACTICE 3: Reject and log invalid requests
        $error = '🚫 CSRF validation failed — request rejected. The token was missing or invalid.';
    } else {
        // Token is valid — proceed with the operation
        $to_user = $_POST['to_user'] ?? '';
        $amount  = (float) ($_POST['amount'] ?? 0);

        // Validate inputs
        if (empty($to_user) || $amount <= 0) {
            $error = '❌ Invalid transfer details.';
        } else {
            $stmt = $pdo->prepare("SELECT balance FROM users WHERE username = ?");
            $stmt->execute([$logged_in_user]);
            $sender = $stmt->fetch();

            if ($sender && $sender['balance'] >= $amount) {
                $pdo->prepare("UPDATE users SET balance = balance - ? WHERE username = ?")->execute([$amount, $logged_in_user]);
                $pdo->prepare("UPDATE users SET balance = balance + ? WHERE username = ?")->execute([$amount, $to_user]);
                $pdo->prepare("INSERT INTO transfers (from_user, to_user, amount) VALUES (?, ?, ?)")->execute([$logged_in_user, $to_user, $amount]);

                // BEST PRACTICE 4: Rotate token after successful use
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $csrf_token = $_SESSION['csrf_token'];

                $message = "✅ Transferred \${$amount} to " . htmlspecialchars($to_user) . " — CSRF token validated.";
            } else {
                $error = '❌ Insufficient balance.';
            }
        }
    }
}

// Simple user switcher
if (isset($_GET['switch'])) {
    $_SESSION['csrf_secure_demo_user'] = in_array($_GET['switch'], ['alice','bob','charlie','admin'])
        ? $_GET['switch'] : 'alice';
    unset($_SESSION['csrf_token']); // Reset token when switching user
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$users     = $pdo->query("SELECT username, balance FROM users ORDER BY username")->fetchAll();
$transfers = $pdo->query("SELECT * FROM transfers ORDER BY created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSRF – Secure Demo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php render_demo_header(
    'Cross-Site Request Forgery — Secured',
    'secure',
    'CSRF',
    'Every form includes a signed CSRF token. Requests without a valid token are rejected.'
); ?>

<div class="demo-container">

    <div class="panel safe-panel">
        <div class="panel-header"><span>✔</span><h3>Defenses Active</h3></div>
        <div class="panel-body">
            <ul class="checklist">
                <li>CSRF token generated with <code>random_bytes(32)</code> — cryptographically secure</li>
                <li>Token stored in <code>$_SESSION</code> — inaccessible to cross-origin sites</li>
                <li>Token embedded as hidden field in every state-changing form</li>
                <li><code>hash_equals()</code> used for constant-time comparison (prevents timing attacks)</li>
                <li>Token rotated after each successful use</li>
                <li>Recommend <code>SameSite=Strict</code> on session cookie (see code comment)</li>
            </ul>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Current CSRF Token (session):</strong><br>
        <code style="word-break:break-all;font-size:0.8rem"><?= htmlspecialchars($csrf_token) ?></code>
        <br><small style="color:var(--muted)">A cross-origin attacker cannot read this token from your session.</small>
    </div>

    <div class="alert alert-info">
        Simulated logged-in user: <strong><?= htmlspecialchars($logged_in_user) ?></strong>
        &nbsp;—&nbsp;
        <a href="?switch=bob" style="color:var(--accent)">Switch to bob</a> |
        <a href="?switch=alice" style="color:var(--accent)">Switch to alice</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="two-col">
        <div class="panel">
            <div class="panel-header"><span>💸</span><h3>Transfer Funds (CSRF Protected)</h3></div>
            <div class="panel-body">
                <!-- SECURE: CSRF token embedded as hidden field -->
                <form method="POST">
                    <!-- BEST PRACTICE: Token included in every state-changing form -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

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
                    <button type="submit" class="btn-submit safe">Transfer (CSRF Token Included)</button>
                </form>

                <div class="output-box" style="margin-top:18px">
                    <div class="output-label">View Page Source → Hidden Field in Form</div>
                    <code>&lt;input type="hidden" name="csrf_token"<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; value="<?= htmlspecialchars(substr($csrf_token, 0, 24)) ?>..."&gt;</code>
                </div>
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
        <div class="panel-header"><span>📄</span><h3>The Secure Code</h3></div>
        <div class="panel-body">
<pre><code><span class="php-comment">// ✅ SECURE — generate token once per session</span>
<span class="php-keyword">if</span> (<span class="php-function">empty</span>(<span class="php-variable">$_SESSION</span>[<span class="php-string">'csrf_token'</span>])) {
    <span class="php-variable">$_SESSION</span>[<span class="php-string">'csrf_token'</span>] = <span class="php-function">bin2hex</span>(<span class="php-function">random_bytes</span>(<span class="php-string">32</span>));
}
<span class="php-variable">$csrf_token</span> = <span class="php-variable">$_SESSION</span>[<span class="php-string">'csrf_token'</span>];

<span class="php-comment">// ✅ SECURE — validate BEFORE processing</span>
<span class="php-variable">$submitted</span> = <span class="php-variable">$_POST</span>[<span class="php-string">'csrf_token'</span>] ?? <span class="php-string">''</span>;
<span class="php-keyword">if</span> (!<span class="php-function">hash_equals</span>(<span class="php-variable">$csrf_token</span>, <span class="php-variable">$submitted</span>)) {
    <span class="php-function">die</span>(<span class="php-string">'CSRF validation failed'</span>);
}

<span class="php-comment">// ✅ SECURE — embed in every form</span>
<span class="php-string">&lt;input type="hidden" name="csrf_token"
       value="&lt;?= htmlspecialchars($csrf_token) ?&gt;"&gt;</span>

<span class="php-comment">// ✅ SECURE — rotate after use</span>
<span class="php-variable">$_SESSION</span>[<span class="php-string">'csrf_token'</span>] = <span class="php-function">bin2hex</span>(<span class="php-function">random_bytes</span>(<span class="php-string">32</span>));

<span class="php-comment">// ✅ In production — SameSite cookie attribute</span>
<span class="php-function">session_set_cookie_params</span>([
    <span class="php-string">'samesite'</span> => <span class="php-string">'Strict'</span>,
    <span class="php-string">'secure'</span>   => <span class="php-keyword">true</span>,
    <span class="php-string">'httponly'</span> => <span class="php-keyword">true</span>,
]);</code></pre>
        </div>
    </div>

    <a href="../vulnerable/csrf.php" class="btn btn-danger" style="display:inline-block;margin-top:8px">← See the Vulnerable Version</a>
</div>
</body>
</html>
