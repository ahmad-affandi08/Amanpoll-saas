<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Pengguna', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('UnitOrganisasiId', 26)->nullable();
            $table->string('Nama', 180);
            $table->string('Email', 180);
            $table->string('Telepon', 50)->nullable();
            $table->string('KataSandi', 255)->nullable();
            $table->dateTime('EmailTerverifikasiPada', 6)->nullable();
            $table->text('AvatarUrl')->nullable();
            $table->string('NomorPegawai', 80)->nullable();
            $table->string('Jabatan', 120)->nullable();
            $table->string('JenisPengguna', 40)->default('Internal');
            $table->string('Status', 30)->default('Aktif');
            $table->dateTime('TerakhirMasukPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('DihapusPada', 6)->nullable();
            $table->unique(['OrganisasiId', 'Email'], 'UqPenggunaEmail');
            $table->index(['UnitOrganisasiId'], 'IdxPenggunaUnit');
            $table->index(['OrganisasiId', 'Status'], 'IdxPenggunaStatus');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('UnitOrganisasiId')->references('Id')->on('UnitOrganisasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Pengguna');
    }
};
