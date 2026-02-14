<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $pollsTable = config('larapoll.tables.polls', 'larapoll_polls');
        $optionsTable = config('larapoll.tables.options', 'larapoll_poll_options');
        $votesTable = config('larapoll.tables.votes', 'larapoll_poll_votes');

        // Add standalone user_id index on votes table
        Schema::table($votesTable, function (Blueprint $table) {
            $table->index('user_id');
        });

        // Add is_custom index on options table
        Schema::table($optionsTable, function (Blueprint $table) {
            $table->index('is_custom');
        });

        // Change polls.created_by to nullable with nullOnDelete
        Schema::table($pollsTable, function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreignId('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // Change votes.user_id to nullable with nullOnDelete
        Schema::table($votesTable, function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $pollsTable = config('larapoll.tables.polls', 'larapoll_polls');
        $optionsTable = config('larapoll.tables.options', 'larapoll_poll_options');
        $votesTable = config('larapoll.tables.votes', 'larapoll_poll_votes');

        Schema::table($votesTable, function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table($optionsTable, function (Blueprint $table) {
            $table->dropIndex(['is_custom']);
        });

        Schema::table($pollsTable, function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreignId('created_by')->change();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table($votesTable, function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
