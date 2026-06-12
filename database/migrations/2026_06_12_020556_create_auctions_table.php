<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->decimal('starting_price', 14, 2);
            $table->decimal('bid_increment', 14, 2)->default(1000);
            $table->decimal('current_price', 14, 2);
            $table->decimal('buy_now_price', 14, 2)->nullable();
            $table->unsignedInteger('bids_count')->default(0);

           $table->dateTime('starts_at');
           $table->dateTime('ends_at');

            $table->enum('status', ['scheduled', 'active', 'ended'])->default('scheduled');

            $table->timestamps();

            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};