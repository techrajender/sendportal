<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\Subscriber;

class CampaignSubscriberTracking extends Model
{
    protected $table = 'sendportal_campaign_subscriber_tracking';

    protected $fillable = [
        'campaign_id',
        'subscriber_id',
        'subscriber_hash',
        'task_type',
        'status',
        'metadata',
        'tracked_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tracked_at' => 'datetime',
    ];

    /**
     * Get the campaign that owns this tracking record.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /**
     * Get the subscriber that owns this tracking record.
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class, 'subscriber_id');
    }
}
