# SendPortal - Use Cases, Database Design & Screen Specifications

## Table of Contents
1. [Overview](#overview)
2. [Database Design](#database-design)
3. [Use Cases](#use-cases)
4. [Screen Specifications](#screen-specifications)

---

## Overview

SendPortal is a self-hosted email marketing platform that enables businesses to:
- Manage multiple workspaces/brands
- Create and send email campaigns
- Manage subscriber lists with tags
- Track email performance (opens, clicks)
- Create reusable email templates
- Generate analytics and reports

---

## Database Design

### Core Tables

#### 1. Users & Workspaces

```sql
-- Users table
users
├── id (PK)
├── name
├── email (unique)
├── password (hashed)
├── email_verified_at
├── current_workspace_id (FK → workspaces.id)
├── locale
├── remember_token
├── created_at
└── updated_at

-- Workspaces table
workspaces
├── id (PK)
├── owner_id (FK → users.id)
├── name
├── created_at
└── updated_at

-- Workspace Users (Pivot)
workspace_users
├── workspace_id (FK → workspaces.id)
├── user_id (FK → users.id)
├── role (owner|member)
├── created_at
└── updated_at
```

#### 2. Subscribers & Tags

```sql
-- Subscribers table
sendportal_subscribers
├── id (PK)
├── workspace_id (FK → workspaces.id)
├── email (unique per workspace)
├── first_name
├── last_name
├── hash (unique identifier for tracking)
├── subscribed_at
├── unsubscribed_at
├── created_at
└── updated_at

-- Tags table
sendportal_tags
├── id (PK)
├── workspace_id (FK → workspaces.id)
├── name
├── created_at
└── updated_at

-- Subscriber Tags (Pivot)
sendportal_subscriber_tags
├── subscriber_id (FK → sendportal_subscribers.id)
├── tag_id (FK → sendportal_tags.id)
├── created_at
└── updated_at
```

#### 3. Campaigns

```sql
-- Campaigns table
sendportal_campaigns
├── id (PK)
├── workspace_id (FK → workspaces.id)
├── name
├── subject
├── content (HTML)
├── template_id (FK → sendportal_templates.id, nullable)
├── email_service_id (FK → sendportal_email_services.id)
├── status (draft|scheduled|queued|sending|sent|cancelled)
├── scheduled_at (nullable)
├── sent_at (nullable)
├── open_count (unique opens)
├── click_count (unique clicks)
├── sent_count (total sent)
├── created_at
└── updated_at

-- Campaign Exclusions
campaign_exclusions
├── id (PK)
├── campaign_id (FK → sendportal_campaigns.id)
├── excluded_campaign_id (FK → sendportal_campaigns.id)
├── created_at
└── updated_at
```

#### 4. Messages & Tracking

```sql
-- Messages table
sendportal_messages
├── id (PK)
├── campaign_id (FK → sendportal_campaigns.id)
├── subscriber_id (FK → sendportal_subscribers.id)
├── message_hash (unique identifier)
├── sent_at
├── opened_at (first open)
├── clicked_at (first click)
├── open_count
├── click_count
├── created_at
└── updated_at

-- Campaign Subscriber Tracking
sendportal_campaign_subscriber_tracking
├── id (PK)
├── campaign_id (FK → sendportal_campaigns.id)
├── subscriber_id (FK → sendportal_subscribers.id)
├── subscriber_hash
├── task_type (email_sent|email_opened|email_clicked|newsletter_opened|landing_page_opened|thank_you_received|asset_downloaded)
├── status (opened|not_opened|pending|failed)
├── metadata (JSON - URLs, asset names, etc.)
├── tracked_at
├── created_at
└── updated_at

-- Message URLs (for click tracking)
sendportal_message_urls
├── id (PK)
├── source_type (Campaign|Automation)
├── source_id
├── url
├── hash
├── click_count
├── created_at
└── updated_at
```

#### 5. Templates

```sql
-- Templates table
sendportal_templates
├── id (PK)
├── workspace_id (FK → workspaces.id)
├── name
├── content (HTML)
├── created_at
└── updated_at
```

#### 6. Email Services

```sql
-- Email Services table
sendportal_email_services
├── id (PK)
├── workspace_id (FK → workspaces.id)
├── name
├── type_id (FK → sendportal_email_service_types.id)
├── settings (JSON - API keys, etc.)
├── created_at
└── updated_at
```

---

## Use Cases

### UC-1: User Registration & Workspace Setup

**Actor:** New User  
**Goal:** Create account and set up first workspace

**Preconditions:**
- User has access to the application URL
- Registration is enabled

**Main Flow:**
1. User navigates to registration page
2. User enters name, email, password
3. System validates input
4. System creates user account
5. System sends verification email
6. User clicks verification link
7. System creates default workspace
8. User is redirected to dashboard

**Postconditions:**
- User account created and verified
- Default workspace created
- User is logged in

**Database Changes:**
- Insert into `users`
- Insert into `workspaces` (owner_id = user.id)
- Insert into `workspace_users` (role = 'owner')

---

### UC-2: Create Email Campaign

**Actor:** Marketing Manager  
**Goal:** Create a new email campaign

**Preconditions:**
- User is logged in
- User has workspace access
- At least one email service configured

**Main Flow:**
1. User navigates to Campaigns → Create Campaign
2. User enters campaign name
3. User selects email template (optional)
4. User enters email subject
5. User edits email content (HTML editor)
6. User selects email service
7. User selects recipient tags
8. User sets campaign exclusions (optional)
9. User saves as draft or schedules
10. System validates campaign data
11. System creates campaign record

**Postconditions:**
- Campaign created with status 'draft' or 'scheduled'
- Campaign available in campaigns list

**Database Changes:**
- Insert into `sendportal_campaigns`
- Insert into `campaign_exclusions` (if exclusions selected)

---

### UC-3: Send Campaign

**Actor:** Marketing Manager  
**Goal:** Send email campaign to subscribers

**Preconditions:**
- Campaign exists in 'draft' or 'scheduled' status
- Campaign has recipients (tags selected)
- Email service is configured

**Main Flow:**
1. User navigates to Campaign Preview
2. User reviews campaign details
3. User clicks "Send Campaign"
4. System validates campaign
5. System filters subscribers based on tags
6. System applies campaign exclusions
7. System creates message records for each subscriber
8. System queues messages for sending
9. System updates campaign status to 'queued'
10. Queue workers process messages
11. System sends emails via email service
12. System tracks email_sent events
13. System updates campaign status to 'sending' then 'sent'

**Postconditions:**
- Campaign status = 'sent'
- Messages created for all recipients
- Emails queued and sent
- Tracking initialized

**Database Changes:**
- Update `sendportal_campaigns` (status, sent_at, sent_count)
- Insert into `sendportal_messages` (one per subscriber)
- Insert into `sendportal_campaign_subscriber_tracking` (task_type = 'email_sent')

---

### UC-4: Track Email Opens

**Actor:** System (Automatic)  
**Goal:** Track when subscriber opens email

**Preconditions:**
- Email has been sent
- Email contains tracking pixel

**Main Flow:**
1. Subscriber opens email
2. Email client loads tracking pixel image
3. Browser requests tracking URL: `/api/track/{campaignHash}/{subscriberHash}/2`
4. System validates hashes
5. System checks if event already tracked (idempotent)
6. System creates/updates tracking record
7. System updates message opened_at and open_count
8. System updates campaign open_count (unique)
9. System returns 1x1 transparent pixel

**Postconditions:**
- Tracking record created
- Message statistics updated
- Campaign statistics updated

**Database Changes:**
- Insert/Update `sendportal_campaign_subscriber_tracking` (task_type = 'email_opened')
- Update `sendportal_messages` (opened_at, open_count++)
- Update `sendportal_campaigns` (open_count - unique count)

---

### UC-5: Track Email Clicks

**Actor:** System (Automatic)  
**Goal:** Track when subscriber clicks link in email

**Preconditions:**
- Email has been sent
- Email contains tracked links

**Main Flow:**
1. Subscriber clicks link in email
2. Browser requests tracking URL: `/api/track/{campaignHash}/{subscriberHash}/3?redirect={destinationURL}`
3. System validates hashes
4. System checks if event already tracked
5. System creates/updates tracking record
6. System updates message clicked_at and click_count
7. System updates campaign click_count (unique)
8. System updates message_urls click_count
9. System redirects to destination URL

**Postconditions:**
- Tracking record created
- Message statistics updated
- Campaign statistics updated
- URL click count updated
- Subscriber redirected to destination

**Database Changes:**
- Insert/Update `sendportal_campaign_subscriber_tracking` (task_type = 'email_clicked', metadata = URL)
- Update `sendportal_messages` (clicked_at, click_count++)
- Update `sendportal_campaigns` (click_count - unique count)
- Update `sendportal_message_urls` (click_count++)

---

### UC-6: Manage Subscribers

**Actor:** Marketing Manager  
**Goal:** Add, edit, or remove subscribers

**Preconditions:**
- User is logged in
- User has workspace access

**Main Flow:**
1. User navigates to Subscribers
2. User clicks "Add Subscriber"
3. User enters email, first name, last name
4. User selects tags (optional)
5. System validates email (unique per workspace)
6. System creates subscriber
7. System assigns tags
8. System generates subscriber hash

**Postconditions:**
- Subscriber added to workspace
- Tags assigned (if selected)
- Subscriber available for campaigns

**Database Changes:**
- Insert into `sendportal_subscribers`
- Insert into `sendportal_subscriber_tags` (if tags selected)

---

### UC-7: Import Subscribers

**Actor:** Marketing Manager  
**Goal:** Bulk import subscribers from CSV

**Preconditions:**
- User is logged in
- CSV file prepared with email, first_name, last_name columns

**Main Flow:**
1. User navigates to Subscribers → Import
2. User uploads CSV file
3. System validates CSV format
4. System parses CSV rows
5. System validates each email
6. System creates subscribers (skip duplicates)
7. System assigns default tags (if specified)
8. System shows import summary

**Postconditions:**
- Subscribers imported
- Duplicates skipped
- Import summary displayed

**Database Changes:**
- Bulk insert into `sendportal_subscribers`
- Bulk insert into `sendportal_subscriber_tags`

---

### UC-8: Create Email Template

**Actor:** Marketing Manager  
**Goal:** Create reusable email template

**Preconditions:**
- User is logged in
- User has workspace access

**Main Flow:**
1. User navigates to Templates → Create Template
2. User enters template name
3. User designs HTML content (WYSIWYG editor)
4. User adds merge tags ({{first_name}}, {{email}}, etc.)
5. User saves template
6. System validates template
7. System creates template record

**Postconditions:**
- Template created
- Template available for campaigns

**Database Changes:**
- Insert into `sendportal_templates`

---

### UC-9: View Campaign Reports

**Actor:** Marketing Manager  
**Goal:** View campaign performance metrics

**Preconditions:**
- Campaign has been sent
- Tracking data exists

**Main Flow:**
1. User navigates to Campaigns
2. User clicks on sent campaign
3. User navigates to Reports tab
4. System loads campaign statistics
5. System displays:
   - Total sent
   - Unique opens (count and percentage)
   - Unique clicks (count and percentage)
   - Open rate
   - Click rate
   - Top clicked links
   - Opens over time (chart)
   - Clicks over time (chart)

**Postconditions:**
- Campaign statistics displayed
- Charts and graphs rendered

**Database Queries:**
- Select from `sendportal_campaigns` (statistics)
- Select from `sendportal_campaign_subscriber_tracking` (detailed events)
- Select from `sendportal_message_urls` (top links)
- Aggregate queries for time-series data

---

### UC-10: Configure Campaign Exclusions

**Actor:** Marketing Manager  
**Goal:** Exclude recipients who received previous campaigns

**Preconditions:**
- Multiple campaigns exist
- Campaigns have been sent

**Main Flow:**
1. User creates new campaign
2. User navigates to Campaign Preview
3. User sees "Exclude Recipients" section
4. User selects campaigns to exclude from
5. System shows estimated recipient count after exclusions
6. User saves campaign
7. When campaign is sent, system filters out subscribers who received emails from excluded campaigns

**Postconditions:**
- Exclusions configured
- Recipients filtered during send

**Database Changes:**
- Insert into `campaign_exclusions`
- Filter query: Exclude subscribers who have `email_sent` tracking for excluded campaigns

---

### UC-11: Manage Tags

**Actor:** Marketing Manager  
**Goal:** Create and manage subscriber tags for segmentation

**Preconditions:**
- User is logged in
- User has workspace access

**Main Flow:**
1. User navigates to Tags
2. User clicks "Create Tag"
3. User enters tag name
4. System validates (unique per workspace)
5. System creates tag
6. User can assign tags to subscribers
7. User can use tags to segment campaign recipients

**Postconditions:**
- Tag created
- Tag available for assignment and segmentation

**Database Changes:**
- Insert into `sendportal_tags`

---

### UC-12: Switch Workspace

**Actor:** User  
**Goal:** Switch between multiple workspaces

**Preconditions:**
- User belongs to multiple workspaces
- User is logged in

**Main Flow:**
1. User clicks workspace switcher in header
2. System displays list of user's workspaces
3. User selects workspace
4. System updates user's current_workspace_id
5. System redirects to dashboard
6. All data filtered by new workspace

**Postconditions:**
- Workspace switched
- Data filtered by new workspace

**Database Changes:**
- Update `users.current_workspace_id`

---

### UC-13: Invite Team Members

**Actor:** Workspace Owner  
**Goal:** Invite users to join workspace

**Preconditions:**
- User is workspace owner
- User is logged in

**Main Flow:**
1. User navigates to Workspace Settings → Team
2. User clicks "Invite Member"
3. User enters email address
4. System sends invitation email
5. Invited user clicks invitation link
6. If new user: User creates account
7. If existing user: User accepts invitation
8. System adds user to workspace_users
9. User can now access workspace

**Postconditions:**
- Invitation sent
- User added to workspace

**Database Changes:**
- Insert into `invitations`
- Insert into `workspace_users` (when accepted)

---

### UC-14: Configure Email Service

**Actor:** Workspace Owner  
**Goal:** Connect email service provider (SendGrid, Mailgun, etc.)

**Preconditions:**
- User is workspace owner
- User has API credentials from email service

**Main Flow:**
1. User navigates to Settings → Email Services
2. User clicks "Add Email Service"
3. User selects service type (SendGrid, Mailgun, SES, etc.)
4. User enters service name
5. User enters API credentials
6. System validates credentials
7. System saves service configuration
8. Service available for campaigns

**Postconditions:**
- Email service configured
- Service available in campaign creation

**Database Changes:**
- Insert into `sendportal_email_services`

---

### UC-15: View Dashboard Analytics

**Actor:** Marketing Manager  
**Goal:** View overall email marketing performance

**Preconditions:**
- User is logged in
- Campaigns have been sent

**Main Flow:**
1. User navigates to Dashboard
2. System loads workspace statistics:
   - Total subscribers
   - Total campaigns
   - Total emails sent
   - Average open rate
   - Average click rate
   - Recent campaigns
   - Subscriber growth chart
   - Campaign performance chart
3. System displays widgets and charts

**Postconditions:**
- Dashboard displayed with analytics

**Database Queries:**
- Aggregate queries across campaigns, subscribers, messages
- Time-series queries for charts

---

## Screen Specifications

### Screen 1: Login Page

**Route:** `/login`  
**File:** `resources/views/auth/login.blade.php`

**Layout:**
```
┌─────────────────────────────────────┐
│         SendPortal Logo              │
│                                     │
│  ┌─────────────────────────────┐   │
│  │         LOGIN                │   │
│  ├─────────────────────────────┤   │
│  │ Email: [____________]       │   │
│  │                             │   │
│  │ Password: [____________]    │   │
│  │                             │   │
│  │ [ ] Remember Me              │   │
│  │                             │   │
│  │ [    Login Button    ]      │   │
│  │                             │   │
│  │ Forgot Password?             │   │
│  └─────────────────────────────┘   │
│                                     │
│  Don't have account? Register       │
└─────────────────────────────────────┘
```

**Fields:**
- Email (required, email validation)
- Password (required, min 8 chars)
- Remember Me (checkbox)

**Actions:**
- Login button → POST `/login`
- Forgot Password link → `/password/reset`
- Register link → `/register`

---

### Screen 2: Dashboard

**Route:** `/`  
**File:** `resources/views/vendor/sendportal/dashboard/index.blade.php`

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│ [Logo] SendPortal    [Workspace Switcher ▼] [User Menu ▼]  │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │          │  │          │  │          │  │          │  │
│  │  Total   │  │  Total   │  │  Avg     │  │  Avg     │  │
│  │Subscribers│  │Campaigns│  │Open Rate │  │Click Rate│  │
│  │   1,234  │  │   45     │  │  25.3%   │  │  3.2%    │  │
│  │          │  │          │  │          │  │          │  │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Recent Campaigns                    [+ New Campaign]│  │
│  ├──────────────────────────────────────────────────────┤  │
│  │ Name          Status    Sent    Opens   Clicks  Date │  │
│  │ Newsletter #1  Sent     1,200   304     38    Dec 1 │  │
│  │ Product Launch Sent     800     210     25    Nov 28│  │
│  │ Holiday Sale  Scheduled 0       0       0     Dec 5 │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌──────────────────────┐  ┌──────────────────────┐      │
│  │  Subscriber Growth   │  │  Campaign Performance │      │
│  │     [Line Chart]     │  │     [Bar Chart]      │      │
│  └──────────────────────┘  └──────────────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

**Components:**
- Stats cards (4 widgets)
- Recent campaigns table
- Subscriber growth chart
- Campaign performance chart

**Data Sources:**
- `sendportal_subscribers` (count)
- `sendportal_campaigns` (count, stats)
- `sendportal_messages` (aggregates)

---

### Screen 3: Campaigns List

**Route:** `/campaigns`  
**File:** `resources/views/vendor/sendportal/campaigns/index.blade.php`

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│ Campaigns                                    [+ New Campaign]│
├─────────────────────────────────────────────────────────────┤
│ [All] [Draft] [Scheduled] [Sent] [Cancelled]                │
│                                                              │
│ ┌────────────────────────────────────────────────────────┐  │
│ │ ☐ Newsletter #1        Draft     0 sent    [Edit] [⋯] │  │
│ │ ☐ Product Launch       Sent      1,200     [View] [⋯] │  │
│ │ ☐ Holiday Sale         Scheduled 0         [Edit] [⋯] │  │
│ │ ☐ Black Friday         Sent      2,500     [View] [⋯] │  │
│ └────────────────────────────────────────────────────────┘  │
│                                                              │
│ Showing 1-10 of 45 campaigns                                │
│ [< Previous] [1] [2] [3] [Next >]                          │
└─────────────────────────────────────────────────────────────┘
```

**Filters:**
- Status filter tabs
- Search by name
- Sort by date, status, sent count

**Actions:**
- Create Campaign → `/campaigns/create`
- Edit → `/campaigns/{id}/edit`
- View → `/campaigns/{id}`
- Delete (dropdown menu)

---

### Screen 4: Create/Edit Campaign

**Route:** `/campaigns/create` or `/campaigns/{id}/edit`  
**File:** `resources/views/vendor/sendportal/campaigns/create.blade.php`

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│ Create Campaign                              [Save Draft] [→]│
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ Campaign Name: [_____________________________]             │
│                                                              │
│ Email Service: [SendGrid ▼]                                │
│                                                              │
│ Template: [Select Template ▼] [Preview] [Create New]       │
│                                                              │
│ Subject: [_____________________________]                     │
│                                                              │
│ Content:                                                     │
│ ┌────────────────────────────────────────────────────────┐ │
│ │ [B] [I] [U] [Link] [Image] [Merge Tags ▼]             │ │
│ ├────────────────────────────────────────────────────────┤ │
│ │                                                        │ │
│ │  [WYSIWYG Editor - HTML Content]                      │ │
│ │                                                        │ │
│ │  Available merge tags:                                │ │
│ │  {{first_name}} {{last_name}} {{email}}               │ │
│ │  {{subscriber_hash}} {{message_hash}}                 │ │
│ │                                                        │ │
│ └────────────────────────────────────────────────────────┘ │
│                                                              │
│ Recipients:                                                  │
│ [ ] Send to all subscribers                                 │
│ [✓] Send to specific tags                                    │
│     [Tag 1] [Tag 2] [Tag 3] [+ Add Tag]                    │
│                                                              │
│ Exclude Recipients:                                          │
│ [Select campaigns to exclude ▼] (Multi-select)             │
│     [Campaign A] [Campaign B]                               │
│                                                              │
│ Schedule:                                                    │
│ [ ] Send immediately                                         │
│ [✓] Schedule for later                                       │
│     Date: [12/05/2025] Time: [10:00 AM]                    │
│                                                              │
│ Estimated Recipients: 1,234                                  │
└─────────────────────────────────────────────────────────────┘
```

**Fields:**
- Campaign Name (required)
- Email Service (required, dropdown)
- Template (optional, dropdown)
- Subject (required)
- Content (required, HTML editor)
- Recipients (radio: all or tags)
- Tags (multi-select)
- Exclude Campaigns (multi-select)
- Schedule (radio: now or later)
- Scheduled Date/Time (if scheduled)

**Actions:**
- Save Draft → Status = 'draft'
- Preview → Modal with email preview
- Next → Navigate to preview page

---

### Screen 5: Campaign Preview

**Route:** `/campaigns/{id}/preview`  
**File:** `resources/views/vendor/sendportal/campaigns/preview.blade.php`

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│ Preview Campaign: Newsletter #1                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ Campaign Details:                                            │
│ • Name: Newsletter #1                                        │
│ • Subject: Welcome to our newsletter                       │
│ • Recipients: 1,234 subscribers (Tag: Newsletter)          │
│ • Excluded: Campaign A (prevents duplicates)                │
│                                                              │
│ ┌────────────────────────────────────────────────────────┐  │
│ │ Email Preview:                                          │  │
│ │ ┌──────────────────────────────────────────────────┐  │  │
│ │ │ From: noreply@example.com                         │  │  │
│ │ │ To: john@example.com                             │  │  │
│ │ │ Subject: Welcome to our newsletter               │  │  │
│ │ ├──────────────────────────────────────────────────┤  │  │
│ │ │                                                  │  │  │
│ │ │  Hello {{first_name}},                          │  │  │
│ │ │                                                  │  │  │
│ │ │  [Email Content Preview]                        │  │  │
│ │ │                                                  │  │  │
│ │ └──────────────────────────────────────────────────┘  │  │
│ └────────────────────────────────────────────────────────┘  │
│                                                              │
│ [← Back] [Edit] [Send Campaign] [Schedule]                │
└─────────────────────────────────────────────────────────────┘
```

**Actions:**
- Back → Return to edit
- Edit → `/campaigns/{id}/edit`
- Send Campaign → Immediate send
- Schedule → Set scheduled_at and status = 'scheduled'

---

### Screen 6: Campaign Reports

**Route:** `/campaigns/{id}/reports`  
**File:** `resources/views/vendor/sendportal/campaigns/reports/index.blade.php`

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│ Campaign: Newsletter #1 - Reports                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐     │
│ │  Sent    │  │  Opens   │  │  Clicks  │  │  Bounces │     │
│ │  1,234   │  │   312    │  │   45     │  │    12    │     │
│ │          │  │  25.3%   │  │  3.7%    │  │  0.97%   │     │
│ └──────────┘  └──────────┘  └──────────┘  └──────────┘     │
│                                                              │
│ ┌────────────────────────────────────────────────────────┐ │
│ │ Opens Over Time              [Last 7 days ▼]            │ │
│ │ [Line Chart - Opens by Date]                            │ │
│ └────────────────────────────────────────────────────────┘ │
│                                                              │
│ ┌────────────────────────────────────────────────────────┐ │
│ │ Clicks Over Time               [Last 7 days ▼]         │ │
│ │ [Line Chart - Clicks by Date]                          │ │
│ └────────────────────────────────────────────────────────┘ │
│                                                              │
│ Top Clicked Links:                                          │
│ 1. https://example.com/product    23 clicks                │
│ 2. https://example.com/sale       15 clicks                │
│ 3. https://example.com/about       7 clicks                │
│                                                              │
│ [Export Report] [View Recipients]                          │
└─────────────────────────────────────────────────────────────┘
```

**Charts:**
- Opens over time (line chart)
- Clicks over time (line chart)
- Top clicked links (list)

**Data:**
- Aggregated from `sendportal_campaign_subscriber_tracking`
- Grouped by date for time-series

---

### Screen 7: Subscribers List

**Route:** `/subscribers`  
**File:** `resources/views/vendor/sendportal/subscribers/index.blade.php`

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│ Subscribers                    [+ Add] [Import] [Export]   │
├─────────────────────────────────────────────────────────────┤
│ [All Tags ▼] [Search: ________]                            │
│                                                              │
│ ┌────────────────────────────────────────────────────────┐ │
│ │ ☐ Email              Name        Tags        Subscribed│ │
│ │ ☐ john@example.com  John Doe    [Newsletter] Dec 1   │ │
│ │ ☐ jane@example.com   Jane Smith  [VIP] [News] Nov 28  │ │
│ │ ☐ bob@example.com    Bob Wilson   [Newsletter] Nov 25  │ │
│ └────────────────────────────────────────────────────────┘ │
│                                                              │
│ Showing 1-25 of 1,234 subscribers                           │
│ [< Previous] [1] [2] [3] [Next >]                           │
└─────────────────────────────────────────────────────────────┘
```

**Filters:**
- Tag filter (dropdown)
- Search by email/name
- Sort by name, email, subscribed date

**Actions:**
- Add Subscriber → Modal form
- Import → CSV upload
- Export → Download CSV
- Edit → Modal form
- Delete → Confirmation

---

### Screen 8: Tags Management

**Route:** `/tags`  
**File:** `resources/views/vendor/sendportal/tags/index.blade.php`

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│ Tags                                        [+ Create Tag]  │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ ┌────────────────────────────────────────────────────────┐ │
│ │ Newsletter                   1,234 subscribers  [Edit] │ │
│ │ VIP                          456 subscribers   [Edit]  │ │
│ │ Newsletter - Weekly          789 subscribers   [Edit]  │ │
│ │ Promotions                   234 subscribers   [Edit]  │ │
│ └────────────────────────────────────────────────────────┘ │
│                                                              │
│ Showing 4 tags                                                │
└─────────────────────────────────────────────────────────────┘
```

**Actions:**
- Create Tag → Modal form
- Edit Tag → Modal form
- Delete Tag → Confirmation (if no subscribers)

---

### Screen 9: Templates List

**Route:** `/templates`  
**File:** `resources/views/vendor/sendportal/templates/index.blade.php`

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│ Templates                              [+ Create Template] │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ ┌────────────────────────────────────────────────────────┐ │
│ │ Newsletter Template              [Preview] [Edit] [⋯]  │ │
│ │ [Thumbnail Preview]                                     │ │
│ ├────────────────────────────────────────────────────────┤ │
│ │ Product Launch Template         [Preview] [Edit] [⋯]   │ │
│ │ [Thumbnail Preview]                                     │ │
│ └────────────────────────────────────────────────────────┘ │
│                                                              │
│ Showing 1-10 of 15 templates                                 │
└─────────────────────────────────────────────────────────────┘
```

**Actions:**
- Create Template → `/templates/create`
- Preview → Modal
- Edit → `/templates/{id}/edit`
- Clone → Create copy
- Delete → Confirmation

---

### Screen 10: Template Editor

**Route:** `/templates/create` or `/templates/{id}/edit`  
**File:** `resources/views/vendor/sendportal/templates/partials/editor.blade.php`

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│ Create Template                              [Save] [Preview]│
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ Template Name: [_____________________________]              │
│                                                              │
│ Content:                                                     │
│ ┌────────────────────────────────────────────────────────┐ │
│ │ [B] [I] [U] [Link] [Image] [Merge Tags ▼]             │ │
│ ├────────────────────────────────────────────────────────┤ │
│ │                                                        │ │
│ │  [WYSIWYG Editor - HTML]                              │ │
│ │                                                        │ │
│ │  Available merge tags:                                │ │
│ │  {{first_name}} {{last_name}} {{email}}               │ │
│ │                                                        │ │
│ └────────────────────────────────────────────────────────┘ │
│                                                              │
│ [Preview] [Save] [Cancel]                                   │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- WYSIWYG HTML editor
- Merge tags dropdown
- Live preview
- Responsive design preview

---

## API Endpoints

### Tracking API

**Endpoint:** `GET /api/track/{campaignHash}/{subscriberHash}/{taskNumber}`

**Parameters:**
- `campaignHash` - Campaign identifier
- `subscriberHash` - Subscriber hash
- `taskNumber` - Task type (1=sent, 2=opened, 3=clicked, etc.)
- `redirect` (optional) - For clicks, destination URL
- `metadata` (optional) - JSON string for additional data

**Response:**
```json
{
  "success": true,
  "msg": "tracked"
}
```

**Use Cases:**
- UC-4: Track Email Opens
- UC-5: Track Email Clicks

---

## Database Relationships Diagram

```
workspaces
  ├── users (many-to-many via workspace_users)
  ├── sendportal_subscribers
  ├── sendportal_campaigns
  ├── sendportal_tags
  └── sendportal_templates

sendportal_campaigns
  ├── sendportal_messages
  ├── sendportal_campaign_subscriber_tracking
  ├── campaign_exclusions (self-referential)
  └── sendportal_templates

sendportal_subscribers
  ├── sendportal_subscriber_tags (many-to-many)
  ├── sendportal_messages
  └── sendportal_campaign_subscriber_tracking

sendportal_tags
  └── sendportal_subscriber_tags (many-to-many)
```

---

## Implementation Prompts

### Prompt 1: Implement Campaign Creation Screen

```
Create a Laravel Livewire component for campaign creation that includes:
- Form fields: name, subject, content (WYSIWYG editor), email service selection
- Template selector dropdown with preview
- Recipient selection: radio buttons for "all subscribers" or "specific tags" with multi-select tag dropdown
- Campaign exclusion multi-select dropdown showing previous campaigns
- Schedule option: immediate or scheduled with date/time picker
- Estimated recipient count display that updates based on tag selection and exclusions
- Save as draft and preview buttons
- Form validation for all required fields
- Use SendPortal base models and services
```

### Prompt 2: Implement Email Tracking System

```
Create an API endpoint for email tracking that:
- Accepts GET requests to /api/track/{campaignHash}/{subscriberHash}/{taskNumber}
- Validates campaign and subscriber hashes
- Creates idempotent tracking records (prevents duplicate tracking)
- Updates message statistics (opened_at, clicked_at, counts)
- Updates campaign statistics (unique open/click counts)
- For click tracking, handles redirect parameter and updates message_urls
- Returns proper CORS headers to prevent ORB errors
- Returns 1x1 transparent pixel for opens
- Returns 302 redirect for clicks
- Use the sendportal_campaign_subscriber_tracking table
```

### Prompt 3: Implement Campaign Reports Dashboard

```
Create a campaign reports page that displays:
- Statistics cards: sent count, unique opens, unique clicks, open rate, click rate
- Time-series line charts for opens and clicks over time (last 7/30/90 days)
- Top clicked links table with click counts
- Recipient list with individual open/click status
- Export functionality (CSV)
- Real-time data updates
- Use Laravel queries with proper aggregation
- Use Chart.js or similar for visualizations
```

### Prompt 4: Implement Subscriber Import

```
Create a subscriber import feature that:
- Accepts CSV file upload
- Validates CSV format (email, first_name, last_name columns)
- Parses and validates each row
- Skips duplicate emails (per workspace)
- Creates subscribers in bulk
- Assigns default tags if specified
- Shows import progress
- Displays import summary (successful, failed, duplicates)
- Handles large files efficiently
- Provides error reporting for invalid rows
```

### Prompt 5: Implement Campaign Exclusion Logic

```
Create a service that filters campaign recipients based on exclusions:
- When sending a campaign, check campaign_exclusions table
- For each excluded campaign, find subscribers who received emails (have email_sent tracking)
- Remove those subscribers from the recipient list
- Show estimated recipient count before sending
- Log excluded count in campaign metadata
- Integrate with campaign dispatch service
- Ensure exclusions work with tag-based recipient selection
```

---

This document provides a comprehensive foundation for understanding and implementing SendPortal's features, database structure, and user interface.

