<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sendportal\Base\Models\Campaign;

class CampaignExclusion extends Model
{
    protected $table = 'campaign_exclusions';

    protected $fillable = [
        'campaign_id',
        'excluded_campaign_id',
    ];

    /**
     * Get the campaign that has this exclusion
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /**
     * Get the excluded campaign
     */
    public function excludedCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'excluded_campaign_id');
    }
}
