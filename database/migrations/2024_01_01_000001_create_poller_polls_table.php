<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('poller.tables.polls', 'poller_polls');

        Schema::create($table, function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('pollable');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('single_choice')->index();
            $table->string('status')->default('draft')->index();
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('show_results_before_close')->default(false);
            $table->boolean('allow_vote_change')->default(false);
            $table->boolean('requires_comment')->default(false);
            $table->unsignedSmallInteger('max_votes_per_user')->nullable();
            $table->unsignedSmallInteger('min_selections')->nullable();
            $table->unsignedSmallInteger('max_selections')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('poller.tables.polls', 'poller_polls'));
    }
};
