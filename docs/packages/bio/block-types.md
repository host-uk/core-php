---
title: Block Types Reference
description: Complete reference for all biolink block types
updated: 2026-01-29
---

# Block Types Reference

This document provides a complete reference for all available block types in the `core-bio` package.

## Block Type Categories

Blocks are organised into four categories:

| Category | Description |
|----------|-------------|
| `standard` | Basic content blocks (links, text, images) |
| `embeds` | Third-party content embeds (YouTube, Spotify, etc.) |
| `advanced` | Feature-rich blocks (maps, forms, calendars) |
| `payments` | Payment and commerce blocks |

## Tier Access

Block types are gated by subscription tier:

| Tier | Access |
|------|--------|
| `null` (free) | Available to all users |
| `pro` | Requires Pro plan or higher |
| `ultimate` | Requires Ultimate plan |
| `payment` | Requires payment add-on |

## Standard Blocks

### link
Basic clickable link button.

| Property | Value |
|----------|-------|
| Icon | `fas fa-link` |
| Category | standard |
| Has Statistics | Yes |
| Themable | Yes |
| Tier | Free |

**Settings:**
- `url` (string) - Destination URL
- `text` (string) - Button text
- `icon` (string, optional) - FontAwesome icon class

### heading
Section heading/title.

| Property | Value |
|----------|-------|
| Icon | `fas fa-heading` |
| Category | standard |
| Has Statistics | No |
| Themable | Yes |
| Tier | Free |

**Settings:**
- `text` (string) - Heading text
- `level` (int) - HTML heading level (1-6)

### paragraph
Text content block.

| Property | Value |
|----------|-------|
| Icon | `fas fa-paragraph` |
| Category | standard |
| Has Statistics | No |
| Themable | Yes |
| Tier | Free |

**Settings:**
- `text` (string) - Paragraph content

### avatar
Profile image display.

| Property | Value |
|----------|-------|
| Icon | `fas fa-user` |
| Category | standard |
| Has Statistics | Yes |
| Themable | No |
| Tier | Free |

**Settings:**
- `image` (string) - Image path or URL
- `size` (string) - Display size

### image
Image display block.

| Property | Value |
|----------|-------|
| Icon | `fas fa-image` |
| Category | standard |
| Has Statistics | Yes |
| Themable | No |
| Tier | Free |

**Settings:**
- `image` (string) - Image path or URL
- `alt` (string) - Alt text
- `link` (string, optional) - Click destination

### socials
Social media icon links.

| Property | Value |
|----------|-------|
| Icon | `fas fa-users` |
| Category | standard |
| Has Statistics | No |
| Themable | Yes |
| Tier | Free |

**Settings:**
- `platforms` (array) - List of platform handles
- `style` (string) - Icon display style

### business_hours
Opening hours display.

| Property | Value |
|----------|-------|
| Icon | `fas fa-clock` |
| Category | standard |
| Has Statistics | No |
| Themable | Yes |
| Tier | Free |

**Settings:**
- `hours` (array) - Day/time pairs
- `timezone` (string) - Timezone identifier

### modal_text
Expandable text content.

| Property | Value |
|----------|-------|
| Icon | `fas fa-book-open` |
| Category | standard |
| Has Statistics | Yes |
| Themable | Yes |
| Tier | Free |

**Settings:**
- `title` (string) - Modal trigger text
- `content` (string) - Full content

### header (Pro)
Full-width header section.

| Property | Value |
|----------|-------|
| Icon | `fas fa-theater-masks` |
| Category | standard |
| Has Statistics | Yes |
| Themable | No |
| Tier | Pro |

### image_grid (Pro)
Multiple image grid display.

| Property | Value |
|----------|-------|
| Icon | `fas fa-images` |
| Category | standard |
| Has Statistics | Yes |
| Themable | No |
| Tier | Pro |

### divider (Pro)
Visual separator.

| Property | Value |
|----------|-------|
| Icon | `fas fa-grip-lines` |
| Category | standard |
| Has Statistics | No |
| Themable | No |
| Tier | Pro |

### list (Pro)
Bullet/numbered list.

| Property | Value |
|----------|-------|
| Icon | `fas fa-list` |
| Category | standard |
| Has Statistics | No |
| Themable | No |
| Tier | Pro |

### big_link (Ultimate)
Large featured link.

| Property | Value |
|----------|-------|
| Icon | `fas fa-external-link-alt` |
| Category | standard |
| Has Statistics | Yes |
| Themable | Yes |
| Tier | Ultimate |

### audio (Ultimate)
Audio player.

| Property | Value |
|----------|-------|
| Icon | `fas fa-volume-up` |
| Category | standard |
| Has Statistics | No |
| Themable | No |
| Tier | Ultimate |

### video (Ultimate)
Self-hosted video player.

| Property | Value |
|----------|-------|
| Icon | `fas fa-video` |
| Category | standard |
| Has Statistics | No |
| Themable | No |
| Tier | Ultimate |

### file (Ultimate)
File download.

| Property | Value |
|----------|-------|
| Icon | `fas fa-file` |
| Category | standard |
| Has Statistics | Yes |
| Themable | Yes |
| Tier | Ultimate |

### cta (Ultimate)
Call-to-action block.

| Property | Value |
|----------|-------|
| Icon | `fas fa-comments` |
| Category | standard |
| Has Statistics | Yes |
| Themable | Yes |
| Tier | Ultimate |

## Embed Blocks

### youtube (Free)
YouTube video embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | www.youtube.com, youtu.be |
| Tier | Free |

**Settings:**
- `url` (string) - YouTube video URL

### spotify (Free)
Spotify embed (track, album, playlist).

| Property | Value |
|----------|-------|
| Whitelisted Hosts | open.spotify.com |
| Tier | Free |

### soundcloud (Free)
SoundCloud track embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | soundcloud.com |
| Tier | Free |

### tiktok_video (Free)
TikTok video embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | www.tiktok.com |
| Tier | Free |

### twitch (Free)
Twitch stream/video embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | www.twitch.tv |
| Tier | Free |

### vimeo (Free)
Vimeo video embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | vimeo.com |
| Tier | Free |

### applemusic (Pro)
Apple Music embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | music.apple.com |
| Tier | Pro |

### tidal (Pro)
Tidal music embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | tidal.com |
| Tier | Pro |

### mixcloud (Pro)
Mixcloud embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | www.mixcloud.com |
| Tier | Pro |

### kick (Pro)
Kick stream embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | kick.com |
| Tier | Pro |

### twitter_tweet (Pro)
X/Twitter tweet embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | twitter.com, x.com |
| Tier | Pro |

### twitter_video (Pro)
X/Twitter video embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | twitter.com, x.com |
| Tier | Pro |

### pinterest_profile (Pro)
Pinterest profile embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | pinterest.com, www.pinterest.com |
| Tier | Pro |

### instagram_media (Pro)
Instagram post embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | www.instagram.com |
| Tier | Pro |

### snapchat (Pro)
Snapchat embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | www.snapchat.com, snapchat.com |
| Tier | Pro |

### tiktok_profile (Pro)
TikTok profile embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | www.tiktok.com |
| Tier | Pro |

### vk_video (Pro)
VK video embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | vk.com |
| Tier | Pro |

### typeform (Ultimate)
Typeform form embed.

| Property | Value |
|----------|-------|
| Tier | Ultimate |

### calendly (Ultimate)
Calendly scheduling embed.

| Property | Value |
|----------|-------|
| Tier | Ultimate |

### discord (Ultimate)
Discord server widget.

| Property | Value |
|----------|-------|
| Tier | Ultimate |

### facebook (Ultimate)
Facebook content embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | www.facebook.com, fb.watch |
| Tier | Ultimate |

### reddit (Ultimate)
Reddit post embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | www.reddit.com |
| Tier | Ultimate |

### iframe (Ultimate)
Generic iframe embed (use with caution).

| Property | Value |
|----------|-------|
| Tier | Ultimate |

### pdf_document (Ultimate)
PDF viewer embed.

| Property | Value |
|----------|-------|
| Has Statistics | Yes |
| Tier | Ultimate |

### powerpoint_presentation (Ultimate)
PowerPoint viewer.

| Property | Value |
|----------|-------|
| Has Statistics | Yes |
| Tier | Ultimate |

### excel_spreadsheet (Ultimate)
Excel viewer.

| Property | Value |
|----------|-------|
| Has Statistics | Yes |
| Tier | Ultimate |

### rumble (Ultimate)
Rumble video embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | rumble.com |
| Tier | Ultimate |

### telegram (Ultimate)
Telegram channel/post embed.

| Property | Value |
|----------|-------|
| Whitelisted Hosts | t.me |
| Tier | Ultimate |

## Advanced Blocks

### map (Free)
Interactive map display.

| Property | Value |
|----------|-------|
| Icon | `fas fa-map` |
| Category | advanced |
| Has Statistics | Yes |
| Tier | Free |

**Settings:**
- `address` (string) - Location address
- `latitude` (float) - Latitude coordinate
- `longitude` (float) - Longitude coordinate

### email_collector (Free)
Email signup form.

| Property | Value |
|----------|-------|
| Icon | `fas fa-envelope` |
| Category | advanced |
| Has Statistics | No |
| Tier | Free |

**Settings:**
- `placeholder` (string) - Input placeholder
- `button_text` (string) - Submit button text

### phone_collector (Free)
Phone number collection.

| Property | Value |
|----------|-------|
| Icon | `fas fa-phone-square-alt` |
| Category | advanced |
| Has Statistics | No |
| Tier | Free |

### contact_collector (Free)
Full contact form.

| Property | Value |
|----------|-------|
| Icon | `fas fa-address-book` |
| Category | advanced |
| Has Statistics | No |
| Tier | Free |

### rss_feed (Pro)
RSS feed display.

| Property | Value |
|----------|-------|
| Icon | `fas fa-rss` |
| Category | advanced |
| Tier | Pro |

### custom_html (Pro)
Custom HTML content (sanitised).

| Property | Value |
|----------|-------|
| Icon | `fas fa-code` |
| Category | advanced |
| Tier | Pro |

### vcard (Pro)
Downloadable contact card.

| Property | Value |
|----------|-------|
| Icon | `fas fa-id-card` |
| Category | advanced |
| Has Statistics | Yes |
| Tier | Pro |

**Settings:** See `config.vcard_fields` for all available fields.

### alert (Pro)
Notification/announcement.

| Property | Value |
|----------|-------|
| Icon | `fas fa-bell` |
| Category | advanced |
| Has Statistics | Yes |
| Tier | Pro |

### appointment_calendar (Ultimate)
Booking/scheduling widget.

| Property | Value |
|----------|-------|
| Icon | `fas fa-calendar` |
| Category | advanced |
| Tier | Ultimate |

### faq (Ultimate)
Frequently asked questions.

| Property | Value |
|----------|-------|
| Icon | `fas fa-feather` |
| Category | advanced |
| Tier | Ultimate |

### countdown (Ultimate)
Countdown timer.

| Property | Value |
|----------|-------|
| Icon | `fas fa-clock` |
| Category | advanced |
| Tier | Ultimate |

### external_item (Ultimate)
External product/item display.

| Property | Value |
|----------|-------|
| Icon | `fas fa-money-bill-wave` |
| Category | advanced |
| Has Statistics | Yes |
| Tier | Ultimate |

### share (Ultimate)
Social sharing buttons.

| Property | Value |
|----------|-------|
| Icon | `fas fa-share-square` |
| Category | advanced |
| Has Statistics | Yes |
| Tier | Ultimate |

### coupon (Ultimate)
Discount coupon display.

| Property | Value |
|----------|-------|
| Icon | `fas fa-tags` |
| Category | advanced |
| Has Statistics | Yes |
| Tier | Ultimate |

### youtube_feed (Ultimate)
YouTube channel feed.

| Property | Value |
|----------|-------|
| Icon | `fab fa-youtube` |
| Category | advanced |
| Tier | Ultimate |

### timeline (Ultimate)
Event timeline display.

| Property | Value |
|----------|-------|
| Icon | `fas fa-ellipsis-v` |
| Category | advanced |
| Tier | Ultimate |

### review (Ultimate)
Review/testimonial display.

| Property | Value |
|----------|-------|
| Icon | `fas fa-star` |
| Category | advanced |
| Tier | Ultimate |

### image_slider (Ultimate)
Image carousel.

| Property | Value |
|----------|-------|
| Icon | `fas fa-clone` |
| Category | advanced |
| Has Statistics | Yes |
| Tier | Ultimate |

### markdown (Ultimate)
Markdown content renderer.

| Property | Value |
|----------|-------|
| Icon | `fas fa-sticky-note` |
| Category | advanced |
| Tier | Ultimate |

## Payment Blocks

### paypal (Free)
PayPal payment button.

| Property | Value |
|----------|-------|
| Icon | `fab fa-paypal` |
| Category | payments |
| Has Statistics | Yes |
| Tier | Free |

### donation (Payment)
Donation collection.

| Property | Value |
|----------|-------|
| Icon | `fas fa-hand-holding-usd` |
| Category | payments |
| Tier | Payment add-on |

### product (Payment)
Product purchase.

| Property | Value |
|----------|-------|
| Icon | `fas fa-cube` |
| Category | payments |
| Tier | Payment add-on |

### service (Payment)
Service booking/purchase.

| Property | Value |
|----------|-------|
| Icon | `fas fa-comments-dollar` |
| Category | payments |
| Tier | Payment add-on |

## HLCRF Region Support

Some blocks support placement in multiple layout regions:

| Block Type | Allowed Regions |
|------------|-----------------|
| link | H, L, C, R, F |
| heading | H, L, C, R, F |
| socials | H, L, C, R, F |
| divider | H, L, C, R, F |

Blocks without `allowed_regions` config default to Content (C) only.

## Adding Custom Blocks

To add a new block type:

1. Add block definition to `config.php`:
```php
'my_block' => [
    'icon' => 'fas fa-star',
    'color' => '#ff0000',
    'category' => 'advanced',
    'has_statistics' => true,
    'themable' => true,
    'tier' => 'pro',
],
```

2. Create Blade template at `View/Blade/blocks/my_block.blade.php`

3. Add settings schema validation in relevant request classes

4. Document the block type in this reference
