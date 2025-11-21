# Simplified Tracking URLs - Using Task Numbers

## Overview
Use simple task numbers (1, 2, 3, etc.) instead of task type names for easier tracking URLs.

## URL Format
```
GET /api/track/{campaignHash}/{subscriberHash}/{taskNumber}
```

## Task Number Mapping

| Number | Task Type | Description |
|--------|-----------|-------------|
| 1 | email_sent | Email sent successfully |
| 2 | email_opened | Email opened by subscriber |
| 3 | email_clicked | Link clicked in email |
| 4 | newsletter_opened | Newsletter opened |
| 5 | landing_page_opened | Landing page visited |
| 6 | thank_you_received | Thank you email received |
| 7 | asset_downloaded | Asset downloaded |

## Example URLs

### Email Opened (Task #2)
```
https://2e8f4edb9432.ngrok-free.app/api/track/3/a793bf00-6132-43d6-b9cd-4534174c0d13/2
```

### Email Clicked (Task #3)
```
https://2e8f4edb9432.ngrok-free.app/api/track/3/a793bf00-6132-43d6-b9cd-4534174c0d13/3
```

### Landing Page Opened (Task #5)
```
https://2e8f4edb9432.ngrok-free.app/api/track/3/a793bf00-6132-43d6-b9cd-4534174c0d13/5
```

### Asset Downloaded (Task #7) with Metadata
```
https://2e8f4edb9432.ngrok-free.app/api/track/3/a793bf00-6132-43d6-b9cd-4534174c0d13/7?metadata={"asset_name":"brochure.pdf"}
```

## How to Use in Email Templates

### Email Open Tracking Pixel
```html
<img src="{{ config('app.url') }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/2" 
     width="1" height="1" style="display:none;" alt="" />
```

### Click Tracking Links
```html
<a href="{{ config('app.url') }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/3?redirect=https://example.com">
    Click Here
</a>
```

### Landing Page Tracking
```html
<script>
    // Track landing page visit
    fetch('{{ config('app.url') }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/5');
</script>
```

## Query Parameters

### Optional Parameters
- `status` - Override default status (default: 'opened')
- `metadata` - JSON string for additional data

### Examples with Parameters
```
# With status
/api/track/3/abc123/2?status=pending

# With metadata
/api/track/3/abc123/7?metadata={"asset_name":"brochure.pdf","file_size":1024000}
```

## Response Format

### Success Response (200)
```json
{
    "success": true,
    "message": "Event tracked successfully",
    "data": {
        "id": 1,
        "campaign_id": 3,
        "subscriber_hash": "a793bf00-6132-43d6-b9cd-4534174c0d13",
        "task_type": "email_opened",
        "task_number": 2,
        "status": "opened",
        "tracked_at": "2025-11-21T19:45:00Z"
    }
}
```

### Error Response (400) - Invalid Task Number
```json
{
    "success": false,
    "message": "Invalid task number. Must be between 1 and 7.",
    "valid_tasks": {
        "1": "email_sent",
        "2": "email_opened",
        "3": "email_clicked",
        "4": "newsletter_opened",
        "5": "landing_page_opened",
        "6": "thank_you_received",
        "7": "asset_downloaded"
    }
}
```

## Quick Reference

Replace these values in your URLs:
- `3` = Campaign Hash (campaign ID)
- `a793bf00-6132-43d6-b9cd-4534174c0d13` = Subscriber Hash
- `2` = Task Number (1-7)
- `https://2e8f4edb9432.ngrok-free.app` = Your APP_URL

## Benefits

✅ **Simpler URLs** - Easy to remember numbers instead of long task type names  
✅ **Shorter** - Less characters in tracking URLs  
✅ **Cleaner** - Numbers are more readable in email templates  
✅ **Flexible** - Still supports metadata and status parameters

