<?php
/**
 * Shared page header for demo pages.
 *
 * @param string $title       Page title
 * @param string $type        'vulnerable' | 'secure'
 * @param string $vuln        Vulnerability name (e.g. 'XSS')
 * @param string $description Short description shown under title
 */
function render_demo_header(string $title, string $type, string $vuln, string $description): void {
    $tag   = $type === 'vulnerable'
        ? '<span class="tag tag-danger">⚠ Vulnerable</span>'
        : '<span class="tag tag-safe">✔ Secure</span>';
    $back  = htmlspecialchars('../index.php');
    echo <<<HTML
    <div class="demo-header">
        <a href="{$back}" class="btn btn-back">← Back to Lab</a>
        <div class="meta">
            {$tag}
            <span class="tag" style="background:var(--accent-dim);border:1px solid var(--accent);color:var(--accent)">{$vuln}</span>
        </div>
        <h1>{$title}</h1>
        <p>{$description}</p>
    </div>
    HTML;
}
