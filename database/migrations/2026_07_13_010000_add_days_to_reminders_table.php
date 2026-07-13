<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->json('days')->nullable()->after('day_of_week');
        });

        DB::table('reminders')->orderBy('id')->each(function ($reminder) {
            DB::table('reminders')->where('id', $reminder->id)->update([
                'days' => json_encode([(int) $reminder->day_of_week]),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('reminders', fn (Blueprint $table) => $table->dropColumn('days'));
    }
};
