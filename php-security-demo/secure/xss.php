<?php
/**
 * SECURE XSS DEMO
 * ---------------------------------------------------------------
 * Demonstrates best practices to prevent Cross-Site Scripting:
 *  1. htmlspecialchars() on ALL output
 *  2. Prepared statements for DB operations
 *  3. Content-Security-Policy header
 *  4. Input validation / length limits
 * ---------------------------------------------------------------
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

// BEST PRACTICE 1: Set Content-Security-Policy header
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'");

seed_comments();
$pdo = get_db();

// Helper: escape output (should be used on EVERY variable echoed into HTML)
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$message = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // BEST PRACTICE 2: Validate and sanitize input
    $username = trim($_POST['username'] ?? '');
    $comment  = trim($_POST['comment']  ?? '');

    if (strlen($username) < 2 || strlen($username) > 50) {
        $errors[] = 'Username must be between 2 and 50 characters.';
    }
    if (strlen($comment) < 1 || strlen($comment) > 500) {
        $errors[] = 'Comment must be between 1 and 500 characters.';
    }

    // BEST PRACTICE 3: Strip dangerous tags (optional, in addition to output escaping)
    $username = strip_tags($username);
    $comment  = strip_tags($comment);

    if (empty($errors)) {
        // BEST PRACTICE 4: Use prepared statements to avoid SQLi AND for good practice
        $stmt = $pdo->prepare("INSERT INTO comments (username, comment) VALUES (?, ?)");
        $stmt->execute([$username, $comment]);

        // BEST PRACTICE 5: Escape on output — NOT on storage
        $message = 'Comment posted by: ' . e($username);
    }
}

$comments = $pdo->query("SELECT * FROM comments ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- BEST PRACTICE 6: Always declare charset to prevent charset-sniffing attacks -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <title>XSS – Secure Demo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php render_demo_header(
    'Cross-Site Scripting (XSS) — Secured',
    'secure',
    'XSS',
    'All user input is escaped on output. A CSP header is set. Scripts cannot execute.'
); ?>

<div class="demo-container">

    <div class="panel safe-panel">
        <div class="panel-header"><span>✔</span><h3>Defenses Active</h3></div>
        <div class="panel-body">
            <ul class="checklist">
                <li><code>htmlspecialchars()</code> used on every echoed variable (<code>e()</code> helper)</li>
                <li>Content-Security-Policy header blocks inline scripts from external origins</li>
                <li><code>strip_tags()</code> strips HTML before storage</li>
                <li>Input length validation prevents oversized payloads</li>
                <li>Prepared statements used for all DB writes</li>
                <li><code>X-Content-Type-Options: nosniff</code> header set</li>
            </ul>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-header"><span>💬</span><h3>Post a Comment (Secure)</h3></div>
        <div class="panel-body">
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" maxlength="50"
                           value="<?= e($_POST['username'] ?? 'Visitor') ?>" required>
                </div>
                <div class="form-group">
                    <label>Comment (try pasting a script tag!)</label>
                    <textarea name="comment" maxlength="500"
                              placeholder="&lt;script&gt;alert('XSS')&lt;/script&gt;"></textarea>
                </div>
                <button type="submit" class="btn-submit safe">Post Comment Safely</button>
            </form>

            <?php if ($message): ?>
                <div class="output-box" style="margin-top:16px">
                    <div class="output-label">Safely Rendered Output</div>
                    <!-- SECURE: $message already escaped via e() before assignment -->
                    <div><?= $message ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><span>📋</span><h3>Comment Feed (Escaped)</h3></div>
        <div class="panel-body">
            <?php foreach ($comments as $c): ?>
                <div style="padding:12px 0; border-bottom:1px solid var(--border)">
                    <!-- SECURE: Every output escaped with e() -->
                    <strong><?= e($c['username']) ?></strong>
                    <p><?= e($c['comment']) ?></p>
                    <small style="color:var(--muted)"><?= e($c['created_at']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><span>📄</span><h3>The Secure Code</h3></div>
        <div class="panel-body">
<pre><code><span class="php-comment">// ✅ SECURE — escape helper wraps htmlspecialchars</span>
<span class="php-keyword">function</span> <span class="php-function">e</span>(<span class="php-keyword">string</span> <span class="php-variable">$value</span>): <span class="php-keyword">string</span> {
    <span class="php-keyword">return</span> <span class="php-function">htmlspecialchars</span>(<span class="php-variable">$value</span>, ENT_QUOTES | ENT_HTML5, <span class="php-string">'UTF-8'</span>);
}

<span class="php-comment">// ✅ SECURE — escaped on output</span>
<span class="php-keyword">echo</span> <span class="php-string">'Comment by: '</span> . <span class="php-function">e</span>(<span class="php-variable">$username</span>);

<span class="php-comment">// ✅ SECURE — in template</span>
<span class="php-string">&lt;?= e($c['comment']) ?&gt;</span>
<span class="php-string">&lt;?= e($c['username']) ?&gt;</span>

<span class="php-comment">// ✅ SECURE — CSP header set at top of file</span>
<span class="php-function">header</span>(<span class="php-string">"Content-Security-Policy: default-src 'self'"</span>);</code></pre>
        </div>
    </div>

    <a href="../vulnerable/xss.php" class="btn btn-danger" style="display:inline-block;margin-top:8px">← See the Vulnerable Version</a>
</div>
</body>
</html>
