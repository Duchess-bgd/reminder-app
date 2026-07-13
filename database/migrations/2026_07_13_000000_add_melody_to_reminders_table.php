<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->string('melody', 20)->default('chime')->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('reminders', fn (Blueprint $table) => $table->dropColumn('melody'));
    }
};
