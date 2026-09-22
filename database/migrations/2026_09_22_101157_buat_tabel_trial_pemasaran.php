<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Trial dan activation checklist-nya (MARKETING.md 12). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Trial', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();

            // Satu organisasi hanya punya satu perjalanan trial.
            $table->char('OrganisasiId', 26)->unique('UnqTrialOrganisasi');
            $table->char('ProspekId', 26)->nullable();
            $table->char('LanggananId', 26)->nullable();

            // Disalin dari prospeknya supaya attribution tetap terbaca setelah ia menjadi tenant.
            $table->char('PengenalPengunjung', 26)->nullable();

            $table->string('Status', 20);
            $table->dateTime('MulaiPada', 6);
            $table->dateTime('BerakhirPada', 6);
            $table->dateTime('TeraktivasiPada', 6)->nullable();
            $table->dateTime('KonversiPada', 6)->nullable();
            $table->unsignedInteger('HariPerpanjangan')->default(0);

            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->index(['Status', 'BerakhirPada'], 'IdxTrialJadwal');
            $table->index(['PengenalPengunjung'], 'IdxTrialPengunjung');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi')->cascadeOnDelete();
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->nullOnDelete();
            $table->foreign('LanggananId')->references('Id')->on('Langganan')->nullOnDelete();
        });

        Schema::create('ButirAktivasiTrial', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('TrialId', 26);
            $table->string('Butir', 40);
            $table->dateTime('SelesaiPada', 6);

            // Satu butir hanya boleh selesai sekali; waktunya adalah waktu pertama.
            $table->unique(['TrialId', 'Butir'], 'UnqButirAktivasiTrial');
            $table->foreign('TrialId')->references('Id')->on('Trial')->cascadeOnDelete();
        });

        /*
         * Peristiwa dalam aplikasi tidak punya pengenal pengunjung, hanya organisasi.
         * Tanpa kolom ini, perjalanan satu calon pelanggan putus tepat saat ia menjadi tenant.
         */
        Schema::table('EventPemasaran', function (Blueprint $table): void {
            $table->char('OrganisasiId', 26)->nullable()->after('PengenalPengunjung');
            $table->index(['OrganisasiId', 'TerjadiPada'], 'IdxEventPemasaranOrganisasi');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('EventPemasaran', function (Blueprint $table): void {
            $table->dropForeign(['OrganisasiId']);
            $table->dropIndex('IdxEventPemasaranOrganisasi');
            $table->dropColumn('OrganisasiId');
        });

        Schema::dropIfExists('ButirAktivasiTrial');
        Schema::dropIfExists('Trial');
    }
};
