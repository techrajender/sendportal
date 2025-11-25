<?php

namespace App\Services\Content;

use Sendportal\Base\Services\Content\MergeSubjectService as BaseMergeSubjectService;
use Sendportal\Base\Models\Message;

class ExtendedMergeSubjectService extends BaseMergeSubjectService
{
    /**
     * Extend compileTags to include subscriber_hash, message_hash, campaign_hash, and app_url
     */
    protected function compileTags(Message $message): string
    {
        $tags = [
            'email',
            'first_name',
            'last_name',
            'subscriber_hash',
            'message_hash',
            'campaign_hash',
            'app_url'
        ];

        $messageSubject = $message->subject;

        foreach ($tags as $tag) {
            $messageSubject = $this->normalizeTags($messageSubject, $tag);
        }

        return $messageSubject;
    }

    /**
     * Extend mergeSubscriberTags to include subscriber_hash, message_hash, and campaign_hash
     */
    protected function mergeSubscriberTags(string $messageSubject, Message $message): string
    {
        // First merge base tags
        $messageSubject = parent::mergeSubscriberTags($messageSubject, $message);

        // Get campaign if message is for a campaign
        $campaignHash = '';
        if ($message->isCampaign()) {
            // Use source_id directly (simpler and more reliable for subject merge)
            // The campaign repository approach requires workspace_id which might not be available
            if ($message->source_id) {
                $campaignHash = (string) $message->source_id;
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
            $messageSubject = str_ireplace($search, $replace, $messageSubject);
        }

        return $messageSubject;
    }
}

