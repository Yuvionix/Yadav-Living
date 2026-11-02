<?php
require_once __DIR__ . '/Includes/env_loader.php';

$siteUrl = rtrim((string)hms_env('APP_URL', ''), '/');
if ($siteUrl === '' && preg_match('/^[A-Za-z0-9.-]+$/', (string)hms_env('RENDER_EXTERNAL_HOSTNAME', ''))) {
    $siteUrl = 'https://' . hms_env('RENDER_EXTERNAL_HOSTNAME');
}

$siteParts = parse_url($siteUrl);
$siteUrlIsValid = filter_var($siteUrl, FILTER_VALIDATE_URL)
    && is_array($siteParts)
    && isset($siteParts['scheme'], $siteParts['host'])
    && in_array(strtolower($siteParts['scheme']), array('http', 'https'), true)
    && !array_intersect(array('user', 'pass', 'query', 'fragment'), array_keys($siteParts));

if (!$siteUrlIsValid) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Public site URL is not configured.');
}

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?php echo htmlspecialchars($siteUrl . '/home/home.php', ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></loc>
    <changefreq>monthly</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
