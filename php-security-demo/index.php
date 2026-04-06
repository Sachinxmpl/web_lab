<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Security Demo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="hero">
        <div class="hero-content">
            <div class="badge">Educational Lab</div>
            <h1>PHP Security<br><span class="accent">Vulnerability Demo</span></h1>
            <p class="subtitle">Explore common web vulnerabilities and their mitigations in a safe, controlled environment.</p>
        </div>
    </div>

    <div class="container">
        <div class="warning-banner">
            ⚠️ <strong>Warning:</strong> This project is for <strong>educational purposes only</strong>. Never deploy vulnerable code in production.
        </div>

        <div class="cards-grid">

            <div class="card xss">
                <div class="card-icon">🔓</div>
                <div class="card-label">Vulnerability 01</div>
                <h2>Cross-Site Scripting</h2>
                <p>XSS allows attackers to inject malicious scripts into web pages viewed by other users.</p>
                <div class="card-links">
                    <a href="vulnerable/xss.php" class="btn btn-danger">Vulnerable Demo</a>
                    <a href="secure/xss.php" class="btn btn-safe">Secure Demo</a>
                </div>
            </div>

            <div class="card sqli">
                <div class="card-icon">💉</div>
                <div class="card-label">Vulnerability 02</div>
                <h2>SQL Injection</h2>
                <p>SQLi lets attackers manipulate database queries to bypass auth or extract data.</p>
                <div class="card-links">
                    <a href="vulnerable/sqli.php" class="btn btn-danger">Vulnerable Demo</a>
                    <a href="secure/sqli.php" class="btn btn-safe">Secure Demo</a>
                </div>
            </div>

            <div class="card csrf">
                <div class="card-icon">🎭</div>
                <div class="card-label">Vulnerability 03</div>
                <h2>Cross-Site Request Forgery</h2>
                <p>CSRF tricks authenticated users into submitting unintended requests to other sites.</p>
                <div class="card-links">
                    <a href="vulnerable/csrf.php" class="btn btn-danger">Vulnerable Demo</a>
                    <a href="secure/csrf.php" class="btn btn-safe">Secure Demo</a>
                </div>
            </div>

        </div>

        <div class="info-section">
            <h2>How to Use This Lab</h2>
            <div class="steps">
                <div class="step">
                    <span class="step-num">1</span>
                    <div>
                        <strong>Click "Vulnerable Demo"</strong> to see how the attack works without any protection.
                    </div>
                </div>
                <div class="step">
                    <span class="step-num">2</span>
                    <div>
                        <strong>Try the attack payloads</strong> listed on each demo page.
                    </div>
                </div>
                <div class="step">
                    <span class="step-num">3</span>
                    <div>
                        <strong>Click "Secure Demo"</strong> to see the same scenario with proper defenses applied.
                    </div>
                </div>
                <div class="step">
                    <span class="step-num">4</span>
                    <div>
                        <strong>Compare the code</strong> in <code>/vulnerable/</code> vs <code>/secure/</code> directories.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>PHP Security Demo &mdash; For Educational Use Only</p>
    </footer>
</body>
</html>
