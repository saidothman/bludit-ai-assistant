<?php
/**
 * TEMPORARY DEBUG FILE - DELETE AFTER TESTING
 * Access via: http://localhost:8000/bl-plugins/bludit-ai-assistant/debug_session.php
 */

header('Content-Type: text/plain');

ob_start();
$loadTime = microtime(true);

if (!defined('BLUDIT')) define('BLUDIT', true);
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if (!defined('PATH_ROOT')) define('PATH_ROOT', dirname(__DIR__, 2) . DS);
if (!defined('PATH_BOOT')) define('PATH_BOOT', PATH_ROOT . 'bl-kernel' . DS . 'boot' . DS);

require PATH_BOOT . 'init.php';
require PATH_BOOT . 'rules' . DS . '60.plugins.php';

ob_clean();

echo "=== SESSION DEBUG ===" . PHP_EOL;
echo "Session status: " . session_status() . PHP_EOL;
echo "Session name: " . session_name() . PHP_EOL;
echo "Session ID: " . session_id() . PHP_EOL;
echo "Session cookie sent: " . (isset($_COOKIE[session_name()]) ? 'YES -> ' . $_COOKIE[session_name()] : 'NO') . PHP_EOL;
echo PHP_EOL;
echo "=== _SESSION KEYS ===" . PHP_EOL;
if (empty($_SESSION)) {
    echo "(empty - no session data)" . PHP_EOL;
} else {
    foreach ($_SESSION as $k => $v) {
        echo "$k => " . (is_string($v) ? $v : json_encode($v)) . PHP_EOL;
    }
}
echo PHP_EOL;
echo "=== NONCE CHECK ===" . PHP_EOL;
echo "ai_assistant_nonce in session: " . (isset($_SESSION['ai_assistant_nonce']) ? 'YES -> ' . $_SESSION['ai_assistant_nonce'] : 'NO') . PHP_EOL;
echo PHP_EOL;
echo "=== PLUGINS ===" . PHP_EOL;
echo "AiAssistant in plugins[all]: " . (isset($plugins['all']['AiAssistant']) ? 'YES' : 'NO') . PHP_EOL;
