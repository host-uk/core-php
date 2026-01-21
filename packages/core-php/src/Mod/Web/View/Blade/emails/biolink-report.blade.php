<x-mail::message>
# {{ ucfirst($frequency) }} Report for /{{ $biolink->url }}

Here is your analytics summary for **{{ $dateRange }}**.

---

## Overview

@if(($summary['clicks'] ?? 0) > 0)
- **Total Clicks:** {{ number_format($summary['clicks']) }}
- **Unique Visitors:** {{ number_format($summary['unique_clicks']) }}
@else
No clicks recorded during this period.
@endif

@if(count($countries) > 0)
---

## Top Countries

@foreach(array_slice($countries, 0, 5) as $country)
- {{ $country['country_name'] ?? $country['country_code'] }}: {{ number_format($country['clicks']) }} clicks
@endforeach
@endif

@if(count($devices) > 0)
---

## Devices

@foreach($devices as $device)
- {{ ucfirst($device['device_type'] ?? 'Unknown') }}: {{ number_format($device['clicks']) }} clicks
@endforeach
@endif

@if(count($referrers) > 0)
---

## Top Referrers

@foreach(array_slice($referrers, 0, 5) as $referrer)
- {{ $referrer['referrer'] ?? 'Direct' }}: {{ number_format($referrer['clicks']) }} clicks
@endforeach
@endif

---

<x-mail::button :url="$viewUrl">
View Full Analytics
</x-mail::button>

This report was sent because you have {{ $frequency }} email reports enabled for this bio.

Thanks,<br>
{{ config('app.name') }}

<x-mail::subcopy>
To change your report settings, visit your biolink settings page or reply to this email.
</x-mail::subcopy>
</x-mail::message>
