# Bludit AI Assistant User Guide

Learn how to configure and use the **AI Assistant** plugin for Bludit CMS.

---

## 🔌 1. Activation

The plugin directory is set up correctly directly under `bl-plugins/bludit-ai-assistant/`.

To activate the plugin:
1. Log in to your Bludit CMS administration panel (`/admin`).
2. Navigate to the **Settings** sidebar option and click on **Plugins**.
3. Scroll down or search for **AI Assistant**.
4. Click the **Activate** button next to it.

---

## ⚙️ 2. Configuration & Multi-Provider API Keys

Once activated, click the **Settings** button next to the AI Assistant plugin:

1. **Enable AI Assistant**: Keep this set to **Enabled**.
2. **AI Provider**: Choose your preferred model provider:
   - **OpenAI**: Requires a key from [OpenAI Platform](https://platform.openai.com/).
   - **Gemini**: Requires a key from [Google AI Studio](https://aistudio.google.com/).
   - **DeepSeek**: Requires a key from [DeepSeek Platform](https://platform.deepseek.com/).
3. **API Key**: Paste the secret API Key corresponding to your chosen provider.
4. **Model**: The model list dynamically updates based on the chosen provider:
   - **OpenAI**: `gpt-4o-mini` (Fast & cost-effective) or `gpt-4o` (High intelligence).
   - **Gemini**: `gemini-2.5-flash` or `gemini-2.0-flash` (Recommended).
   - **DeepSeek**: `deepseek-chat` or `deepseek-reasoner`.
5. **Max Tokens**: Set the maximum length of generated content (default: `300`).
6. Click **Save**.

---

## ✦ 3. How to Use the AI Assistant

When creating or editing a post:

1. Write some content in the editor (at least 20 characters).
2. The **AI Assistant** sidebar panel will appear below or beside the editor fields.
3. Click one of the action buttons:
   - **Generate titles**: Generates 3 SEO-optimised titles. You can click **↑ Use** on any title to apply it directly to the Post Title field, or click to copy it to clipboard.
   - **Generate meta**: Generates a search-engine ready 150-160 character meta description. Click it to copy.
   - **Suggest keywords**: Extracts 8 relevant keywords as chips. Click any chip to copy it.
