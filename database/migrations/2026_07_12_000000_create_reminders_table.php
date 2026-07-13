<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('time');
            $table->string('color', 20)->default('violet');
            $table->boolean('is_active')->default(true);
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
