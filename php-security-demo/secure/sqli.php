<?php
/**
 * SECURE SQL INJECTION DEMO
 * ---------------------------------------------------------------
 * Demonstrates best practices to prevent SQL Injection:
 *  1. Prepared statements with parameterized queries (PDO)
 *  2. Input validation before querying
 *  3. Least-privilege: only select needed columns
 *  4. Password hashing (password_hash / password_verify)
 *  5. Error messages that don't expose DB details
 * ---------------------------------------------------------------
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = get_db();

$success        = '';
$error          = '';
$search_results = [];

// ── Login (Secure) ────────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // BEST PRACTICE 1: Validate before querying
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        // BEST PRACTICE 2: Use a prepared statement — user input NEVER touches SQL string
        $stmt = $pdo->prepare("SELECT id, username, email, balance, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // BEST PRACTICE 3: Use password_verify() — in real apps, passwords stored with password_hash()
        // (Demo DB uses plain text for simplicity — real apps MUST use password_hash)
        if ($user && $user['password'] === $password) {
            // In production: if ($user && password_verify($password, $user['password']))
            $success = "✅ Logged in as: {$user['username']} (Balance: \${$user['balance']})";
        } else {
            // BEST PRACTICE 4: Generic error message — don't reveal whether username or password was wrong
            $error = '❌ Invalid credentials.';
        }
    }
}

// ── Search (Secure) ───────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'search') {
    $term = trim($_POST['search'] ?? '');

    if (strlen($term) < 1 || strlen($term) > 50) {
        $error = 'Search term must be between 1 and 50 characters.';
    } else {
        // BEST PRACTICE 5: Prepared statement with LIKE — bind with wildcards in the value, not SQL
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username LIKE ?");
        $stmt->execute(['%' . $term . '%']); // ← wildcard in the parameter value, safe
        $search_results = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SQL Injection – Secure Demo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php render_demo_header(
    'SQL Injection — Secured',
    'secure',
    'SQL Injection',
    'Prepared statements ensure user input is always treated as data, never as SQL code.'
); ?>

<div class="demo-container">

    <div class="panel safe-panel">
        <div class="panel-header"><span>✔</span><h3>Defenses Active</h3></div>
        <div class="panel-body">
            <ul class="checklist">
                <li>PDO prepared statements — user input <strong>never</strong> touches the SQL string</li>
                <li>Input validation (length, type) before any DB operation</li>
                <li>Least-privilege column selection — only fetch columns needed</li>
                <li>Generic error messages — no DB schema exposed to users</li>
                <li>Passwords should use <code>password_hash()</code> / <code>password_verify()</code> (noted in code)</li>
                <li>UNION injection impossible — query structure is fixed at prepare time</li>
            </ul>
        </div>
    </div>

    <div class="two-col">
        <div class="panel">
            <div class="panel-header"><span>🔐</span><h3>Login (Try to Bypass)</h3></div>
            <div class="panel-body">
                <div class="alert alert-info" style="margin-bottom:14px">
                    Try: <code>' OR 1=1--</code> as username — it won't work.
                    <br>Valid: <code>alice</code> / <code>password1</code>
                </div>

                <?php if ($success && isset($_POST['action']) && $_POST['action'] === 'login'): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php elseif ($error && isset($_POST['action']) && $_POST['action'] === 'login'): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="text" name="password">
                    </div>
                    <button type="submit" class="btn-submit safe">Login Securely</button>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><span>🔎</span><h3>User Search (UNION-proof)</h3></div>
            <div class="panel-body">
                <div class="alert alert-info" style="margin-bottom:14px">
                    Try: <code>%' UNION SELECT...</code> — the query structure is fixed.
                </div>

                <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'search'): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="search">
                    <div class="form-group">
                        <label>Search Username</label>
                        <input type="text" name="search" maxlength="50"
                               value="<?= htmlspecialchars($_POST['search'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn-submit safe">Search Safely</button>
                </form>

                <?php if (!empty($search_results)): ?>
                    <div style="margin-top:16px">
                        <table>
                            <thead><tr><th>ID</th><th>Username</th><th>Email</th></tr></thead>
                            <tbody>
                            <?php foreach ($search_results as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif (isset($_POST['action']) && $_POST['action'] === 'search' && empty($error)): ?>
                    <p style="color:var(--muted);margin-top:14px">No users found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><span>📄</span><h3>The Secure Code</h3></div>
        <div class="panel-body">
<pre><code><span class="php-comment">// ✅ SECURE — prepared statement: SQL structure fixed BEFORE input is bound</span>
<span class="php-variable">$stmt</span> = <span class="php-variable">$pdo</span>-><span class="php-function">prepare</span>(<span class="php-string">"SELECT id, username, email, balance
                   FROM users
                   WHERE username = ?"</span>);
<span class="php-variable">$stmt</span>-><span class="php-function">execute</span>([<span class="php-variable">$username</span>]); <span class="php-comment">// ← bound as DATA, not SQL</span>

<span class="php-comment">// ✅ SECURE — LIKE with wildcards in parameter value</span>
<span class="php-variable">$stmt</span> = <span class="php-variable">$pdo</span>-><span class="php-function">prepare</span>(<span class="php-string">"SELECT id, username, email FROM users WHERE username LIKE ?"</span>);
<span class="php-variable">$stmt</span>-><span class="php-function">execute</span>([<span class="php-string">'%'</span> . <span class="php-variable">$term</span> . <span class="php-string">'%'</span>]);

<span class="php-comment">// ✅ In production — ALWAYS hash passwords</span>
<span class="php-variable">$hash</span> = <span class="php-function">password_hash</span>(<span class="php-variable">$plaintext</span>, PASSWORD_BCRYPT);
<span class="php-keyword">if</span> (<span class="php-function">password_verify</span>(<span class="php-variable">$input</span>, <span class="php-variable">$hash</span>)) { <span class="php-comment">/* authenticated */</span> }</code></pre>
        </div>
    </div>

    <a href="../vulnerable/sqli.php" class="btn btn-danger" style="display:inline-block;margin-top:8px">← See the Vulnerable Version</a>
</div>
</body>
</html>
