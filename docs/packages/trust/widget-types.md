---
title: Widget Types
description: Reference documentation for all Trust widget types and their configuration
updated: 2026-01-29
---

# Widget Types

This document describes all available widget types in core-trust, their purpose, and configuration options.

## Overview

Trust widgets are categorised into six groups:

1. **Informational** - Messages, announcements, coupons
2. **Social Proof** - Live counters, conversions, activity feeds
3. **Reviews** - Customer testimonials
4. **Collectors** - Email capture, contact forms
5. **Engagement** - Countdown timers, videos, social share
6. **Custom** - User-defined HTML/CSS

## Common Configuration

All widget types share these fields:

```json
{
  "name": "Widget display name",
  "type": "widget_type_key",
  "is_enabled": true,
  "position": "bottom-left",
  "display_duration": 5,
  "display_delay": 3,
  "display_frequency": 0,
  "style": {
    "background_color": "#ffffff",
    "text_color": "#1f2937",
    "accent_color": "#3b82f6",
    "animation": "slide"
  },
  "display_targeting": {
    "pages": ["*"],
    "devices": ["desktop", "mobile", "tablet"]
  },
  "start_date": null,
  "end_date": null,
  "schedule_timezone": "campaign",
  "schedule_time_start": null,
  "schedule_time_end": null,
  "schedule_days": null
}
```

### Position Options
- `bottom-left` (default)
- `bottom-right`
- `top-left`
- `top-right`
- `bottom-center`
- `top-center`

### Animation Options
- `slide` (default)
- `fade`
- `bounce`
- `zoom`

---

## Informational Widgets

### informational

Basic information message with title and body text.

**Content schema:**
```json
{
  "title": "Welcome",
  "message": "We're glad you're here.",
  "icon": "info",
  "url": "https://example.com/learn-more"
}
```

**Use cases:**
- Welcome messages
- Important notices
- Feature announcements

---

### coupon

Display a promotional coupon code.

**Content schema:**
```json
{
  "title": "Special Offer",
  "code": "SAVE10",
  "message": "Use this code for 10% off"
}
```

**Use cases:**
- Discount promotions
- First-time visitor offers
- Holiday sales

---

### cookie

GDPR-compliant cookie consent notice.

**Content schema:**
```json
{
  "title": "Cookie Notice",
  "message": "We use cookies to improve your experience.",
  "accept_text": "Accept",
  "decline_text": "Decline",
  "policy_url": "/privacy"
}
```

**Note:** This widget should integrate with your cookie consent management.

---

### announcement

Highlighted announcement banner.

**Content schema:**
```json
{
  "title": "New Feature",
  "message": "Check out our latest update!",
  "icon": "bullhorn",
  "url": "/blog/new-feature",
  "cta_text": "Learn More"
}
```

---

## Social Proof Widgets

### live_counter

Display number of current visitors (real or simulated).

**Content schema:**
```json
{
  "title": "Live now",
  "message": "{count} people viewing this page",
  "min_count": 5,
  "max_count": 50
}
```

**Dynamic data:**
```json
{
  "count": 23
}
```

**Behaviour:**
- Shows real count from last 5 minutes of events if available
- Falls back to random number between `min_count` and `max_count`
- Never shows less than `min_count`

---

### conversions_counter

Display total conversions today.

**Content schema:**
```json
{
  "title": "Sales today",
  "message": "{count} orders placed"
}
```

**Dynamic data:**
```json
{
  "count": 47
}
```

**Caching:** 15-minute cache, cleared on new conversion.

---

### latest_conversion

Show the most recent purchase/signup.

**Content schema:**
```json
{
  "title": "Recent purchase",
  "message": "{name} from {location} just purchased {product}"
}
```

**Dynamic data:**
```json
{
  "available": true,
  "name": "John S.",
  "location": "London",
  "product": "Pro Plan",
  "time_ago": "2 minutes ago",
  "image": "https://..."
}
```

**Privacy:** Names anonymised (first name + last initial).

---

### conversions_feed

Rotating feed of recent conversions.

**Content schema:**
```json
{
  "title": "Recent activity",
  "interval": 5
}
```

**Dynamic data:**
```json
{
  "conversions": [
    {
      "name": "Jane D.",
      "location": "Manchester",
      "product": "Basic Plan",
      "time_ago": "5 minutes ago",
      "image": null
    }
  ]
}
```

**Display:** Shows up to 10 conversions, rotating based on `interval` seconds.

---

## Review Widgets

### review

Display a specific customer review.

**Content schema:**
```json
{
  "review_id": 123,
  "show_rating": true,
  "show_source": true
}
```

**Note:** Requires reviews to be added to the campaign first.

---

### random_review

Show a random high-rated (4-5 stars) review.

**Content schema:**
```json
{
  "show_rating": true,
  "show_source": true,
  "show_product": false
}
```

**Dynamic data:**
```json
{
  "available": true,
  "reviewer_name": "Sarah Johnson",
  "reviewer_title": "Marketing Manager",
  "reviewer_image": "https://...",
  "reviewer_initials": "SJ",
  "rating": 5,
  "content": "Excellent service, highly recommended!",
  "product": "Enterprise Plan"
}
```

---

### reviews_carousel

Rotating carousel of customer reviews.

**Content schema:**
```json
{
  "interval": 5,
  "show_rating": true,
  "max_reviews": 5
}
```

**Dynamic data:**
```json
{
  "reviews": [
    {
      "reviewer_name": "Tom Wilson",
      "reviewer_title": "CEO",
      "reviewer_image": null,
      "reviewer_initials": "TW",
      "rating": 5,
      "content": "Transformed our business..."
    }
  ]
}
```

---

## Collector Widgets

### email_collector

Capture email addresses from visitors.

**Content schema:**
```json
{
  "title": "Stay Updated",
  "message": "Subscribe to our newsletter",
  "placeholder": "Enter your email",
  "button_text": "Subscribe",
  "success_message": "Thanks for subscribing!"
}
```

**Submission endpoint:** `POST /api/trust/collect`
```json
{
  "pixel_key": "...",
  "notification_id": 123,
  "email": "user@example.com"
}
```

**Note:** Email is required for this widget type.

---

### request_collector

General purpose contact/request form.

**Content schema:**
```json
{
  "title": "Contact Us",
  "message": "Send us a message",
  "fields": ["name", "email", "message"],
  "button_text": "Send",
  "success_message": "We'll be in touch!"
}
```

**Submission endpoint:** `POST /api/trust/collect`
```json
{
  "pixel_key": "...",
  "notification_id": 123,
  "name": "John Doe",
  "email": "john@example.com",
  "message": "I have a question..."
}
```

---

## Engagement Widgets

### countdown

Countdown timer for urgency.

**Content schema:**
```json
{
  "title": "Offer ends in",
  "end_date": "2026-02-14T23:59:59Z",
  "expired_message": "Offer has ended",
  "show_days": true,
  "show_hours": true,
  "show_minutes": true,
  "show_seconds": true
}
```

**Note:** Timer calculated client-side for accuracy.

---

### video

Video popup widget.

**Content schema:**
```json
{
  "title": "Watch Demo",
  "video_url": "https://youtube.com/watch?v=...",
  "autoplay": false,
  "thumbnail": "https://..."
}
```

**Supported platforms:**
- YouTube
- Vimeo
- Direct MP4/WebM URLs

---

### share

Social sharing buttons.

**Content schema:**
```json
{
  "title": "Share this page",
  "platforms": ["twitter", "facebook", "linkedin", "email"],
  "share_text": "Check out this amazing product!"
}
```

---

### feedback

Quick feedback collection (emoji reactions).

**Content schema:**
```json
{
  "title": "How was your experience?",
  "options": [
    {"emoji": "😊", "label": "Great"},
    {"emoji": "😐", "label": "Okay"},
    {"emoji": "😞", "label": "Poor"}
  ],
  "success_message": "Thanks for your feedback!"
}
```

---

## Custom Widgets

### custom_html

User-defined widget with custom HTML/CSS.

**Content schema:**
```json
{
  "html": "<div class=\"my-widget\">...</div>"
}
```

**Custom CSS field:**
```css
.my-widget {
  padding: 20px;
}
```

**Security:**
- CSS is sanitised (see CssSanitiser)
- CSS is scoped to widget container
- HTML sanitisation status TBD (see security.md)

**Caution:** Review security implications before enabling custom HTML widgets.

---

## A/B Testing

Any widget type can be A/B tested:

1. Create control notification
2. Create variant via `createVariant()`
3. Start test via `startAbTest()`
4. Visitors assigned deterministically by visitor_id
5. Track results via `ABTestService::getTestResults()`
6. Declare winner and apply

**Variant-specific content:**
```json
{
  "ab_test_id": "uuid",
  "is_control": false,
  "traffic_split": 50,
  "test_started_at": "2026-01-29T10:00:00Z"
}
```

---

## Dynamic Data Refresh

Some widget types fetch fresh data:

| Type | Refresh Method |
|------|----------------|
| `live_counter` | GET /api/trust/notification?notification_id=X |
| `latest_conversion` | Included in initial payload, cached |
| `conversions_counter` | Included in initial payload, cached |
| `conversions_feed` | Included in initial payload, cached |
| `random_review` | Randomised on each page load |
| `reviews_carousel` | Included in initial payload |

**Client-side refresh:** Widgets can poll `/api/trust/notification` for updates.

---

## Adding New Widget Types

1. Add type definition to `config.php`:
   ```php
   'my_widget' => [
       'name' => 'My Widget',
       'icon' => 'star',
       'category' => 'custom',
       'description' => 'Description here',
   ],
   ```

2. Add to `Notification::TYPES` constant

3. Handle dynamic data in `TrustService::prepareNotificationData()`:
   ```php
   $data['dynamic'] = match ($notification->type) {
       'my_widget' => $this->getMyWidgetData($notification),
       // ...
   };
   ```

4. Add default content in `Notification::getDefaultContent()`:
   ```php
   'my_widget' => [
       'title' => 'Default Title',
   ],
   ```

5. Update NotificationEditor component for UI

6. Add tests for new widget type
