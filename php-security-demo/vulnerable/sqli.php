<?php
/**
 * VULNERABLE SQL INJECTION DEMO
 * ---------------------------------------------------------------
 * This page is INTENTIONALLY INSECURE for educational purposes.
 * It concatenates user input directly into SQL queries.
 * NEVER write code like this in production.
 * ---------------------------------------------------------------
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = get_db();

$result  = null;
$query   = '';
$error   = '';
$success = '';

// ── Login (Authentication Bypass via SQLi) ───────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // VULNERABLE: Raw string concatenation — attacker controls the SQL
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";

    try {
        $result = $pdo->query($query)->fetch();
        if ($result) {
            $success = "✅ Logged in as: {$result['username']} (Balance: \${$result['balance']})";
        } else {
            $error = "❌ Invalid credentials.";
        }
    } catch (Exception $e) {
        $error = "DB Error: " . $e->getMessage();
    }
}

// ── Search (UNION-based data extraction) ─────────────────────────────────────
$search_results = [];
$search_query   = '';
if (isset($_POST['action']) && $_POST['action'] === 'search') {
    $term = $_POST['search'] ?? '';

    // VULNERABLE: Input injected directly
    $search_query = "SELECT id, username, email FROM users WHERE username LIKE '%$term%'";

    try {
        $search_results = $pdo->query($search_query)->fetchAll();
    } catch (Exception $e) {
        $error = "DB Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SQL Injection – Vulnerable Demo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php render_demo_header(
    'SQL Injection (SQLi)',
    'vulnerable',
    'SQL Injection',
    'User input is concatenated directly into SQL queries — attackers control the database.'
); ?>

<div class="demo-container">

    <div class="panel danger-panel">
        <div class="panel-header"><span>⚠</span><h3>Attack Payloads to Try</h3></div>
        <div class="panel-body">
            <p style="color:var(--muted);margin-bottom:14px"><strong>Authentication Bypass</strong> — enter these in the Username field (any password):</p>
            <pre><code>' OR '1'='1
' OR 1=1--
admin'--
' OR 'x'='x</code></pre>
            <p style="color:var(--muted);margin:14px 0 8px"><strong>UNION-based Data Dump</strong> — enter in the Search field:</p>
            <pre><code>%' UNION SELECT id, username, password FROM users--
%' UNION SELECT 1, sqlite_version(), 3--</code></pre>
        </div>
    </div>

    <div class="two-col">
        <div class="panel">
            <div class="panel-header"><span>🔐</span><h3>Login (Bypass Me)</h3></div>
            <div class="panel-body">
                <?php if ($success && isset($_POST['action']) && $_POST['action'] === 'login'): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php elseif ($error && isset($_POST['action']) && $_POST['action'] === 'login'): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="' OR 1=1--">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="text" name="password" value="" placeholder="anything">
                    </div>
                    <button type="submit" class="btn-submit">Login</button>
                </form>

                <?php if ($query && isset($_POST['action']) && $_POST['action'] === 'login'): ?>
                    <div class="output-box" style="margin-top:14px">
                        <div class="output-label">Executed SQL Query</div>
                        <code><?= htmlspecialchars($query) ?></code>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><span>🔎</span><h3>User Search (UNION Attack)</h3></div>
            <div class="panel-body">
                <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'search'): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="search">
                    <div class="form-group">
                        <label>Search Username</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($_POST['search'] ?? '') ?>"
                               placeholder="Try: %' UNION SELECT ...">
                    </div>
                    <button type="submit" class="btn-submit">Search</button>
                </form>

                <?php if ($search_query): ?>
                    <div class="output-box" style="margin-top:14px">
                        <div class="output-label">Executed SQL Query</div>
                        <code><?= htmlspecialchars($search_query) ?></code>
                    </div>
                <?php endif; ?>

                <?php if (!empty($search_results)): ?>
                    <div style="margin-top:16px">
                        <table>
                            <thead><tr><th>ID</th><th>Username</th><th>Email / Data</th></tr></thead>
                            <tbody>
                            <?php foreach ($search_results as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['username'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><span>📄</span><h3>The Vulnerable Code</h3></div>
        <div class="panel-body">
<pre><code><span class="php-comment">// ❌ VULNERABLE — string interpolation with raw user input</span>
<span class="php-variable">$username</span> = <span class="php-variable">$_POST</span>[<span class="php-string">'username'</span>];
<span class="php-variable">$password</span> = <span class="php-variable">$_POST</span>[<span class="php-string">'password'</span>];

<span class="php-variable">$query</span> = <span class="php-string">"SELECT * FROM users
           WHERE username = '<span class="php-variable">$username</span>'
           AND password = '<span class="php-variable">$password</span>'"</span>;

<span class="php-comment">// Attacker input: ' OR '1'='1
// Resulting query:
// SELECT * FROM users WHERE username = '' OR '1'='1' AND password = ''
// → Returns ALL users → Authenticated!</span></code></pre>
        </div>
    </div>

    <a href="../secure/sqli.php" class="btn btn-safe" style="display:inline-block;margin-top:8px">→ See the Secure Version</a>
</div>
</body>
</html>
