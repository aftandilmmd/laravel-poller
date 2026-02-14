<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $pollsTable = config('larapoll.tables.polls', 'larapoll_polls');
        $table = config('larapoll.tables.options', 'larapoll_poll_options');

        Schema::create($table, function (Blueprint $table) use ($pollsTable) {
            $table->id();
            $table->foreignId('poll_id')->constrained($pollsTable)->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('votes_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['poll_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('larapoll.tables.options', 'larapoll_poll_options'));
    }
};
