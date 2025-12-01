# Email Tracking System - Complete Guide

## Overview

The SendPortal email tracking system monitors subscriber interactions with email campaigns, including opens, clicks, and other events. The system tracks events in real-time and updates campaign statistics automatically.

## Features

- ✅ **Real-time tracking** - Events tracked immediately when they occur
- ✅ **Automatic statistics** - Campaign open/click counts and ratios calculated automatically
- ✅ **Click link tracking** - Tracks which URLs are clicked and how many times
- ✅ **Message-level tracking** - Tracks opens/clicks per message with timestamps
- ✅ **Idempotent** - Same event won't be tracked twice for the same subscriber
- ✅ **CORS support** - Proper headers for cross-origin requests
- ✅ **Multiple formats** - Supports both simplified task numbers and legacy task type names

## Tracking Events (Task Types)

| Number | Task Type | Description |
|--------|-----------|-------------|
| 1 | `email_sent` | Email sent successfully (automatically tracked) |
| 2 | `email_opened` | Email opened by subscriber |
| 3 | `email_clicked` | Link clicked in email |
| 4 | `newsletter_opened` | Newsletter opened |
| 5 | `landing_page_opened` | Landing page visited |
| 6 | `thank_you_received` | Thank you email received |
| 7 | `asset_downloaded` | Asset downloaded |

## API Endpoints

### Simplified Format (Recommended)

**Endpoint:**
```
GET /api/track/{campaignHash}/{subscriberHash}/{taskNumber}
```

**Example:**
```
GET /api/track/26/a793bf00-6132-43d6-b9cd-4534174c0d13/2
```

**Query Parameters:**
- `status` (optional) - Override default status (default: `opened`)
- `metadata` (optional) - JSON string for additional data
- `redirect` (optional) - For click tracking, the destination URL to redirect to

**Response:**
- Returns JSON response: `{"success": true, "msg": "tracked"}`
- Includes proper CORS headers to prevent ORB (Opaque Response Blocking) errors
- Works with ngrok, Cloudflare, and any JS fetch / image / pixel call
- For click tracking with `redirect` parameter, returns HTTP 302 redirect

### Legacy Format (Backward Compatible)

**Endpoint:**
```
GET /api/track/{campaignId}/{subscriberHash}/{taskType}
```

**Example:**
```
GET /api/track/26/a793bf00-6132-43d6-b9cd-4534174c0d13/email_opened
```

## How It Works

### 1. Tracking Flow

When a tracking event occurs:

1. **Event Recorded** - Creates/updates record in `campaign_subscriber_tracking` table
2. **Message Updated** - Updates `Message` model with `opened_at`/`clicked_at` and increments `open_count`/`click_count`
3. **Campaign Counts Updated** - Recalculates unique `open_count` and `click_count` on `Campaign` model
4. **URL Tracking** - For clicks with URLs, creates/updates `MessageUrl` records for "Top Clicked Links"

### 2. Database Tables

#### `campaign_subscriber_tracking`
Stores individual tracking events:
- `campaign_id` - Campaign ID
- `subscriber_id` - Subscriber ID
- `subscriber_hash` - Subscriber hash (for URL tracking)
- `task_type` - Event type (email_opened, email_clicked, etc.)
- `status` - Event status (opened, not_opened, pending, failed)
- `metadata` - JSON data (URLs, asset names, etc.)
- `tracked_at` - When the event occurred

#### `messages`
Stores per-message tracking:
- `opened_at` - First time message was opened
- `clicked_at` - First time message was clicked
- `open_count` - Total number of opens for this message
- `click_count` - Total number of clicks for this message

#### `campaigns`
Stores campaign-level statistics:
- `open_count` - Unique number of subscribers who opened
- `click_count` - Unique number of subscribers who clicked
- `sent_count` - Total number of messages sent

#### `message_urls`
Stores clicked link tracking:
- `source_type` - Campaign or Automation
- `source_id` - Campaign/Automation ID
- `url` - The clicked URL
- `click_count` - Number of times this URL was clicked
- `hash` - Unique hash for the URL

### 3. Campaign Statistics

The system automatically calculates:

- **Open Ratio** - `open_count / sent_count`
- **Click Ratio** - `click_count / sent_count`
- **Total Opens** - Sum of all `open_count` from related messages
- **Avg. Time To Open** - Average time from sent/delivered to first open
- **Avg. Time To Click** - Average time from sent/delivered to first click

## Email Template Usage

### Email Open Tracking Pixel

Add this at the end of your email HTML:

```html
<img src="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/2" 
     width="1" height="1" style="display:none;" alt="" />
```

**Important:** Use `{{ app_url }}` not `{{ config('app.url') }}` in email templates.

### Click Tracking Links

For links you want to track, wrap them with the tracking URL:

```html
<a href="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/3?redirect=https://example.com/product">
    Click Here to View Product
</a>
```

The `redirect` parameter:
- Tracks the click event
- Stores the URL in `MessageUrl` table (for "Top Clicked Links")
- Redirects the user to the destination URL

### With Metadata

You can also pass metadata for additional context:

```html
<a href="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/3?redirect=https://example.com&metadata={%22link_text%22:%22Buy%20Now%22}">
    Buy Now
</a>
```

### Landing Page Tracking

Track when subscribers visit a landing page:

```html
<script>
    fetch('{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/5');
</script>
```

### Asset Download Tracking

```html
<a href="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/7?redirect=https://example.com/download.pdf&metadata={%22asset_name%22:%22brochure.pdf%22}">
    Download PDF
</a>
```

## Available Merge Tags

Use these merge tags in your email templates:

### Subscriber Information
- `{{email}}` - Subscriber's email address
- `{{first_name}}` - Subscriber's first name
- `{{last_name}}` - Subscriber's last name

### Tracking & Links
- `{{subscriber_hash}}` - Subscriber's unique hash (for tracking URLs)
- `{{campaign_hash}}` - Campaign ID (for tracking URLs)
- `{{app_url}}` - Application URL from config
- `{{unsubscribe_url}}` - Unsubscribe link URL
- `{{webview_url}}` - Webview link URL

## Campaign Reports

### Opens Report

Shows:
- **Total Opens** - Sum of all message open counts
- **Unique Opens** - Number of unique subscribers who opened
- **Avg. Time To Open** - Average time from sent to first open

### Clicks Report

Shows:
- **Total Clicks** - Sum of all message click counts
- **Unique Clicks** - Number of unique subscribers who clicked
- **Avg. Time To Click** - Average time from sent to first click
- **Top Clicked Links** - URLs sorted by click count

### How Statistics Are Calculated

- **Open Ratio** = `campaign.open_count / campaign.sent_count`
- **Click Ratio** = `campaign.click_count / campaign.sent_count`
- **Total Opens** = Sum of `message.open_count` for all messages in campaign
- **Avg. Time To Open** = Average of `(opened_at - COALESCE(delivered_at, sent_at))` for all opens

## Testing

### Test Tracking Endpoint

```bash
# Test email opened tracking
curl "https://your-app-url.com/api/track/26/a793bf00-6132-43d6-b9cd-4534174c0d13/2"

# Should return:
# {"success":true,"msg":"tracked"}

# Check headers
curl -I "https://your-app-url.com/api/track/26/a793bf00-6132-43d6-b9cd-4534174c0d13/2"

# Should return:
# HTTP/1.1 200 OK
# Content-Type: application/json
# Access-Control-Allow-Origin: *
# Cross-Origin-Resource-Policy: cross-origin
```

### Test Click Tracking with Redirect

```bash
# Test click tracking with redirect
curl -I "https://your-app-url.com/api/track/26/a793bf00-6132-43d6-b9cd-4534174c0d13/3?redirect=https://example.com"

# Should return:
# HTTP/1.1 302 Found
# Location: https://example.com
```

### Check Tracking Records

```php
// In tinker or code
use App\Models\CampaignSubscriberTracking;

$tracking = CampaignSubscriberTracking::where('campaign_id', 26)
    ->where('task_type', 'email_opened')
    ->orderBy('tracked_at', 'desc')
    ->get();
```

### Check MessageUrl Records

```php
use Sendportal\Base\Models\MessageUrl;

$urls = MessageUrl::where('source_type', 'Sendportal\Base\Models\Campaign')
    ->where('source_id', 26)
    ->orderBy('click_count', 'desc')
    ->get();
```

## Troubleshooting

### Email Opens Not Being Tracked

#### 1. Check if Tracking Records Are Created

```php
\App\Models\CampaignSubscriberTracking::where('campaign_id', YOUR_CAMPAIGN_ID)
    ->where('task_type', 'email_opened')
    ->get();
```

#### 2. Email Clients Block Tracking Pixels

Many email clients block tracking pixels by default:
- **Gmail**: Blocks images by default (user must click "Display images")
- **Outlook**: Blocks external images
- **Apple Mail**: Blocks tracking pixels
- **Yahoo Mail**: Blocks images by default

**This is expected behavior.** Tracking only works when:
- User enables images in their email client
- User clicks "Display images" or "Load images"
- Email client doesn't block tracking pixels

#### 3. Verify Endpoint is Accessible

```bash
curl "https://YOUR_APP_URL/api/track/26/a793bf00-6132-43d6-b9cd-4534174c0d13/2"
```

Should return:
- HTTP 200 OK
- JSON response: `{"success":true,"msg":"tracked"}`
- Content-Type: application/json
- Access-Control-Allow-Origin: *
- Cross-Origin-Resource-Policy: cross-origin

#### 4. Check Application Logs

```bash
tail -f storage/logs/laravel.log | grep -i "track"
```

Look for:
- "Tracking pixel accessed" - Endpoint was called
- "Tracking successful" - Record was created
- "Updated message opened_at and open_count" - Message was updated
- "Updated campaign open_count" - Campaign count was updated

#### 5. Verify Merge Tags

Make sure you're using merge tags correctly:
```html
<img src="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/2" />
```

**NOT:**
```html
<img src="{{ config('app.url') }}/api/track/..." />
```

### Clicked Links Not Showing

#### 1. Check if URL is Provided

Click tracking requires a `redirect` parameter or URL in metadata:

```html
<!-- ✅ Correct -->
<a href="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/3?redirect=https://example.com">
    Click Here
</a>

<!-- ❌ Missing URL -->
<a href="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/3">
    Click Here
</a>
```

#### 2. Check MessageUrl Records

```php
use Sendportal\Base\Models\MessageUrl;

$urls = MessageUrl::where('source_type', 'Sendportal\Base\Models\Campaign')
    ->where('source_id', YOUR_CAMPAIGN_ID)
    ->get();
```

### ERR_BLOCKED_BY_ORB Errors

If you see `ERR_BLOCKED_BY_ORB` in browser console:

1. **Use Cloudflare Tunnel instead of ngrok** - ngrok free accounts show browser warnings
2. **Check CORS headers** - Already implemented in `TrackingController`
3. **Clear browser cache** - Old cached responses may cause issues

### Statistics Not Updating

1. **Clear config cache:**
   ```bash
   php artisan config:clear
   ```

2. **Check if events are being tracked:**
   ```php
   \App\Models\CampaignSubscriberTracking::where('campaign_id', YOUR_CAMPAIGN_ID)
       ->latest('tracked_at')
       ->first();
   ```

3. **Refresh campaign model:**
   ```php
   $campaign = \Sendportal\Base\Models\Campaign::find(YOUR_CAMPAIGN_ID);
   $campaign->refresh();
   echo "Open Count: " . $campaign->open_count;
   echo "Click Count: " . $campaign->click_count;
   ```

## Development Setup

### Using Cloudflare Tunnel (Recommended)

Instead of ngrok, use Cloudflare Tunnel for better email tracking:

```bash
# Install cloudflared
brew install cloudflare/cloudflare/cloudflared

# Start tunnel
cloudflared tunnel --url http://localhost:8000

# Update APP_URL in .env
APP_URL=https://your-cloudflare-url.trycloudflare.com
```

Benefits:
- ✅ No browser warning pages
- ✅ Better for email tracking
- ✅ No ERR_BLOCKED_BY_ORB errors
- ✅ Free and reliable

### Local Development

For local testing:

```bash
# Update .env
APP_URL=http://localhost:8000

# Clear config cache
php artisan config:clear
```

## Code Structure

### Key Files

- **`app/Services/TrackingService.php`** - Core tracking logic
- **`app/Http/Controllers/Api/TrackingController.php`** - API endpoints
- **`app/Models/CampaignSubscriberTracking.php`** - Tracking model
- **`routes/api.php`** - API routes
- **`app/Repositories/Campaigns/ExtendedCampaignTenantRepository.php`** - Custom repository for average time calculations

### Service Methods

- `track()` - Main tracking method
- `updateMessageTracking()` - Updates Message model
- `updateCampaignCounts()` - Updates Campaign counts
- `updateMessageUrl()` - Updates MessageUrl for clicked links

## Examples

### Complete Email Template

```html
<!DOCTYPE html>
<html>
<head>
    <title>Welcome Email</title>
</head>
<body>
    <h1>Hello {{first_name}}!</h1>
    <p>Thank you for subscribing.</p>
    
    <p>
        <a href="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/3?redirect=https://example.com/product">
            View Our Products
        </a>
    </p>
    
    <p>
        <a href="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/3?redirect=https://example.com/blog">
            Read Our Blog
        </a>
    </p>
    
    <!-- Tracking pixel (must be at the end) -->
    <img src="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/2" 
         width="1" height="1" style="display:none;" alt="" />
</body>
</html>
```

### Testing with cURL

```bash
# Test open tracking
curl "https://your-app-url.com/api/track/26/a793bf00-6132-43d6-b9cd-4534174c0d13/2"

# Test click tracking with redirect
curl -L "https://your-app-url.com/api/track/26/a793bf00-6132-43d6-b9cd-4534174c0d13/3?redirect=https://example.com"

# Test with metadata
curl "https://your-app-url.com/api/track/26/a793bf00-6132-43d6-b9cd-4534174c0d13/7?metadata=%7B%22asset_name%22%3A%22brochure.pdf%22%7D"
```

## Important Notes

- **Tracking pixels only work if images are enabled** in the email client
- **Many email clients block tracking by default** for privacy
- **Tracking is not 100% accurate** - some opens/clicks may not be tracked
- **The endpoint returns JSON** (`{"success":true,"msg":"tracked"}`) with proper CORS headers to prevent ORB errors
- **Works with ngrok, Cloudflare, and any JS fetch / image / pixel call**
- **Use `{{ app_url }}` not `{{ config('app.url') }}`** in email templates
- **Click tracking requires `redirect` parameter** to track URLs in "Top Clicked Links"
- **Campaign statistics update automatically** when tracking events occur

## Support

For issues or questions:
1. Check application logs: `storage/logs/laravel.log`
2. Check database records: `sendportal_campaign_subscriber_tracking` table
3. Verify routes: `php artisan route:list --path=api/track`
4. Test endpoints directly with cURL

