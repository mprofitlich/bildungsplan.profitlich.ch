<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurse', function (Blueprint $table) {
            $table->unsignedInteger('nummer')->nullable()->unique()->after('id')
                ->comment('Kurze Kennung für Adressen; vergibt der Server, die Id bleibt die des Browsers.');
        });

        // Bestehende Kurse in der Reihenfolge, in der sie angelegt wurden.
        $nummer = 0;
        foreach (DB::table('kurse')->orderBy('created_at')->orderBy('id')->pluck('id') as $id) {
            DB::table('kurse')->where('id', $id)->update(['nummer' => ++$nummer]);
        }
    }

    public function down(): void
    {
        Schema::table('kurse', function (Blueprint $table) {
            $table->dropUnique(['nummer']);
            $table->dropColumn('nummer');
        });
    }
};
