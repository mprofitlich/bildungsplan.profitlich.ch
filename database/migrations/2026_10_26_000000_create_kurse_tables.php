<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Exakt abgeglichen, ohne Subdomains, etwa sluz.ch.');
            $table->timestamps();
        });

        DB::table('domains')->insert(['name' => 'sluz.ch', 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('login_links', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token_hash', 64)->unique()->comment('SHA-256 des Tokens; das Token selbst steht nur in der Mail.');
            $table->timestamp('gueltig_bis');
            $table->timestamp('eingeloest_am')->nullable();
            $table->timestamps();
        });

        Schema::create('kurse', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Vom Browser vergeben, damit lokale Kurse beim Login ihre Id behalten.');
            $table->foreignId('besitzer_id')->constrained('users')->cascadeOnDelete();
            $table->string('titel');
            $table->text('beschrieb');
            $table->json('ziele')->comment('Ids der gewählten Leistungsziele, etwa b1.1-schule.');
            $table->boolean('im_curriculum')->default(false);
            $table->unsignedInteger('version')->default(1)->comment('Steigt mit jeder Speicherung; Grundlage der Versionsprüfung (ADR 0003).');
            $table->foreignId('gesperrt_von')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('gesperrt_bis')->nullable()->comment('Bearbeitungssperre; abgelaufen, sobald in der Vergangenheit.');
            $table->timestamps();
        });

        Schema::create('freigaben', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('kurs_id')->constrained('kurse')->cascadeOnDelete();
            $table->string('email')->index()->comment('Gilt der Adresse, auch bevor deren Person sich je eingeloggt hat.');
            $table->timestamps();
            $table->unique(['kurs_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freigaben');
        Schema::dropIfExists('kurse');
        Schema::dropIfExists('login_links');
        Schema::dropIfExists('domains');
    }
};
