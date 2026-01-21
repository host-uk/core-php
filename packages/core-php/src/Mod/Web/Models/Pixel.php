<?php

namespace Core\Mod\Web\Models;

use Core\Mod\Tenant\Concerns\BelongsToWorkspace;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pixel extends Model
{
    use BelongsToWorkspace;
    use SoftDeletes;

    protected $table = 'biolink_pixels';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'type',
        'name',
        'pixel_id',
    ];

    /**
     * Available pixel types.
     */
    public const TYPES = [
        'facebook' => 'Facebook Pixel',
        'google_analytics' => 'Google Analytics',
        'google_tag_manager' => 'Google Tag Manager',
        'google_ads' => 'Google Ads',
        'tiktok' => 'TikTok Pixel',
        'twitter' => 'Twitter Pixel',
        'pinterest' => 'Pinterest Tag',
        'linkedin' => 'LinkedIn Insight',
        'snapchat' => 'Snapchat Pixel',
        'quora' => 'Quora Pixel',
        'bing' => 'Microsoft/Bing UET',
    ];

    /**
     * Get the workspace that owns this pixel.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the biolinks using this pixel.
     */
    public function biolinks(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'biolink_pixel', 'pixel_id', 'biolink_id');
    }

    /**
     * Get the user that owns this pixel.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the pixel type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by workspace.
     */
    public function scopeForWorkspace($query, Workspace $workspace)
    {
        return $query->where('workspace_id', $workspace->id);
    }

    /**
     * Generate the pixel script for this type.
     */
    public function getScript(): string
    {
        return match ($this->type) {
            'facebook' => $this->getFacebookScript(),
            'google_analytics' => $this->getGoogleAnalyticsScript(),
            'google_tag_manager' => $this->getGTMScript(),
            'google_ads' => $this->getGoogleAdsScript(),
            'tiktok' => $this->getTikTokScript(),
            'twitter' => $this->getTwitterScript(),
            'pinterest' => $this->getPinterestScript(),
            'linkedin' => $this->getLinkedInScript(),
            'snapchat' => $this->getSnapchatScript(),
            'bing' => $this->getBingScript(),
            default => '',
        };
    }

    /**
     * Get the noscript/body portion for GTM (goes after opening body tag).
     */
    public function getBodyScript(): string
    {
        if ($this->type !== 'google_tag_manager') {
            return '';
        }

        $id = e($this->pixel_id);

        return <<<HTML
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={$id}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
HTML;
    }

    /**
     * Facebook Pixel script.
     */
    protected function getFacebookScript(): string
    {
        $id = e($this->pixel_id);

        return <<<HTML
<!-- Facebook Pixel -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window,document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{$id}');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={$id}&ev=PageView&noscript=1"
/></noscript>
HTML;
    }

    /**
     * Google Analytics script.
     */
    protected function getGoogleAnalyticsScript(): string
    {
        $id = e($this->pixel_id);

        return <<<HTML
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$id}"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '{$id}');
</script>
HTML;
    }

    /**
     * Google Tag Manager script.
     */
    protected function getGTMScript(): string
    {
        $id = e($this->pixel_id);

        return <<<HTML
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$id}');</script>
HTML;
    }

    /**
     * Google Ads script.
     */
    protected function getGoogleAdsScript(): string
    {
        $id = e($this->pixel_id);

        return <<<HTML
<!-- Google Ads -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$id}"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '{$id}');
</script>
HTML;
    }

    /**
     * TikTok Pixel script.
     */
    protected function getTikTokScript(): string
    {
        $id = e($this->pixel_id);

        return <<<HTML
<!-- TikTok Pixel -->
<script>
!function (w, d, t) {
w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
ttq.load('{$id}');
ttq.page();
}(window, document, 'ttq');
</script>
HTML;
    }

    /**
     * Twitter Pixel script.
     */
    protected function getTwitterScript(): string
    {
        $id = e($this->pixel_id);

        return <<<HTML
<!-- Twitter Pixel -->
<script>
!function(e,t,n,s,u,a){e.twq||(s=e.twq=function(){s.exe?s.exe.apply(s,arguments):s.queue.push(arguments);
},s.version='1.1',s.queue=[],u=t.createElement(n),u.async=!0,u.src='https://static.ads-twitter.com/uwt.js',
a=t.getElementsByTagName(n)[0],a.parentNode.insertBefore(u,a))}(window,document,'script');
twq('config','{$id}');
</script>
HTML;
    }

    /**
     * Pinterest Tag script.
     */
    protected function getPinterestScript(): string
    {
        $id = e($this->pixel_id);

        return <<<HTML
<!-- Pinterest Tag -->
<script>
!function(e){if(!window.pintrk){window.pintrk=function(){window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var
n=window.pintrk;n.queue=[],n.version="3.0";var
t=document.createElement("script");t.async=!0,t.src=e;var
r=document.getElementsByTagName("script")[0];r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");
pintrk('load', '{$id}');
pintrk('page');
</script>
<noscript><img height="1" width="1" style="display:none;"
src="https://ct.pinterest.com/v3/?tid={$id}&event=pagevisit&noscript=1"/></noscript>
HTML;
    }

    /**
     * LinkedIn Insight Tag script.
     */
    protected function getLinkedInScript(): string
    {
        $id = e($this->pixel_id);

        return <<<HTML
<!-- LinkedIn Insight Tag -->
<script type="text/javascript">
_linkedin_partner_id = "{$id}";
window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
window._linkedin_data_partner_ids.push(_linkedin_partner_id);
</script><script type="text/javascript">
(function(l) {
if (!l){window.lintrk = function(a,b){window.lintrk.q.push([a,b])};
window.lintrk.q=[]}
var s = document.getElementsByTagName("script")[0];
var b = document.createElement("script");
b.type = "text/javascript";b.async = true;
b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
s.parentNode.insertBefore(b, s);})(window.lintrk);
</script>
<noscript><img height="1" width="1" style="display:none;"
src="https://px.ads.linkedin.com/collect/?pid={$id}&fmt=gif"/></noscript>
HTML;
    }

    /**
     * Snapchat Pixel script.
     */
    protected function getSnapchatScript(): string
    {
        $id = e($this->pixel_id);

        return <<<HTML
<!-- Snapchat Pixel -->
<script type='text/javascript'>
(function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function()
{a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};
a.queue=[];var s='script';r=t.createElement(s);r.async=!0;
r.src=n;var u=t.getElementsByTagName(s)[0];
u.parentNode.insertBefore(r,u);})(window,document,
'https://sc-static.net/scevent.min.js');
snaptr('init', '{$id}');
snaptr('track', 'PAGE_VIEW');
</script>
HTML;
    }

    /**
     * Microsoft/Bing UET script.
     */
    protected function getBingScript(): string
    {
        $id = e($this->pixel_id);

        return <<<HTML
<!-- Microsoft/Bing UET -->
<script>
(function(w,d,t,r,u){var f,n,i;w[u]=w[u]||[],f=function(){var o={ti:"{$id}"};
o.q=w[u],w[u]=new UET(o),w[u].push("pageLoad")},n=d.createElement(t),n.src=r,
n.async=1,n.onload=n.onreadystatechange=function(){var s=this.readyState;
s&&s!=="loaded"&&s!=="complete"||(f(),n.onload=n.onreadystatechange=null)},
i=d.getElementsByTagName(t)[0],i.parentNode.insertBefore(n,i)})
(window,document,"script","//bat.bing.com/bat.js","uetq");
</script>
<noscript><img src="//bat.bing.com/action/0?ti={$id}&Ver=2" height="0" width="0" style="display:none;visibility:hidden"/></noscript>
HTML;
    }
}
