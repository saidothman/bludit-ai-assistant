<?php

/**
 * AIClient
 *
 * Thin cURL wrapper supporting OpenAI, DeepSeek, and Gemini APIs.
 *
 * SSL strategy (works on localhost, shared hosting, VPS, Docker, any OS):
 *  1. Use the bundled cacert.pem shipped with this plugin (most reliable)
 *  2. Fall back to php.ini curl.cainfo if set
 *  3. Fall back to common system CA bundle paths
 *  4. If nothing found, throw a clear error — never silently disable SSL
 */
class AIClient
{
    // OpenAI-compatible endpoints
    private const ENDPOINT_OPENAI    = 'https://api.openai.com/v1/chat/completions';
    private const ENDPOINT_DEEPSEEK  = 'https://api.deepseek.com/chat/completions';

    public function __construct(
        private readonly string $provider    = 'openai',
        private readonly string $apiKey      = '',
        private readonly string $model       = 'gpt-4o-mini',
        private readonly int    $maxTokens   = 300,
        private readonly float  $temperature = 0.7
    ) {}

    /**
     * Send a system + user prompt and return the response text.
     *
     * @throws RuntimeException on cURL error, SSL error, or API error
     */
    public function complete(string $systemPrompt, string $userMessage): string
    {
        if ($this->provider === 'gemini') {
            return $this->completeGemini($systemPrompt, $userMessage);
        }

        return $this->completeOpenAICompatible($systemPrompt, $userMessage);
    }

    // ── OpenAI-compatible (OpenAI + DeepSeek) ────────────────────────────────

    private function completeOpenAICompatible(string $systemPrompt, string $userMessage): string
    {
        $url = $this->provider === 'deepseek'
            ? self::ENDPOINT_DEEPSEEK
            : self::ENDPOINT_OPENAI;

        $payload = json_encode([
            'model'       => $this->model,
            'max_tokens'  => $this->maxTokens,
            'temperature' => $this->temperature,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userMessage],
            ],
        ]);

        $raw  = $this->curlPost($url, $payload, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);

        $body = json_decode($raw['body'], true);

        if ($raw['code'] !== 200) {
            $msg = $body['error']['message'] ?? "HTTP {$raw['code']}";
            throw new RuntimeException(ucfirst($this->provider) . ' API error: ' . $msg);
        }

        return trim($body['choices'][0]['message']['content'] ?? '');
    }

    // ── Gemini ────────────────────────────────────────────────────────────────

    private function completeGemini(string $systemPrompt, string $userMessage): string
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . urlencode($this->model)
            . ':generateContent?key='
            . urlencode($this->apiKey);

        $payload = json_encode([
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [['text' => $userMessage]],
                ],
            ],
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'generationConfig' => [
                'temperature'     => $this->temperature,
                'maxOutputTokens' => $this->maxTokens,
            ],
        ]);

        $raw  = $this->curlPost($url, $payload, ['Content-Type: application/json']);
        $body = json_decode($raw['body'], true);

        if ($raw['code'] !== 200) {
            $msg = $body['error']['message'] ?? "HTTP {$raw['code']}";
            throw new RuntimeException('Gemini API error: ' . $msg);
        }

        return trim($body['candidates'][0]['content']['parts'][0]['text'] ?? '');
    }

    // ── Shared cURL helper ────────────────────────────────────────────────────

    /**
     * @return array{body: string, code: int}
     * @throws RuntimeException on cURL / SSL failure
     */
    private function curlPost(string $url, string $payload, array $headers): array
    {
        $caBundle = $this->resolveCaBundle();

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
            // SSL — always verified, never disabled
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO         => $caBundle,
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            throw new RuntimeException(
                'cURL error (' . $errno . '): ' . $error . "\n" .
                'CA bundle used: ' . $caBundle
            );
        }

        return ['body' => $body, 'code' => $code];
    }

    // ── SSL CA bundle resolver ────────────────────────────────────────────────

    /**
     * Resolve the best available CA certificate bundle.
     *
     * Order of priority:
     *  1. Bundled cacert.pem shipped inside this plugin (works everywhere)
     *  2. php.ini curl.cainfo directive
     *  3. php.ini openssl.cafile directive
     *  4. Common system paths (Linux distros, macOS, Windows XAMPP/WAMP)
     *
     * @throws RuntimeException if no CA bundle can be found anywhere
     */
    private function resolveCaBundle(): string
    {
        // ── 1. Bundled cert (highest priority — consistent across all environments)
        $bundled = __DIR__ . DIRECTORY_SEPARATOR . 'cacert.pem';
        if (file_exists($bundled) && is_readable($bundled)) {
            return $bundled;
        }

        // ── 2. PHP ini curl.cainfo
        $iniCa = ini_get('curl.cainfo');
        if (!empty($iniCa) && file_exists($iniCa) && is_readable($iniCa)) {
            return $iniCa;
        }

        // ── 3. PHP ini openssl.cafile
        $opensslCa = ini_get('openssl.cafile');
        if (!empty($opensslCa) && file_exists($opensslCa) && is_readable($opensslCa)) {
            return $opensslCa;
        }

        // ── 4. System paths
        $systemPaths = [
            // Linux
            '/etc/ssl/certs/ca-certificates.crt',     // Ubuntu, Debian
            '/etc/pki/tls/certs/ca-bundle.crt',       // CentOS, RHEL, Fedora
            '/etc/ssl/ca-bundle.pem',                  // OpenSUSE
            '/etc/pki/tls/cacert.pem',                 // older CentOS
            '/usr/share/ssl/certs/ca-bundle.crt',
            '/usr/local/share/certs/ca-root-nss.crt', // FreeBSD
            '/etc/ssl/cert.pem',                       // Alpine Linux, macOS
            // macOS
            '/usr/local/etc/openssl/cert.pem',         // Homebrew OpenSSL
            '/opt/homebrew/etc/openssl@3/cert.pem',    // Homebrew on Apple Silicon
            // Windows (XAMPP / WAMP / Laragon)
            'C:/xampp/php/extras/ssl/cacert.pem',
            'C:/wamp64/bin/php/php8.1.0/extras/ssl/cacert.pem',
            'C:/laragon/bin/php/php-8.1/extras/ssl/cacert.pem',
        ];

        foreach ($systemPaths as $path) {
            if (file_exists($path) && is_readable($path)) {
                return $path;
            }
        }

        // ── Nothing found — throw a helpful error instead of disabling SSL
        throw new RuntimeException(
            'No SSL CA certificate bundle found. ' .
            'Please download https://curl.se/ca/cacert.pem and save it as ' .
            __DIR__ . DIRECTORY_SEPARATOR . 'cacert.pem'
        );
    }
}
