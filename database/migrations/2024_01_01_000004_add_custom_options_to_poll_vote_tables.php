<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $pollsTable = config('poll-vote.tables.polls', 'poll_vote_polls');
        $optionsTable = config('poll-vote.tables.options', 'poll_vote_poll_options');

        Schema::table($pollsTable, function (Blueprint $table) {
            $table->boolean('allow_custom_options')->default(false)->after('allow_vote_change');
            $table->unsignedSmallInteger('max_custom_options')->nullable()->after('allow_custom_options');
        });

        Schema::table($optionsTable, function (Blueprint $table) {
            $table->boolean('is_custom')->default(false)->after('votes_count');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('is_custom');
        });
    }

    public function down(): void
    {
        $pollsTable = config('poll-vote.tables.polls', 'poll_vote_polls');
        $optionsTable = config('poll-vote.tables.options', 'poll_vote_poll_options');

        Schema::table($optionsTable, function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['is_custom', 'created_by']);
        });

        Schema::table($pollsTable, function (Blueprint $table) {
            $table->dropColumn(['allow_custom_options', 'max_custom_options']);
        });
    }
};
