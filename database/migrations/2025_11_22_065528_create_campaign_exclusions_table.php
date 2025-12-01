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
        Schema::create('campaign_exclusions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('excluded_campaign_id');
            $table->timestamps();

            $table->unique(['campaign_id', 'excluded_campaign_id'], 'campaign_exclusion_unique');
            $table->foreign('campaign_id')->references('id')->on('sendportal_campaigns')->onDelete('cascade');
            $table->foreign('excluded_campaign_id')->references('id')->on('sendportal_campaigns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_exclusions');
    }
};
