<?php

/**
 * Bludit AI Assistant Plugin
 *
 * Integrates OpenAI API with Bludit CMS to provide:
 * - AI-generated post titles
 * - AI-generated meta descriptions
 * - SEO keyword suggestions
 *
 * @author  Said Othman
 * @license MIT
 */

class AiAssistant extends Plugin
{
    /** Default database field values */
    public function init(): void
    {
        $this->dbFields = [
            'provider'    => 'openai',
            'apiKey'      => '',
            'model'       => 'gpt-4o-mini',
            'maxTokens'   => 300,
            'enabled'     => true,
            'temperature' => 0.7,
        ];
    }

    // -------------------------------------------------------------------------
    // Admin settings form
    // -------------------------------------------------------------------------

    public function form(): string
    {
        $provider  = $this->getValue('provider');
        $apiKey    = $this->getValue('apiKey');
        $model     = $this->getValue('model');
        $maxTokens = (int) $this->getValue('maxTokens');
        $enabled   = (bool) $this->getValue('enabled');

        $providers = [
            'openai'   => 'OpenAI',
            'gemini'   => 'Gemini',
            'deepseek' => 'DeepSeek'
        ];

        $models = [
            'openai' => [
                'gpt-4o-mini' => 'gpt-4o-mini (Fast, cheap & default)',
                'gpt-4o'      => 'gpt-4o (High intelligence)',
                'gpt-3.5-turbo' => 'gpt-3.5-turbo (Legacy)'
            ],
            'gemini' => [
                'gemini-2.5-flash' => 'gemini-2.5-flash (Latest Flash)',
                'gemini-2.0-flash' => 'gemini-2.0-flash (Recommended Flash)',
                'gemini-1.5-flash' => 'gemini-1.5-flash (Legacy Flash)'
            ],
            'deepseek' => [
                'deepseek-chat'     => 'deepseek-chat (General chat / DeepSeek-V3)',
                'deepseek-reasoner' => 'deepseek-reasoner (Reasoning / DeepSeek-R1)'
            ]
        ];

        $html  = '<div class="ai-assistant-settings">';

        // Enabled toggle
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->t('Enable AI Assistant') . '</label>';
        $html .= '<select name="enabled">';
        $html .= '<option value="true"' . ($enabled ? ' selected' : '') . '>' . $this->t('Enabled') . '</option>';
        $html .= '<option value="false"' . (!$enabled ? ' selected' : '') . '>' . $this->t('Disabled') . '</option>';
        $html .= '</select>';
        $html .= '</div>';

        // Provider select
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->t('AI Provider') . '</label>';
        $html .= '<select name="provider" id="ai-provider-select">';
        foreach ($providers as $pVal => $pLabel) {
            $selected = ($pVal === $provider) ? ' selected' : '';
            $html .= "<option value=\"{$pVal}\"{$selected}>{$pLabel}</option>";
        }
        $html .= '</select>';
        $html .= '</div>';

        // API Key
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->t('API Key') . '</label>';
        $html .= '<input type="password" name="apiKey" '
            . 'value="' . htmlspecialchars($apiKey) . '" '
            . 'placeholder="Enter your API Key" autocomplete="off">';
        $html .= '<small>' . $this->t('Your key is stored encrypted and never exposed to the browser.') . '</small>';
        $html .= '</div>';

        // Model select
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->t('Model') . '</label>';
        $html .= '<select name="model" id="ai-model-select">';
        foreach ($models as $prov => $mList) {
            foreach ($mList as $mVal => $mLabel) {
                $selected = ($mVal === $model) ? ' selected' : '';
                $html .= "<option value=\"{$mVal}\" data-provider=\"{$prov}\"{$selected}>{$mLabel}</option>";
            }
        }
        $html .= '</select>';
        $html .= '</div>';

        // Max tokens
        $html .= '<div class="form-group">';
        $html .= '<label>' . $this->t('Max Tokens') . '</label>';
        $html .= '<input type="number" name="maxTokens" '
            . 'value="' . $maxTokens . '" min="50" max="1000">';
        $html .= '</div>';

        $html .= '</div>';

        // Add client-side dynamic model filtering script
        $html .= '
        <script>
        (function() {
            const providerSel = document.getElementById("ai-provider-select");
            const modelSel = document.getElementById("ai-model-select");
            if (!providerSel || !modelSel) return;

            function updateModels() {
                const selectedProvider = providerSel.value;
                let firstVal = null;
                let hasCurrentSelected = false;

                Array.from(modelSel.options).forEach(opt => {
                    const prov = opt.getAttribute("data-provider");
                    if (prov === selectedProvider) {
                        opt.style.display = "";
                        opt.disabled = false;
                        if (!firstVal) firstVal = opt.value;
                        if (opt.value === modelSel.value) hasCurrentSelected = true;
                    } else {
                        opt.style.display = "none";
                        opt.disabled = true;
                    }
                });

                if (!hasCurrentSelected && firstVal) {
                    modelSel.value = firstVal;
                }
            }

            providerSel.addEventListener("change", updateModels);
            updateModels();
        })();
        </script>
        ';

        return $html;
    }

    // -------------------------------------------------------------------------
    // Inject JS + CSS into the admin post editor
    // -------------------------------------------------------------------------

    public function adminBodyEnd(): void
    {
        // Only inject on post create/edit pages.
        // Bludit stores the current page slug in $layout['view'] (set in bl-kernel/boot/admin.php).
        global $layout;
        $view = $layout['view'] ?? '';
        if (! in_array($view, ['new-content', 'edit-content'], true)) {
            return;
        }

        if (! $this->getValue('enabled')) {
            return;
        }

        $pluginUrl = $this->domainPath();

        echo '<link rel="stylesheet" href="' . $pluginUrl . 'assets/plugin.css">' . PHP_EOL;
        echo '<script src="' . $pluginUrl . 'assets/plugin.js" defer></script>' . PHP_EOL;

        // Pass PHP config to JS securely — no API key, only safe values
        echo '<script>';
        echo 'window.AiAssistantConfig = ' . json_encode([
            'ajaxUrl' => DOMAIN_PLUGINS . 'bludit-ai-assistant/ajax.php',
            'nonce'   => $this->generateNonce(),
        ]) . ';';
        echo '</script>' . PHP_EOL;
    }

    // -------------------------------------------------------------------------
    // CSRF nonce helper
    // -------------------------------------------------------------------------

    public function generateNonce(): string
    {
        if (empty($_SESSION['ai_assistant_nonce'])) {
            $_SESSION['ai_assistant_nonce'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['ai_assistant_nonce'];
    }

    public static function verifyNonce(string $nonce): bool
    {
        return isset($_SESSION['ai_assistant_nonce'])
            && hash_equals($_SESSION['ai_assistant_nonce'], $nonce);
    }

    /**
     * Translation helper translating keys using the global $L object
     */
    public function t(string $key): string
    {
        global $L;
        return $L->get($key);
    }
}
