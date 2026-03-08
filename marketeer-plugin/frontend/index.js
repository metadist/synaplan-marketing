export default {
  name: 'marketeer',
  displayName: 'Marketeer Campaign Generator',

  mount(el, context) {
    el.innerHTML = `
      <div style="padding: 20px; font-family: system-ui, -apple-system, sans-serif; max-width: 800px;">
        <h2 style="color: var(--brand, #00b79d); margin-bottom: 8px; font-size: 24px;">
          Marketeer — Campaign Generator
        </h2>
        <p style="color: var(--txt-secondary, #888); margin-bottom: 24px; font-size: 14px;">
          AI-powered marketing campaign management — from idea to going live.
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">

          <div style="background: var(--bg-card, #f5f5f5); padding: 20px; border-radius: 12px; border: 1px solid var(--border-light, #e0e0e0);">
            <h3 style="color: var(--txt-primary, #333); margin-bottom: 12px; font-size: 16px;">Campaign Management</h3>
            <ul style="font-size: 13px; color: var(--txt-secondary, #666); list-style: none; padding: 0; margin: 0;">
              <li style="margin-bottom: 8px;">&#x1F4CB; Create, edit, delete campaigns</li>
              <li style="margin-bottom: 8px;">&#x1F4A1; AI campaign planning from an idea</li>
              <li style="margin-bottom: 8px;">&#x1F3AF; Target audience &amp; USP configuration</li>
              <li style="margin-bottom: 8px;">&#x1F517; CTA management (register, email, custom)</li>
            </ul>
          </div>

          <div style="background: var(--bg-card, #f5f5f5); padding: 20px; border-radius: 12px; border: 1px solid var(--border-light, #e0e0e0);">
            <h3 style="color: var(--txt-primary, #333); margin-bottom: 12px; font-size: 16px;">Landing Pages</h3>
            <ul style="font-size: 13px; color: var(--txt-secondary, #666); list-style: none; padding: 0; margin: 0;">
              <li style="margin-bottom: 8px;">&#x1F4C4; AI-generated responsive HTML pages</li>
              <li style="margin-bottom: 8px;">&#x270F;&#xFE0F; Refine with follow-up prompts</li>
              <li style="margin-bottom: 8px;">&#x1F310; Multi-language support (EN, DE, FR, ES...)</li>
              <li style="margin-bottom: 8px;">&#x1F4E6; ZIP download for deployment</li>
            </ul>
          </div>

          <div style="background: var(--bg-card, #f5f5f5); padding: 20px; border-radius: 12px; border: 1px solid var(--border-light, #e0e0e0);">
            <h3 style="color: var(--txt-primary, #333); margin-bottom: 12px; font-size: 16px;">Ad Copy &amp; Social</h3>
            <ul style="font-size: 13px; color: var(--txt-secondary, #666); list-style: none; padding: 0; margin: 0;">
              <li style="margin-bottom: 8px;">&#x1F4E2; Google Ads RSA (headlines + descriptions)</li>
              <li style="margin-bottom: 8px;">&#x1F4BC; LinkedIn professional posts</li>
              <li style="margin-bottom: 8px;">&#x1F4F8; Instagram captions + hashtags</li>
              <li style="margin-bottom: 8px;">&#x1F4AC; Discord announcements</li>
            </ul>
          </div>

          <div style="background: var(--bg-card, #f5f5f5); padding: 20px; border-radius: 12px; border: 1px solid var(--border-light, #e0e0e0);">
            <h3 style="color: var(--txt-primary, #333); margin-bottom: 12px; font-size: 16px;">Google Ads Planning</h3>
            <ul style="font-size: 13px; color: var(--txt-secondary, #666); list-style: none; padding: 0; margin: 0;">
              <li style="margin-bottom: 8px;">&#x1F50D; AI keyword list generation</li>
              <li style="margin-bottom: 8px;">&#x1F4CA; Full campaign structure (ad groups, ads)</li>
              <li style="margin-bottom: 8px;">&#x1F4E4; Export keywords for Google Ads import</li>
              <li style="margin-bottom: 8px;">&#x1F4B0; Budget &amp; bidding recommendations</li>
            </ul>
          </div>

          <div style="background: var(--bg-card, #f5f5f5); padding: 20px; border-radius: 12px; border: 1px solid var(--border-light, #e0e0e0);">
            <h3 style="color: var(--txt-primary, #333); margin-bottom: 12px; font-size: 16px;">Images &amp; Collateral</h3>
            <ul style="font-size: 13px; color: var(--txt-secondary, #666); list-style: none; padding: 0; margin: 0;">
              <li style="margin-bottom: 8px;">&#x1F5BC;&#xFE0F; Hero banners (1920x1080)</li>
              <li style="margin-bottom: 8px;">&#x1F4F1; Social images (LinkedIn, Instagram, OG)</li>
              <li style="margin-bottom: 8px;">&#x1F3A8; Icons (512x512)</li>
              <li style="margin-bottom: 8px;">&#x1F4D0; Display ad banners (728x90, 300x250, 160x600)</li>
            </ul>
          </div>

          <div style="background: var(--bg-card, #f5f5f5); padding: 20px; border-radius: 12px; border: 1px solid var(--border-light, #e0e0e0);">
            <h3 style="color: var(--txt-primary, #333); margin-bottom: 12px; font-size: 16px;">Compliance &amp; Launch</h3>
            <ul style="font-size: 13px; color: var(--txt-secondary, #666); list-style: none; padding: 0; margin: 0;">
              <li style="margin-bottom: 8px;">&#x2705; GDPR compliance checker</li>
              <li style="margin-bottom: 8px;">&#x1F36A; Cookie consent snippet generator</li>
              <li style="margin-bottom: 8px;">&#x1F4DD; AI-powered compliance review</li>
              <li style="margin-bottom: 8px;">&#x1F680; Pre-launch readiness checklist</li>
            </ul>
          </div>
        </div>
      </div>
    `;
  },
};
