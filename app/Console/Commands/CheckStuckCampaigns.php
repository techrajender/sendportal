<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\CampaignStatus;
use Sendportal\Base\Models\Message;

class CheckStuckCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:check-stuck {--fix : Automatically fix stuck campaigns}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for campaigns stuck in sending status and optionally fix them';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Checking for stuck campaigns...');
        Log::info('CheckStuckCampaigns command started');

        try {
            // Find campaigns in sending status
            $stuckCampaigns = Campaign::where('status_id', CampaignStatus::STATUS_SENDING)
                ->get();

            if ($stuckCampaigns->isEmpty()) {
                $this->info('No campaigns found in sending status.');
                Log::info('CheckStuckCampaigns: No campaigns in sending status');
                return 0;
            }

            $this->info("Found {$stuckCampaigns->count()} campaign(s) in sending status.");
            Log::info('CheckStuckCampaigns: Found campaigns in sending status', [
                'count' => $stuckCampaigns->count(),
            ]);

        $fixedCount = 0;
        $stillStuckCount = 0;

        foreach ($stuckCampaigns as $campaign) {
            $totalMessages = Message::where('source_id', $campaign->id)
                ->where('source_type', Campaign::class)
                ->count();

            $sentMessages = Message::where('source_id', $campaign->id)
                ->where('source_type', Campaign::class)
                ->whereNotNull('sent_at')
                ->count();

            $this->line("Campaign: {$campaign->name} (ID: {$campaign->id})");
            $this->line("  Total messages: {$totalMessages}, Sent: {$sentMessages}");

            // Check if all messages are sent
            if ($totalMessages > 0 && $sentMessages >= $totalMessages) {
                $this->warn("  → All messages sent but status is still 'sending'. Campaign is stuck!");

                if ($this->option('fix')) {
                    // Only update if still in sending status to avoid race conditions
                    $updated = Campaign::where('id', $campaign->id)
                        ->where('status_id', CampaignStatus::STATUS_SENDING)
                        ->update(['status_id' => CampaignStatus::STATUS_SENT]);

                    if ($updated > 0) {
                        Log::info('Fixed stuck campaign - marked as sent', [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'total_messages' => $totalMessages,
                            'sent_messages' => $sentMessages,
                        ]);

                        $this->info("  ✓ Fixed: Campaign marked as sent.");
                        $fixedCount++;
                    } else {
                        $this->comment("  → Campaign status was already updated by another process.");
                    }
                } else {
                    $stillStuckCount++;
                }
            } elseif ($totalMessages === 0) {
                $this->warn("  → No messages found. Campaign should be marked as sent.");

                if ($this->option('fix')) {
                    // Only update if still in sending status to avoid race conditions
                    $updated = Campaign::where('id', $campaign->id)
                        ->where('status_id', CampaignStatus::STATUS_SENDING)
                        ->update(['status_id' => CampaignStatus::STATUS_SENT]);
                    
                    if ($updated > 0) {

                        Log::info('Fixed stuck campaign with 0 messages - marked as sent', [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                        ]);

                        $this->info("  ✓ Fixed: Campaign marked as sent.");
                        $fixedCount++;
                    } else {
                        $this->comment("  → Campaign status was already updated by another process.");
                    }
                } else {
                    $stillStuckCount++;
                }
            } else {
                $remaining = $totalMessages - $sentMessages;
                $minutesSinceUpdate = $campaign->updated_at ? now()->diffInMinutes($campaign->updated_at) : 0;

                if ($minutesSinceUpdate > 10) {
                    $this->warn("  → Still processing: {$remaining} messages remaining. Last update: {$minutesSinceUpdate} minutes ago.");
                    $stillStuckCount++;
                } else {
                    $this->info("  → Still processing: {$remaining} messages remaining. Last update: {$minutesSinceUpdate} minutes ago. (Not stuck yet)");
                }
            }
        }

            $this->newLine();

            if ($this->option('fix')) {
                $this->info("Fixed {$fixedCount} stuck campaign(s).");
                Log::info('CheckStuckCampaigns: Fixed campaigns', [
                    'fixed_count' => $fixedCount,
                    'still_stuck_count' => $stillStuckCount,
                ]);
                if ($stillStuckCount > 0) {
                    $this->warn("{$stillStuckCount} campaign(s) still need attention.");
                }
            } else {
                $this->info("Found {$stillStuckCount} potentially stuck campaign(s).");
                $this->comment("Run with --fix to automatically fix them.");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error checking stuck campaigns: ' . $e->getMessage());
            Log::error('CheckStuckCampaigns: Error occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}

