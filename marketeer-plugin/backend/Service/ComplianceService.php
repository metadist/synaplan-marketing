<?php

declare(strict_types=1);

namespace Plugin\Marketeer\Service;

/**
 * Provides GDPR/privacy compliance utilities for marketing campaigns.
 *
 * Generates cookie consent snippets, privacy policy templates,
 * tracking configuration, and compliance checklists.
 */
final readonly class ComplianceService
{
    /**
     * Generate a static compliance checklist based on campaign configuration.
     * This is a deterministic check (no AI) for quick validation.
     *
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $assets
     * @return array<string, mixed>
     */
    /**
     * @param array<string, mixed> $campaign
     * @param array<string, mixed> $assets
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function quickComplianceCheck(array $campaign, array $assets, array $config = []): array
    {
        $checks = [];

        $checks[] = $this->checkPrivacyPolicy($campaign, $assets, $config);
        $checks[] = $this->checkImprint($campaign, $assets, $config);
        $checks[] = $this->checkCookieConsent($campaign, $assets);
        $checks[] = $this->checkTrackingConsent($campaign);
        $checks[] = $this->checkDataCollection($campaign);
        $checks[] = $this->checkSslRequired();
        $checks[] = $this->checkAdsPolicies($campaign);

        $hasBlocking = false;
        foreach ($checks as $check) {
            if ($check['status'] === 'fail') {
                $hasBlocking = true;
            }
        }

        return [
            'compliant' => !$hasBlocking,
            'checks' => $checks,
            'summary' => $hasBlocking
                ? 'Blocking compliance issues found. Resolve before launching.'
                : 'No blocking issues. Review warnings before launching.',
        ];
    }

    /**
     * Generate a cookie consent HTML snippet for embedding in landing pages.
     *
     * @param array<string, mixed> $config
     */
    public function generateCookieConsentSnippet(array $config, string $language): string
    {
        $texts = $this->getCookieConsentTexts($language);
        $brandName = $config['brand_name'] ?? 'Synaplan';
        $privacyUrl = $config['privacy_policy_url'] ?? '#privacy';

        return <<<HTML
<!-- Cookie Consent Banner — GDPR compliant -->
<div id="cookie-consent" style="display:none;position:fixed;bottom:0;left:0;right:0;background:#1a1a2e;color:#fff;padding:16px 24px;z-index:9999;font-family:system-ui,sans-serif;font-size:14px;box-shadow:0 -2px 10px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div style="flex:1;min-width:200px;">
    <strong>{$texts['title']}</strong>
    <p style="margin:4px 0 0;opacity:0.85;font-size:13px;">
      {$texts['message']} <a href="{$privacyUrl}" style="color:#00b79d;text-decoration:underline;">{$texts['privacy_link']}</a>
    </p>
  </div>
  <div style="display:flex;gap:8px;flex-shrink:0;">
    <button onclick="acceptCookies('essential')" style="background:transparent;border:1px solid #fff;color:#fff;padding:8px 16px;border-radius:6px;cursor:pointer;font-size:13px;">{$texts['essential_only']}</button>
    <button onclick="acceptCookies('all')" style="background:#00b79d;border:none;color:#fff;padding:8px 16px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;">{$texts['accept_all']}</button>
  </div>
</div>
<script>
(function(){
  if(!localStorage.getItem('cookie_consent')){
    document.getElementById('cookie-consent').style.display='flex';
  }
  window.acceptCookies=function(level){
    localStorage.setItem('cookie_consent',level);
    localStorage.setItem('cookie_consent_date',new Date().toISOString());
    document.getElementById('cookie-consent').style.display='none';
    if(level==='all'){
      // Fire deferred tracking scripts
      document.querySelectorAll('script[data-consent="analytics"]').forEach(function(s){
        var n=document.createElement('script');n.src=s.dataset.src;document.head.appendChild(n);
      });
    }
  };
})();
</script>
HTML;
    }

    /**
     * Generate tracking code snippets with consent gating.
     *
     * @param array<string, mixed> $tracking
     */
    public function generateTrackingSnippets(array $tracking): string
    {
        $snippets = '';

        if (!empty($tracking['gtm_id'])) {
            $gtmId = htmlspecialchars($tracking['gtm_id'], ENT_QUOTES);
            $snippets .= <<<HTML

<!-- Google Tag Manager (deferred until consent) -->
<script data-consent="analytics" data-src="https://www.googletagmanager.com/gtm.js?id={$gtmId}"></script>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={$gtmId}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
HTML;
        }

        if (!empty($tracking['gads_conversion_id'])) {
            $convId = htmlspecialchars($tracking['gads_conversion_id'], ENT_QUOTES);
            $snippets .= <<<HTML

<!-- Google Ads Conversion Tracking (deferred until consent) -->
<script data-consent="analytics" data-src="https://www.googletagmanager.com/gtag/js?id={$convId}"></script>
HTML;
        }

        return $snippets;
    }

    /**
     * @return array{category: string, item: string, status: string, details: string}
     */
    private function checkPrivacyPolicy(array $campaign, array $assets, array $config = []): array
    {
        $urlConfigured = !empty($config['privacy_policy_url']) && $config['privacy_policy_url'] !== '#';
        $hasLinkInPage = false;
        foreach ($assets['pages'] ?? [] as $page) {
            if (str_contains($page['html'] ?? '', 'privacy') || str_contains($page['html'] ?? '', 'datenschutz')) {
                $hasLinkInPage = true;
            }
        }

        if (!$urlConfigured) {
            return [
                'category' => 'GDPR',
                'item' => 'Privacy policy URL',
                'status' => 'fail',
                'details' => 'No privacy policy URL configured. Set it in Plugin Settings → Legal Pages. Required by GDPR Art. 13.',
            ];
        }

        return [
            'category' => 'GDPR',
            'item' => 'Privacy policy link on landing page',
            'status' => $hasLinkInPage ? 'pass' : 'warning',
            'details' => $hasLinkInPage
                ? 'Privacy policy URL configured and referenced in landing page'
                : 'URL is configured but regenerate the landing page to include it',
        ];
    }

    /**
     * @return array{category: string, item: string, status: string, details: string}
     */
    private function checkImprint(array $campaign, array $assets, array $config = []): array
    {
        $urlConfigured = !empty($config['imprint_url']) && $config['imprint_url'] !== '#';
        $hasLinkInPage = false;
        foreach ($assets['pages'] ?? [] as $page) {
            if (str_contains($page['html'] ?? '', 'imprint') || str_contains($page['html'] ?? '', 'impressum')) {
                $hasLinkInPage = true;
            }
        }

        if (!$urlConfigured) {
            return [
                'category' => 'Legal',
                'item' => 'Imprint / Legal notice URL',
                'status' => 'fail',
                'details' => 'No imprint URL configured. Set it in Plugin Settings → Legal Pages. Required in Germany/Austria by TMG §5.',
            ];
        }

        return [
            'category' => 'Legal',
            'item' => 'Imprint / Legal notice',
            'status' => $hasLinkInPage ? 'pass' : 'warning',
            'details' => $hasLinkInPage
                ? 'Imprint URL configured and referenced in landing page'
                : 'URL is configured but regenerate the landing page to include it',
        ];
    }

    /**
     * @return array{category: string, item: string, status: string, details: string}
     */
    private function checkCookieConsent(array $campaign, array $assets): array
    {
        $hasCookieBanner = false;
        foreach ($assets['pages'] ?? [] as $page) {
            if (str_contains($page['html'] ?? '', 'cookie-consent') || str_contains($page['html'] ?? '', 'cookie_consent')) {
                $hasCookieBanner = true;
            }
        }

        return [
            'category' => 'ePrivacy',
            'item' => 'Cookie consent mechanism',
            'status' => $hasCookieBanner ? 'pass' : 'warning',
            'details' => $hasCookieBanner
                ? 'Cookie consent mechanism detected'
                : 'Add cookie consent banner if using analytics/tracking cookies (ePrivacy Directive)',
        ];
    }

    /**
     * @return array{category: string, item: string, status: string, details: string}
     */
    private function checkTrackingConsent(array $campaign): array
    {
        $hasTracking = !empty($campaign['tracking']['gtm_id'])
            || !empty($campaign['tracking']['gads_conversion_id'])
            || !empty($campaign['tracking']['meta_pixel_id']);

        if (!$hasTracking) {
            return [
                'category' => 'ePrivacy',
                'item' => 'Tracking consent',
                'status' => 'pass',
                'details' => 'No tracking configured — no consent needed for tracking',
            ];
        }

        return [
            'category' => 'ePrivacy',
            'item' => 'Tracking consent',
            'status' => 'warning',
            'details' => 'Tracking is configured. Ensure scripts only fire after user consent (GDPR Art. 6(1)(a)). '
                . 'Use the generated cookie consent snippet to gate tracking.',
        ];
    }

    /**
     * @return array{category: string, item: string, status: string, details: string}
     */
    private function checkDataCollection(array $campaign): array
    {
        $hasCtas = !empty($campaign['ctas']);
        $hasEmailCta = false;
        $hasRegisterCta = false;

        foreach ($campaign['ctas'] ?? [] as $cta) {
            if (($cta['type'] ?? '') === 'email') {
                $hasEmailCta = true;
            }
            if (($cta['type'] ?? '') === 'register') {
                $hasRegisterCta = true;
            }
        }

        if (!$hasCtas) {
            return [
                'category' => 'GDPR',
                'item' => 'Data collection disclosure',
                'status' => 'pass',
                'details' => 'No data collection CTAs configured',
            ];
        }

        $details = 'CTAs that collect personal data detected. Ensure: ';
        if ($hasRegisterCta) {
            $details .= 'Registration form has privacy policy checkbox. ';
        }
        if ($hasEmailCta) {
            $details .= 'Email collection has opt-in consent. ';
        }
        $details .= 'All forms must disclose data processing purpose (GDPR Art. 13).';

        return [
            'category' => 'GDPR',
            'item' => 'Data collection disclosure',
            'status' => 'warning',
            'details' => $details,
        ];
    }

    /**
     * @return array{category: string, item: string, status: string, details: string}
     */
    private function checkSslRequired(): array
    {
        return [
            'category' => 'Technical',
            'item' => 'HTTPS required',
            'status' => 'manual_check',
            'details' => 'Ensure landing pages are served over HTTPS. Required for GDPR compliance and Google Ads approval.',
        ];
    }

    /**
     * @return array{category: string, item: string, status: string, details: string}
     */
    private function checkAdsPolicies(array $campaign): array
    {
        $platforms = $campaign['platforms'] ?? [];

        $notes = [];
        if (in_array('google', $platforms, true)) {
            $notes[] = 'Google Ads: Verify landing page meets quality guidelines (clear business info, relevant content, easy navigation)';
        }
        if (in_array('linkedin', $platforms, true)) {
            $notes[] = 'LinkedIn: Ensure professional tone and accurate company information';
        }
        if (in_array('instagram', $platforms, true)) {
            $notes[] = 'Instagram/Meta: Comply with advertising policies (no misleading claims, proper disclaimers)';
        }

        return [
            'category' => 'Advertising',
            'item' => 'Platform-specific ad policies',
            'status' => empty($notes) ? 'pass' : 'manual_check',
            'details' => empty($notes)
                ? 'No advertising platforms configured'
                : implode('. ', $notes),
        ];
    }

    /**
     * @return array{title: string, message: string, privacy_link: string, essential_only: string, accept_all: string}
     */
    private function getCookieConsentTexts(string $language): array
    {
        return match ($language) {
            'de' => [
                'title' => 'Cookie-Einstellungen',
                'message' => 'Wir verwenden Cookies, um Ihre Erfahrung zu verbessern und unsere Dienste zu analysieren.',
                'privacy_link' => 'Datenschutzerklärung',
                'essential_only' => 'Nur essenzielle',
                'accept_all' => 'Alle akzeptieren',
            ],
            'fr' => [
                'title' => 'Paramètres des cookies',
                'message' => 'Nous utilisons des cookies pour améliorer votre expérience et analyser nos services.',
                'privacy_link' => 'Politique de confidentialité',
                'essential_only' => 'Essentiels uniquement',
                'accept_all' => 'Tout accepter',
            ],
            'es' => [
                'title' => 'Configuración de cookies',
                'message' => 'Utilizamos cookies para mejorar su experiencia y analizar nuestros servicios.',
                'privacy_link' => 'Política de privacidad',
                'essential_only' => 'Solo esenciales',
                'accept_all' => 'Aceptar todo',
            ],
            default => [
                'title' => 'Cookie Settings',
                'message' => 'We use cookies to improve your experience and analyze our services.',
                'privacy_link' => 'Privacy Policy',
                'essential_only' => 'Essential only',
                'accept_all' => 'Accept all',
            ],
        };
    }
}
