<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Admin platform berada di luar tenant. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('AdminPlatform', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Nama', 160);
            $table->string('Email', 180);
            $table->string('KataSandi', 255);
            $table->string('Status', 30)->default('Aktif');
            $table->string('TokenIngat', 100)->nullable();
            $table->dateTime('TerakhirMasukPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['Email'], 'UqAdminPlatformEmail');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('AdminPlatform');
    }
};
