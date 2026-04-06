<?php
/**
 * VULNERABLE XSS DEMO
 * ---------------------------------------------------------------
 * This page is INTENTIONALLY INSECURE for educational purposes.
 * It directly echoes user input without sanitization.
 * NEVER write code like this in production.
 * ---------------------------------------------------------------
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

seed_comments();
$pdo = get_db();

// VULNERABLE: No sanitization, raw user input inserted into DB and echoed
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? 'Anonymous'; // ← raw, unsanitized
    $comment  = $_POST['comment']  ?? '';           // ← raw, unsanitized

    // VULNERABLE: No prepared statement, no escaping (for comments we use concat string)
    $pdo->exec("INSERT INTO comments (username, comment) VALUES ('$username', '$comment')");
    $message = "Comment posted by: $username"; // ← reflected XSS
}

// VULNERABLE: Stored XSS — raw data fetched and echoed without escaping
$comments = $pdo->query("SELECT * FROM comments ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>XSS – Vulnerable Demo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php render_demo_header(
    'Cross-Site Scripting (XSS)',
    'vulnerable',
    'XSS',
    'User input is echoed directly into the page without sanitization — scripts execute.'
); ?>

<div class="demo-container">

    <div class="panel danger-panel">
        <div class="panel-header">
            <span>⚠</span>
            <h3>Attack Payloads to Try</h3>
        </div>
        <div class="panel-body">
            <p style="color:var(--muted);margin-bottom:12px">Paste these in the <strong>Comment</strong> field below:</p>
            <pre><code>&lt;script&gt;alert('XSS!')&lt;/script&gt;
&lt;img src=x onerror="alert('Stored XSS')"&gt;
&lt;svg onload="document.body.style.background='red'"&gt;&lt;/svg&gt;
&lt;a href="javascript:alert('XSS via href')"&gt;Click me&lt;/a&gt;</code></pre>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><span>💬</span><h3>Post a Comment (Vulnerable)</h3></div>
        <div class="panel-body">
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="Hacker" required>
                </div>
                <div class="form-group">
                    <label>Comment</label>
                    <textarea name="comment" placeholder="Try a script tag here..."></textarea>
                </div>
                <button type="submit" class="btn-submit">Post Comment</button>
            </form>

            <?php if ($message): ?>
                <div class="output-box" style="margin-top:16px">
                    <div class="output-label">Reflected Output (Vulnerable)</div>
                    <!-- VULNERABLE: $message echoed without escaping -->
                    <div><?= $message ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><span>📋</span><h3>Comment Feed (Stored XSS)</h3></div>
        <div class="panel-body">
            <?php foreach ($comments as $c): ?>
                <div style="padding:12px 0; border-bottom:1px solid var(--border)">
                    <!-- VULNERABLE: $c['username'] and $c['comment'] echoed raw -->
                    <strong><?= $c['username'] ?></strong>
                    <p><?= $c['comment'] ?></p>
                    <small style="color:var(--muted)"><?= $c['created_at'] ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel info-panel">
        <div class="panel-header"><span>🔍</span><h3>Why This Is Dangerous</h3></div>
        <div class="panel-body">
            <ul class="checklist" style="--safe:#ff4757">
                <li style="--safe:#ff4757">Reflected XSS: <code>$_POST</code> values echoed directly into HTML with <code>&lt;?= $var ?&gt;</code></li>
                <li style="--safe:#ff4757">Stored XSS: Script payloads are saved to the DB and executed for every visitor</li>
                <li style="--safe:#ff4757">Cookie theft, session hijacking, keylogging all become possible</li>
                <li style="--safe:#ff4757">Attackers can silently redirect users to phishing sites</li>
            </ul>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><span>📄</span><h3>The Vulnerable Code</h3></div>
        <div class="panel-body">
<pre><code><span class="php-comment">// ❌ VULNERABLE — raw echo without escaping</span>
<span class="php-keyword">echo</span> <span class="php-string">"Comment posted by: $username"</span>;

<span class="php-comment">// ❌ VULNERABLE — raw DB echo</span>
<span class="php-keyword">echo</span> <span class="php-variable">$c</span>[<span class="php-string">'comment'</span>];

<span class="php-comment">// ❌ In template:</span>
<span class="php-string">&lt;?= $message ?&gt;</span>
<span class="php-string">&lt;?= $c['comment'] ?&gt;</span></code></pre>
        </div>
    </div>

    <a href="../secure/xss.php" class="btn btn-safe" style="display:inline-block;margin-top:8px">→ See the Secure Version</a>
</div>
</body>
</html>
