# Campaign Exclusion Feature

This feature allows you to exclude recipients from a campaign if they have already received emails from selected campaigns. This helps prevent sending duplicate emails to the same recipients when using the same tags.

## How It Works

1. **Multi-Select Dropdown**: In the campaign preview page, you can select multiple campaigns to exclude recipients from.
2. **Exclusion Logic**: Recipients who received emails (have `email_sent` tracking record) in the selected campaigns will be excluded from the current campaign.
3. **Tag-Based**: If the same tag is selected for multiple campaigns, recipients will be excluded to prevent duplicates.

## Implementation

### Files Created/Modified

1. **`app/Services/CampaignExclusionService.php`**: Service to handle exclusion logic
2. **`app/Providers/CampaignViewServiceProvider.php`**: View composer to pass campaigns to preview view
3. **`resources/views/vendor/sendportal/campaigns/preview.blade.php`**: Added multi-select dropdown
4. **`config/app.php`**: Registered `CampaignViewServiceProvider`

### Integration with Campaign Sending

Since the campaign sending logic is in the vendor package (`sendportal/base`), you need to integrate the exclusion logic. Here are the options:

#### Option 1: Override the Campaign Service (Recommended)

Create a custom service that extends the base campaign service and filters subscribers:

```php
// app/Services/Campaigns/ExtendedCampaignService.php
namespace App\Services\Campaigns;

use App\Services\CampaignExclusionService;
use Sendportal\Base\Services\Campaigns\CampaignService as BaseCampaignService;

class ExtendedCampaignService extends BaseCampaignService
{
    protected $exclusionService;

    public function __construct(CampaignExclusionService $exclusionService)
    {
        $this->exclusionService = $exclusionService;
        parent::__construct(...func_get_args());
    }

    // Override the method that gets subscribers for a campaign
    // This is a placeholder - you'll need to find the actual method in the base service
    protected function getSubscribersForCampaign($campaign, $tags = [])
    {
        $subscribers = parent::getSubscribersForCampaign($campaign, $tags);
        
        // Get exclude campaign IDs from request or campaign metadata
        $excludeCampaignIds = request()->input('exclude_campaigns', []);
        
        if (!empty($excludeCampaignIds)) {
            $subscribers = $this->exclusionService->filterSubscribers($subscribers, $excludeCampaignIds);
        }
        
        return $subscribers;
    }
}
```

Then bind it in `AppServiceProvider`:

```php
$this->app->bind(
    \Sendportal\Base\Services\Campaigns\CampaignService::class,
    \App\Services\Campaigns\ExtendedCampaignService::class
);
```

#### Option 2: Use Event Listener

Listen to the campaign sending event and filter subscribers:

```php
// app/Listeners/FilterCampaignSubscribers.php
namespace App\Listeners;

use App\Services\CampaignExclusionService;
use Illuminate\Contracts\Queue\ShouldQueue;

class FilterCampaignSubscribers implements ShouldQueue
{
    protected $exclusionService;

    public function __construct(CampaignExclusionService $exclusionService)
    {
        $this->exclusionService = $exclusionService;
    }

    public function handle($event)
    {
        // Filter subscribers based on exclude_campaigns
        // This is a placeholder - you'll need to find the actual event
    }
}
```

#### Option 3: Store Exclusion in Campaign Metadata

Store the `exclude_campaigns` in the campaign's metadata and filter when querying subscribers:

1. Modify the campaign send controller to store `exclude_campaigns` in campaign metadata
2. When querying subscribers, check the metadata and exclude accordingly

## Usage

1. Go to Campaign Preview page
2. In the "EXCLUDE RECIPIENTS FROM CAMPAIGNS" section, select one or more campaigns
3. Recipients who received emails from selected campaigns will be excluded
4. This works with tags - if the same tag is selected, duplicates are eliminated

## Notes

- Only campaigns that have been sent (`sent_count > 0`) are shown in the dropdown
- The current campaign is automatically excluded from the list
- Exclusion is based on `email_sent` tracking records in the `campaign_subscriber_tracking` table

