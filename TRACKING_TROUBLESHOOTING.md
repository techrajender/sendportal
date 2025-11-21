# Email Tracking Troubleshooting Guide

## Common Issues and Solutions

### Issue: Email Opens Not Being Tracked

#### 1. Check if Tracking Records Are Being Created
```bash
php artisan tinker
```
```php
\App\Models\CampaignSubscriberTracking::where('campaign_id', YOUR_CAMPAIGN_ID)
    ->where('task_type', 'email_opened')
    ->orderBy('tracked_at', 'desc')
    ->get();
```

#### 2. Email Clients Block Tracking Pixels
Many email clients block tracking pixels by default:
- **Gmail**: Blocks images by default (user must click "Display images")
- **Outlook**: Blocks external images
- **Apple Mail**: Blocks tracking pixels
- **Yahoo Mail**: Blocks images by default

**Solution**: This is expected behavior. Tracking only works when:
- User enables images in their email client
- User clicks "Display images" or "Load images"
- Email client doesn't block tracking pixels

#### 3. Check Tracking URL Format
The URL should be:
```
https://YOUR_APP_URL/api/track/{campaignHash}/{subscriberHash}/{taskNumber}
```

Example:
```
https://2e8f4edb9432.ngrok-free.app/api/track/20/a793bf00-6132-43d6-b9cd-4534174c0d13/2
```

#### 4. Verify Endpoint is Accessible
Test the URL directly in a browser:
```bash
curl -I "https://YOUR_APP_URL/api/track/20/a793bf00-6132-43d6-b9cd-4534174c0d13/2"
```

Should return:
- HTTP 200 OK
- Content-Type: image/gif

#### 5. Check Application Logs
```bash
tail -f storage/logs/laravel.log | grep -i "track"
```

Look for:
- "Tracking pixel accessed" - Endpoint was called
- "Tracking successful" - Record was created
- "Tracking failed" - There was an error

#### 6. Verify Merge Tags in Email
Make sure you're using merge tags correctly:
```html
<img src="{{ app_url }}/api/track/{{ campaign_hash }}/{{ subscriber_hash }}/2" 
     width="1" height="1" style="display:none;" alt="" />
```

**NOT:**
```html
<img src="{{ config('app.url') }}/api/track/..." />
```

#### 7. Check if Email Was Sent After Fix
If you sent the email before implementing the fix, the tracking pixel might not be in the email. Send a new test email.

## Testing Tracking

### Test Tracking Endpoint Directly
```bash
curl "https://YOUR_APP_URL/api/track/20/a793bf00-6132-43d6-b9cd-4534174c0d13/2"
```

Should return a 1x1 transparent GIF image.

### Check Tracking Records
```php
// In tinker
$tracking = \App\Models\CampaignSubscriberTracking::where('campaign_id', 20)
    ->where('subscriber_hash', 'a793bf00-6132-43d6-b9cd-4534174c0d13')
    ->where('task_type', 'email_opened')
    ->latest('tracked_at')
    ->first();

if ($tracking) {
    echo "Last opened: " . $tracking->tracked_at->diffForHumans();
} else {
    echo "No opens tracked yet";
}
```

## Expected Behavior

1. **Email Sent**: Automatically tracked when email is sent
2. **Email Opened**: Tracked when user opens email AND images are enabled
3. **Email Clicked**: Tracked when user clicks a tracked link
4. **Other Events**: Tracked when specific actions occur

## Important Notes

- **Tracking pixels only work if images are enabled** in the email client
- **Many email clients block tracking by default** for privacy
- **Tracking is not 100% accurate** - some opens/clicks may not be tracked
- **The endpoint returns a 1x1 transparent GIF** - this is correct behavior

## Debugging Steps

1. ✅ Check if endpoint is accessible (curl test)
2. ✅ Check if tracking records are being created
3. ✅ Verify merge tags are correct in email template
4. ✅ Check application logs for errors
5. ✅ Test with a new email (not an old one)
6. ✅ Verify subscriber and campaign exist
7. ✅ Check if email client blocks images

## Contact

If tracking still doesn't work after following these steps, check:
- Application logs: `storage/logs/laravel.log`
- Database records: `sendportal_campaign_subscriber_tracking` table
- Route accessibility: `php artisan route:list --path=api/track`

