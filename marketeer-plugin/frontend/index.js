export default {
  name: 'marketeer',
  displayName: 'Marketeer Landing Page Generator',

  mount(el, context) {
    el.innerHTML = `
      <div style="padding: 20px; font-family: sans-serif;">
        <h2 style="color: var(--brand, #00b79d); margin-bottom: 16px;">
          Marketeer — Landing Page Generator
        </h2>
        <p style="color: var(--txt-primary, #333); margin-bottom: 8px;">
          AI-powered marketing campaign generator with <strong>landing pages</strong>,
          <strong>keyword lists</strong>, and <strong>social media images</strong>.
        </p>
        <div style="background: var(--bg-card, #f5f5f5); padding: 16px; border-radius: 8px; margin-top: 16px; border: 1px solid var(--border-light, #e0e0e0);">
          <h3 style="color: var(--txt-primary, #333); margin-bottom: 12px; font-size: 18px;">Plugin Status</h3>
          <ul style="margin-top: 8px; font-size: 14px; color: var(--txt-secondary, #666); list-style: none; padding: 0;">
            <li style="margin-bottom: 8px;">&#x2705; <strong>Status:</strong> Active</li>
            <li style="margin-bottom: 8px;">&#x2705; <strong>Version:</strong> 1.0.0 (MVP)</li>
            <li style="margin-bottom: 8px;">&#x2705; <strong>API Endpoints:</strong> Available</li>
          </ul>
        </div>
        <div style="background: var(--bg-card, #f5f5f5); padding: 16px; border-radius: 8px; margin-top: 16px; border: 1px solid var(--border-light, #e0e0e0);">
          <h3 style="color: var(--txt-primary, #333); margin-bottom: 12px; font-size: 18px;">MVP Capabilities</h3>
          <ul style="margin-top: 8px; font-size: 14px; color: var(--txt-secondary, #666); list-style: none; padding: 0;">
            <li style="margin-bottom: 8px;">&#x1F4C4; <strong>Landing Pages</strong> — Generate full HTML pages via AI prompts</li>
            <li style="margin-bottom: 8px;">&#x1F50D; <strong>Keyword Lists</strong> — Google Ads keyword generation per language</li>
            <li style="margin-bottom: 8px;">&#x1F5BC; <strong>Social Images</strong> — Hero, LinkedIn, Instagram, OG images</li>
            <li style="margin-bottom: 8px;">&#x1F310; <strong>Multi-Language</strong> — Separate directories per language</li>
            <li style="margin-bottom: 8px;">&#x1F4E6; <strong>ZIP Download</strong> — Package entire campaigns for deployment</li>
            <li style="margin-bottom: 8px;">&#x270F; <strong>Refinement</strong> — Iterate on content with follow-up prompts</li>
          </ul>
        </div>
        <div style="background: var(--bg-card, #f5f5f5); padding: 16px; border-radius: 8px; margin-top: 16px; border: 1px solid var(--border-light, #e0e0e0);">
          <h3 style="color: var(--txt-primary, #333); margin-bottom: 12px; font-size: 18px;">API Endpoints</h3>
          <ul style="margin-top: 8px; font-size: 14px; color: var(--txt-secondary, #666); list-style: none; padding: 0;">
            <li style="margin-bottom: 8px;"><code style="background: var(--bg-chip, #f0f0f0); padding: 2px 6px; border-radius: 4px;">GET /campaigns</code> — List all campaigns</li>
            <li style="margin-bottom: 8px;"><code style="background: var(--bg-chip, #f0f0f0); padding: 2px 6px; border-radius: 4px;">POST /campaigns</code> — Create campaign</li>
            <li style="margin-bottom: 8px;"><code style="background: var(--bg-chip, #f0f0f0); padding: 2px 6px; border-radius: 4px;">POST /campaigns/{id}/generate</code> — Generate landing page</li>
            <li style="margin-bottom: 8px;"><code style="background: var(--bg-chip, #f0f0f0); padding: 2px 6px; border-radius: 4px;">POST /campaigns/{id}/generate-keywords</code> — Generate keywords</li>
            <li style="margin-bottom: 8px;"><code style="background: var(--bg-chip, #f0f0f0); padding: 2px 6px; border-radius: 4px;">POST /campaigns/{id}/generate-image</code> — Generate images</li>
            <li style="margin-bottom: 8px;"><code style="background: var(--bg-chip, #f0f0f0); padding: 2px 6px; border-radius: 4px;">POST /campaigns/{id}/refine</code> — Refine with prompts</li>
            <li style="margin-bottom: 8px;"><code style="background: var(--bg-chip, #f0f0f0); padding: 2px 6px; border-radius: 4px;">GET /campaigns/{id}/download</code> — Download ZIP</li>
          </ul>
        </div>
      </div>
    `;
  },
};
