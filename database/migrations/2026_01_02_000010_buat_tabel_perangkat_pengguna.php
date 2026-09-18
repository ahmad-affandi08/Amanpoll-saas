<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PerangkatPengguna', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PenggunaId', 26);
            $table->string('NamaPerangkat', 180)->nullable();
            $table->string('Platform', 60)->nullable();
            $table->string('IdentitasPerangkat', 255)->nullable();
            $table->text('TokenPush')->nullable();
            $table->dateTime('TerakhirSinkronPada', 6)->nullable();
            $table->string('Status', 30)->default('Aktif');
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->index(['PenggunaId', 'Status'], 'IdxPerangkatPengguna');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PenggunaId')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PerangkatPengguna');
    }
};
