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
        $table = config('larapoll.tables.votes', 'larapoll_poll_votes');

        Schema::create($table, function (Blueprint $table) use ($pollsTable, $optionsTable) {
            $table->id();
            $table->foreignId('poll_id')->constrained($pollsTable)->cascadeOnDelete();
            $table->foreignId('poll_option_id')->constrained($optionsTable)->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment')->nullable();
            $table->unsignedTinyInteger('rank')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['poll_id', 'poll_option_id', 'user_id']);
            $table->index(['poll_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('larapoll.tables.votes', 'larapoll_poll_votes'));
    }
};
