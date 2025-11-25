<?php

namespace App\Services\Content;

use Sendportal\Base\Services\Content\MergeContentService as BaseMergeContentService;
use Sendportal\Base\Models\Message;

class ExtendedMergeContentService extends BaseMergeContentService
{
    /**
     * Extend compileTags to include subscriber_hash, message_hash, campaign_hash, and app_url
     */
    protected function compileTags(string $content): string
    {
        $tags = [
            'email',
            'first_name',
            'last_name',
            'unsubscribe_url',
            'webview_url',
            'subscriber_hash',
            'message_hash',
            'campaign_hash',
            'app_url'
        ];

        foreach ($tags as $tag) {
            $content = $this->normalizeTags($content, $tag);
        }

        return $content;
    }

    /**
     * Extend mergeSubscriberTags to include subscriber_hash, message_hash, and campaign_hash
     */
    protected function mergeSubscriberTags(string $content, Message $message): string
    {
        // First merge base tags
        $content = parent::mergeSubscriberTags($content, $message);

        // Get campaign if message is for a campaign
        $campaignHash = '';
        if ($message->isCampaign()) {
            // Use the same approach as base service - get campaign from repository
            try {
                $campaign = $this->campaignRepo->find($message->workspace_id, $message->source_id);
                if ($campaign) {
                    // Use campaign ID as hash (campaigns don't have a hash field)
                    $campaignHash = (string) $campaign->id;
                }
            } catch (\Exception $e) {
                // If campaign not found, campaign_hash will be empty
                \Illuminate\Support\Facades\Log::debug('Could not load campaign for campaign_hash', [
                    'message_id' => $message->id,
                    'source_id' => $message->source_id,
                    'workspace_id' => $message->workspace_id
                ]);
            }
        }

        // Add custom tags
        $tags = [
            'subscriber_hash' => optional($message->subscriber)->hash ?? '',
            'message_hash' => $message->hash ?? '',
            'campaign_hash' => $campaignHash,
            'app_url' => config('app.url', '')
        ];

        foreach ($tags as $key => $replace) {
            // Replace all variations: {{tag}}, {{ tag }}, {{tag }}, {{ tag}}
            $search = [
                '{{' . $key . '}}',
                '{{ ' . $key . ' }}',
                '{{' . $key . ' }}',
                '{{ ' . $key . '}}',
            ];
            $content = str_ireplace($search, $replace, $content);
        }

        return $content;
    }
}

