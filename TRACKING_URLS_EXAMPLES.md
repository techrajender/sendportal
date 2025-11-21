# Tracking URLs - Examples and Usage

## API Endpoint Format
```
GET /api/track/{campaignId}/{subscriberHash}/{taskType}?status={status}&metadata={json}
```

## Sample Tracking URLs

### 1. Email Sent (Automatic)
This is automatically tracked when emails are sent, but you can manually track it:
```
GET https://2e8f4edb9432.ngrok-free.app/api/track/8/34fd2666-7f61-4fb5-a203-db55748c043e/email_sent
```

### 2. Email Opened
Track when a subscriber opens an email:
```
GET https://2e8f4edb9432.ngrok-free.app/api/track/8/34fd2666-7f61-4fb5-a203-db55748c043e/email_opened
```

### 3. Email Clicked
Track when a subscriber clicks a link in the email:
```
GET https://2e8f4edb9432.ngrok-free.app/api/track/8/34fd2666-7f61-4fb5-a203-db55748c043e/email_clicked?metadata={"url":"https://example.com/product","link_text":"Buy Now"}
```

### 4. Newsletter Opened
Track newsletter opens:
```
GET https://2e8f4edb9432.ngrok-free.app/api/track/8/34fd2666-7f61-4fb5-a203-db55748c043e/newsletter_opened
```

### 5. Landing Page Opened
Track landing page visits:
```
GET https://2e8f4edb9432.ngrok-free.app/api/track/8/34fd2666-7f61-4fb5-a203-db55748c043e/landing_page_opened?metadata={"page":"/welcome","referrer":"email"}
```

### 6. Thank You Email Received
Track thank you email receipt:
```
GET https://2e8f4edb9432.ngrok-free.app/api/track/8/34fd2666-7f61-4fb5-a203-db55748c043e/thank_you_received
```

### 7. Asset Downloaded
Track asset downloads with metadata:
```
GET https://2e8f4edb9432.ngrok-free.app/api/track/8/34fd2666-7f61-4fb5-a203-db55748c043e/asset_downloaded?metadata={"asset_name":"brochure.pdf","file_size":1024000,"file_type":"pdf"}
```

## How to Use in Email Templates

### Email Open Tracking Pixel
Add this as a 1x1 transparent image at the end of your email HTML:
```html
<img src="{{ config('app.url') }}/api/track/{{ $campaign->id }}/{{ $subscriber->hash }}/email_opened" 
     width="1" height="1" style="display:none;" alt="" />
```

### Click Tracking Links
Wrap your links with the tracking URL:
```html
<a href="{{ config('app.url') }}/api/track/{{ $campaign->id }}/{{ $subscriber->hash }}/email_clicked?metadata={%22url%22:%22https://example.com%22}&redirect=https://example.com">
    Click Here
</a>
```

Or use a redirect approach:
```html
<a href="{{ config('app.url') }}/track/click/{{ $campaign->id }}/{{ $subscriber->hash }}?url=https://example.com">
    Click Here
</a>
```

## Response Format

### Success Response (200)
```json
{
    "success": true,
    "message": "Event tracked successfully",
    "data": {
        "id": 1,
        "campaign_id": 8,
        "subscriber_hash": "34fd2666-7f61-4fb5-a203-db55748c043e",
        "task_type": "email_opened",
        "status": "opened",
        "tracked_at": "2025-11-21T19:45:00Z"
    }
}
```

### Error Response (404)
```json
{
    "success": false,
    "message": "Failed to track event. Invalid campaign, subscriber, or task type."
}
```

## Testing URLs

Replace these values with your actual data:
- `8` = Campaign ID
- `34fd2666-7f61-4fb5-a203-db55748c043e` = Subscriber Hash (from messages table)
- `https://2e8f4edb9432.ngrok-free.app` = Your APP_URL

## Quick Test Commands

```bash
# Test email opened tracking
curl "https://2e8f4edb9432.ngrok-free.app/api/track/8/34fd2666-7f61-4fb5-a203-db55748c043e/email_opened"

# Test with metadata
curl "https://2e8f4edb9432.ngrok-free.app/api/track/8/34fd2666-7f61-4fb5-a203-db55748c043e/asset_downloaded?metadata=%7B%22asset_name%22%3A%22brochure.pdf%22%7D"
```

