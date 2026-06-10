/**
 * Bludit AI Assistant — Admin Editor Integration
 *
 * Injects an AI sidebar panel into the Bludit post editor.
 * All API calls go to ajax.php (server-side proxy).
 */

(function () {
  'use strict';

  const cfg = window.AiAssistantConfig || {};

  // ── DOM setup ──────────────────────────────────────────────────────────────

  function init() {
    // Anchor the panel right after the title div — always present in new/edit-content views
    const anchor = document.getElementById('jseditorTitle');
    if (!anchor) return;

    const panel = buildPanel();
    anchor.insertAdjacentElement('afterend', panel);
  }

  function getEditor() {
    // Bludit's editor textarea has id="jseditor"
    return document.getElementById('jseditor');
  }

  // ── Build the panel ────────────────────────────────────────────────────────

  function buildPanel() {
    const panel = el('div', { class: 'ai-panel' });

    panel.innerHTML = `
      <div class="ai-panel__header">
        <span class="ai-panel__icon" aria-hidden="true">✦</span>
        <span class="ai-panel__title">AI Assistant</span>
      </div>
      <div class="ai-panel__actions">
        <button class="ai-btn" data-action="titles"   type="button">Generate titles</button>
        <button class="ai-btn" data-action="meta"     type="button">Generate meta</button>
        <button class="ai-btn" data-action="keywords" type="button">Suggest keywords</button>
      </div>
      <div class="ai-panel__results" aria-live="polite"></div>
    `;

    panel.querySelectorAll('.ai-btn').forEach((btn) => {
      btn.addEventListener('click', () => handleAction(btn, panel));
    });

    return panel;
  }

  // ── Action handler ─────────────────────────────────────────────────────────

  async function handleAction(btn, panel) {
    const action  = btn.dataset.action;
    const content = getEditorContent();
    const results = panel.querySelector('.ai-panel__results');

    if (!content || content.length < 20) {
      showResults(results, [{ text: 'Write some content first, then click again.', isError: true }]);
      return;
    }

    setLoading(panel, true);

    try {
      const data = await postAjax(action, content);
      const items = parseResult(action, data.result);
      showResults(results, items, action);
    } catch (err) {
      showResults(results, [{ text: err.message || 'Something went wrong.', isError: true }]);
    } finally {
      setLoading(panel, false);
    }
  }

  // ── AJAX ───────────────────────────────────────────────────────────────────

  async function postAjax(action, content) {
    const body = new URLSearchParams({ action, content, nonce: cfg.nonce || '' });
    const res  = await fetch(cfg.ajaxUrl, { method: 'POST', body });
    const json = await res.json();

    if (!res.ok || json.error) {
      throw new Error(json.error || `HTTP ${res.status}`);
    }
    return json;
  }

  // ── Result parsing ─────────────────────────────────────────────────────────

  function parseResult(action, raw) {
    if (action === 'titles') {
      return raw
        .split('\n')
        .filter((l) => l.trim())
        .map((l) => ({ text: l.replace(/^\d+\.\s*/, '').trim() }));
    }
    if (action === 'keywords') {
      return raw.split(',').map((k) => ({ text: k.trim(), isChip: true }));
    }
    // meta — single string
    return [{ text: raw.trim() }];
  }

  // ── Render results ─────────────────────────────────────────────────────────

  function showResults(container, items, action) {
    container.innerHTML = '';

    if (items.length === 0) {
      container.textContent = 'No results.';
      return;
    }

    const list = el('ul', { class: 'ai-results' });

    items.forEach(({ text, isError, isChip }) => {
      const item = el('li', {
        class: ['ai-result-item', isError && 'ai-result-item--error', isChip && 'ai-result-item--chip']
          .filter(Boolean)
          .join(' '),
      });

      item.textContent = text;

      if (!isError) {
        item.title = 'Click to copy';
        item.setAttribute('role', 'button');
        item.setAttribute('tabindex', '0');
        item.addEventListener('click', () => copyToClipboard(text, item));
        item.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') copyToClipboard(text, item);
        });

        // For title suggestions: also offer to insert into the title field
        if (action === 'titles') {
          const insertBtn = el('button', { class: 'ai-insert-btn', type: 'button' });
          insertBtn.textContent = '↑ Use';
          insertBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            insertTitle(text);
          });
          item.appendChild(insertBtn);
        }
      }

      list.appendChild(item);
    });

    container.appendChild(list);
  }

  // ── Helpers ────────────────────────────────────────────────────────────────

  function getEditorContent() {
    // Prefer Bludit's global editor API (works with TinyMCE, markdown, and plain textarea)
    if (typeof window.editorGetContent === 'function') {
      return window.editorGetContent();
    }
    // Fallback: read the raw textarea (id="jseditor")
    const textarea = document.getElementById('jseditor');
    if (textarea) return textarea.value;

    return '';
  }

  function insertTitle(text) {
    // Bludit's title field has id="jstitle"
    const titleInput = document.getElementById('jstitle');
    if (titleInput) {
      titleInput.value = text;
      titleInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }

  async function copyToClipboard(text, el) {
    try {
      await navigator.clipboard.writeText(text);
      const original = el.textContent;
      el.textContent = '✓ Copied!';
      setTimeout(() => { el.textContent = original; }, 1500);
    } catch {
      // Fallback for older browsers
      const ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
    }
  }

  function setLoading(panel, loading) {
    panel.querySelectorAll('.ai-btn').forEach((b) => {
      b.disabled = loading;
      if (loading) {
        b.dataset.originalText = b.textContent;
        b.textContent = 'Thinking…';
      } else if (b.dataset.originalText) {
        b.textContent = b.dataset.originalText;
      }
    });
  }

  function el(tag, attrs = {}) {
    const node = document.createElement(tag);
    Object.entries(attrs).forEach(([k, v]) => node.setAttribute(k, v));
    return node;
  }

  // ── Boot ───────────────────────────────────────────────────────────────────

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
