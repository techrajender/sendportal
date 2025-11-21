# Comprehensive Campaign Tracking System - Summary

## Overview
A generic tracking system to monitor subscriber actions for each campaign with detailed event tracking.

## Tracking Events (Task Types)
1. **email_sent** - Email sending
2. **email_opened** - Email opening
3. **email_clicked** - Email link click
4. **newsletter_opened** - Newsletter open
5. **landing_page_opened** - Landing page opening
6. **thank_you_received** - Thank you email received
7. **asset_downloaded** - Asset download

## Status Types
- **opened** - Action completed
- **not_opened** - Action not completed
- **pending** - Action pending
- **failed** - Action failed

## Database Structure

### Table: `campaign_subscriber_tracking`
- `id` (primary key)
- `campaign_id` (foreign key to campaigns)
- `subscriber_id` (foreign key to subscribers)
- `subscriber_hash` (string, for tracking via hash)
- `task_type` (enum: email_sent, email_opened, email_clicked, newsletter_opened, landing_page_opened, thank_you_received, asset_downloaded)
- `status` (enum: opened, not_opened, pending, failed)
- `metadata` (JSON, optional additional data)
- `tracked_at` (timestamp)
- `created_at`, `updated_at`

## API Endpoint

### GET `/api/track/{campaignId}/{subscriberHash}/{taskType}`
**Parameters:**
- `campaignId` - Campaign ID
- `subscriberHash` - Subscriber hash ID
- `taskType` - One of the task types above

**Optional Query Parameters:**
- `status` - Override default status (defaults to 'opened')
- `metadata` - JSON string for additional tracking data

**Response:**
- 200 OK with tracking confirmation
- 404 if campaign or subscriber not found
- 400 for invalid task type

## UI Components

### Campaign Tracking Tab
- Location: Campaign detail page → "Tracking" tab
- Shows table of all subscribers with their tracking status
- Columns:
  - Subscriber Email
  - Email Sent
  - Email Opened
  - Email Clicked
  - Newsletter Opened
  - Landing Page Opened
  - Thank You Received
  - Asset Downloaded
  - Last Activity

### Individual Subscriber Tracking View
- Click on subscriber row to see detailed timeline
- Shows all events with timestamps
- Visual indicators for completed/pending actions

## Implementation Steps

1. **Database Migration**
   - Create `campaign_subscriber_tracking` table
   - Add indexes for performance

2. **Model**
   - Create `CampaignSubscriberTracking` model
   - Define relationships with Campaign and Subscriber

3. **Controller**
   - Create `TrackingController` with generic tracking endpoint
   - Add validation for task types and statuses

4. **Routes**
   - Add tracking route
   - Add campaign tracking view route

5. **Views**
   - Create tracking tab view
   - Create subscriber detail tracking view

6. **Service**
   - Create `TrackingService` to handle tracking logic
   - Prevent duplicate tracking (idempotency)

## Features

- **Idempotent Tracking**: Same event won't be tracked twice
- **Real-time Updates**: Tracking happens immediately
- **Metadata Support**: Store additional context (URL clicked, asset name, etc.)
- **Timeline View**: See complete subscriber journey
- **Export**: Export tracking data for analysis

## Example Usage

### Track Email Open
```
GET /api/track/8/abc123hash/email_opened
```

### Track Asset Download with Metadata
```
GET /api/track/8/abc123hash/asset_downloaded?metadata={"asset_name":"brochure.pdf","file_size":1024000}
```

### Track Landing Page Open
```
GET /api/track/8/abc123hash/landing_page_opened
```

