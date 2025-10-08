<?php
// Load polyfills first for PHP 5.6 compatibility helpers
require_once __DIR__ . '/polyfills.php';

require_once __DIR__ . '/Env.php';

// Load .env if present
$envPath = dirname(__DIR__) . '/.env';
Env::load($envPath);

// Timezone
define('APP_TIMEZONE', Env::get('TIMEZONE', 'America/Sao_Paulo'));
date_default_timezone_set(APP_TIMEZONE);

// Configs
define('UASG', (int) Env::get('UASG', '160517'));
define('CACHE_TTL', (int) Env::get('CACHE_TTL', '600'));
define('MAX_RETRIES', (int) Env::get('MAX_RETRIES', '6'));
define('BASE_BACKOFF', (float) Env::get('BASE_BACKOFF', '1.0'));

// Prefer IPv4 for compras.gov.br endpoints by default; override via .env (values: auto|v4|v6)
define('HTTP_IPRESOLVE', strtolower((string) Env::get('HTTP_IPRESOLVE', 'v4')));

// Proxy (opcional)
define('HTTP_PROXY_URL', (string) Env::get('HTTP_PROXY_URL', ''));
define('HTTP_PROXY_USERPWD', (string) Env::get('HTTP_PROXY_USERPWD', ''));
define('HTTP_NOPROXY', (string) Env::get('HTTP_NOPROXY', ''));
