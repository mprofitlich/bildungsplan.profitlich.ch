<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurse', function (Blueprint $table) {
            $table->json('kriterien')->nullable()->after('beschrieb')
                ->comment('Text der Lehrperson je Beurteilungskriterium, etwa {"vermittlung": "…"}; fehlende gelten als leer.');
        });
    }

    public function down(): void
    {
        Schema::table('kurse', function (Blueprint $table) {
            $table->dropColumn('kriterien');
        });
    }
};
