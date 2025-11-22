# Email Merge Tags Guide

## Available Merge Tags

You can use these merge tags in your email content and subject lines:

### Subscriber Information
- `{{email}}` - Subscriber's email address
- `{{first_name}}` - Subscriber's first name
- `{{last_name}}` - Subscriber's last name

### Tracking & Links
- `{{subscriber_hash}}` - Subscriber's unique hash (for tracking URLs)
- `{{message_hash}}` - Message's unique hash (same as used in unsubscribe URLs)
- `{{campaign_hash}}` - Campaign ID (for tracking URLs)
- `{{app_url}}` - Application URL from config (replaces `{{ config('app.url') }}`)
- `{{unsubscribe_url}}` - Unsubscribe link URL
- `{{webview_url}}` - Webview link URL

## Usage Examples

### In Email Content

**Using simplified task numbers (Recommended):**
```html
<!-- Email opened tracking (Task #2) -->
<img src="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/2" 
     width="1" height="1" style="display:none;" alt="" />

<!-- Email clicked tracking (Task #3) -->
<a href="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/3?redirect=https://example.com">
    Click Here
</a>

<!-- Landing page tracking (Task #5) -->
<script>
    fetch('{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/5');
</script>
```

**Task Numbers:**
- 1 = email_sent
- 2 = email_opened
- 3 = email_clicked
- 4 = newsletter_opened
- 5 = landing_page_opened
- 6 = thank_you_received
- 7 = asset_downloaded

**Legacy format (still supported):**
```html
<img src="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/email_opened" 
     width="1" height="1" style="display:none;" alt="" />
```

**Note:** Use `{{ app_url }}` instead of `{{ config('app.url') }}` in email content. The `config()` function is Blade syntax and won't work in email merge tags.

**Personalized greeting:**
```html
<p>Hello {{first_name}} {{last_name}},</p>
<p>Your email: {{email}}</p>
<p>Campaign Hash: {{campaign_hash}}</p>
<p>Subscriber Hash: {{subscriber_hash}}</p>
<p>Message Hash: {{message_hash}}</p>
```

### In Email Subject

```html
Subject: Welcome {{first_name}}! - Special Offer
```

## Tracking URL Examples

### Using Subscriber Hash
```
{{ config('app.url') }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/email_opened
```

### Using Message Hash (Recommended - simpler)
```
{{ config('app.url') }}/api/track/{{ message_hash }}/email_opened
```

## Notes

- All merge tags are case-insensitive: `{{first_name}}` = `{{FIRST_NAME}}` = `{{First_Name}}`
- Tags are replaced when the email is sent
- If a subscriber field is empty, the tag will be replaced with an empty string
- `message_hash` is the same hash used in unsubscribe URLs, making it easy to use in tracking

