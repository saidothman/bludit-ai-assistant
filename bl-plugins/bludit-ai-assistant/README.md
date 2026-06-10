# Bludit AI Assistant Plugin

> AI-powered content assistant for [Bludit CMS](https://www.bludit.com/) — generate SEO titles, meta descriptions, and keyword suggestions directly inside the post editor.

![CI](https://github.com/YOUR_GITHUB_USERNAME/bludit-ai-assistant/actions/workflows/ci.yml/badge.svg)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php)
![License](https://img.shields.io/badge/license-MIT-green)
![Bludit](https://img.shields.io/badge/Bludit-4.0%2B-blue)

---

## ✦ Features

| Feature | Description |
|---|---|
| **Title generator** | Produces 3 SEO-optimised post titles based on your content |
| **Meta description** | Generates a 150–160 character meta description |
| **Keyword suggestions** | Extracts 8 relevant SEO keywords |
| **One-click insert** | Click any suggestion to copy it or insert it directly into the title field |
| **Secure by design** | API key never leaves the server — all requests proxied through PHP |
| **Rate limiting** | 10 requests per IP per minute, no Redis needed |
| **CSRF protection** | Session-bound nonce on every AJAX request |

---

## 📸 Demo

<!-- Replace with your actual GIF -->
![AI Assistant Demo](docs/demo.gif)

---

## Requirements

- PHP 8.1+
- Bludit CMS 4.0+
- OpenAI API key ([get one here](https://platform.openai.com/api-keys))
- `ext-curl` and `ext-json` PHP extensions (standard on most hosts)

---

## Installation

**Option 1 — Manual (recommended for Bludit)**

1. Download or clone this repository.
2. Copy the `bl-plugins/bludit-ai-assistant/` folder into your Bludit installation's `bl-plugins/` directory.
3. Log in to the Bludit admin panel.
4. Go to **Plugins** and activate **AI Assistant**.
5. Click **Settings** next to the plugin and enter your OpenAI API key.

**Option 2 — Git clone directly into Bludit**

```bash
cd /path/to/your/bludit/bl-plugins
git clone https://github.com/YOUR_GITHUB_USERNAME/bludit-ai-assistant.git
```

---

## Configuration

| Setting | Default | Description |
|---|---|---|
| API Key | _(empty)_ | Your OpenAI secret key — stored server-side only |
| Model | `gpt-4o-mini` | OpenAI model to use (mini = fast & cheap, gpt-4o = highest quality) |
| Max Tokens | `300` | Maximum length of AI response |
| Enabled | `true` | Toggle the plugin on/off without deactivating |

---

## How It Works

```
Browser (Admin Editor)
    │
    │  POST /bl-plugins/bludit-ai-assistant/ajax.php
    │  { action, content, nonce }
    │
    ▼
ajax.php (server-side proxy)
    ├─ Verify CSRF nonce
    ├─ Rate-limit check (RateLimiter)
    ├─ Build prompt (PromptBuilder)
    └─ Call OpenAI API (OpenAIClient)
              │
              ▼
        OpenAI API
        (API key never leaves server)
              │
              ▼
    JSON response → browser
```

**Key design decisions:**

- **No Composer dependency on OpenAI SDK** — keeps the plugin self-contained and compatible with shared hosting. The HTTP call is a simple cURL wrapper.
- **PromptBuilder is a pure class** — all prompts are in one place, easy to test and tune without touching HTTP code.
- **File-based rate limiter** — works on SiteGround and shared hosts where Redis is unavailable.

---

## Development

```bash
# Clone the repo
git clone https://github.com/YOUR_GITHUB_USERNAME/bludit-ai-assistant.git
cd bludit-ai-assistant

# Install dev dependencies
composer install

# Run tests
composer test
```

### Running tests

```
PHPUnit 10.x

PromptBuilderTest
  ✓ titles returns system and user keys
  ✓ titles user prompt contains content
  ✓ titles requests exactly 3 titles
  ✓ meta description prompt specifies character limit
  ✓ keywords requests exactly 8 keywords
  ✓ long content is truncated
  ✓ html tags are stripped

RateLimiterTest
  ✓ first request is allowed
  ✓ multiple requests within limit are allowed
  ✓ request beyond limit is blocked
  ✓ different ips are tracked independently
  ✓ storage directory is created if missing

OK (12 tests, 18 assertions)
```

---

## Project Structure

```
bludit-ai-assistant/
├── bl-plugins/
│   └── bludit-ai-assistant/
│       ├── plugin.php           # Main Bludit plugin class
│       ├── ajax.php             # Server-side AJAX proxy (secure)
│       ├── metadata.json        # Plugin metadata
│       ├── src/
│       │   ├── OpenAIClient.php # HTTP wrapper for OpenAI API
│       │   ├── PromptBuilder.php# All prompt construction logic
│       │   └── RateLimiter.php  # File-based IP rate limiter
│       └── assets/
│           ├── plugin.js        # Admin editor UI integration
│           └── plugin.css       # Styles for the AI panel
├── tests/
│   └── AiAssistantTest.php      # PHPUnit tests
├── .github/
│   └── workflows/ci.yml         # GitHub Actions CI (PHP 8.1/8.2/8.3)
├── composer.json
└── README.md
```

---

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you'd like to change. Make sure tests pass before submitting a PR.

```bash
composer test
```

---

## Roadmap

- [ ] Support for alternative AI providers (Anthropic Claude, Mistral)
- [ ] Multilingual prompt support (German, Arabic)
- [ ] Content readability score
- [ ] Auto-tagging based on content analysis
- [ ] Bludit Plugin Directory submission

---

## License

[MIT](LICENSE) — Said Malla Othman
