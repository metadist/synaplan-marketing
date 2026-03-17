const MK_VERSION = 'v1.0.5'
const MK_BUILD = new Date().toISOString().replace(/[-T:]/g, (m) => m === 'T' ? '-' : m === '-' ? '' : '').slice(0, 15)

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
  { id: 'facebook', label: 'Facebook / Meta', icon: '📘' },
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

const IMG_STYLES = [
  { id: 'tech-forward', label: 'Tech-Forward', desc: 'Gradients, geometric shapes, modern & sleek' },
  { id: 'photorealistic', label: 'Photorealistic', desc: 'Natural lighting, real textures, depth of field' },
  { id: 'illustration', label: 'Illustration', desc: 'Hand-drawn, warm, artistic brush strokes' },
  { id: 'flat-design', label: 'Flat Design', desc: 'Bold solid colors, simple shapes, no shadows' },
  { id: '3d-render', label: '3D Render', desc: 'Polished 3D scene, realistic materials, cinematic' },
  { id: 'watercolor', label: 'Watercolor', desc: 'Soft washes of color, organic flowing edges' },
  { id: 'minimalist', label: 'Minimalist', desc: 'Maximum whitespace, one focal element, elegant' },
  { id: 'retro', label: 'Retro / Vintage', desc: 'Muted tones, halftone, nostalgic 70s/80s vibe' },
  { id: 'corporate', label: 'Corporate', desc: 'Clean, polished, professional business look' },
  { id: 'bold-graphic', label: 'Bold Graphic', desc: 'High contrast, punchy colors, poster style' },
]

const BG_STYLES = ['solid', 'parallax', 'image_cover', 'icon_fixed', 'icon_floating', 'glass_3d_ball']
const BG_POSITIONS = ['center center', 'center top', 'center bottom', 'left center', 'right center', 'left top', 'right top', 'left bottom', 'right bottom']
const BG_IMAGE_SIZES = ['cover', 'contain', '10%', '15%', '20%', '25%', '30%', '35%', '40%', '50%', '60%', '70%', '80%']
const BG_MOTION_LEVELS = ['subtle', 'medium', 'wild']
const TEXT_ALIGNS = ['left', 'center', 'right']

const STYLE_PRESETS = {
  parallax: {
    id: 'parallax',
    emoji: '🌊',
    label: 'Parallax Buzzwords',
    summary: 'Animated keyword rows, strong motion, high energy',
    style_prompt: 'Use layered parallax buzzword rows with smooth motion and strong visual hierarchy. Keep the hero text highly readable and conversion-driven.',
    values: { background_style: 'parallax', background_color: '#111111', background_secondary_color: '#1f2937', hero_text_align: 'center', background_motion_intensity: 'medium', background_overlay_opacity: '0.48' },
  },
  image_cover: {
    id: 'image_cover',
    emoji: '🖼',
    label: 'Image Cover Hero',
    summary: 'Full-cover image background with dark readable overlay',
    style_prompt: 'Create a cinematic hero with a full-cover background image and a subtle dark overlay. Keep content readable and centered around one clear CTA.',
    values: { background_style: 'image_cover', background_color: '#10131a', background_secondary_color: '#1b2230', background_image_position: 'center center', background_image_size: 'cover', hero_text_align: 'center', background_overlay_opacity: '0.58' },
  },
  icon_fixed: {
    id: 'icon_fixed',
    emoji: '📌',
    label: 'Static Icon Backdrop',
    summary: 'Minimal clean background with one fixed icon/logo',
    style_prompt: 'Use a minimal static background with one decorative icon/shape in the back. The icon should support the topic but never distract from the CTA.',
    values: { background_style: 'icon_fixed', background_color: '#0f1116', background_secondary_color: '#1a2030', background_icon_position: 'right bottom', background_icon_size_percent: '20', background_icon_opacity: '0.30', hero_text_align: 'left', background_overlay_opacity: '0.44' },
  },
  icon_floating: {
    id: 'icon_floating',
    emoji: '🎈',
    label: 'Bouncing Logo/Icon',
    summary: 'Playful floating icon motion in the background',
    style_prompt: 'Use a playful floating icon in the background with gentle motion. Keep animation subtle enough for business pages and maintain excellent readability.',
    values: { background_style: 'icon_floating', background_color: '#0d1218', background_secondary_color: '#1a2733', background_icon_position: 'center center', background_icon_size_percent: '20', background_icon_opacity: '0.34', background_motion_intensity: 'medium', hero_text_align: 'center', background_overlay_opacity: '0.46' },
  },
  glass_3d_ball: {
    id: 'glass_3d_ball',
    emoji: '🔮',
    label: 'Glass 3D Ball',
    summary: 'Pseudo-3D bouncing ball under glass UI layer',
    style_prompt: 'Create a modern glassmorphism hero with a pseudo-3D bouncing ball scene in the background. Keep text sharp, premium, and easy to read.',
    values: { background_style: 'glass_3d_ball', background_color: '#0d1117', background_secondary_color: '#202b3a', hero_text_align: 'center', background_motion_intensity: 'medium', background_overlay_opacity: '0.42' },
  },
  solid: {
    id: 'solid',
    emoji: '⚫',
    label: 'Solid Premium',
    summary: 'Calm static gradient, minimal and highly readable',
    style_prompt: 'Use a clean premium layout with a calm static background and strong typography contrast. Keep it readable and conversion-focused.',
    values: { background_style: 'solid', background_color: '#111111', background_secondary_color: '#2b2b2b', hero_text_align: 'center', background_overlay_opacity: '0.38' },
  },
}

function getStylePreset(id) {
  return STYLE_PRESETS[id] || STYLE_PRESETS.parallax
}

function getPresetPayload(id) {
  const preset = getStylePreset(id)
  return { ...preset.values, style_prompt: preset.style_prompt }
}

// ─── API Client ──────────────────────────────────────────────────────────────

function showSessionExpired() {
  if (document.querySelector('.mk-session-banner')) return
  const banner = h('div', { className: 'mk-session-banner' },
    h('div', { style: { display: 'flex', alignItems: 'center', gap: '12px', justifyContent: 'center', flexWrap: 'wrap' } },
      h('span', { style: { fontSize: '20px' } }, '🔒'),
      h('span', null, 'Your session has expired.'),
      h('button', {
        className: 'mk-btn mk-primary',
        style: { padding: '6px 16px', fontSize: '13px' },
        onClick: () => { window.location.reload() },
      }, 'Reload & log in'),
    ),
  )
  document.body.prepend(banner)
}

function showUpgradePrompt(payload = {}) {
  const existing = document.querySelector('.mk-upgrade-overlay')
  if (existing) existing.remove()

  const overlay = h('div', { className: 'mk-overlay mk-upgrade-overlay' })
  const modal = h('div', { className: 'mk-modal' })
  const action = payload.action || 'requests'
  const limitLine = (payload.used != null && payload.limit != null)
    ? `You have used ${payload.used}/${payload.limit} ${action.toLowerCase()} on the ${payload.current_level || 'current'} plan.`
    : 'You have reached the usage limit for your current plan.'

  modal.append(
    h('h3', null, 'Upgrade required'),
    h('p', { className: 'mk-modal-sub' },
      payload.error || `${limitLine} Upgrade your account to continue using Marketeer AI features.`),
    h('div', { className: 'mk-modal-actions' },
      h('button', {
        className: 'mk-btn mk-secondary',
        onClick: () => overlay.remove(),
      }, 'Not now'),
      h('button', {
        className: 'mk-btn mk-primary',
        onClick: () => {
          window.location.href = payload.upgrade_url || '/subscription'
        },
      }, 'View plans'),
    ),
  )

  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) overlay.remove()
  })
  overlay.append(modal)
  document.body.append(overlay)
}

function createApi(baseUrl, userId) {
  const url = (path) => `${baseUrl}/api/v1/user/${userId}/plugins/marketeer${path}`
  let refreshPromise = null

  async function refreshToken() {
    if (refreshPromise) return refreshPromise
    refreshPromise = fetch(`${baseUrl}/api/v1/auth/refresh`, { method: 'POST', credentials: 'include' })
      .then(r => r.ok)
      .catch(() => false)
      .finally(() => { refreshPromise = null })
    return refreshPromise
  }

  async function call(method, path, body, _isRetry) {
    let res
    try {
      const opts = {
        method,
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'include',
      }
      if (body) opts.body = JSON.stringify(body)
      res = await fetch(url(path), opts)
    } catch (e) {
      return { success: false, error: 'Network error — check your connection and try again.', _network: true }
    }

    if (res.status === 401 && !_isRetry) {
      const refreshed = await refreshToken()
      if (refreshed) return call(method, path, body, true)
      showSessionExpired()
      return { success: false, error: 'Session expired — please reload the page to log in again.', _auth: true }
    }
    if (res.status === 401 || res.status === 403) {
      showSessionExpired()
      return { success: false, error: 'Session expired — please reload the page to log in again.', _auth: true }
    }

    const contentType = res.headers.get('content-type') || ''
    if (!contentType.includes('application/json')) {
      return { success: false, error: `Unexpected server response (${res.status}). Try reloading the page.` }
    }
    if (!res.ok) {
      try {
        const data = await res.json()
        if (res.status === 429 || data?.code === 'rate_limit_exceeded') {
          showUpgradePrompt(data || {})
        }
        return data
      } catch {
        return { success: false, error: `Server error (${res.status})` }
      }
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
  .mk-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:8px;border:none;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;color:var(--txt-primary,#e0e0e0)}
  .mk-btn:hover{opacity:.85}.mk-btn:disabled{opacity:.4;cursor:not-allowed}
  .mk-primary{background:var(--brand,#00b79d);color:#fff}
  .mk-secondary{background:var(--bg-chip,#333);color:var(--txt-primary,#e0e0e0)}
  .mk-danger{background:#c0392b;color:#fff}
  .mk-ghost{background:transparent;color:var(--brand,#00b79d);padding:8px 12px}
  .mk-input{width:100%;padding:10px 14px;border-radius:8px;border:1px solid var(--border-light,#444);background:var(--bg-input,var(--bg-chip,#151520));color:var(--txt-primary,#e0e0e0);font-size:14px;outline:none}
  .mk-input:focus{border-color:var(--brand,#00b79d)}
  .mk-textarea{min-height:80px;resize:vertical;font-family:inherit}
  .mk-label{display:block;font-size:12px;font-weight:600;color:var(--txt-secondary,#999);margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px}
  .mk-field{margin-bottom:16px}
  .mk-tabs{display:flex;gap:0;border-bottom:2px solid var(--border-light,#333);margin-bottom:20px;overflow-x:auto}
  .mk-tab{padding:10px 18px;font-size:13px;font-weight:500;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;white-space:nowrap;color:var(--txt-secondary,#999);transition:all .15s;background:none;border-top:none;border-left:none;border-right:none;font-family:inherit}
  .mk-tab:hover{color:var(--txt-primary,#e0e0e0)}.mk-tab.active{color:var(--brand,#00b79d);border-bottom-color:var(--brand,#00b79d)}
  .mk-spinner{display:inline-block;width:16px;height:16px;border:2px solid transparent;border-top-color:currentColor;border-radius:50%;animation:mk-spin .6s linear infinite}
  @keyframes mk-spin{to{transform:rotate(360deg)}}
  .mk-empty{text-align:center;padding:40px 20px;color:var(--txt-secondary,#999)}
  .mk-empty p{margin:8px 0}
  .mk-chips{display:flex;flex-wrap:wrap;gap:6px}
  .mk-chip{padding:4px 12px;border-radius:16px;font-size:12px;border:1px solid var(--border-light,#444);cursor:pointer;transition:all .15s;user-select:none;color:var(--txt-primary,#e0e0e0)}
  .mk-chip.on{background:var(--brand,#00b79d);color:#fff;border-color:var(--brand,#00b79d)}
  .mk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
  .mk-preset-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px}
  .mk-preset{border:1px solid var(--border-light,#444);border-radius:10px;padding:10px 12px;background:var(--bg-input,var(--bg-chip,#151520));cursor:pointer;transition:border-color .15s,transform .15s}
  .mk-preset:hover{border-color:var(--brand,#00b79d);transform:translateY(-1px)}
  .mk-preset.on{border-color:var(--brand,#00b79d);box-shadow:0 0 0 1px rgba(0,183,157,.35) inset}
  .mk-preset-title{font-size:13px;font-weight:700;margin-bottom:4px}
  .mk-preset-sub{font-size:11px;color:var(--txt-secondary,#999);line-height:1.4}
  .mk-tag{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;background:var(--bg-chip,#333);margin:0 4px 4px 0}
  .mk-pre{background:var(--bg-input,var(--bg-chip,#151520));border:1px solid var(--border-light,#444);border-radius:8px;padding:14px;font-size:12px;white-space:pre-wrap;word-break:break-word;max-height:400px;overflow:auto;line-height:1.6;font-family:ui-monospace,monospace}
  .mk-preview{border:1px solid var(--border-light,#444);border-radius:8px;overflow:hidden;background:#fff}
  .mk-preview iframe{width:100%;height:500px;border:none}
  .mk-sep{border:none;border-top:1px solid var(--border-light,#333);margin:20px 0}
  .mk-back{cursor:pointer;color:var(--brand,#00b79d);font-size:13px;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px;background:none;border:none;padding:0;font-family:inherit}
  .mk-back:hover{text-decoration:underline}
  .mk-stat{text-align:center;padding:12px}.mk-stat-num{font-size:28px;font-weight:700;color:var(--brand,#00b79d)}.mk-stat-lbl{font-size:11px;color:var(--txt-secondary,#999);text-transform:uppercase}
  .mk-toast{position:fixed;bottom:24px;right:24px;background:#00b79d;color:#fff;padding:12px 20px;border-radius:8px;font-size:13px;z-index:9999;animation:mk-fade .3s}
  .mk-toast.error{background:#c0392b}
  .mk-session-banner{position:fixed;top:0;left:0;right:0;z-index:10001;background:#c0392b;color:#fff;padding:12px 20px;font-size:14px;text-align:center;animation:mk-fade .3s;box-shadow:0 2px 12px rgba(0,0,0,0.4)}
  @keyframes mk-fade{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
  .mk-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:10000;display:flex;align-items:center;justify-content:center;animation:mk-fade .2s;color:var(--txt-primary,#e0e0e0);font-family:system-ui,-apple-system,sans-serif}
  .mk-modal{background:var(--bg-card,#1e1e2e);border:1px solid var(--border-light,#333);border-radius:16px;padding:28px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,0.5);color:var(--txt-primary,#e0e0e0)}
  .mk-modal h3{font-size:18px;font-weight:700;margin:0 0 4px;color:var(--txt-primary,#e0e0e0)}
  .mk-modal-sub{color:var(--txt-secondary,#999);font-size:13px;margin:0 0 20px;line-height:1.5}
  .mk-modal-issue{display:flex;align-items:flex-start;gap:8px;padding:8px 12px;background:rgba(192,57,43,0.12);border:1px solid rgba(192,57,43,0.25);border-radius:8px;margin-bottom:8px;font-size:13px;color:#e74c3c}
  .mk-modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border-light,#333)}
  @keyframes mk-shake{0%,100%{transform:translateX(0)}15%{transform:translateX(-3px) rotate(-1deg)}30%{transform:translateX(3px) rotate(1deg)}45%{transform:translateX(-2px) rotate(-.5deg)}60%{transform:translateX(2px) rotate(.5deg)}75%{transform:translateX(-1px)}90%{transform:translateX(1px)}}
  @keyframes mk-pulse-glow{0%,100%{box-shadow:0 0 0 0 rgba(0,183,157,.4)}50%{box-shadow:0 0 10px 3px rgba(0,183,157,.2)}}
  .mk-attention{position:relative;animation:mk-pulse-glow 2s ease-in-out infinite}
  .mk-attention:hover{animation:mk-shake .5s ease-in-out}
  .mk-attention::after{content:attr(data-tooltip);position:absolute;top:calc(100% + 8px);left:50%;transform:translateX(-50%);background:var(--bg-card,#1e1e2e);border:1px solid var(--brand,#00b79d);color:var(--txt-primary,#e0e0e0);padding:5px 12px;border-radius:6px;font-size:11px;font-weight:500;white-space:nowrap;opacity:0;pointer-events:none;transition:opacity .2s;z-index:10}
  .mk-attention:hover::after{opacity:1}
`

// ─── Helpers ─────────────────────────────────────────────────────────────────

function h(tag, attrs, ...children) {
  const el = document.createElement(tag)
  if (attrs) Object.entries(attrs).forEach(([k, v]) => {
    if (k === 'style' && typeof v === 'object') Object.assign(el.style, v)
    else if (k.startsWith('on')) el.addEventListener(k.slice(2).toLowerCase(), v)
    else if (k === 'className') el.className = v
    else if (k === 'html') el.innerHTML = v
    else if (k === 'value') el.value = v
    else el.setAttribute(k, v)
  })
  children.flat().forEach(c => { if (c != null) el.append(typeof c === 'string' ? c : c) })
  return el
}

function toast(msg, err) {
  const text = msg || (err ? 'An error occurred' : 'Done')
  const t = h('div', { className: `mk-toast${err ? ' error' : ''}` }, text)
  document.body.append(t)
  setTimeout(() => t.remove(), 3000)
}

function langLabel(code) {
  const lang = LANGS.find(l => l.code === code)
  return (lang?.flag ?? '') + ' ' + (lang?.label || code)
}

function isValidUrl(str) {
  if (!str || typeof str !== 'string') return false
  try {
    const u = new URL(str.trim())
    return u.protocol === 'https:' || u.protocol === 'http:'
  } catch { return false }
}

// ─── App ─────────────────────────────────────────────────────────────────────

export default {
  mount(el, context) {
    const api = createApi(context.apiBaseUrl, context.userId)
    let state = { page: 'dashboard', campaignId: null, tab: 'pages' }
    let pageGenerationQueue = Promise.resolve()

    const style = document.createElement('style')
    style.textContent = CSS
    el.append(style)

    const root = h('div', { className: 'mk' })
    el.append(root)
    el.append(h('div', { style: { padding: '6px 2px 0', fontSize: '10px', opacity: 0.4, color: '#888' } }, `Marketeer ${MK_VERSION} · ${MK_BUILD}`))

    let cachedConfig = null

    function nav(page, opts = {}) {
      Object.assign(state, { page, ...opts })
      render()
    }

    async function queuePageGeneration(task) {
      const run = pageGenerationQueue.then(task, task)
      pageGenerationQueue = run.catch(() => {})
      return run
    }

    async function navToNewCampaign() {
      const d = await api.get('/config')
      if (!d.success) { toast('Could not load settings', true); return }
      cachedConfig = d.config
      const issues = []
      if (!isValidUrl(d.config.cta_url)) issues.push({ key: 'cta_url', label: 'Default CTA URL', hint: 'https://your-site.com/register' })
      if (!isValidUrl(d.config.privacy_policy_url)) issues.push({ key: 'privacy_policy_url', label: 'Privacy Policy URL', hint: 'https://your-site.com/privacy' })
      if (!isValidUrl(d.config.imprint_url)) issues.push({ key: 'imprint_url', label: 'Imprint / Legal Notice URL', hint: 'https://your-site.com/imprint' })
      if (issues.length === 0) { nav('new'); return }
      showSettingsModal(d.config, issues)
    }

    function showSettingsModal(cfg, issues) {
      const existing = document.querySelector('.mk-overlay')
      if (existing) existing.remove()

      const overlay = h('div', { className: 'mk-overlay' })
      const modal = h('div', { className: 'mk-modal' })

      modal.append(
        h('h3', null, 'Complete your settings first'),
        h('p', { className: 'mk-modal-sub' },
          'Before creating a campaign, the following settings need valid URLs. These are required for compliance and will appear on every landing page.'),
      )

      const issueList = h('div', { style: { marginBottom: '16px' } })
      issues.forEach(iss => {
        issueList.append(h('div', { className: 'mk-modal-issue' }, '❌ ', h('span', null, `${iss.label} is missing or invalid`)))
      })
      modal.append(issueList)

      const fields = {}
      issues.forEach(iss => {
        const wrap = h('div', { className: 'mk-field' })
        wrap.append(h('label', { className: 'mk-label' }, iss.label))
        const input = h('input', {
          className: 'mk-input',
          value: cfg[iss.key] || '',
          placeholder: iss.hint,
        })
        fields[iss.key] = input
        wrap.append(input)
        modal.append(wrap)
      })

      const actions = h('div', { className: 'mk-modal-actions' })
      actions.append(h('button', { className: 'mk-btn mk-secondary', onClick: () => overlay.remove() }, 'Cancel'))
      actions.append(h('button', { className: 'mk-btn mk-ghost', onClick: () => { overlay.remove(); nav('config') } }, 'Open full settings'))

      const saveBtn = h('button', { className: 'mk-btn mk-primary' }, 'Save & Continue')
      saveBtn.addEventListener('click', async () => {
        const updates = {}
        let valid = true
        for (const iss of issues) {
          const val = fields[iss.key].value.trim()
          if (!isValidUrl(val)) {
            fields[iss.key].style.borderColor = '#c0392b'
            valid = false
          } else {
            fields[iss.key].style.borderColor = ''
            updates[iss.key] = val
          }
        }
        if (!valid) { toast('Please enter valid URLs (starting with https://)', true); return }
        saveBtn.disabled = true
        saveBtn.innerHTML = '<span class="mk-spinner"></span> Saving...'
        const r = await api.put('/config', updates)
        if (r.success) {
          toast('Settings saved!')
          overlay.remove()
          nav('new')
        } else {
          toast(r.error || 'Failed to save', true)
          saveBtn.disabled = false
          saveBtn.textContent = 'Save & Continue'
        }
      })
      actions.append(saveBtn)
      modal.append(actions)

      overlay.append(modal)
      overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove() })
      document.body.append(overlay)
    }

    function getEffectiveFinalUrl(campaign, lang) {
      const override = ((campaign.final_urls || {})[lang] || '').trim()
      if (override) return override
      return (campaign.final_url || '').trim()
    }

    async function downloadCampaignZip(campaign) {
      const draft = {
        final_url: (campaign.final_url || campaign.cta_url || '').trim(),
        final_urls: { ...(campaign.final_urls || {}) },
      }
      const missingLangs = (campaign.languages || []).filter(lang => !getEffectiveFinalUrl(draft, lang))

      if (missingLangs.length === 0) {
        window.location.href = api.downloadUrl(`/campaigns/${campaign.id}/download`)
        return
      }

      const existing = document.querySelector('.mk-overlay')
      if (existing) existing.remove()

      const overlay = h('div', { className: 'mk-overlay' })
      const modal = h('div', { className: 'mk-modal' })
      modal.append(
        h('h3', null, 'Set final landing page URLs'),
        h('p', { className: 'mk-modal-sub' },
          'Google Ads export needs a real final URL for each landing page language. Set a default URL once and optionally override individual languages.'),
      )

      const defaultWrap = h('div', { className: 'mk-field' })
      defaultWrap.append(h('label', { className: 'mk-label' }, 'Default Google Ads Final URL'))
      const defaultInput = h('input', {
        className: 'mk-input',
        value: draft.final_url,
        placeholder: campaign.cta_url || 'https://your-site.com/landing-page',
      })
      defaultWrap.append(defaultInput)
      modal.append(defaultWrap)

      const overrideInputs = {}
      ;(campaign.languages || []).forEach(lang => {
        const wrap = h('div', { className: 'mk-field' })
        wrap.append(h('label', { className: 'mk-label' }, `Override for ${langLabel(lang)} (optional)`))
        const input = h('input', {
          className: 'mk-input',
          value: (draft.final_urls[lang] || '').trim(),
          placeholder: 'Uses default final URL when empty',
        })
        overrideInputs[lang] = input
        wrap.append(input)
        modal.append(wrap)
      })

      const actions = h('div', { className: 'mk-modal-actions' })
      actions.append(h('button', { className: 'mk-btn mk-secondary', onClick: () => overlay.remove() }, 'Cancel'))

      const saveBtn = h('button', { className: 'mk-btn mk-primary' }, 'Save & Download ZIP')
      saveBtn.addEventListener('click', async () => {
        const final_url = defaultInput.value.trim()
        const final_urls = {}
        let hasError = false

        defaultInput.style.borderColor = ''
        Object.values(overrideInputs).forEach(input => { input.style.borderColor = '' })

        for (const lang of campaign.languages || []) {
          const override = overrideInputs[lang].value.trim()
          if (override) final_urls[lang] = override
          const effective = override || final_url
          if (!isValidUrl(effective)) {
            hasError = true
            if (override) overrideInputs[lang].style.borderColor = '#c0392b'
            else defaultInput.style.borderColor = '#c0392b'
          }
        }

        if (hasError) {
          toast('Please enter a valid final URL for every language.', true)
          return
        }

        saveBtn.disabled = true
        saveBtn.innerHTML = '<span class="mk-spinner"></span> Saving...'
        const r = await api.put(`/campaigns/${campaign.id}`, { final_url, final_urls })
        if (!r.success) {
          toast(r.error || 'Failed to save final URLs', true)
          saveBtn.disabled = false
          saveBtn.textContent = 'Save & Download ZIP'
          return
        }

        campaign.final_url = final_url
        campaign.final_urls = final_urls
        overlay.remove()
        window.location.href = api.downloadUrl(`/campaigns/${campaign.id}/download`)
      })
      actions.append(saveBtn)
      modal.append(actions)

      overlay.append(modal)
      overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove() })
      document.body.append(overlay)
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
        root.innerHTML = ''
        root.append(h('div', { className: 'mk-empty' },
          h('p', { style: { fontSize: '32px', marginBottom: '8px' } }, d._auth ? '🔒' : '⚠️'),
          h('p', null, d.error || 'Could not load dashboard. Is the plugin installed?'),
          h('button', { className: 'mk-btn mk-primary', style: { marginTop: '12px' }, onClick: () => window.location.reload() }, 'Reload page'),
        ))
        return
      }

      const ov = d.overview
      const noCampaigns = !d.campaigns.length
      const settingsBtn = h('button', { className: 'mk-btn mk-secondary' + (noCampaigns ? ' mk-attention' : ''), onClick: () => nav('config') }, '⚙ Settings')
      if (noCampaigns) settingsBtn.setAttribute('data-tooltip', 'Start here to prepare the basic settings')
      const header = h('div', { className: 'mk-row', style: { justifyContent: 'space-between', marginBottom: '20px' } },
        h('div', null,
          h('h2', null, 'Campaigns'),
          h('p', { className: 'mk-sub', style: { margin: 0 } }, `${ov.total_campaigns} campaigns · ${ov.total_pages} pages`),
        ),
        h('div', { className: 'mk-row' },
          settingsBtn,
          h('button', { className: 'mk-btn mk-primary', onClick: () => navToNewCampaign() }, '+ New Campaign'),
        ),
      )
      root.append(header)

      if (!d.campaigns.length) {
        root.append(h('div', { className: 'mk-empty' },
          h('p', { style: { fontSize: '40px' } }, '📢'),
          h('p', null, 'No campaigns yet.'),
          h('button', { className: 'mk-btn mk-primary', onClick: () => navToNewCampaign() }, 'Create your first campaign'),
        ))
        return
      }

      d.campaigns.forEach(c => {
        const card = h('div', { className: 'mk-card', style: { cursor: 'pointer' }, role: 'button', tabindex: '0', onClick: () => nav('campaign', { campaignId: c.id, tab: 'pages' }) },
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
      const form = {
        slug: '',
        title: '',
        topic: '',
        audience: '',
        usps: '',
        target_url: '',
        style_preset: 'parallax',
        languages: ['en'],
        platforms: ['google'],
        ctas: [{ type: 'register', label: 'Start Free Trial', url: '' }],
      }

      root.append(
        h('button', { className: 'mk-back', onClick: () => nav('dashboard') }, '← Back to campaigns'),
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
          field('Primary Target URL', 'text', form.target_url, v => form.target_url = v, 'https://your-site.com/offer'),
          field('Target Audience', 'text', form.audience, v => form.audience = v, 'e.g. CTOs, developers, knowledge workers'),
          field('Unique Selling Points', 'textarea', form.usps, v => form.usps = v, 'One per line'),
        )

        container.append(h('div', { className: 'mk-field' },
          h('label', { className: 'mk-label' }, 'Landing Design Preset (Quick Start)'),
          h('div', { className: 'mk-preset-grid' },
            ...Object.values(STYLE_PRESETS).map(preset => {
              const selected = form.style_preset === preset.id
              const card = h('div', {
                className: `mk-preset${selected ? ' on' : ''}`,
                onClick: () => { form.style_preset = preset.id; renderStep1() },
              },
              h('div', { className: 'mk-preset-title' }, `${preset.emoji} ${preset.label}`),
              h('div', { className: 'mk-preset-sub' }, preset.summary))
              return card
            }),
          ),
          h('div', { style: { fontSize: '11px', color: 'var(--txt-secondary)', marginTop: '8px' } },
            'Only campaign text + URL are needed. Background behavior and AI style prompt are auto-applied from the preset.',
          ),
        ))

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
        try {
          const d = await api.post('/plan', { idea: form.topic, language: form.languages[0] || 'en' })
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
        } catch (e) {
          toast('Planning failed: ' + (e.message || 'Network error'), true)
        } finally {
          btn.disabled = false; btn.textContent = '✨ Plan with AI'
        }
      }

      async function createCampaign() {
        if (!form.slug || !form.title || !form.topic) { toast('Fill in name, headline and topic', true); return }
        const presetPayload = getPresetPayload(form.style_preset)
        const ctaUrl = (form.target_url || '').trim()
        if (!ctaUrl) {
          toast('Please add a primary target URL', true)
          return
        }
        const ctas = [{ type: 'register', label: 'Start Free Trial', url: ctaUrl }]
        const body = {
          slug: form.slug,
          title: form.title,
          topic: form.topic,
          cta_url: ctaUrl,
          target_audience: form.audience,
          unique_selling_points: form.usps.split('\n').map(s => s.trim()).filter(Boolean),
          languages: form.languages,
          platforms: form.platforms,
          ctas,
          ...presetPayload,
        }
        try {
          const d = await api.post('/campaigns', body)
          if (d.success) {
            toast('Campaign created!')
            nav('campaign', { campaignId: d.campaign.id, tab: 'pages' })
          } else {
            toast(d.error || 'Creation failed', true)
          }
        } catch (e) {
          toast('Creation failed: ' + (e.message || 'Network error'), true)
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
        h('button', { className: 'mk-back', onClick: () => nav('dashboard') }, '← Campaigns'),
        h('div', { className: 'mk-row', style: { justifyContent: 'space-between', marginBottom: '4px' } },
          h('h2', null, c.title),
          h('div', { className: 'mk-row' },
            h('span', { className: `mk-badge mk-badge-${c.status}` }, c.status),
            h('button', { className: 'mk-btn mk-secondary', onClick: () => downloadCampaignZip(c) }, '📦 Download ZIP'),
          ),
        ),
        h('p', { className: 'mk-sub' }, (c.languages || []).map(l => langLabel(l)).join(' · ') + ' — ' + (c.platforms || []).map(p => PLATFORMS.find(x => x.id === p)?.label || p).join(', ')),
      )

      const tabs = [
        { id: 'pages', label: '📄 Pages' },
        { id: 'adcopy', label: '📢 Ad Copy' },
        { id: 'ads', label: '📊 Google Ads' },
        { id: 'images', label: '🖼 Media' },
        { id: 'compliance', label: '🛡 Compliance' },
        { id: 'settings', label: '⚙ Settings' },
      ]

      const tabBar = h('div', { className: 'mk-tabs' })
      tabs.forEach(t => {
        tabBar.append(h('button', {
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
      const publishedPages = data.published_pages || {}
      const langs = campaign.languages || ['en']
      const generatedCount = langs.filter(lang => !!pages[lang]).length

      ct.append(h('div', { className: 'mk-row', style: { justifyContent: 'space-between', marginBottom: '16px' } },
        h('h3', null, 'Landing Pages'),
        h('div', { className: 'mk-row', style: { gap: '8px', alignItems: 'center' } },
          generatedCount === langs.length
            ? h('span', { className: 'mk-badge mk-badge-active' }, `${generatedCount}/${langs.length} languages`)
            : generatedCount > 0
              ? h('span', { className: 'mk-badge mk-badge-paused' }, `${generatedCount}/${langs.length} languages`)
              : h('span', { className: 'mk-badge mk-badge-draft' }, `0/${langs.length} languages`),
          asyncBtn(
            generatedCount > 0 ? `🔄 Regenerate all ${langs.length} languages` : `✨ Generate all ${langs.length} languages`,
            generatedCount > 0 ? 'mk-secondary' : 'mk-primary',
            async (btn) => {
              let ok = 0
              const failed = []
              for (let j = 0; j < langs.length; j++) {
                const lang = langs[j]
                btn.innerHTML = `<span class="mk-spinner"></span> ${j + 1}/${langs.length}: ${langLabel(lang)}...`
                const d = await queuePageGeneration(() => api.post(`/campaigns/${campaign.id}/generate`, { language: lang }))
                if (d.success) ok++
                else failed.push(`${langLabel(lang)}: ${d.error || 'Failed'}`)
              }
              if (ok > 0) {
                toast(`${ok}/${langs.length} landing pages generated!`)
                render()
              }
              failed.forEach(msg => toast(msg, true))
            },
          ),
        ),
      ))

      langs.forEach(lang => {
        const page = pages[lang]
        const card = h('div', { className: 'mk-card' })
        card.append(h('div', { className: 'mk-row', style: { justifyContent: 'space-between' } },
          h('h3', null, langLabel(lang) + ' Landing Page'),
          page ? h('span', { className: 'mk-badge mk-badge-active' }, 'Generated') : h('span', { className: 'mk-badge mk-badge-draft' }, 'Not generated'),
        ))

        const ctaUrls = campaign.cta_urls || {}
        const finalUrls = campaign.final_urls || {}
        const ctaRow = h('div', { className: 'mk-field', style: { marginTop: '8px', marginBottom: '8px' } })
        const ctaLabel = h('label', { className: 'mk-label' }, 'CTA Target URL for ' + langLabel(lang))
        const ctaInput = h('input', {
          className: 'mk-input',
          value: ctaUrls[lang] || '',
          placeholder: campaign.cta_url || 'https://your-site.com/register (uses campaign default)',
          style: { fontSize: '13px' },
        })
        let ctaSaveTimeout = null
        ctaInput.addEventListener('input', () => {
          clearTimeout(ctaSaveTimeout)
          ctaSaveTimeout = setTimeout(async () => {
            const updated = { ...(campaign.cta_urls || {}), [lang]: ctaInput.value.trim() }
            const r = await api.put(`/campaigns/${campaign.id}`, { cta_urls: updated })
            if (r.success) { campaign.cta_urls = updated; toast('CTA URL saved') }
          }, 800)
        })
        ctaRow.append(ctaLabel, ctaInput)
        card.append(ctaRow)

        const finalRow = h('div', { className: 'mk-field', style: { marginTop: '8px', marginBottom: '8px' } })
        const finalLabel = h('label', { className: 'mk-label' }, 'Google Ads Final URL for ' + langLabel(lang))
        const finalInput = h('input', {
          className: 'mk-input',
          value: finalUrls[lang] || '',
          placeholder: campaign.final_url || campaign.cta_url || 'https://your-site.com/landing-page (uses campaign default)',
          style: { fontSize: '13px' },
        })
        let finalSaveTimeout = null
        finalInput.addEventListener('input', () => {
          clearTimeout(finalSaveTimeout)
          finalSaveTimeout = setTimeout(async () => {
            const updated = { ...(campaign.final_urls || {}), [lang]: finalInput.value.trim() }
            if (!finalInput.value.trim()) delete updated[lang]
            const r = await api.put(`/campaigns/${campaign.id}`, { final_urls: updated })
            if (r.success) { campaign.final_urls = updated; toast('Final URL saved') }
          }, 800)
        })
        finalRow.append(finalLabel, finalInput)
        card.append(finalRow)

        if (page) {
          const htmlUrl = fileUrl(campaign.id, lang + '/index.html')
          const published = publishedPages[lang] || null
          const publicUrl = published?.slug ? `${context.apiBaseUrl}/api/v1/marketeer/public/${published.slug}` : ''
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
              /* Public publishing buttons hidden for v1.0.0 - will be re-enabled later */
              asyncBtn('🔄 Regenerate', 'mk-secondary', async () => {
                const d = await queuePageGeneration(() => api.post(`/campaigns/${campaign.id}/generate`, { language: lang }))
                if (d.success) { toast(`${langLabel(lang)} page regenerated!`); render() } else toast(`${langLabel(lang)}: ${d.error || 'Failed'}`, true)
              }),
            ),
            asyncBtn('🗑 Delete', 'mk-danger', async () => {
              if (!confirm('Delete this page?')) return
              const d = await api.del(`/campaigns/${campaign.id}/pages/${lang}`)
              if (d.success) { toast('Page deleted'); render() } else toast(d.error || 'Delete failed', true)
            }),
          ))
          /* Public URL display hidden for v1.0.0 */
        } else {
          card.append(h('div', { className: 'mk-empty', style: { padding: '24px' } },
            h('p', null, 'No landing page for this language yet.'),
            asyncBtn('✨ Generate Landing Page', 'mk-primary', async () => {
              const d = await queuePageGeneration(() => api.post(`/campaigns/${campaign.id}/generate`, { language: lang }))
              if (d.success) { toast(`${langLabel(lang)} page generated!`); render() } else toast(`${langLabel(lang)}: ${d.error || 'Failed'}`, true)
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
      const platforms = campaign.platforms || ['google']

      platforms.forEach(plat => {
        const pInfo = PLATFORMS.find(p => p.id === plat) || { icon: '📝', label: plat }
        const allExisting = langs.map(lang => {
          if (plat === 'google') return Object.values(adCopy).find(a => a.language === lang) || null
          return Object.values(socialPosts).find(s => s.platform === plat && s.language === lang) || null
        })
        const generatedCount = allExisting.filter(Boolean).length

        const card = h('div', { className: 'mk-card' })
        card.append(h('div', { className: 'mk-row', style: { justifyContent: 'space-between' } },
          h('h3', null, pInfo.icon + ' ' + pInfo.label),
          generatedCount === langs.length
            ? h('span', { className: 'mk-badge mk-badge-active' }, `${generatedCount}/${langs.length} languages`)
            : generatedCount > 0
              ? h('span', { className: 'mk-badge mk-badge-paused' }, `${generatedCount}/${langs.length} languages`)
              : h('span', null),
        ))

        langs.forEach((lang, i) => {
          const existing = allExisting[i]
          const langSection = h('div', { style: { marginTop: i > 0 ? '16px' : '8px', paddingTop: i > 0 ? '12px' : '0', borderTop: i > 0 ? '1px solid var(--border-light,#333)' : 'none' } })
          langSection.append(h('div', { style: { fontSize: '12px', fontWeight: '600', color: 'var(--txt-secondary,#999)', marginBottom: '6px', textTransform: 'uppercase', letterSpacing: '.5px' } }, langLabel(lang)))

          if (existing) {
            if (plat === 'google') renderGoogleAdPreview(langSection, existing)
            else renderSocialPostPreview(langSection, existing, plat)
          } else {
            langSection.append(h('div', { style: { fontSize: '13px', color: 'var(--txt-secondary,#666)', fontStyle: 'italic', padding: '8px 0' } }, 'Not yet generated'))
          }
          card.append(langSection)
        })

        card.append(h('div', { style: { marginTop: '14px', paddingTop: '12px', borderTop: '1px solid var(--border-light,#333)' } },
          asyncBtn(generatedCount > 0 ? `🔄 Regenerate all ${langs.length} languages` : `✨ Generate all ${langs.length} languages`, generatedCount > 0 ? 'mk-secondary' : 'mk-primary', async (btn) => {
            let ok = 0
            for (let j = 0; j < langs.length; j++) {
              btn.innerHTML = `<span class="mk-spinner"></span> ${j + 1}/${langs.length}: ${langLabel(langs[j])}...`
              const d = await api.post(`/campaigns/${campaign.id}/generate-ad-copy`, { platform: plat, language: langs[j] })
              if (d.success) ok++; else toast(`${langLabel(langs[j])}: ${d.error || 'Failed'}`, true)
            }
            if (ok > 0) { toast(`${pInfo.label}: ${ok}/${langs.length} languages generated!`); render() }
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
      const allEntries = Object.values(adsCampaigns)
      const langs = campaign.languages || ['en']

      ct.append(h('div', { className: 'mk-row', style: { justifyContent: 'space-between', marginBottom: '16px' } },
        h('h3', null, 'Google Ads Campaign Plans'),
        h('div', { className: 'mk-row', style: { gap: '8px' } },
          asyncBtn(`🔑 Keywords (all)`, 'mk-secondary', async (btn) => {
            let totalKw = 0
            for (let j = 0; j < langs.length; j++) {
              btn.innerHTML = `<span class="mk-spinner"></span> Keywords ${j + 1}/${langs.length}: ${langLabel(langs[j])}...`
              const d = await api.post(`/campaigns/${campaign.id}/generate-keywords`, { language: langs[j], count: 60 })
              if (d.success) totalKw += d.count; else toast(`${langLabel(langs[j])}: ${d.error || 'Failed'}`, true)
            }
            if (totalKw > 0) toast(`${totalKw} keywords saved across ${langs.length} languages!`)
          }),
          asyncBtn(`✨ Generate all languages`, 'mk-primary', async (btn) => {
            let ok = 0
            for (let j = 0; j < langs.length; j++) {
              btn.innerHTML = `<span class="mk-spinner"></span> ${j + 1}/${langs.length}: ${langLabel(langs[j])}...`
              const d = await api.post(`/campaigns/${campaign.id}/ads-campaigns/generate`, { language: langs[j] })
              if (d.success) ok++; else toast(`${langLabel(langs[j])}: ${d.error || 'Failed'}`, true)
            }
            if (ok > 0) { toast(`${ok}/${langs.length} Ads plans generated!`); render() }
          }),
        ),
      ))

      langs.forEach(lang => {
        const entries = allEntries.filter(ac => ac.language === lang)
        const card = h('div', { className: 'mk-card' })
        card.append(h('div', { className: 'mk-row', style: { justifyContent: 'space-between', marginBottom: '10px' } },
          h('h3', { style: { margin: 0 } }, langLabel(lang)),
          entries.length
            ? h('span', { className: 'mk-badge mk-badge-active' }, `${entries.length} plan${entries.length > 1 ? 's' : ''}`)
            : h('span', { className: 'mk-badge mk-badge-draft' }, 'Not generated'),
        ))

        if (!entries.length) {
          card.append(h('div', { style: { padding: '16px 0', textAlign: 'center', color: 'var(--txt-secondary,#666)', fontSize: '13px', fontStyle: 'italic' } },
            'No Ads plan for this language yet.',
          ))
        }

        entries.forEach(ac => {
          const planSection = h('div', { style: { padding: '12px', background: 'var(--bg-input,var(--bg-chip,#151520))', borderRadius: '8px', marginBottom: '8px' } })
          planSection.append(h('div', { className: 'mk-row', style: { justifyContent: 'space-between', marginBottom: '8px' } },
            h('div', { style: { fontWeight: '600', fontSize: '14px' } }, ac.campaign_name || 'Untitled'),
            asyncBtn('🗑', 'mk-danger', async () => {
              const d = await api.del(`/campaigns/${campaign.id}/ads-campaigns/${ac.id}`)
              if (d.success) { toast('Plan deleted'); render() } else toast(d.error || 'Delete failed', true)
            }, { style: { padding: '3px 8px', fontSize: '11px' } }),
          ))
          planSection.append(h('div', { className: 'mk-row', style: { gap: '16px', marginBottom: '8px' } },
            miniStat(ac.campaign_type || '—', 'Type'),
            miniStat(ac.bidding_strategy || '—', 'Bidding'),
            miniStat(ac.daily_budget_suggestion ? '$' + ac.daily_budget_suggestion + '/day' : '—', 'Budget'),
            miniStat(String((ac.ad_groups || []).length), 'Ad Groups'),
          ))
          const groups = ac.ad_groups || []
          groups.forEach(g => {
            const kws = g.keywords || []
            const ads = g.ads || []
            planSection.append(h('details', { style: { marginBottom: '6px' } },
              h('summary', { style: { cursor: 'pointer', fontWeight: '600', fontSize: '12px', padding: '4px 0' } },
                `${g.name} — ${kws.length} keywords · ${ads.length} ads`),
              h('div', { className: 'mk-pre', style: { marginTop: '4px', fontSize: '11px' } },
                kws.map(k => {
                  const kw = typeof k === 'string' ? k : k.keyword
                  const mt = typeof k === 'object' ? ` [${k.match_type}]` : ''
                  return kw + mt
                }).join('\n'),
              ),
            ))
          })
          card.append(planSection)
        })

        card.append(h('div', { className: 'mk-row', style: { marginTop: '8px', gap: '8px' } },
          asyncBtn(entries.length ? '🔄 Regenerate' : '✨ Generate', entries.length ? 'mk-secondary' : 'mk-primary', async (btn) => {
            btn.innerHTML = `<span class="mk-spinner"></span> ${langLabel(lang)}...`
            const d = await api.post(`/campaigns/${campaign.id}/ads-campaigns/generate`, { language: lang })
            if (d.success) { toast(`${langLabel(lang)} Ads plan generated!`); render() } else toast(d.error || 'Failed', true)
          }),
          asyncBtn('🔑 Keywords', 'mk-secondary', async (btn) => {
            btn.innerHTML = `<span class="mk-spinner"></span> Keywords...`
            const d = await api.post(`/campaigns/${campaign.id}/generate-keywords`, { language: lang, count: 60 })
            if (d.success) toast(`${d.count} ${langLabel(lang)} keywords saved!`); else toast(d.error || 'Failed', true)
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
      const videoFiles = files.filter(f => f.path.includes('/videos/'))
      const collaterals = data.collaterals || {}
      const curStyle = IMG_STYLES.find(s => s.id === (campaign.image_style || 'tech-forward'))

      const styleBar = h('div', { className: 'mk-card', style: { padding: '14px 20px' } })
      styleBar.append(h('div', { className: 'mk-row', style: { justifyContent: 'space-between' } },
        h('div', null,
          h('div', { style: { fontSize: '11px', fontWeight: '600', color: 'var(--txt-secondary,#999)', textTransform: 'uppercase', letterSpacing: '.5px', marginBottom: '2px' } }, 'Image Style'),
          h('div', { style: { fontSize: '14px', fontWeight: '600' } }, curStyle?.label || 'Tech-Forward'),
          h('div', { style: { fontSize: '12px', color: 'var(--txt-secondary)', fontStyle: 'italic' } }, curStyle?.desc || ''),
        ),
        h('button', { className: 'mk-btn mk-ghost', style: { fontSize: '12px' }, onClick: () => { state.tab = 'settings'; render() } }, '⚙ Change style'),
      ))
      if (campaign.image_style_notes) {
        styleBar.append(h('div', { style: { fontSize: '12px', color: 'var(--txt-secondary)', marginTop: '6px', borderTop: '1px solid var(--border-light,#333)', paddingTop: '6px' } }, '📝 ' + campaign.image_style_notes))
      }
      ct.append(styleBar)

      const promptOverrides = {}

      langs.forEach(lang => {
        const langCard = h('div', { className: 'mk-card' })
        const langImages = imageFiles.filter(f => f.path.includes(`/${lang}/images/`))
        const generatedCount = IMG_TYPES.filter(img => langImages.some(f => f.path.includes(`/${lang}/images/${img.id}.`))).length

        langCard.append(h('div', { className: 'mk-row', style: { justifyContent: 'space-between', marginBottom: '12px' } },
          h('h3', { style: { margin: 0 } }, langLabel(lang) + ' Images'),
          generatedCount > 0
            ? h('span', { className: generatedCount === IMG_TYPES.length ? 'mk-badge mk-badge-active' : 'mk-badge mk-badge-paused' }, `${generatedCount}/${IMG_TYPES.length}`)
            : h('span', { className: 'mk-badge mk-badge-draft' }, 'None'),
        ))

        const grid = h('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: '10px' } })

        IMG_TYPES.forEach(img => {
          const existing = langImages.find(f => f.path.includes(`/${lang}/images/${img.id}.`))
          const collKey = Object.keys(collaterals).find(k => collaterals[k]?.type === img.id && collaterals[k]?.language === lang)
          const lastPrompt = collKey ? collaterals[collKey]?.prompt : null
          const tile = h('div', { style: { border: '1px solid var(--border-light,#333)', borderRadius: '8px', overflow: 'hidden', background: 'var(--bg-input,var(--bg-chip,#151520))' } })

          if (existing) {
            const rel = `${lang}/images/${img.id}.png`
            const url = fileUrl(campaign.id, rel)
            const thumb = h('div', { style: { height: '110px', background: '#0a0a0a', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer' } })
            const imgEl = h('img', { src: url, style: { maxWidth: '100%', maxHeight: '110px', objectFit: 'contain' } })
            imgEl.onerror = () => { imgEl.style.display = 'none'; thumb.append(h('div', { style: { color: '#666', fontSize: '11px' } }, 'N/A')) }
            thumb.append(imgEl)
            thumb.addEventListener('click', () => window.open(url, '_blank'))
            tile.append(thumb)

            const info = h('div', { style: { padding: '8px 10px' } })
            info.append(h('div', { style: { fontWeight: '600', fontSize: '12px' } }, img.label))
            info.append(h('div', { style: { fontSize: '10px', color: 'var(--txt-secondary)', marginBottom: '4px' } }, img.dim))

            const promptToggle = h('button', { className: 'mk-btn mk-ghost', style: { padding: '2px 6px', fontSize: '10px', marginBottom: '4px' }, onClick: () => {
              promptArea.style.display = promptArea.style.display === 'none' ? 'block' : 'none'
            } }, '✏️ Edit prompt')
            info.append(promptToggle)

            const pKey = `${lang}_${img.id}`
            const promptArea = h('div', { style: { display: 'none', marginBottom: '6px' } })
            const pInput = h('textarea', { className: 'mk-input', rows: 3, value: promptOverrides[pKey] || lastPrompt || '', placeholder: 'Custom prompt (leave empty for auto)...', style: { fontSize: '11px', padding: '6px 8px', resize: 'vertical' }, onInput: (e) => { promptOverrides[pKey] = e.target.value } })
            promptArea.append(pInput)
            info.append(promptArea)

            info.append(h('div', { className: 'mk-row', style: { gap: '4px' } },
              h('button', { className: 'mk-btn mk-secondary', style: { padding: '3px 8px', fontSize: '10px' }, onClick: () => { navigator.clipboard.writeText(url); toast('Copied!') } }, '📋'),
              h('a', { href: url, download: `${img.id}.png`, className: 'mk-btn mk-secondary', style: { padding: '3px 8px', fontSize: '10px', textDecoration: 'none' } }, '⬇'),
              asyncBtn('🔄', 'mk-secondary', async () => {
                const body = { type: img.id, language: lang }
                const customPrompt = promptOverrides[pKey]?.trim()
                if (customPrompt) body.prompt = customPrompt
                const d = await api.post(`/campaigns/${campaign.id}/generate-image`, body)
                if (d.success) { toast(`${img.label} regenerated!`); render() } else toast(d.error || 'Failed', true)
              }, { style: { padding: '3px 8px', fontSize: '10px' } }),
            ))
            tile.append(info)
          } else {
            const pKey = `${lang}_${img.id}`
            const empty = h('div', { style: { padding: '14px 10px', textAlign: 'center' } })
            empty.append(h('div', { style: { fontSize: '22px', marginBottom: '4px', opacity: 0.3 } }, '🖼'))
            empty.append(h('div', { style: { fontWeight: '600', fontSize: '12px' } }, img.label))
            empty.append(h('div', { style: { fontSize: '10px', color: 'var(--txt-secondary)', marginBottom: '6px' } }, img.dim))

            const pInput = h('textarea', { className: 'mk-input', rows: 2, value: promptOverrides[pKey] || '', placeholder: 'Custom prompt (optional)...', style: { fontSize: '11px', padding: '6px 8px', resize: 'vertical', marginBottom: '6px', textAlign: 'left' }, onInput: (e) => { promptOverrides[pKey] = e.target.value } })
            empty.append(pInput)

            empty.append(asyncBtn('✨ Generate', 'mk-primary', async () => {
              const body = { type: img.id, language: lang }
              const customPrompt = promptOverrides[pKey]?.trim()
              if (customPrompt) body.prompt = customPrompt
              const d = await api.post(`/campaigns/${campaign.id}/generate-image`, body)
              if (d.success) { toast(`${img.label} generated!`); render() } else toast(d.error || 'Failed', true)
            }, { style: { width: '100%', justifyContent: 'center', padding: '5px 10px', fontSize: '11px' } }))
            tile.append(empty)
          }
          grid.append(tile)
        })

        langCard.append(grid)

        langCard.append(h('div', { style: { marginTop: '10px', paddingTop: '10px', borderTop: '1px solid var(--border-light,#333)' } },
          asyncBtn(generatedCount > 0 ? '🔄 Regenerate all images' : '✨ Generate all images', generatedCount > 0 ? 'mk-secondary' : 'mk-primary', async (btn) => {
            let ok = 0
            for (let i = 0; i < IMG_TYPES.length; i++) {
              btn.innerHTML = `<span class="mk-spinner"></span> ${i + 1}/${IMG_TYPES.length}: ${IMG_TYPES[i].label}...`
              const body = { type: IMG_TYPES[i].id, language: lang }
              const pKey = `${lang}_${IMG_TYPES[i].id}`
              const customPrompt = promptOverrides[pKey]?.trim()
              if (customPrompt) body.prompt = customPrompt
              const d = await api.post(`/campaigns/${campaign.id}/generate-image`, body)
              if (d.success) ok++; else toast(`${IMG_TYPES[i].label}: ${d.error || 'Failed'}`, true)
            }
            if (ok > 0) { toast(`${langLabel(lang)}: ${ok}/${IMG_TYPES.length} images generated!`); render() }
          }),
        ))
        ct.append(langCard)
      })

      // ── Promotional Video ──
      ct.append(h('h3', { style: { marginTop: '28px', fontSize: '15px', fontWeight: '600' } }, 'Promotional Video (optional)'))
      ct.append(h('p', { style: { fontSize: '12px', color: 'var(--txt-secondary)', marginBottom: '12px' } },
        'Generate a short AI video clip for Performance Max, YouTube, or social media campaigns.'))

      langs.forEach(lang => {
        const existingVideo = videoFiles.find(f => f.path.includes(`/${lang}/videos/promo.mp4`))
        const vCard = h('div', { className: 'mk-card' })
        vCard.append(h('div', { className: 'mk-row', style: { justifyContent: 'space-between', marginBottom: '10px' } },
          h('h3', { style: { margin: 0, fontSize: '14px' } }, langLabel(lang) + ' Video'),
          existingVideo ? h('span', { className: 'mk-badge mk-badge-active' }, 'Generated') : h('span', { className: 'mk-badge mk-badge-draft' }, 'None'),
        ))

        if (existingVideo) {
          const rel = `${lang}/videos/promo.mp4`
          const url = fileUrl(campaign.id, rel)
          vCard.append(h('video', { src: url, controls: true, style: { width: '100%', maxHeight: '260px', borderRadius: '8px', background: '#000', marginBottom: '10px' } }))
          const actionRow = h('div', { className: 'mk-row', style: { gap: '8px', marginBottom: '10px', flexWrap: 'wrap' } },
            h('a', { href: url, download: `promo_${lang}.mp4`, className: 'mk-btn mk-secondary', style: { padding: '5px 12px', fontSize: '12px', textDecoration: 'none' } }, '⬇ Download'),
            h('button', { className: 'mk-btn mk-secondary', style: { padding: '5px 12px', fontSize: '12px' }, onClick: () => { navigator.clipboard.writeText(url); toast('URL copied!') } }, '📋 Copy URL'),
          )
          if (langs.length > 1) {
            const otherLangs = langs.filter(l => l !== lang)
            actionRow.append(asyncBtn(`📤 Use for all languages`, 'mk-secondary', async () => {
              const d = await api.post(`/campaigns/${campaign.id}/share-video`, { source_language: lang })
              if (d.success) { toast(`Video copied to ${otherLangs.map(langLabel).join(', ')}!`); render() } else toast(d.error || 'Failed', true)
            }, { style: { padding: '5px 12px', fontSize: '12px' } }))
          }
          vCard.append(actionRow)
          const regenDesc = h('textarea', { className: 'mk-input', rows: 2, placeholder: 'New description (leave empty for auto)...', style: { width: '100%', resize: 'vertical', marginBottom: '8px' } })
          const regenDur = h('select', { className: 'mk-input', style: { width: 'auto', marginBottom: '8px', marginRight: '8px' } })
          ;[4, 6, 8].forEach(d => regenDur.append(h('option', { value: d, selected: d === 6 }, `${d}s`)))
          vCard.append(regenDesc, h('div', { className: 'mk-row', style: { gap: '8px' } }, regenDur, asyncBtn('🔄 Regenerate', 'mk-secondary', async () => {
            const d = await api.post(`/campaigns/${campaign.id}/generate-video`, { description: regenDesc.value || undefined, language: lang, duration: parseInt(regenDur.value) })
            if (d.success) { toast(`${langLabel(lang)} video regenerated!`); render() } else toast(d.error || 'Failed', true)
          })))
        } else {
          const descInput = h('textarea', { className: 'mk-input', rows: 2, placeholder: 'Describe the video (leave empty for auto-generated prompt)...', style: { width: '100%', resize: 'vertical', marginBottom: '8px' } })
          const durSelect = h('select', { className: 'mk-input', style: { width: 'auto', marginBottom: '8px', marginRight: '8px' } })
          ;[4, 6, 8].forEach(d => durSelect.append(h('option', { value: d, selected: d === 6 }, `${d}s`)))
          vCard.append(descInput, h('div', { className: 'mk-row', style: { gap: '8px' } }, durSelect, asyncBtn('🎬 Generate Video', 'mk-primary', async () => {
            const d = await api.post(`/campaigns/${campaign.id}/generate-video`, { description: descInput.value || undefined, language: lang, duration: parseInt(durSelect.value) })
            if (d.success) { toast(`${langLabel(lang)} video generated!`); render() } else toast(d.error || 'Failed', true)
          })))
          vCard.append(h('div', { style: { fontSize: '11px', color: 'var(--txt-secondary)', marginTop: '6px' } }, 'Takes 1-2 min. Uses your configured video model.'))
        }
        ct.append(vCard)
      })
    }

    function formatSize(bytes) {
      if (bytes > 1048576) return (bytes / 1048576).toFixed(1) + ' MB'
      if (bytes > 1024) return (bytes / 1024).toFixed(0) + ' KB'
      return bytes + ' B'
    }

    // ── Compliance Tab ───────────────────────────────────────────────────

    async function renderComplianceTab(ct, campaign) {
      const langs = campaign.languages || ['en']
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

      const toolsCard = h('div', { className: 'mk-card' })
      toolsCard.append(h('h3', null, 'Tools'))

      const resultsContainer = h('div')

      toolsCard.append(h('div', { className: 'mk-row', style: { gap: '8px', flexWrap: 'wrap' } },
        asyncBtn(`🤖 AI Compliance Review (${langs.length} lang${langs.length > 1 ? 's' : ''})`, 'mk-secondary', async (btn) => {
          resultsContainer.innerHTML = ''
          for (let j = 0; j < langs.length; j++) {
            btn.innerHTML = `<span class="mk-spinner"></span> Reviewing ${j + 1}/${langs.length}: ${langLabel(langs[j])}...`
            const d = await api.post(`/campaigns/${campaign.id}/compliance/ai-review`, { language: langs[j] })
            if (d.success) {
              const section = h('div', { className: 'mk-card', style: { marginTop: '12px' } })
              section.append(h('h3', { style: { fontSize: '14px' } }, langLabel(langs[j]) + ' — AI Review'))
              const area = h('div', { className: 'mk-pre', style: { maxHeight: '400px' } })
              area.textContent = JSON.stringify(d.review, null, 2)
              section.append(area)
              resultsContainer.append(section)
            } else toast(`${langLabel(langs[j])}: ${d.error || 'Failed'}`, true)
          }
          toast('AI review complete for all languages')
        }),
        asyncBtn('🍪 Cookie Consent Snippets', 'mk-secondary', async (btn) => {
          resultsContainer.innerHTML = ''
          for (let j = 0; j < langs.length; j++) {
            btn.innerHTML = `<span class="mk-spinner"></span> ${j + 1}/${langs.length}: ${langLabel(langs[j])}...`
            const d = await api.get(`/compliance/cookie-snippet?language=${langs[j]}`)
            if (d.success) {
              const section = h('div', { className: 'mk-card', style: { marginTop: '12px' } })
              section.append(h('h3', { style: { fontSize: '14px' } }, langLabel(langs[j]) + ' — Cookie Consent'))
              const area = h('div', { className: 'mk-pre' })
              area.textContent = d.cookie_consent_html
              section.append(area)
              resultsContainer.append(section)
            }
          }
          toast('Snippets loaded')
        }),
        asyncBtn('🚀 Pre-Launch Check', 'mk-secondary', async () => {
          resultsContainer.innerHTML = ''
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
            resultsContainer.append(area)
            toast('Checklist complete')
          } else toast(d.error || 'Failed', true)
        }),
      ))

      ct.append(toolsCard)
      ct.append(resultsContainer)
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
      f.background_style = f.background_style || 'parallax'
      f.background_color = f.background_color || '#111111'
      f.background_secondary_color = f.background_secondary_color || '#1f2937'
      f.background_image_url = f.background_image_url || ''
      f.background_image_position = f.background_image_position || 'center center'
      f.background_image_size = f.background_image_size || 'cover'
      f.background_icon_url = f.background_icon_url || ''
      f.background_icon_position = f.background_icon_position || 'center center'
      f.background_icon_size_percent = f.background_icon_size_percent == null ? '20' : String(f.background_icon_size_percent)
      f.background_icon_opacity = f.background_icon_opacity == null ? '0.35' : String(f.background_icon_opacity)
      f.background_motion_intensity = f.background_motion_intensity || 'medium'
      f.hero_text_align = f.hero_text_align || 'center'
      f.background_overlay_opacity = f.background_overlay_opacity == null ? '0.48' : String(f.background_overlay_opacity)
      f.style_prompt = (f.style_prompt || getStylePreset(f.background_style).style_prompt || '').trim()
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
      sec2.append(h('div', { style: { fontSize: '12px', color: 'var(--txt-secondary)', marginBottom: '10px' } }, 'Quick style presets apply recommended background values and default AI style prompts.'))
      sec2.append(h('div', { className: 'mk-preset-grid', style: { marginBottom: '12px' } },
        ...Object.values(STYLE_PRESETS).map(preset => {
          const selected = f.background_style === preset.id
          return h('div', {
            className: `mk-preset${selected ? ' on' : ''}`,
            onClick: async () => {
              const payload = getPresetPayload(preset.id)
              const d = await api.put(`/campaigns/${campaign.id}`, payload)
              if (d.success) {
                toast(`Preset "${preset.label}" applied`)
                render()
              } else {
                toast(d.error || 'Failed to apply preset', true)
              }
            },
          },
          h('div', { className: 'mk-preset-title' }, `${preset.emoji} ${preset.label}`),
          h('div', { className: 'mk-preset-sub' }, preset.summary))
        }),
      ))
      sec2.append(h('div', { className: 'mk-row', style: { gap: '16px', alignItems: 'flex-end' } },
        h('div', { className: 'mk-grow' }, field('Accent Color', 'text', f.accent_color || '#00b79d', v => f.accent_color = v, '#00b79d')),
        colorPreview(f.accent_color || '#00b79d'),
      ))
      sec2.append(field('Brand Logo URL', 'text', f.brand_logo_url || '', v => f.brand_logo_url = v, 'https://.../logo.png'))
      sec2.append(field('Color Scheme Description', 'text', f.color_scheme || '', v => f.color_scheme = v, 'e.g. dark backgrounds with vibrant accent'))
      sec2.append(h('div', { className: 'mk-row', style: { gap: '16px', alignItems: 'flex-end' } },
        h('div', { className: 'mk-grow' },
          h('div', { className: 'mk-field' },
            h('label', { className: 'mk-label' }, 'Background Style'),
            selectInput(BG_STYLES, f.background_style, v => f.background_style = v),
          ),
        ),
        h('div', { style: { width: '180px', flexShrink: 0 } },
          h('div', { className: 'mk-field' },
            h('label', { className: 'mk-label' }, 'Hero Text Align'),
            selectInput(TEXT_ALIGNS, f.hero_text_align, v => f.hero_text_align = v),
          ),
        ),
      ))
      sec2.append(h('div', { className: 'mk-row', style: { gap: '16px', alignItems: 'flex-end' } },
        h('div', { className: 'mk-grow' }, field('Background Color', 'text', f.background_color, v => f.background_color = v, '#111111')),
        h('div', { className: 'mk-grow' }, field('Secondary Background Color', 'text', f.background_secondary_color, v => f.background_secondary_color = v, '#1f2937')),
      ))
      sec2.append(field('Background Image URL (optional)', 'text', f.background_image_url, v => f.background_image_url = v, 'https://.../hero-bg.jpg'))
      sec2.append(field('Background Icon URL (optional)', 'text', f.background_icon_url, v => f.background_icon_url = v, 'https://.../icon.svg'))
      sec2.append(h('div', { className: 'mk-row', style: { gap: '16px', alignItems: 'flex-end' } },
        h('div', { className: 'mk-grow' },
          h('div', { className: 'mk-field' },
            h('label', { className: 'mk-label' }, 'Background Image Position'),
            selectInput(BG_POSITIONS, f.background_image_position, v => f.background_image_position = v),
          ),
        ),
        h('div', { style: { width: '180px', flexShrink: 0 } },
          h('div', { className: 'mk-field' },
            h('label', { className: 'mk-label' }, 'Background Image Size'),
            selectInput(BG_IMAGE_SIZES, f.background_image_size, v => f.background_image_size = v),
          ),
        ),
      ))
      sec2.append(h('div', { className: 'mk-row', style: { gap: '16px', alignItems: 'flex-end' } },
        h('div', { className: 'mk-grow' },
          h('div', { className: 'mk-field' },
            h('label', { className: 'mk-label' }, 'Icon Position'),
            selectInput(BG_POSITIONS, f.background_icon_position, v => f.background_icon_position = v),
          ),
        ),
        h('div', { style: { width: '180px', flexShrink: 0 } },
          field('Icon Size %', 'text', f.background_icon_size_percent, v => f.background_icon_size_percent = v, '20'),
        ),
        h('div', { style: { width: '180px', flexShrink: 0 } },
          field('Icon Opacity', 'text', f.background_icon_opacity, v => f.background_icon_opacity = v, '0.35'),
        ),
      ))
      sec2.append(h('div', { className: 'mk-row', style: { gap: '16px', alignItems: 'flex-end' } },
        h('div', { className: 'mk-grow' },
          h('div', { className: 'mk-field' },
            h('label', { className: 'mk-label' }, 'Motion Intensity'),
            selectInput(BG_MOTION_LEVELS, f.background_motion_intensity, v => f.background_motion_intensity = v),
          ),
        ),
        h('div', { style: { width: '180px', flexShrink: 0 } },
          field('Overlay Opacity (0-1)', 'text', f.background_overlay_opacity, v => f.background_overlay_opacity = v, '0.48'),
        ),
      ))
      sec2.append(field('Style Prompt (auto default per preset)', 'textarea', f.style_prompt, v => f.style_prompt = v, 'Optional extra style direction for AI'))
      sec2.append(h('div', { style: { fontSize: '11px', color: 'var(--txt-secondary)', marginTop: '-12px', marginBottom: '16px' } }, 'Used for landing pages and AI image generation.'))
      ct.append(sec2)

      // --- Image Style ---
      const secStyle = h('div', { className: 'mk-card' })
      secStyle.append(h('h3', null, 'Image Style'))
      secStyle.append(h('div', { style: { fontSize: '12px', color: 'var(--txt-secondary)', marginBottom: '12px' } }, 'Controls the visual direction of all AI-generated images. Pick a preset and optionally add custom notes.'))

      const styleChips = h('div', { className: 'mk-chips', style: { marginBottom: '14px' } })
      IMG_STYLES.forEach(s => {
        const isOn = (f.image_style || 'tech-forward') === s.id
        const chip = h('div', { className: `mk-chip${isOn ? ' on' : ''}`, title: s.desc })
        chip.append(s.label)
        chip.addEventListener('click', () => {
          f.image_style = s.id
          styleChips.querySelectorAll('.mk-chip').forEach(c => c.className = 'mk-chip')
          chip.className = 'mk-chip on'
          stylePreview.textContent = s.desc
        })
        styleChips.append(chip)
      })
      secStyle.append(styleChips)

      const activeStyle = IMG_STYLES.find(s => s.id === (f.image_style || 'tech-forward'))
      const stylePreview = h('div', { style: { fontSize: '12px', color: 'var(--brand,#00b79d)', marginBottom: '12px', fontStyle: 'italic' } }, activeStyle?.desc || '')
      secStyle.append(stylePreview)

      secStyle.append(field('Custom Style Notes (optional)', 'textarea', f.image_style_notes || '', v => f.image_style_notes = v, 'e.g. "Include subtle circuit board patterns" or "Use warm earth tones instead of cool blues" or "Show people using laptops in a cozy office"'))
      ct.append(secStyle)

      // --- Call to Action ---
      const sec3 = h('div', { className: 'mk-card' })
      sec3.append(h('h3', null, 'Call to Action'))
      sec3.append(h('div', { style: { fontSize: '12px', color: 'var(--txt-secondary)', marginBottom: '12px' } }, 'Primary button is the main action. Secondary is optional (shown as a text link below).'))
      sec3.append(field('Default Google Ads Final URL', 'text', f.final_url || f.cta_url || '', v => f.final_url = v, 'https://your-site.com/landing-page'))

      const ctaTypes = [
        { code: 'register', label: 'Register / Sign Up' },
        { code: 'email', label: 'Email / Contact' },
        { code: 'download', label: 'Download' },
        { code: 'custom', label: 'Custom URL' },
      ]
      for (let i = 0; i < 2; i++) {
        const lbl = i === 0 ? 'Primary' : 'Secondary (optional)'
        sec3.append(h('div', { style: { padding: '12px', background: 'var(--bg-input,var(--bg-chip,#151520))', borderRadius: '8px', marginBottom: '10px' } },
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
        const d = await api.del(`/campaigns/${campaign.id}`)
        if (d.success) { toast('Campaign deleted'); nav('dashboard') } else toast(d.error || 'Delete failed', true)
      }))
      actions.append(asyncBtn('Save All Changes', 'mk-primary', async () => {
        const ctas = f.ctas.filter(c => c.label && c.url)
        const d = await api.put(`/campaigns/${campaign.id}`, {
          title: f.title, topic: f.topic, target_audience: f.target_audience,
          unique_selling_points: f.unique_selling_points || [],
          cta_url: (ctas[0] || {}).url || f.cta_url,
          final_url: f.final_url || '',
          status: f.status, languages: f.languages, platforms: f.platforms,
          ctas, accent_color: f.accent_color, modal_content: f.modal_content || '',
          brand_logo_url: f.brand_logo_url || '', color_scheme: f.color_scheme || '',
          image_style: f.image_style || 'tech-forward', image_style_notes: f.image_style_notes || '',
          background_style: f.background_style || 'parallax',
          background_color: f.background_color || '#111111',
          background_secondary_color: f.background_secondary_color || '#1f2937',
          background_image_url: f.background_image_url || '',
          background_image_position: f.background_image_position || 'center center',
          background_image_size: f.background_image_size || 'cover',
          background_icon_url: f.background_icon_url || '',
          background_icon_position: f.background_icon_position || 'center center',
          background_icon_size_percent: f.background_icon_size_percent || '20',
          background_icon_opacity: f.background_icon_opacity || '0.35',
          background_motion_intensity: f.background_motion_intensity || 'medium',
          hero_text_align: f.hero_text_align || 'center',
          background_overlay_opacity: f.background_overlay_opacity || '0.48',
          style_prompt: f.style_prompt || getStylePreset(f.background_style || 'parallax').style_prompt,
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
      root.append(h('button', { className: 'mk-back', onClick: () => nav('dashboard') }, '← Back'))
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
      sec1.append(h('div', { className: 'mk-row', style: { gap: '16px', alignItems: 'flex-end' } },
        h('div', { className: 'mk-grow' },
          h('div', { className: 'mk-field' },
            h('label', { className: 'mk-label' }, 'Default Background Style'),
            selectInput(BG_STYLES, cfg.default_background_style || 'parallax', v => cfg.default_background_style = v),
          ),
        ),
        h('div', { style: { width: '180px', flexShrink: 0 } },
          h('div', { className: 'mk-field' },
            h('label', { className: 'mk-label' }, 'Default Hero Text Align'),
            selectInput(TEXT_ALIGNS, cfg.default_hero_text_align || 'center', v => cfg.default_hero_text_align = v),
          ),
        ),
      ))
      sec1.append(h('div', { className: 'mk-row', style: { gap: '16px', alignItems: 'flex-end' } },
        h('div', { className: 'mk-grow' }, field('Default Background Color', 'text', cfg.default_background_color || '#111111', v => cfg.default_background_color = v, '#111111')),
        h('div', { className: 'mk-grow' }, field('Default Secondary Color', 'text', cfg.default_background_secondary_color || '#1f2937', v => cfg.default_background_secondary_color = v, '#1f2937')),
      ))
      sec1.append(field('Default Background Image URL', 'text', cfg.default_background_image_url || '', v => cfg.default_background_image_url = v, 'https://.../hero-bg.jpg'))
      sec1.append(field('Default Background Icon URL', 'text', cfg.default_background_icon_url || '', v => cfg.default_background_icon_url = v, 'https://.../icon.svg'))
      sec1.append(h('div', { className: 'mk-row', style: { gap: '16px', alignItems: 'flex-end' } },
        h('div', { className: 'mk-grow' },
          h('div', { className: 'mk-field' },
            h('label', { className: 'mk-label' }, 'Default Background Image Position'),
            selectInput(BG_POSITIONS, cfg.default_background_image_position || 'center center', v => cfg.default_background_image_position = v),
          ),
        ),
        h('div', { style: { width: '180px', flexShrink: 0 } },
          h('div', { className: 'mk-field' },
            h('label', { className: 'mk-label' }, 'Default Image Size'),
            selectInput(BG_IMAGE_SIZES, cfg.default_background_image_size || 'cover', v => cfg.default_background_image_size = v),
          ),
        ),
      ))
      sec1.append(h('div', { className: 'mk-row', style: { gap: '16px', alignItems: 'flex-end' } },
        h('div', { className: 'mk-grow' },
          h('div', { className: 'mk-field' },
            h('label', { className: 'mk-label' }, 'Default Icon Position'),
            selectInput(BG_POSITIONS, cfg.default_background_icon_position || 'center center', v => cfg.default_background_icon_position = v),
          ),
        ),
        h('div', { style: { width: '180px', flexShrink: 0 } },
          field('Default Icon Size %', 'text', cfg.default_background_icon_size_percent || '20', v => cfg.default_background_icon_size_percent = v, '20'),
        ),
        h('div', { style: { width: '180px', flexShrink: 0 } },
          field('Default Icon Opacity', 'text', cfg.default_background_icon_opacity || '0.35', v => cfg.default_background_icon_opacity = v, '0.35'),
        ),
      ))
      sec1.append(h('div', { className: 'mk-row', style: { gap: '16px', alignItems: 'flex-end' } },
        h('div', { className: 'mk-grow' },
          h('div', { className: 'mk-field' },
            h('label', { className: 'mk-label' }, 'Default Motion Intensity'),
            selectInput(BG_MOTION_LEVELS, cfg.default_background_motion_intensity || 'medium', v => cfg.default_background_motion_intensity = v),
          ),
        ),
        h('div', { style: { width: '180px', flexShrink: 0 } },
          field('Default Overlay Opacity', 'text', cfg.default_background_overlay_opacity || '0.48', v => cfg.default_background_overlay_opacity = v, '0.48'),
        ),
      ))
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

      const sec4 = h('div', { className: 'mk-card' })
      sec4.append(h('h3', null, 'Landing Page Prompt'))
      sec4.append(h('div', { style: { fontSize: '12px', color: 'var(--txt-secondary)', marginBottom: '8px' } },
        'Customize the AI prompt used to generate landing pages. Available placeholders: ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{language}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{brand_name}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{accent_color}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{color_scheme}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{background_style}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{background_color}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{background_secondary_color}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{background_image_url}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{background_image_position}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{background_image_size}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{background_icon_url}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{background_icon_position}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{background_icon_size_percent}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{background_icon_opacity}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{background_motion_intensity}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{hero_text_align}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{background_overlay_opacity}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{privacy_url}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{imprint_url}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{logo_section}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{cta_buttons}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{usp_list}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{modal_section}}'),
      ))
      const isCustom = cfg.landing_page_prompt && cfg.landing_page_prompt.trim().length > 0
      const promptStatus = h('span', {
        className: `mk-badge ${isCustom ? 'mk-badge-active' : 'mk-badge-draft'}`,
        style: { marginLeft: '8px', fontSize: '11px' },
      }, isCustom ? 'Custom' : 'Default')
      sec4.querySelector('h3').append(promptStatus)

      const promptArea = h('textarea', {
        className: 'mk-input mk-textarea',
        value: cfg.landing_page_prompt || '',
        placeholder: '(using built-in default — paste your custom prompt here to override)',
        style: { width: '100%', minHeight: '220px', resize: 'vertical', fontFamily: 'monospace', fontSize: '12px', lineHeight: '1.5' },
        onInput: (e) => { cfg.landing_page_prompt = e.target.value },
      })
      sec4.append(promptArea)

      const promptActions = h('div', { className: 'mk-row', style: { gap: '8px', marginTop: '8px' } })
      promptActions.append(
        asyncBtn('Load current default', 'mk-secondary', async () => {
          const r = await api.get('/config/default-prompt')
          if (r.success) {
            promptArea.value = r.prompt
            cfg.landing_page_prompt = r.prompt
            toast('Default prompt loaded into editor — edit and save when ready.')
          } else toast(r.error || 'Failed', true)
        }, { style: { fontSize: '12px', padding: '6px 12px' } }),
        h('button', {
          className: 'mk-btn mk-secondary',
          style: { fontSize: '12px', padding: '6px 12px' },
          onClick: () => {
            promptArea.value = ''
            cfg.landing_page_prompt = ''
            toast('Prompt cleared — will use built-in default after saving.')
          },
        }, 'Reset to default'),
      )
      sec4.append(promptActions)
      root.append(sec4)

      // ── Media Prompts (Image + Video) ──
      const sec5 = h('div', { className: 'mk-card' })
      sec5.append(h('h3', null, 'Media Prompts'))

      // -- Image Prompt --
      const imgHeader = h('div', { style: { marginBottom: '12px' } })
      const imgLabel = h('div', { style: { fontSize: '14px', fontWeight: '600', marginBottom: '4px' } }, 'Image Prompt')
      const isImgCustom = cfg.image_prompt && cfg.image_prompt.trim().length > 0
      imgLabel.append(h('span', {
        className: `mk-badge ${isImgCustom ? 'mk-badge-active' : 'mk-badge-draft'}`,
        style: { marginLeft: '8px', fontSize: '11px' },
      }, isImgCustom ? 'Custom' : 'Default'))
      imgHeader.append(imgLabel)
      imgHeader.append(h('div', { style: { fontSize: '12px', color: 'var(--txt-secondary)', marginBottom: '4px' } },
        'Customize the AI prompt used to generate marketing images. Available placeholders: ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{brand_name}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{title}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{accent_color}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{color_scheme}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{style_description}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{type_hint}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{dimensions}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{style_notes}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{image_type}}'),
      ))
      sec5.append(imgHeader)

      const imgPromptArea = h('textarea', {
        className: 'mk-input mk-textarea',
        value: cfg.image_prompt || '',
        placeholder: '(using built-in default — paste your custom prompt here to override)',
        style: { width: '100%', minHeight: '140px', resize: 'vertical', fontFamily: 'monospace', fontSize: '12px', lineHeight: '1.5' },
        onInput: (e) => { cfg.image_prompt = e.target.value },
      })
      sec5.append(imgPromptArea)

      const imgPromptActions = h('div', { className: 'mk-row', style: { gap: '8px', marginTop: '8px' } })
      imgPromptActions.append(
        asyncBtn('Load current default', 'mk-secondary', async () => {
          const r = await api.get('/config/default-media-prompts')
          if (r.success) {
            imgPromptArea.value = r.image_prompt
            cfg.image_prompt = r.image_prompt
            toast('Default image prompt loaded into editor — edit and save when ready.')
          } else toast(r.error || 'Failed', true)
        }, { style: { fontSize: '12px', padding: '6px 12px' } }),
        h('button', {
          className: 'mk-btn mk-secondary',
          style: { fontSize: '12px', padding: '6px 12px' },
          onClick: () => {
            imgPromptArea.value = ''
            cfg.image_prompt = ''
            toast('Image prompt cleared — will use built-in default after saving.')
          },
        }, 'Reset to default'),
      )
      sec5.append(imgPromptActions)

      // -- Video Prompt --
      sec5.append(h('hr', { className: 'mk-sep' }))
      const vidHeader = h('div', { style: { marginBottom: '12px' } })
      const vidLabel = h('div', { style: { fontSize: '14px', fontWeight: '600', marginBottom: '4px' } }, 'Video Prompt')
      const isVidCustom = cfg.video_prompt && cfg.video_prompt.trim().length > 0
      vidLabel.append(h('span', {
        className: `mk-badge ${isVidCustom ? 'mk-badge-active' : 'mk-badge-draft'}`,
        style: { marginLeft: '8px', fontSize: '11px' },
      }, isVidCustom ? 'Custom' : 'Default'))
      vidHeader.append(vidLabel)
      vidHeader.append(h('div', { style: { fontSize: '12px', color: 'var(--txt-secondary)', marginBottom: '4px' } },
        'Customize the AI prompt used to generate promotional videos (auto-generated mode only; user-provided descriptions bypass this). Available placeholders: ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{brand_name}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{title}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{topic}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{usps}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{accent_color}}'),
        ', ',
        h('code', { style: { fontSize: '11px', background: 'rgba(255,255,255,0.08)', padding: '1px 5px', borderRadius: '3px' } }, '{{color_scheme}}'),
      ))
      sec5.append(vidHeader)

      const vidPromptArea = h('textarea', {
        className: 'mk-input mk-textarea',
        value: cfg.video_prompt || '',
        placeholder: '(using built-in default — paste your custom prompt here to override)',
        style: { width: '100%', minHeight: '140px', resize: 'vertical', fontFamily: 'monospace', fontSize: '12px', lineHeight: '1.5' },
        onInput: (e) => { cfg.video_prompt = e.target.value },
      })
      sec5.append(vidPromptArea)

      const vidPromptActions = h('div', { className: 'mk-row', style: { gap: '8px', marginTop: '8px' } })
      vidPromptActions.append(
        asyncBtn('Load current default', 'mk-secondary', async () => {
          const r = await api.get('/config/default-media-prompts')
          if (r.success) {
            vidPromptArea.value = r.video_prompt
            cfg.video_prompt = r.video_prompt
            toast('Default video prompt loaded into editor — edit and save when ready.')
          } else toast(r.error || 'Failed', true)
        }, { style: { fontSize: '12px', padding: '6px 12px' } }),
        h('button', {
          className: 'mk-btn mk-secondary',
          style: { fontSize: '12px', padding: '6px 12px' },
          onClick: () => {
            vidPromptArea.value = ''
            cfg.video_prompt = ''
            toast('Video prompt cleared — will use built-in default after saving.')
          },
        }, 'Reset to default'),
      )
      sec5.append(vidPromptActions)

      root.append(sec5)

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
