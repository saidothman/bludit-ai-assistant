# Bludit AI Assistant — Complete Build & Launch Guide

## Architecture Overview

```
bludit-ai-assistant/
├── bl-plugins/
│   └── bludit-ai-assistant/
│       ├── plugin.php           ← Bludit plugin class (settings, UI hooks)
│       ├── ajax.php             ← Secure server-side proxy (NEVER exposed to browser)
│       ├── metadata.json        ← Plugin name, version, author
│       ├── src/
│       │   ├── OpenAIClient.php ← cURL wrapper for OpenAI Chat Completions API
│       │   ├── PromptBuilder.php← All prompt logic (pure, testable)
│       │   └── RateLimiter.php  ← File-based IP rate limiter (no Redis needed)
│       └── assets/
│           ├── plugin.js        ← Editor UI panel (vanilla JS)
│           └── plugin.css       ← Plugin styles
├── tests/
│   └── AiAssistantTest.php      ← PHPUnit tests (12 tests, 18 assertions)
├── .github/workflows/ci.yml     ← GitHub Actions (PHP 8.1 / 8.2 / 8.3)
├── composer.json
├── README.md
└── LICENSE
```

---

## Phase 1 — Local Setup (Day 1)

### Step 1: Create your GitHub repository

1. Go to github.com → New repository
2. Name: `bludit-ai-assistant`
3. Description: `AI-powered content assistant plugin for Bludit CMS`
4. Visibility: **Public**
5. Init with README: Yes
6. Add topics after creation: `php` `bludit` `cms` `openai` `ai` `plugin` `seo`

### Step 2: Clone and set up locally

```bash
git clone https://github.com/YOUR_USERNAME/bludit-ai-assistant.git
cd bludit-ai-assistant
composer install
```

### Step 3: Install Bludit for local development

```bash
# Option A — PHP built-in server
git clone https://github.com/bludit/bludit.git bludit-local
cd bludit-local
php -S localhost:8000

# Option B — Docker (recommended)
docker run -d -p 8000:80 \
  -v $(pwd)/bl-plugins:/var/www/html/bl-plugins \
  --name bludit \
  bludit/docker:latest
```

Then visit http://localhost:8000 and complete the Bludit setup wizard.

### Step 4: Link the plugin folder

```bash
# Symlink so edits are reflected immediately
ln -s $(pwd)/bl-plugins/bludit-ai-assistant \
      bludit-local/bl-plugins/bludit-ai-assistant
```

---

## Phase 2 — Development (Days 2–8)

### Step 5: Understand the Bludit plugin lifecycle

Bludit calls these methods automatically:

| Method | When called |
|--------|-------------|
| `init()` | Plugin loaded — register DB fields here |
| `form()` | Admin settings page — return HTML string |
| `adminBodyEnd()` | Bottom of every admin page — inject JS/CSS |
| `beforePageCreate()` | Before a page is saved |

### Step 6: Copy all source files into place

All files are provided in this package. Copy them following the directory structure above.

### Step 7: Update YOUR details in these files

- `metadata.json` → set your `website` URL with your real GitHub username
- `README.md` → replace `YOUR_GITHUB_USERNAME` in all badge/link URLs
- `composer.json` → update `"name"` with your GitHub username

### Step 8: Get an OpenAI API key

1. Go to platform.openai.com/api-keys
2. Create a new secret key
3. Copy it — you'll paste it into Bludit admin settings
4. Recommended: set a monthly spending limit of $5 in billing settings

### Step 9: Test the plugin in Bludit admin

1. Log in to Bludit admin
2. Go to Plugins → find "AI Assistant" → Activate
3. Click Settings → paste your OpenAI API key → Save
4. Go to New Content → write a few sentences
5. The "✦ AI Assist" panel should appear below the editor

---

## Phase 3 — Testing (Days 9–10)

### Step 10: Run the test suite

```bash
composer test
```

Expected output:
```
PHPUnit 10.x

PromptBuilderTest        .......    7 tests
RateLimiterTest          .....      5 tests

OK (12 tests, 18 assertions)
```

### Step 11: Manual security checklist

Test each of these in your browser's DevTools (Network tab):

- [ ] POST to ajax.php without a nonce → should return 403
- [ ] POST to ajax.php with wrong nonce → should return 403
- [ ] POST 11 times in 60 seconds → 11th should return 429
- [ ] POST with empty content → should return 400
- [ ] POST with action="invalid" → should return 400
- [ ] Check that the API key is NOT visible in any browser network request

### Step 12: Test edge cases

- Very long content (paste a 1000-word article) → should truncate cleanly
- Content with HTML tags → should strip tags before sending to API
- Disable the plugin in settings → panel should disappear from editor

---

## Phase 4 — Polish & Documentation (Days 11–12)

### Step 13: Record a demo GIF

1. Open Bludit admin → New Content
2. Write 3-4 sentences about a dental topic
3. Click "Generate titles" and show the results appearing
4. Total recording: 20-30 seconds

Tools: OBS (free), Kap (Mac), ShareX (Windows)

To convert to GIF: upload to ezgif.com → resize to max 800px wide → optimize.

Save as `docs/demo.gif` and it will auto-appear in the README.

### Step 14: Final README checklist

- [ ] Replace all `YOUR_GITHUB_USERNAME` placeholders
- [ ] Verify badge URLs are correct (CI badge points to your repo)
- [ ] Add the demo GIF
- [ ] Check that the Features table renders correctly on GitHub

---

## Phase 5 — Launch (Days 13–14)

### Step 15: Tag your first release

```bash
git add .
git commit -m "feat: initial release v1.0.0

- AI title generation (3 suggestions)
- Meta description generation (150-160 chars)
- SEO keyword extraction (8 keywords)
- CSRF nonce protection
- File-based rate limiting (10 req/min)
- PHPUnit test suite (12 tests)"

git tag v1.0.0
git push origin main --tags
```

Then on GitHub: Releases → Create release from tag → add changelog.

### Step 16: Post in the Bludit community

URL: https://forum.bludit.org

Post title: "Plugin: AI Assistant — generate titles, meta descriptions & keywords with OpenAI"

Template:
```
Hi Bludit community,

I built an AI Assistant plugin that adds OpenAI-powered content suggestions 
directly inside the post editor.

Features:
- 3 SEO title suggestions from your content
- Meta description (150-160 chars)  
- 8 keyword suggestions

GitHub: https://github.com/YOUR_USERNAME/bludit-ai-assistant

Feedback welcome!
```

### Step 17: Share on LinkedIn/XING

Post template:
```
Just shipped an open-source PHP plugin: Bludit AI Assistant 🚀

It integrates OpenAI directly into Bludit CMS so content creators 
can generate SEO titles, meta descriptions, and keywords without 
leaving the editor.

Tech highlights:
✅ PHP 8.1+ with OOP / SOLID architecture  
✅ Zero external dependencies (pure cURL)
✅ CSRF + rate limiting for security
✅ PHPUnit test suite
✅ GitHub Actions CI across PHP 8.1/8.2/8.3

GitHub: [link]

#PHP #Laravel #OpenAI #OpenSource #WebDevelopment #PHP8
```

### Step 18: Add to your CV under Projects

```
Bludit AI Assistant (Open Source) — 2025
PHP plugin integrating OpenAI API with Bludit flat-file CMS.
Features: AI-generated titles, meta descriptions, and SEO keywords.
Stack: PHP 8.1, OOP, REST API, PHPUnit, GitHub Actions CI/CD.
github.com/YOUR_USERNAME/bludit-ai-assistant
```

---

## ATS Keywords This Project Covers

PHP · OOP · SOLID-Prinzipien · REST API · JSON · PHPUnit · TDD · 
OpenAI API · Plugin-Entwicklung · CI/CD · GitHub Actions · 
Composer · cURL · Sicherheit (CSRF, Rate Limiting) · Open Source

---

## Total Estimated Time

| Phase | Days | Hours |
|-------|------|-------|
| Setup + Bludit install | 1 | 2–3h |
| Core plugin development | 5 | 15–20h |
| Tests + security | 2 | 4–6h |
| README + GIF demo | 1 | 2–3h |
| Launch + promotion | 1 | 1–2h |
| **Total** | **10** | **24–34h** |
