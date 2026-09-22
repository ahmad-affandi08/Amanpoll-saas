<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Kolom yang dicast `encrypted` menyimpan ciphertext base64, bukan JSON dan bukan teks pendek. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('IntegrasiEksternal', function (Blueprint $table): void {
            $table->text('KonfigurasiTerenkripsi')->nullable()->change();
        });

        Schema::table('PanggilanBalikWeb', function (Blueprint $table): void {
            $table->text('Rahasia')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('IntegrasiEksternal', function (Blueprint $table): void {
            $table->json('KonfigurasiTerenkripsi')->nullable()->change();
        });

        Schema::table('PanggilanBalikWeb', function (Blueprint $table): void {
            $table->string('Rahasia', 255)->nullable()->change();
        });
    }
};
