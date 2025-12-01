<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sendportal_campaign_subscriber_tracking', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('campaign_id')->index();
            $table->unsignedInteger('subscriber_id')->index();
            $table->string('subscriber_hash', 255)->index();
            $table->enum('task_type', [
                'email_sent',
                'email_opened',
                'email_clicked',
                'newsletter_opened',
                'landing_page_opened',
                'thank_you_received',
                'asset_downloaded'
            ]);
            $table->enum('status', [
                'opened',
                'not_opened',
                'pending',
                'failed'
            ])->default('opened');
            $table->json('metadata')->nullable();
            $table->timestamp('tracked_at')->useCurrent();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('campaign_id')
                ->references('id')
                ->on('sendportal_campaigns')
                ->onDelete('cascade');
            
            $table->foreign('subscriber_id')
                ->references('id')
                ->on('sendportal_subscribers')
                ->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['campaign_id', 'subscriber_id', 'task_type']);
            $table->index(['subscriber_hash', 'task_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sendportal_campaign_subscriber_tracking');
    }
};
