<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Program referral, kode per pelanggan, perjalanan tiap referral, dan imbalannya (MARKETING.md 20). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ProgramReferral', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 80)->unique('UnqProgramReferralKode');
            $table->string('Nama', 190);
            $table->string('Keterangan', 500)->nullable();
            $table->string('JenisReward', 30);
            $table->decimal('NilaiReward', 12, 2)->default(0);
            $table->unsignedInteger('HariKedaluwarsa')->default(90);
            $table->boolean('Aktif')->default(false);
            $table->dateTime('DibuatPada', 6);
            $table->dateTime('DiperbaruiPada', 6);
        });

        // Kode dipisah dari perjalanannya: satu pelanggan satu kode, satu baris Referral satu orang yang diajak.
        Schema::create('KodeReferral', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('ProgramReferralId', 26);
            $table->char('OrganisasiId', 26);
            $table->string('Kode', 40)->unique('UnqKodeReferral');
            $table->boolean('Aktif')->default(true);
            $table->dateTime('DibuatPada', 6);

            $table->unique(['ProgramReferralId', 'OrganisasiId'], 'UnqKodeReferralPemilik');
            $table->foreign('ProgramReferralId')->references('Id')->on('ProgramReferral')->cascadeOnDelete();
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi')->cascadeOnDelete();
        });

        Schema::create('Referral', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('ProgramReferralId', 26);
            $table->char('KodeReferralId', 26);
            $table->char('OrganisasiPerujukId', 26);
            $table->char('PengenalPengunjung', 26);
            $table->char('ProspekId', 26)->nullable();
            $table->char('OrganisasiBaruId', 26)->nullable();
            $table->char('LanggananId', 26)->nullable();
            $table->string('Status', 20);
            $table->string('AlasanDitolak', 300)->nullable();
            $table->dateTime('DibuatPada', 6);
            $table->dateTime('DiklikPada', 6)->nullable();
            $table->dateTime('MenjadiLeadPada', 6)->nullable();
            $table->dateTime('MenjadiTrialPada', 6)->nullable();
            $table->dateTime('MenjadiPaidPada', 6)->nullable();
            $table->dateTime('KedaluwarsaPada', 6);
            $table->dateTime('DiperbaruiPada', 6);

            // Satu pengunjung hanya punya satu referral per program; klik kedua tidak melahirkan baris kedua.
            $table->unique(['ProgramReferralId', 'PengenalPengunjung'], 'UnqReferralPengunjung');
            $table->index(['Status', 'KedaluwarsaPada'], 'IdxReferralKedaluwarsa');
            $table->index(['OrganisasiPerujukId', 'Status'], 'IdxReferralPerujuk');

            $table->foreign('ProgramReferralId')->references('Id')->on('ProgramReferral')->cascadeOnDelete();
            $table->foreign('KodeReferralId')->references('Id')->on('KodeReferral')->cascadeOnDelete();
            $table->foreign('OrganisasiPerujukId')->references('Id')->on('Organisasi')->cascadeOnDelete();
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->nullOnDelete();
            $table->foreign('OrganisasiBaruId')->references('Id')->on('Organisasi')->nullOnDelete();
        });

        Schema::create('RewardReferral', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            // Satu referral menghasilkan paling banyak satu imbalan; itulah yang menahan pemberian ganda.
            $table->char('ReferralId', 26)->unique('UnqRewardReferralSekali');
            $table->char('OrganisasiPenerimaId', 26);
            $table->string('Jenis', 30);
            $table->decimal('Nilai', 12, 2)->default(0);
            $table->string('Status', 20);
            $table->unsignedInteger('Percobaan')->default(0);
            $table->string('Ringkasan', 500)->nullable();
            $table->string('Galat', 500)->nullable();
            $table->dateTime('DibuatPada', 6);
            $table->dateTime('DiberikanPada', 6)->nullable();
            $table->dateTime('DiperbaruiPada', 6);

            $table->index(['Status', 'DibuatPada'], 'IdxRewardReferralAntre');
            $table->foreign('ReferralId')->references('Id')->on('Referral')->cascadeOnDelete();
            $table->foreign('OrganisasiPenerimaId')->references('Id')->on('Organisasi')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RewardReferral');
        Schema::dropIfExists('Referral');
        Schema::dropIfExists('KodeReferral');
        Schema::dropIfExists('ProgramReferral');
    }
};
