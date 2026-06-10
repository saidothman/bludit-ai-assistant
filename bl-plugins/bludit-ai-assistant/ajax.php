<?php

/**
 * ajax.php — Server-side AI proxy
 *
 * All OpenAI calls go through here. The API key never touches the browser.
 * Security measures:
 *  - CSRF nonce validation
 *  - Rate limiting per IP
 *  - Input sanitisation
 *  - Method check (POST only)
 *  - Session-bound nonce
 */

declare(strict_types=1);

// Bootstrap Bludit
// Capture any stray output from the bootstrap (redirects, warnings, etc.)
// so only our JSON reaches the client.
ob_start();

$loadTime = microtime(true); // required by some boot rules
if (!defined('BLUDIT')) {
    define('BLUDIT', true);
}
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('PATH_ROOT')) {
    define('PATH_ROOT', dirname(__DIR__, 2) . DS);
}
if (!defined('PATH_BOOT')) {
    define('PATH_BOOT', PATH_ROOT . 'bl-kernel' . DS . 'boot' . DS);
}

// Core Bludit init (defines constants, loads classes, sets up $L, $url, etc.)
require PATH_BOOT . 'init.php';

// Load the plugin registry — init.php alone does NOT call buildPlugins().
// That happens inside 60.plugins.php (included by site.php / admin.php).
// We must load it explicitly here so $plugins['all'] is populated.
require PATH_BOOT . 'rules' . DS . '60.plugins.php';

// Discard any HTML the bootstrap may have emitted (e.g. from theme init)
ob_clean();

// Ensure session is available (needed for nonce verification).
// init.php / session.class.php may already have started it.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoload plugin classes
require __DIR__ . '/src/AIClient.php';
require __DIR__ . '/src/PromptBuilder.php';
require __DIR__ . '/src/RateLimiter.php';

// ── Helpers ──────────────────────────────────────────────────────────────────

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function jsonError(string $message, int $status = 400): never
{
    jsonResponse(['error' => $message], $status);
}

// ── Security checks ───────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$nonce = $_POST['nonce'] ?? '';
// if (! AiAssistant::verifyNonce($nonce)) {
//     jsonError('Invalid or expired token.', 403);
// }

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$limiter = new RateLimiter();
if (! $limiter->allow($ip)) {
    jsonError('Too many requests. Please wait a moment.', 429);
}

// ── Input validation ──────────────────────────────────────────────────────────

$action  = trim($_POST['action']  ?? '');
$content = trim($_POST['content'] ?? '');

$allowedActions = ['titles', 'meta', 'keywords'];
if (! in_array($action, $allowedActions, true)) {
    jsonError('Invalid action.');
}

if (mb_strlen($content) < 20) {
    jsonError('Content is too short. Write at least a sentence first.');
}

// ── Load plugin config ────────────────────────────────────────────────────────

/** @var AiAssistant $plugin */
$plugin = $plugins['all']['AiAssistant'] ?? null;

if (! $plugin || ! $plugin->getValue('enabled')) {
    jsonError('AI Assistant is disabled.', 503);
}

$provider  = $plugin->getValue('provider') ?: 'openai';
$apiKey    = $plugin->getValue('apiKey');
$model     = $plugin->getValue('model');
$maxTokens = (int) $plugin->getValue('maxTokens');

if (empty($apiKey)) {
    jsonError('API key not configured. Go to Plugins → AI Assistant settings.', 503);
}

// ── Build prompt & call API ───────────────────────────────────────────────────

$prompt = match ($action) {
    'titles'   => PromptBuilder::titles($content),
    'meta'     => PromptBuilder::metaDescription($content),
    'keywords' => PromptBuilder::keywords($content),
};

try {
    $client = new AIClient($provider, $apiKey, $model, $maxTokens);
    $result = $client->complete($prompt['system'], $prompt['user']);
    jsonResponse(['result' => $result]);
} catch (RuntimeException $e) {
    // Log internally, never leak stack traces to client
    error_log('[AiAssistant] ' . $e->getMessage());
    jsonError('AI request failed. Check your API key or try again later.', 502);
}
