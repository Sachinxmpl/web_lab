# PHP Security Vulnerability Demo

An educational lab demonstrating three common web vulnerabilities and their mitigations, built with plain PHP and SQLite (no framework required).



## Vulnerabilities Covered

| # | Vulnerability | Vulnerable File | Secure File |
|---|---|---|---|
| 1 | Cross-Site Scripting (XSS) | `vulnerable/xss.php` | `secure/xss.php` |
| 2 | SQL Injection (SQLi) | `vulnerable/sqli.php` | `secure/sqli.php` |
| 3 | Cross-Site Request Forgery (CSRF) | `vulnerable/csrf.php` | `secure/csrf.php` |

---

## Project Structure

```
php-security-demo/
├── index.php                  # Lab home page
├── README.md
│
├── assets/
│   └── css/
│       └── style.css          # Shared stylesheet
│
├── includes/
│   ├── db.php                 # SQLite helper + seeding
│   └── header.php             # Shared page header component
│
├── vulnerable/
│   ├── xss.php                # ❌ Vulnerable XSS demo
│   ├── sqli.php               # ❌ Vulnerable SQL Injection demo
│   └── csrf.php               # ❌ Vulnerable CSRF demo
│
├── secure/
│   ├── xss.php                # ✅ Secure XSS demo
│   ├── sqli.php               # ✅ Secure SQL Injection demo
│   └── csrf.php               # ✅ Secure CSRF demo
│
└── data/
    └── demo.sqlite            # Auto-created on first run
```

---

## Getting Started

### Requirements

- PHP 8.0 or higher
- `pdo_sqlite` extension enabled (on by default in most PHP installs)

### Run with PHP's built-in server

```bash
git clone https://github.com/your-username/php-security-demo.git
cd php-security-demo
php -S localhost:8080
```

Then open [http://localhost:8080](http://localhost:8080) in your browser.

### Run with Apache / Nginx

Copy the project folder into your web root (e.g. `/var/www/html/php-security-demo`) and visit the URL in your browser. Ensure the `data/` directory is writable:

```bash
chmod 755 data/
```

---

## Vulnerability Breakdown

### 1. Cross-Site Scripting (XSS)

**Attack:** An attacker injects malicious `<script>` tags or event handlers into user-supplied input. When rendered in a browser, the script executes in the victim's session.

**Types demonstrated:**
- **Reflected XSS** — payload is submitted and immediately echoed back in the response
- **Stored XSS** — payload is saved to the database and executed for every subsequent visitor

**Vulnerable pattern:**
```php
// ❌ Raw echo — any HTML/script in $comment executes in the browser
echo $comment;
echo "<p>Hello, $username!</p>";
```

**Secure pattern:**
```php
// ✅ Escape all output with htmlspecialchars()
function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
echo e($comment);
echo "<p>Hello, " . e($username) . "!</p>";
```

**Key mitigations:**
- Always escape output with `htmlspecialchars()` — every variable, every time
- Set a `Content-Security-Policy` header to restrict script sources
- Use `X-Content-Type-Options: nosniff`
- Declare `charset=UTF-8` to prevent charset-sniffing attacks

---

### 2. SQL Injection (SQLi)

**Attack:** User input is concatenated directly into a SQL query, allowing an attacker to modify the query's logic — bypassing authentication, dumping data, or deleting records.

**Types demonstrated:**
- **Authentication bypass** — `' OR 1=1--` logs in without valid credentials
- **UNION-based extraction** — `UNION SELECT` dumps password hashes from the database

**Vulnerable pattern:**
```php
// ❌ String interpolation — attacker controls query structure
$query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
// Payload: ' OR '1'='1  → always true, bypasses auth
```

**Secure pattern:**
```php
// ✅ Prepared statement — structure is fixed before input is bound
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
$stmt->execute([$username, $password]);

// ✅ LIKE with wildcards in the parameter, not the SQL
$stmt = $pdo->prepare("SELECT * FROM users WHERE username LIKE ?");
$stmt->execute(['%' . $term . '%']);
```

**Key mitigations:**
- **Always use prepared statements** (PDO or MySQLi) — no exceptions
- Validate and sanitize input (length, type, format) before querying
- Use `password_hash()` and `password_verify()` — never store plaintext passwords
- Return generic error messages — never expose query details to users
- Apply least-privilege DB roles (read-only accounts for read-only operations)

---

### 3. Cross-Site Request Forgery (CSRF)

**Attack:** A malicious website tricks an authenticated user's browser into sending a state-changing request (e.g., a funds transfer) to your application. The browser automatically includes the victim's session cookie.

**Attack scenario:**
```html
<!-- Hosted on evil.com — fires automatically when victim visits -->
<form method="POST" action="https://yourbank.com/transfer" id="x">
    <input type="hidden" name="to_user" value="hacker">
    <input type="hidden" name="amount" value="9999">
</form>
<script>document.getElementById('x').submit();</script>
```

**Vulnerable pattern:**
```php
// ❌ No token check — any origin can trigger this
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    transferFunds($_POST['to_user'], $_POST['amount']);
}
```

**Secure pattern:**
```php
// ✅ Generate token once per session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ Validate before processing
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    die('CSRF validation failed');
}

// ✅ Embed in every form
// <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">

// ✅ Rotate after use
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
```

**Key mitigations:**
- Include a unique, session-tied CSRF token in every state-changing form
- Use `hash_equals()` for constant-time comparison (prevents timing attacks)
- Rotate the token after each successful submission
- Set `SameSite=Strict` (or `Lax`) on session cookies
- Verify the `Origin` / `Referer` header as an additional layer

---

## Demo Credentials

The SQLite database is auto-seeded with these users:

| Username | Password |
|---|---|
| admin | admin123 |
| alice | password1 |
| bob | password2 |
| charlie | qwerty |

> Passwords are stored in plaintext **for demo simplicity only**. In any real application, always use `password_hash()` and `password_verify()`.

---

## Security Best Practices Cheatsheet

| Vulnerability | Primary Fix | Secondary Mitigations |
|---|---|---|
| XSS | `htmlspecialchars()` on all output | CSP header, `strip_tags()`, charset declaration |
| SQLi | Prepared statements (PDO) | Input validation, least-privilege DB roles, generic errors |
| CSRF | Synchronizer token pattern | `SameSite` cookies, `Origin`/`Referer` validation |

---

## License

MIT — free to use for learning, teaching, and workshops.
