const LANGS = [
  { code: 'en', label: 'English', flag: '🇬🇧' },
  { code: 'de', label: 'Deutsch', flag: '🇩🇪' },
  { code: 'es', label: 'Español', flag: '🇪🇸' },
  { code: 'fr', label: 'Français', flag: '🇫🇷' },
  { code: 'it', label: 'Italiano', flag: '🇮🇹' },
  { code: 'pt', label: 'Português', flag: '🇵🇹' },
  { code: 'tr', label: 'Türkçe', flag: '🇹🇷' },
  { code: 'ja', label: '日本語', flag: '🇯🇵' },
  { code: 'zh', label: '中文', flag: '🇨🇳' },
  { code: 'ar', label: 'العربية', flag: '🇸🇦' },
]

const PLATFORMS = [
  { id: 'google', label: 'Google Ads', icon: '📊' },
  { id: 'linkedin', label: 'LinkedIn', icon: '💼' },
  { id: 'instagram', label: 'Instagram', icon: '📸' },
  { id: 'discord', label: 'Discord', icon: '💬' },
]

const IMG_TYPES = [
  { id: 'hero', label: 'Hero Banner', dim: '1920×1080' },
  { id: 'og', label: 'Social / OG', dim: '1200×630' },
  { id: 'linkedin', label: 'LinkedIn', dim: '1200×627' },
  { id: 'instagram', label: 'Instagram', dim: '1080×1080' },
  { id: 'icon', label: 'Icon', dim: '512×512' },
  { id: 'banner_wide', label: 'Leaderboard', dim: '728×90' },
  { id: 'banner_rect', label: 'Rectangle', dim: '300×250' },
]

// ─── API Client ──────────────────────────────────────────────────────────────

function createApi(baseUrl, userId) {
  const url = (path) => `${baseUrl}/api/v1/user/${userId}/plugins/marketeer${path}`

  async function call(method, path, body) {
    const opts = {
      method,
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'include',
    }
    if (body) opts.body = JSON.stringify(body)
    const res = await fetch(url(path), opts)
    const contentType = res.headers.get('content-type') || ''
    if (!contentType.includes('application/json')) {
      return { success: false, error: `Server returned ${res.status}: non-JSON response` }
    }
    if (!res.ok) {
      try { return await res.json() } catch { return { success: false, error: `Server error ${res.status}` } }
    }
    return res.json()
  }

  return {
    get: (p) => call('GET', p),
    post: (p, b) => call('POST', p, b),
    put: (p, b) => call('PUT', p, b),
    del: (p) => call('DELETE', p),
    downloadUrl: (p) => url(p),
  }
}

// ─── Styles ──────────────────────────────────────────────────────────────────

const CSS = `
  .mk{font-family:system-ui,-apple-system,sans-serif;color:var(--txt-primary,#e0e0e0);max-width:960px;margin:0 auto}
  .mk *{box-sizing:border-box}
  .mk h2{font-size:22px;font-weight:600;margin:0 0 4px}
  .mk h3{font-size:17px;font-weight:600;margin:0 0 8px}
  .mk-sub{color:var(--txt-secondary,#999);font-size:13px;margin:0 0 20px}
  .mk-card{background:var(--bg-card,#1e1e2e);border:1px solid var(--border-light,#333);border-radius:12px;padding:20px;margin-bottom:16px}
  .mk-row{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
  .mk-grow{flex:1;min-width:0}
  .mk-badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase}
  .mk-badge-draft{background:#555;color:#ccc}.mk-badge-active{background:#00b79d;color:#fff}.mk-badge-paused{background:#e6a700;color:#000}.mk-badge-completed{background:#3b82f6;color:#fff}
  .mk-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:8px;border:none;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s}
  .mk-btn:hover{opacity:.85}.mk-btn:disabled{opacity:.4;cursor:not-allowed}
  .mk-primary{background:var(--brand,#00b79d);color:#fff}
  .mk-secondary{background:var(--bg-chip,#333);color:var(--txt-primary,#e0e0e0)}
  .mk-danger{background:#c0392b;color:#fff}
  .mk-ghost{background:transparent;color:var(--brand,#00b79d);padding:8px 12px}
  .mk-input{width:100%;padding:10px 14px;border-radius:8px;border:1px solid var(--border-light,#444);background:var(--bg-input,#151520);color:var(--txt-primary,#e0e0e0);font-size:14px;outline:none}
  .mk-input:focus{border-color:var(--brand,#00b79d)}
  .mk-textarea{min-height:80px;resize:vertical;font-family:inherit}
  .mk-label{display:block;font-size:12px;font-weight:600;color:var(--txt-secondary,#999);margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px}
  .mk-field{margin-bottom:16px}
  .mk-tabs{display:flex;gap:0;border-bottom:2px solid var(--border-light,#333);margin-bottom:20px;overflow-x:auto}
  .mk-tab{padding:10px 18px;font-size:13px;font-weight:500;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;white-space:nowrap;color:var(--txt-secondary,#999);transition:all .15s}
  .mk-tab:hover{color:var(--txt-primary,#e0e0e0)}.mk-tab.active{color:var(--brand,#00b79d);border-bottom-color:var(--brand,#00b79d)}
  .mk-spinner{display:inline-block;width:16px;height:16px;border:2px solid transparent;border-top-color:currentColor;border-radius:50%;animation:mk-spin .6s linear infinite}
  @keyframes mk-spin{to{transform:rotate(360deg)}}
  .mk-empty{text-align:center;padding:40px 20px;color:var(--txt-secondary,#999)}
  .mk-empty p{margin:8px 0}
  .mk-chips{display:flex;flex-wrap:wrap;gap:6px}
  .mk-chip{padding:4px 12px;border-radius:16px;font-size:12px;border:1px solid var(--border-light,#444);cursor:pointer;transition:all .15s;user-select:none}
  .mk-chip.on{background:var(--brand,#00b79d);color:#fff;border-color:var(--brand,#00b79d)}
  .mk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
  .mk-tag{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;background:var(--bg-chip,#333);margin:0 4px 4px 0}
  .mk-pre{background:var(--bg-input,#151520);border:1px solid var(--border-light,#444);border-radius:8px;padding:14px;font-size:12px;white-space:pre-wrap;word-break:break-word;max-height:400px;overflow:auto;line-height:1.6;font-family:ui-monospace,monospace}
  .mk-preview{border:1px solid var(--border-light,#444);border-radius:8px;overflow:hidden;background:#fff}
  .mk-preview iframe{width:100%;height:500px;border:none}
  .mk-sep{border:none;border-top:1px solid var(--border-light,#333);margin:20px 0}
  .mk-back{cursor:pointer;color:var(--brand,#00b79d);font-size:13px;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
  .mk-back:hover{text-decoration:underline}
  .mk-stat{text-align:center;padding:12px}.mk-stat-num{font-size:28px;font-weight:700;color:var(--brand,#00b79d)}.mk-stat-lbl{font-size:11px;color:var(--txt-secondary,#999);text-transform:uppercase}
  .mk-toast{position:fixed;bottom:24px;right:24px;background:#00b79d;color:#fff;padding:12px 20px;border-radius:8px;font-size:13px;z-index:9999;animation:mk-fade .3s}
  .mk-toast.error{background:#c0392b}
  @keyframes mk-fade{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
`

// ─── Helpers ─────────────────────────────────────────────────────────────────

function h(tag, attrs, ...children) {
  const el = document.createElement(tag)
  if (attrs) Object.entries(attrs).forEach(([k, v]) => {
    if (k === 'style' && typeof v === 'object') Object.assign(el.style, v)
    else if (k.startsWith('on')) el.addEventListener(k.slice(2).toLowerCase(), v)
    else if (k === 'className') el.className = v
    else if (k === 'html') el.innerHTML = v
    else el.setAttribute(k, v)
  })
  children.flat().forEach(c => { if (c != null) el.append(typeof c === 'string' ? c : c) })
  return el
}

function toast(msg, err) {
  const t = h('div', { className: `mk-toast${err ? ' error' : ''}` }, msg)
  document.body.append(t)
  setTimeout(() => t.remove(), 3000)
}

function langLabel(code) {
  return LANGS.find(l => l.code === code)?.flag + ' ' + (LANGS.find(l => l.code === code)?.label || code)
}

// ─── App ─────────────────────────────────────────────────────────────────────

export default {
  mount(el, context) {
    const api = createApi(context.apiBaseUrl, context.userId)
    let state = { page: 'dashboard', campaignId: null, tab: 'pages' }

    const style = document.createElement('style')
    style.textContent = CSS
    el.append(style)

    const root = h('div', { className: 'mk' })
    el.append(root)

    function nav(page, opts = {}) {
      Object.assign(state, { page, ...opts })
      render()
    }

    function render() {
      root.innerHTML = ''
      const p = state.page
      if (p === 'dashboard') renderDashboard()
      else if (p === 'new') renderNewCampaign()
      else if (p === 'campaign') renderCampaignDetail()
      else if (p === 'config') renderConfig()
    }

    // ── Dashboard ────────────────────────────────────────────────────────

    async function renderDashboard() {
      root.innerHTML = '<div class="mk-empty"><div class="mk-spinner" style="width:24px;height:24px"></div><p>Loading campaigns...</p></div>'
      const d = await api.get('/dashboard')
      root.innerHTML = ''

      if (!d.success) {
        root.innerHTML = '<div class="mk-empty"><p>Could not load dashboard. Is the plugin installed?</p></div>'
        return
      }

      const ov = d.overview
      const header = h('div', { className: 'mk-row', style: { justifyContent: 'space-between', marginBottom: '20px' } },
        h('div', null,
          h('h2', null, 'Campaigns'),
          h('p', { className: 'mk-sub', style: { margin: 0 } }, `${ov.total_campaigns} campaigns · ${ov.total_pages} pages`),
        ),
        h('div', { className: 'mk-row' },
          h('button', { className: 'mk-btn mk-secondary', onClick: () => nav('config') }, '⚙ Settings'),
          h('button', { className: 'mk-btn mk-primary', onClick: () => nav('new') }, '+ New Campaign'),
        ),
      )
      root.append(header)

      if (!d.campaigns.length) {
        root.append(h('div', { className: 'mk-empty' },
          h('p', { style: { fontSize: '40px' } }, '📢'),
          h('p', null, 'No campaigns yet.'),
          h('button', { className: 'mk-btn mk-primary', onClick: () => nav('new') }, 'Create your first campaign'),
        ))
        return
      }

      d.campaigns.forEach(c => {
        const card = h('div', { className: 'mk-card', style: { cursor: 'pointer' }, onClick: () => nav('campaign', { campaignId: c.id, tab: 'pages' }) },
          h('div', { className: 'mk-row', style: { justifyContent: 'space-between' } },
            h('div', null,
              h('h3', { style: { margin: 0 } }, c.title),
              h('div', { style: { marginTop: '4px' } },
                ...(c.languages || []).map(l => h('span', { className: 'mk-tag' }, langLabel(l))),
                ...(c.platforms || []).map(p => h('span', { className: 'mk-tag' }, PLATFORMS.find(x => x.id === p)?.icon || p)),
              ),
            ),
            h('span', { className: `mk-badge mk-badge-${c.status || 'draft'}` }, c.status || 'draft'),
          ),
          h('div', { className: 'mk-row', style: { marginTop: '12px', gap: '20px' } },
            stat(c.pages_count, 'Pages'), stat(c.ad_copy_count + c.social_posts_count, 'Ad Copy'), stat(c.ads_campaigns_count, 'Ads Plans'), stat(c.files_count, 'Files'),
          ),
        )
        root.append(card)
      })
    }

    function stat(n, lbl) {
      return h('div', { className: 'mk-stat' }, h('div', { className: 'mk-stat-num' }, String(n)), h('div', { className: 'mk-stat-lbl' }, lbl))
    }

    // ── New Campaign ─────────────────────────────────────────────────────

    function renderNewCampaign() {
      const form = { slug: '', title: '', topic: '', audience: '', usps: '', languages: ['en'], platforms: ['google'], ctas: [{ type: 'register', label: 'Start Free Trial', url: '' }] }

      root.append(
        h('div', { className: 'mk-back', onClick: () => nav('dashboard') }, '← Back to campaigns'),
        h('h2', null, 'New Campaign'),
        h('p', { className: 'mk-sub' }, 'Describe your campaign idea. The AI will help you build everything from here.'),
      )

      const container = h('div')
      root.append(container)

      function renderStep1() {
        container.innerHTML = ''
        container.append(
          field('Campaign Name', 'text', form.slug, v => form.slug = v, 'e.g. synaplan-launch'),
          field('Headline', 'text', form.title, v => form.title = v, 'e.g. Synaplan — Your AI Knowledge Hub'),
          field('Topic / Angle', 'textarea', form.topic, v => form.topic = v, 'Describe what this campaign promotes and the key angle...'),
          field('Target Audience', 'text', form.audience, v => form.audience = v, 'e.g. CTOs, developers, knowledge workers'),
          field('Unique Selling Points', 'textarea', form.usps, v => form.usps = v, 'One per line'),
        )

        container.append(h('div', { className: 'mk-field' },
          h('label', { className: 'mk-label' }, 'Languages'),
          chipSelect(LANGS, form.languages, v => form.languages = v),
        ))

        container.append(h('div', { className: 'mk-field' },
          h('label', { className: 'mk-label' }, 'Platforms'),
          chipSelect(PLATFORMS.map(p => ({ code: p.id, label: p.icon + ' ' + p.label })), form.platforms, v => form.platforms = v),
        ))

        container.append(h('hr', { className: 'mk-sep' }))

        const btnRow = h('div', { className: 'mk-row', style: { justifyContent: 'space-between' } },
          h('button', { className: 'mk-btn mk-ghost', onClick: () => nav('dashboard') }, 'Cancel'),
          h('div', { className: 'mk-row' },
            h('button', { className: 'mk-btn mk-secondary', onClick: () => planFromIdea() }, '✨ Plan with AI'),
            h('button', { className: 'mk-btn mk-primary', onClick: () => createCampaign() }, 'Create Campaign →'),
          ),
        )
        container.append(btnRow)
      }

      async function planFromIdea() {
        if (!form.topic) { toast('Enter a topic first', true); return }
        const btn = container.querySelector('.mk-secondary')
        btn.disabled = true; btn.innerHTML = '<span class="mk-spinner"></span> Planning...'
        const d = await api.post('/plan', { idea: form.topic, language: form.languages[0] || 'en' })
        btn.disabled = false; btn.textContent = '✨ Plan with AI'
        if (d.success && d.plan) {
          const p = d.plan
          if (p.campaign_name) form.slug = p.campaign_name
          if (p.title) form.title = p.title
          if (p.topic) form.topic = p.topic
          if (p.target_audience) form.audience = p.target_audience
          if (p.unique_selling_points) form.usps = p.unique_selling_points.join('\n')
          if (p.recommended_platforms) form.platforms = p.recommended_platforms
          if (p.languages) form.languages = p.languages
          if (p.cta_suggestions?.length) form.ctas = p.cta_suggestions.map(c => ({ type: c.type, label: c.label, url: c.url_path || '' }))
          toast('AI plan applied!')
          renderStep1()
        } else {
          toast(d.error || 'Planning failed', true)
        }
      }

      async function createCampaign() {
        if (!form.slug || !form.title || !form.topic) { toast('Fill in name, headline and topic', true); return }
        const body = {
          slug: form.slug,
          title: form.title,
          topic: form.topic,
          target_audience: form.audience,
          unique_selling_points: form.usps.split('\n').map(s => s.trim()).filter(Boolean),
          languages: form.languages,
          platforms: form.platforms,
          ctas: form.ctas,
        }
        const d = await api.post('/campaigns', body)
        if (d.success) {
          toast('Campaign created!')
          nav('campaign', { campaignId: d.campaign.id, tab: 'pages' })
        } else {
          toast(d.error || 'Creation failed', true)
        }
      }

      renderStep1()
    }

    // ── Campaign Detail ──────────────────────────────────────────────────

    async function renderCampaignDetail() {
      root.innerHTML = '<div class="mk-empty"><div class="mk-spinner" style="width:24px;height:24px"></div><p>Loading campaign...</p></div>'
      const d = await api.get(`/campaigns/${state.campaignId}`)
      if (!d.success) { root.innerHTML = `<div class="mk-empty"><p>${d.error}</p></div>`; return }
      root.innerHTML = ''

      const c = d.campaign
      root.append(
        h('div', { className: 'mk-back', onClick: () => nav('dashboard') }, '← Campaigns'),
        h('div', { className: 'mk-row', style: { justifyContent: 'space-between', marginBottom: '4px' } },
          h('h2', null, c.title),
          h('div', { className: 'mk-row' },
            h('span', { className: `mk-badge mk-badge-${c.status}` }, c.status),
            h('a', { href: api.downloadUrl(`/campaigns/${c.id}/download`), className: 'mk-btn mk-secondary', style: { textDecoration: 'none' } }, '📦 Download ZIP'),
          ),
        ),
        h('p', { className: 'mk-sub' }, (c.languages || []).map(l => langLabel(l)).join(' · ') + ' — ' + (c.platforms || []).map(p => PLATFORMS.find(x => x.id === p)?.label || p).join(', ')),
      )

      const tabs = [
        { id: 'pages', label: '📄 Pages' },
        { id: 'adcopy', label: '📢 Ad Copy' },
        { id: 'ads', label: '📊 Google Ads' },
        { id: 'images', label: '🖼 Images' },
        { id: 'compliance', label: '🛡 Compliance' },
        { id: 'settings', label: '⚙ Settings' },
      ]

      const tabBar = h('div', { className: 'mk-tabs' })
      tabs.forEach(t => {
        tabBar.append(h('div', {
          className: `mk-tab${state.tab === t.id ? ' active' : ''}`,
          onClick: () => { state.tab = t.id; render() },
        }, t.label))
      })
      root.append(tabBar)

      const content = h('div')
      root.append(content)

      if (state.tab === 'pages') renderPagesTab(content, c, d)
      else if (state.tab === 'adcopy') renderAdCopyTab(content, c, d)
      else if (state.tab === 'ads') renderAdsTab(content, c, d)
      else if (state.tab === 'images') renderImagesTab(content, c, d)
      else if (state.tab === 'compliance') renderComplianceTab(content, c)
      else if (state.tab === 'settings') renderSettingsTab(content, c)
    }

    // ── Pages Tab ────────────────────────────────────────────────────────

    function renderPagesTab(ct, campaign, data) {
      const pages = data.pages || {}
      const langs = campaign.languages || ['en']

      langs.forEach(lang => {
        const page = pages[lang]
        const card = h('div', { className: 'mk-card' })
        card.append(h('div', { className: 'mk-row', style: { justifyContent: 'space-between' } },
          h('h3', null, langLabel(lang) + ' Landing Page'),
          page ? h('span', { className: 'mk-badge mk-badge-active' }, 'Generated') : h('span', { className: 'mk-badge mk-badge-draft' }, 'Not generated'),
        ))

        if (page) {
          const htmlUrl = fileUrl(campaign.id, lang + '/index.html')
          const previewFrame = h('div', { className: 'mk-preview', style: { marginTop: '12px', position: 'relative' } })
          const iframe = h('iframe', { sandbox: 'allow-same-origin allow-scripts allow-popups', style: { width: '100%', height: '500px', border: 'none' } })
          previewFrame.append(iframe)
          const openBtn = h('a', { href: htmlUrl, target: '_blank', style: { position: 'absolute', top: '8px', right: '8px', background: 'rgba(0,0,0,0.7)', color: '#fff', padding: '4px 10px', borderRadius: '4px', fontSize: '11px', textDecoration: 'none', zIndex: 5 } }, '↗ Open full page')
          previewFrame.append(openBtn)
          card.append(previewFrame)
          requestAnimationFrame(() => {
            const doc = iframe.contentDocument || iframe.contentWindow.document
            doc.open()
            doc.write(page.html)
            doc.close()
          })

          const refineRow = h('div', { className: 'mk-row', style: { marginTop: '10px', gap: '8px' } })
          const refineInput = h('input', { className: 'mk-input', placeholder: 'Describe changes... e.g. "Make the headline bigger"', style: { flex: 1 } })
          refineRow.append(refineInput)
          refineRow.append(asyncBtn('Refine', 'mk-primary', async () => {
            if (!refineInput.value) return
            const d = await api.post(`/campaigns/${campaign.id}/refine`, { prompt: refineInput.value, language: lang, target: 'html' })
            if (d.success) { toast('Page refined!'); render() } else toast(d.error || 'Failed', true)
          }))
          card.append(refineRow)

          card.append(h('div', { className: 'mk-row', style: { marginTop: '8px', justifyContent: 'space-between' } },
            h('div', { className: 'mk-row', style: { gap: '6px' } },
              h('button', { className: 'mk-btn mk-secondary', style: { padding: '5px 12px', fontSize: '12px' }, onClick: () => { navigator.clipboard.writeText(htmlUrl); toast('Page URL copied!') } }, '📋 Copy URL'),
              asyncBtn('🔄 Regenerate', 'mk-secondary', async () => {
                const d = await api.post(`/campaigns/${campaign.id}/generate`, { language: lang })
                if (d.success) { toast('Page regenerated!'); render() } else toast(d.error || 'Failed', true)
              }),
            ),
            asyncBtn('🗑 Delete', 'mk-danger', async () => {
              if (!confirm('Delete this page?')) return
              await api.del(`/campaigns/${campaign.id}/pages/${lang}`)
              toast('Page deleted'); render()
            }),
          ))
        } else {
          card.append(h('div', { className: 'mk-empty', style: { padding: '24px' } },
            h('p', null, 'No landing page for this language yet.'),
            asyncBtn('✨ Generate Landing Page', 'mk-primary', async () => {
              const d = await api.post(`/campaigns/${campaign.id}/generate`, { language: lang })
              if (d.success) { toast('Page generated!'); render() } else toast(d.error || 'Failed', true)
            }),
          ))
        }
        ct.append(card)
      })
    }

    // ── Ad Copy Tab ──────────────────────────────────────────────────────

    async function renderAdCopyTab(ct, campaign, data) {
      const adCopy = data.ad_copy || {}
      const socialPosts = data.social_posts || {}
      const langs = campaign.languages || ['en']

      const langSelect = h('select', { className: 'mk-input', style: { width: 'auto', marginBottom: '16px' } })
      langs.forEach(l => langSelect.append(h('option', { value: l }, langLabel(l))))
      ct.append(langSelect)

      const platforms = campaign.platforms || ['google']
      platforms.forEach(plat => {
        const pInfo = PLATFORMS.find(p => p.id === plat) || { icon: '📝', label: plat }
        const existing = plat === 'google' ? Object.values(adCopy).find(a => a.language === langs[0]) : Object.values(socialPosts).find(s => s.platform === plat && s.language === langs[0])

        const card = h('div', { className: 'mk-card' })
        card.append(h('div', { className: 'mk-row', style: { justifyContent: 'space-between' } },
          h('h3', null, pInfo.icon + ' ' + pInfo.label),
          existing ? h('span', { className: 'mk-badge mk-badge-active' }, 'Generated') : h('span', null),
        ))

        if (existing) {
          if (plat === 'google') renderGoogleAdPreview(card, existing)
          else renderSocialPostPreview(card, existing, plat)
        }

        card.append(h('div', { style: { marginTop: '12px' } },
          asyncBtn(existing ? '🔄 Regenerate' : '✨ Generate', existing ? 'mk-secondary' : 'mk-primary', async () => {
            const lang = langSelect.value || langs[0]
            const d = await api.post(`/campaigns/${campaign.id}/generate-ad-copy`, { platform: plat, language: lang })
            if (d.success) { toast(`${pInfo.label} copy generated!`); render() } else toast(d.error || 'Failed', true)
          }),
        ))
        ct.append(card)
      })
    }

    function renderGoogleAdPreview(card, data) {
      const headlines = data.headlines || []
      const descriptions = data.descriptions || []
      card.append(h('div', { style: { marginTop: '8px' } },
        h('div', { className: 'mk-label' }, `Headlines (${headlines.length})`),
        h('div', { className: 'mk-pre' }, headlines.map((h, i) => `${i + 1}. ${h} [${h.length} chars]`).join('\n')),
        h('div', { className: 'mk-label', style: { marginTop: '12px' } }, `Descriptions (${descriptions.length})`),
        h('div', { className: 'mk-pre' }, descriptions.map((d, i) => `${i + 1}. ${d} [${d.length} chars]`).join('\n')),
      ))
      if (data.sitelink_suggestions?.length) {
        card.append(h('div', { style: { marginTop: '8px' } },
          h('div', { className: 'mk-label' }, 'Sitelinks'),
          ...data.sitelink_suggestions.map(s => h('span', { className: 'mk-tag' }, s.title)),
        ))
      }
    }

    function renderSocialPostPreview(card, data, plat) {
      const text = data.post_text || data.caption || data.message || ''
      card.append(h('div', { className: 'mk-pre', style: { marginTop: '8px' } }, text))
      const tags = data.hashtags || []
      if (tags.length) card.append(h('div', { style: { marginTop: '8px' } }, ...tags.map(t => h('span', { className: 'mk-tag' }, t))))
      if (data.story_text) card.append(h('div', { style: { marginTop: '8px', fontSize: '12px', color: 'var(--txt-secondary)' } }, 'Story: ' + data.story_text))
    }

    // ── Google Ads Tab ───────────────────────────────────────────────────

    async function renderAdsTab(ct, campaign, data) {
      const adsCampaigns = data.ads_campaigns || {}
      const entries = Object.values(adsCampaigns)

      ct.append(h('div', { className: 'mk-row', style: { justifyContent: 'space-between', marginBottom: '16px' } },
        h('h3', null, 'Google Ads Campaign Plans'),
        asyncBtn('✨ Generate Plan', 'mk-primary', async () => {
          const lang = (campaign.languages || ['en'])[0]
          const d = await api.post(`/campaigns/${campaign.id}/ads-campaigns/generate`, { language: lang })
          if (d.success) { toast('Ads plan generated!'); render() } else toast(d.error || 'Failed', true)
        }),
      ))

      if (!entries.length) {
        ct.append(h('div', { className: 'mk-empty' }, h('p', null, 'No Google Ads plans yet. Generate one to get started.')))
        return
      }

      // Also generate keyword file
      ct.append(h('div', { style: { marginBottom: '16px' } },
        asyncBtn('🔑 Generate Keyword File', 'mk-secondary', async () => {
          const lang = (campaign.languages || ['en'])[0]
          const d = await api.post(`/campaigns/${campaign.id}/generate-keywords`, { language: lang, count: 60 })
          if (d.success) toast(`${d.count} keywords saved!`); else toast(d.error || 'Failed', true)
        }),
      ))

      entries.forEach(ac => {
        const card = h('div', { className: 'mk-card' })
        card.append(h('h3', null, ac.campaign_name || 'Untitled'))
        card.append(h('div', { className: 'mk-row', style: { gap: '20px', marginBottom: '12px' } },
          miniStat(ac.campaign_type || '—', 'Type'),
          miniStat(ac.bidding_strategy || '—', 'Bidding'),
          miniStat(ac.daily_budget_suggestion ? '$' + ac.daily_budget_suggestion + '/day' : '—', 'Budget'),
          miniStat(String((ac.ad_groups || []).length), 'Ad Groups'),
        ))

        const groups = ac.ad_groups || []
        groups.forEach(g => {
          const kws = g.keywords || []
          const ads = g.ads || []
          card.append(h('details', { style: { marginBottom: '8px' } },
            h('summary', { style: { cursor: 'pointer', fontWeight: '600', fontSize: '13px', padding: '6px 0' } },
              `${g.name} — ${kws.length} keywords · ${ads.length} ads`),
            h('div', { className: 'mk-pre', style: { marginTop: '4px' } },
              kws.map(k => {
                const kw = typeof k === 'string' ? k : k.keyword
                const mt = typeof k === 'object' ? ` [${k.match_type}]` : ''
                return kw + mt
              }).join('\n'),
            ),
          ))
        })

        card.append(h('div', { className: 'mk-row', style: { marginTop: '12px' } },
          asyncBtn('🗑 Delete', 'mk-danger', async () => {
            await api.del(`/campaigns/${campaign.id}/ads-campaigns/${ac.id}`)
            toast('Plan deleted'); render()
          }),
        ))
        ct.append(card)
      })
    }

    function miniStat(val, lbl) {
      return h('div', null,
        h('div', { style: { fontWeight: '600', fontSize: '14px' } }, val),
        h('div', { style: { fontSize: '10px', color: 'var(--txt-secondary)', textTransform: 'uppercase' } }, lbl),
      )
    }

    // ── Images Tab ───────────────────────────────────────────────────────

    function fileUrl(campaignId, relativePath) {
      return `${context.apiBaseUrl}/api/v1/user/${context.userId}/plugins/marketeer/campaigns/${campaignId}/file/${relativePath}`
    }

    function renderImagesTab(ct, campaign, data) {
      const files = data.files || []
      const langs = campaign.languages || ['en']
      const imageFiles = files.filter(f => f.path.includes('/images/'))

      const langSelect = h('select', { className: 'mk-input', style: { width: 'auto', marginBottom: '16px' } })
      langs.forEach(l => langSelect.append(h('option', { value: l }, langLabel(l))))
      ct.append(langSelect)

      const grid = h('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))', gap: '14px' } })

      IMG_TYPES.forEach(img => {
        const lang = langSelect.value || langs[0]
        const existing = imageFiles.find(f => f.path.includes(`/${lang}/images/${img.id}.`))
        const card = h('div', { className: 'mk-card', style: { padding: '0', overflow: 'hidden' } })

        if (existing) {
          const rel = `${lang}/images/${img.id}.png`
          const url = fileUrl(campaign.id, rel)
          const thumbWrap = h('div', { style: { width: '100%', height: '140px', background: '#0a0a0a', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', position: 'relative' } })
          const imgEl = h('img', { src: url, style: { maxWidth: '100%', maxHeight: '140px', objectFit: 'contain' } })
          imgEl.onerror = () => { imgEl.style.display = 'none'; thumbWrap.append(h('div', { style: { color: '#666', fontSize: '13px' } }, 'Preview unavailable')) }
          thumbWrap.append(imgEl)
          thumbWrap.addEventListener('click', () => window.open(url, '_blank'))
          card.append(thumbWrap)

          const info = h('div', { style: { padding: '10px 12px' } })
          info.append(h('div', { style: { fontWeight: '600', fontSize: '13px', marginBottom: '2px' } }, img.label))
          info.append(h('div', { style: { fontSize: '11px', color: 'var(--txt-secondary)', marginBottom: '8px' } }, img.dim + ' · ' + formatSize(existing.size)))

          const actions = h('div', { className: 'mk-row', style: { gap: '6px', flexWrap: 'wrap' } })
          actions.append(h('button', { className: 'mk-btn mk-secondary', style: { padding: '4px 10px', fontSize: '11px' }, onClick: () => { navigator.clipboard.writeText(url); toast('URL copied!') } }, '📋 Copy URL'))
          actions.append(h('a', { href: url, download: `${img.id}.png`, className: 'mk-btn mk-secondary', style: { padding: '4px 10px', fontSize: '11px', textDecoration: 'none' } }, '⬇ Download'))
          actions.append(asyncBtn('🔄', 'mk-secondary', async () => {
            const d = await api.post(`/campaigns/${campaign.id}/generate-image`, { type: img.id, language: lang })
            if (d.success) { toast(`${img.label} regenerated!`); render() } else toast(d.error || 'Failed', true)
          }, { style: { padding: '4px 10px', fontSize: '11px' } }))

          info.append(actions)
          card.append(info)
        } else {
          const empty = h('div', { style: { padding: '20px 14px', textAlign: 'center' } })
          empty.append(h('div', { style: { fontSize: '28px', marginBottom: '6px', opacity: 0.3 } }, '🖼'))
          empty.append(h('div', { style: { fontWeight: '600', fontSize: '13px' } }, img.label))
          empty.append(h('div', { style: { fontSize: '11px', color: 'var(--txt-secondary)', marginBottom: '10px' } }, img.dim))
          empty.append(asyncBtn('✨ Generate', 'mk-primary', async () => {
            const d = await api.post(`/campaigns/${campaign.id}/generate-image`, { type: img.id, language: langSelect.value || langs[0] })
            if (d.success) { toast(`${img.label} generated!`); render() } else toast(d.error || 'Failed', true)
          }, { style: { width: '100%', justifyContent: 'center' } }))
          card.append(empty)
        }

        grid.append(card)
      })

      ct.append(grid)

      langSelect.addEventListener('change', () => render())
    }

    function formatSize(bytes) {
      if (bytes > 1048576) return (bytes / 1048576).toFixed(1) + ' MB'
      if (bytes > 1024) return (bytes / 1024).toFixed(0) + ' KB'
      return bytes + ' B'
    }

    // ── Compliance Tab ───────────────────────────────────────────────────

    async function renderComplianceTab(ct, campaign) {
      const quickRes = await api.get(`/campaigns/${campaign.id}/compliance`)
      if (!quickRes.success) { ct.append(h('p', null, quickRes.error)); return }

      const comp = quickRes.compliance
      ct.append(h('div', { className: 'mk-card' },
        h('div', { className: 'mk-row', style: { justifyContent: 'space-between' } },
          h('h3', null, 'Quick Compliance Check'),
          h('span', { className: `mk-badge ${comp.compliant ? 'mk-badge-active' : 'mk-badge-paused'}` }, comp.compliant ? 'Compliant' : 'Review Needed'),
        ),
        h('div', { style: { marginTop: '12px' } },
          ...comp.checks.map(c => h('div', { style: { padding: '6px 0', borderBottom: '1px solid var(--border-light,#333)', fontSize: '13px' } },
            h('span', null, statusIcon(c.status) + ' '),
            h('strong', null, c.category + ': '),
            c.item,
            h('div', { style: { fontSize: '11px', color: 'var(--txt-secondary)', marginTop: '2px' } }, c.details),
          )),
        ),
      ))

      ct.append(h('div', { className: 'mk-card' },
        h('h3', null, 'Tools'),
        h('div', { className: 'mk-row' },
          asyncBtn('🤖 AI Compliance Review', 'mk-secondary', async () => {
            const lang = (campaign.languages || ['en'])[0]
            const d = await api.post(`/campaigns/${campaign.id}/compliance/ai-review`, { language: lang })
            if (d.success) {
              const r = d.review
              const area = h('div', { className: 'mk-pre', style: { marginTop: '12px', maxHeight: '500px' } })
              area.textContent = JSON.stringify(r, null, 2)
              ct.append(area)
              toast('AI review complete')
            } else toast(d.error || 'Failed', true)
          }),
          asyncBtn('🍪 Cookie Consent Snippet', 'mk-secondary', async () => {
            const lang = (campaign.languages || ['en'])[0]
            const d = await api.get(`/compliance/cookie-snippet?language=${lang}`)
            if (d.success) {
              const area = h('div', { className: 'mk-pre', style: { marginTop: '12px' } })
              area.textContent = d.cookie_consent_html
              ct.append(area)
              toast('Snippet loaded')
            }
          }),
          asyncBtn('🚀 Pre-Launch Check', 'mk-secondary', async () => {
            const d = await api.post(`/campaigns/${campaign.id}/checklist`)
            if (d.success) {
              const cl = d.checklist
              const area = h('div', { className: 'mk-card', style: { marginTop: '12px' } },
                h('div', { className: 'mk-row', style: { justifyContent: 'space-between' } },
                  h('h3', null, 'Launch Readiness'),
                  h('div', { className: 'mk-stat' }, h('div', { className: 'mk-stat-num' }, String(cl.score || '?')), h('div', { className: 'mk-stat-lbl' }, 'Score')),
                ),
                ...(cl.blocking_issues || []).map(b => h('div', { style: { padding: '4px 0', fontSize: '13px', color: '#e74c3c' } }, '❌ ' + b)),
                ...(cl.recommendations || []).map(r => h('div', { style: { padding: '4px 0', fontSize: '13px', color: 'var(--txt-secondary)' } }, '💡 ' + r)),
              )
              ct.append(area)
              toast('Checklist complete')
            } else toast(d.error || 'Failed', true)
          }),
        ),
      ))
    }

    function statusIcon(s) {
      return { pass: '✅', warning: '⚠️', fail: '❌', manual_check: '👁' }[s] || '❓'
    }

    // ── Settings Tab ─────────────────────────────────────────────────────

    function renderSettingsTab(ct, campaign) {
      const f = {
        ...campaign,
        ctas: JSON.parse(JSON.stringify(campaign.ctas || [])),
      }
      while (f.ctas.length < 2) f.ctas.push({ type: '', label: '', url: '' })

      // --- General ---
      const sec1 = h('div', { className: 'mk-card' })
      sec1.append(h('h3', null, 'General'))
      sec1.append(h('div', { className: 'mk-row', style: { gap: '16px' } },
        h('div', { className: 'mk-grow' }, field('Headline', 'text', f.title, v => f.title = v)),
        h('div', { style: { width: '140px', flexShrink: 0 } },
          h('div', { className: 'mk-field' },
            h('label', { className: 'mk-label' }, 'Status'),
            selectInput(['draft', 'active', 'paused', 'completed'], f.status, v => f.status = v),
          ),
        ),
      ))
      sec1.append(field('Topic / Angle', 'textarea', f.topic, v => f.topic = v))
      sec1.append(field('Target Audience', 'text', f.target_audience, v => f.target_audience = v))
      sec1.append(field('Unique Selling Points', 'textarea', (f.unique_selling_points || []).join('\n'), v => f.unique_selling_points = v.split('\n').map(s => s.trim()).filter(Boolean), 'One per line'))
      sec1.append(h('div', { className: 'mk-field' },
        h('label', { className: 'mk-label' }, 'Languages'),
        chipSelect(LANGS, f.languages || [], v => f.languages = v),
      ))
      sec1.append(h('div', { className: 'mk-field' },
        h('label', { className: 'mk-label' }, 'Platforms'),
        chipSelect(PLATFORMS.map(p => ({ code: p.id, label: p.icon + ' ' + p.label })), f.platforms || [], v => f.platforms = v),
      ))
      ct.append(sec1)

      // --- Appearance ---
      const sec2 = h('div', { className: 'mk-card' })
      sec2.append(h('h3', null, 'Appearance'))
      sec2.append(h('div', { className: 'mk-row', style: { gap: '16px', alignItems: 'flex-end' } },
        h('div', { className: 'mk-grow' }, field('Accent Color', 'text', f.accent_color || '#00b79d', v => f.accent_color = v, '#00b79d')),
        colorPreview(f.accent_color || '#00b79d'),
      ))
      sec2.append(field('Brand Logo URL', 'text', f.brand_logo_url || '', v => f.brand_logo_url = v, 'https://.../logo.png'))
      sec2.append(field('Color Scheme Description', 'text', f.color_scheme || '', v => f.color_scheme = v, 'e.g. dark backgrounds with vibrant accent'))
      sec2.append(h('div', { style: { fontSize: '11px', color: 'var(--txt-secondary)', marginTop: '-12px', marginBottom: '16px' } }, 'Used for landing pages and AI image generation.'))
      ct.append(sec2)

      // --- Call to Action ---
      const sec3 = h('div', { className: 'mk-card' })
      sec3.append(h('h3', null, 'Call to Action'))
      sec3.append(h('div', { style: { fontSize: '12px', color: 'var(--txt-secondary)', marginBottom: '12px' } }, 'Primary button is the main action. Secondary is optional (shown as a text link below).'))

      const ctaTypes = [
        { code: 'register', label: 'Register / Sign Up' },
        { code: 'email', label: 'Email / Contact' },
        { code: 'download', label: 'Download' },
        { code: 'custom', label: 'Custom URL' },
      ]
      for (let i = 0; i < 2; i++) {
        const lbl = i === 0 ? 'Primary' : 'Secondary (optional)'
        sec3.append(h('div', { style: { padding: '12px', background: 'var(--bg-input,#151520)', borderRadius: '8px', marginBottom: '10px' } },
          h('div', { style: { fontSize: '11px', fontWeight: '700', color: 'var(--txt-secondary)', textTransform: 'uppercase', marginBottom: '8px', letterSpacing: '.5px' } }, lbl),
          h('div', { className: 'mk-row', style: { gap: '10px' } },
            h('div', { style: { width: '130px', flexShrink: 0 } },
              h('div', { style: { fontSize: '10px', color: 'var(--txt-secondary)', marginBottom: '2px' } }, 'Type'),
              selectInput(ctaTypes.map(t => t.code), f.ctas[i].type || (i === 0 ? 'register' : ''), v => f.ctas[i].type = v),
            ),
            h('div', { style: { width: '160px', flexShrink: 0 } }, fieldInline('Button Text', f.ctas[i].label, v => f.ctas[i].label = v, i === 0 ? 'Start Free Trial' : 'Book a Demo')),
            h('div', { className: 'mk-grow' }, fieldInline('URL', f.ctas[i].url, v => f.ctas[i].url = v, i === 0 ? 'https://...' : 'mailto:...')),
          ),
        ))
      }
      ct.append(sec3)

      // --- Tracking ---
      const sec3b = h('div', { className: 'mk-card' })
      const tr = f.tracking || {}
      sec3b.append(h('h3', null, 'Tracking'))
      sec3b.append(h('div', { style: { fontSize: '12px', color: 'var(--txt-secondary)', marginBottom: '12px' } }, 'Per-campaign tracking IDs. Leave empty to use plugin defaults.'))
      sec3b.append(h('div', { className: 'mk-row', style: { gap: '12px' } },
        h('div', { className: 'mk-grow' }, fieldInline('GTM Container ID', tr.gtm_id || '', v => { if (!f.tracking) f.tracking = {}; f.tracking.gtm_id = v }, 'GTM-XXXXXXX')),
        h('div', { className: 'mk-grow' }, fieldInline('Google Ads Conv. ID', tr.gads_conversion_id || '', v => { if (!f.tracking) f.tracking = {}; f.tracking.gads_conversion_id = v }, 'AW-XXXXXXXXX')),
        h('div', { className: 'mk-grow' }, fieldInline('Meta Pixel ID', tr.meta_pixel_id || '', v => { if (!f.tracking) f.tracking = {}; f.tracking.meta_pixel_id = v }, '')),
      ))
      ct.append(sec3b)

      // --- Detail Modal ---
      const sec4 = h('div', { className: 'mk-card' })
      sec4.append(h('h3', null, 'Detail Overlay'))
      sec4.append(h('div', { style: { fontSize: '12px', color: 'var(--txt-secondary)', marginBottom: '12px' } }, 'Optional. If content is provided, the primary CTA opens a scrollable overlay instead of linking away. Use HTML for formatting (<h3>, <p>, <ul>, <li>, <strong>).'))
      sec4.append(field('Overlay Content (HTML)', 'textarea', f.modal_content || '', v => f.modal_content = v, '<h3>About</h3>\n<p>Details about your offer...</p>'))
      const ta = sec4.querySelector('textarea')
      if (ta) ta.style.minHeight = '160px'
      ct.append(sec4)

      // --- Actions ---
      const actions = h('div', { className: 'mk-row', style: { justifyContent: 'space-between', marginTop: '8px' } })
      actions.append(asyncBtn('Delete Campaign', 'mk-danger', async () => {
        if (!confirm('Delete this campaign and ALL generated assets permanently?')) return
        await api.del(`/campaigns/${campaign.id}`)
        toast('Campaign deleted'); nav('dashboard')
      }))
      actions.append(asyncBtn('Save All Changes', 'mk-primary', async () => {
        const ctas = f.ctas.filter(c => c.label && c.url)
        const d = await api.put(`/campaigns/${campaign.id}`, {
          title: f.title, topic: f.topic, target_audience: f.target_audience,
          unique_selling_points: f.unique_selling_points || [],
          cta_url: (ctas[0] || {}).url || f.cta_url,
          status: f.status, languages: f.languages, platforms: f.platforms,
          ctas, accent_color: f.accent_color, modal_content: f.modal_content || '',
          brand_logo_url: f.brand_logo_url || '', color_scheme: f.color_scheme || '',
          tracking: f.tracking || {},
        })
        if (d.success) { toast('Saved!'); render() } else toast(d.error || 'Failed', true)
      }))
      ct.append(actions)
    }

    function colorPreview(color) {
      const box = h('div', { style: { width: '36px', height: '36px', borderRadius: '8px', border: '2px solid var(--border-light,#444)', background: color, flexShrink: 0, marginBottom: '16px' } })
      return box
    }

    function fieldInline(label, value, onChange, placeholder) {
      const wrap = h('div')
      wrap.append(h('div', { style: { fontSize: '10px', color: 'var(--txt-secondary)', marginBottom: '2px' } }, label))
      const input = h('input', {
        className: 'mk-input',
        value: value || '',
        placeholder: placeholder || '',
        style: { padding: '7px 10px', fontSize: '13px' },
        onInput: (e) => onChange(e.target.value),
      })
      wrap.append(input)
      return wrap
    }

    // ── Plugin Config ────────────────────────────────────────────────────

    async function renderConfig() {
      root.innerHTML = ''
      root.append(h('div', { className: 'mk-back', onClick: () => nav('dashboard') }, '← Back'))
      root.append(h('h2', null, 'Plugin Settings'), h('p', { className: 'mk-sub' }, 'Defaults for all new campaigns.'))

      const d = await api.get('/config')
      if (!d.success) { root.append(h('p', null, d.error)); return }
      const cfg = d.config

      const sec1 = h('div', { className: 'mk-card' })
      sec1.append(h('h3', null, 'Brand'))
      sec1.append(h('div', { className: 'mk-row', style: { gap: '16px' } },
        h('div', { className: 'mk-grow' }, field('Brand Name', 'text', cfg.brand_name, v => cfg.brand_name = v)),
        h('div', { style: { width: '160px' } }, field('Default Language', 'text', cfg.default_language, v => cfg.default_language = v, 'en')),
      ))
      sec1.append(h('div', { className: 'mk-row', style: { gap: '16px', alignItems: 'flex-end' } },
        h('div', { className: 'mk-grow' }, field('Default CTA URL', 'text', cfg.cta_url, v => cfg.cta_url = v)),
        h('div', { style: { width: '160px', display: 'flex', gap: '8px', alignItems: 'flex-end' } },
          h('div', { className: 'mk-grow' }, field('Accent Color', 'text', cfg.default_accent_color || '#00b79d', v => cfg.default_accent_color = v)),
          colorPreview(cfg.default_accent_color || '#00b79d'),
        ),
      ))
      sec1.append(field('Default Brand Logo URL', 'text', cfg.default_brand_logo_url || '', v => cfg.default_brand_logo_url = v, 'https://.../logo.png'))
      sec1.append(field('Default Color Scheme', 'text', cfg.default_color_scheme || '', v => cfg.default_color_scheme = v, 'e.g. dark backgrounds with vibrant accent'))
      root.append(sec1)

      const sec2 = h('div', { className: 'mk-card' })
      sec2.append(h('h3', null, 'Legal Pages'))
      sec2.append(h('div', { style: { fontSize: '12px', color: 'var(--txt-secondary)', marginBottom: '12px' } }, 'Links shown at the bottom of every landing page and in the cookie consent bar.'))
      sec2.append(h('div', { className: 'mk-row', style: { gap: '16px' } },
        h('div', { className: 'mk-grow' }, field('Privacy Policy URL', 'text', cfg.privacy_policy_url, v => cfg.privacy_policy_url = v, 'https://...')),
        h('div', { className: 'mk-grow' }, field('Imprint URL', 'text', cfg.imprint_url, v => cfg.imprint_url = v, 'https://...')),
      ))
      root.append(sec2)

      const sec3 = h('div', { className: 'mk-card' })
      sec3.append(h('h3', null, 'Tracking'))
      sec3.append(h('div', { style: { fontSize: '12px', color: 'var(--txt-secondary)', marginBottom: '12px' } }, 'Optional. Scripts are injected into landing pages with consent gating.'))
      sec3.append(h('div', { className: 'mk-row', style: { gap: '16px' } },
        h('div', { className: 'mk-grow' }, field('GTM Container ID', 'text', cfg.gtm_id, v => cfg.gtm_id = v, 'GTM-XXXXXXX')),
        h('div', { className: 'mk-grow' }, field('Google Ads Conversion ID', 'text', cfg.gads_conversion_id, v => cfg.gads_conversion_id = v, 'AW-XXXXXXXXX')),
      ))
      root.append(sec3)

      root.append(h('div', { style: { textAlign: 'right', marginTop: '8px' } },
        asyncBtn('Save Settings', 'mk-primary', async () => {
          const r = await api.put('/config', cfg)
          if (r.success) toast('Settings saved!'); else toast(r.error || 'Failed', true)
        }),
      ))
    }

    // ── Shared UI Components ─────────────────────────────────────────────

    function field(label, type, value, onChange, placeholder) {
      const wrap = h('div', { className: 'mk-field' })
      wrap.append(h('label', { className: 'mk-label' }, label))
      const tag = type === 'textarea' ? 'textarea' : 'input'
      const input = h(tag, {
        className: `mk-input${type === 'textarea' ? ' mk-textarea' : ''}`,
        value: value || '',
        placeholder: placeholder || '',
        onInput: (e) => onChange(e.target.value),
      })
      if (type !== 'textarea') input.type = 'text'
      wrap.append(input)
      return wrap
    }

    function selectInput(options, current, onChange) {
      const sel = h('select', { className: 'mk-input', style: { width: 'auto' }, onChange: (e) => onChange(e.target.value) })
      options.forEach(o => {
        const opt = h('option', { value: o }, o.charAt(0).toUpperCase() + o.slice(1))
        if (o === current) opt.selected = true
        sel.append(opt)
      })
      return sel
    }

    function chipSelect(items, selected, onChange) {
      const wrap = h('div', { className: 'mk-chips' })
      items.forEach(item => {
        const code = item.code || item.id
        const label = item.flag ? item.flag + ' ' + item.label : item.label
        const isOn = selected.includes(code)
        const chip = h('div', { className: `mk-chip${isOn ? ' on' : ''}` }, label)
        chip.addEventListener('click', () => {
          const idx = selected.indexOf(code)
          if (idx >= 0) selected.splice(idx, 1); else selected.push(code)
          onChange([...selected])
          chip.className = `mk-chip${selected.includes(code) ? ' on' : ''}`
        })
        wrap.append(chip)
      })
      return wrap
    }

    function asyncBtn(label, cls, fn, extraAttrs) {
      const btn = h('button', { className: `mk-btn ${cls}`, ...(extraAttrs || {}) }, label)
      btn.addEventListener('click', async () => {
        const orig = btn.innerHTML
        btn.disabled = true
        btn.innerHTML = `<span class="mk-spinner"></span> Working...`
        try { await fn(btn) } catch (e) { toast(e.message, true) }
        btn.disabled = false
        btn.innerHTML = orig
      })
      return btn
    }

    // ── Boot ─────────────────────────────────────────────────────────────
    render()
  },
}
