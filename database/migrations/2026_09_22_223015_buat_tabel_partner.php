<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Program partner, partnernya, lead kiriman, aturan komisi, komisi, dan payout (MARKETING.md 21). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ProgramPartner', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 80)->unique('UnqProgramPartnerKode');
            $table->string('Nama', 190);
            $table->string('Keterangan', 500)->nullable();
            $table->unsignedInteger('HariAtribusi')->default(180);
            $table->boolean('Aktif')->default(false);
            $table->dateTime('DibuatPada', 6);
            $table->dateTime('DiperbaruiPada', 6);
        });

        // Partner masuk ke portalnya sendiri, jadi barisnya sekaligus identitas autentikasi.
        Schema::create('Partner', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('ProgramPartnerId', 26);
            $table->string('Kode', 40)->unique('UnqPartnerKode');
            $table->string('NamaPerusahaan', 190);
            $table->string('Jenis', 30);
            $table->string('NamaPic', 190);
            $table->string('EmailPic', 190)->unique('UnqPartnerEmail');
            $table->string('TeleponPic', 40)->nullable();
            $table->string('KataSandi', 255);
            $table->string('TokenIngat', 100)->nullable();
            $table->string('Status', 20);
            $table->string('ReferensiPerjanjian', 190)->nullable();
            $table->string('ReferensiPayout', 190)->nullable();
            $table->dateTime('TerakhirMasukPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6);
            $table->dateTime('DiperbaruiPada', 6);

            $table->index(['ProgramPartnerId', 'Status'], 'IdxPartnerProgram');
            $table->foreign('ProgramPartnerId')->references('Id')->on('ProgramPartner')->cascadeOnDelete();
        });

        Schema::create('LeadPartner', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('PartnerId', 26);
            $table->char('ProspekId', 26)->nullable();
            $table->char('OrganisasiId', 26)->nullable();
            $table->string('NamaPerusahaan', 190);
            $table->string('NamaKontak', 190);
            // Satu alamat hanya boleh diklaim satu partner; pengirim pertama yang memilikinya.
            $table->string('Email', 190)->unique('UnqLeadPartnerEmail');
            $table->string('Telepon', 40)->nullable();
            $table->string('Catatan', 1000)->nullable();
            $table->string('Status', 20);
            $table->string('AlasanDitolak', 300)->nullable();
            $table->dateTime('DikirimPada', 6);
            $table->dateTime('DiterimaPada', 6)->nullable();
            $table->dateTime('MenjadiTrialPada', 6)->nullable();
            $table->dateTime('MenjadiPaidPada', 6)->nullable();
            $table->dateTime('KedaluwarsaPada', 6);
            $table->dateTime('DibuatPada', 6);
            $table->dateTime('DiperbaruiPada', 6);

            $table->index(['PartnerId', 'Status'], 'IdxLeadPartnerPemilik');
            $table->index('OrganisasiId', 'IdxLeadPartnerOrganisasi');
            $table->foreign('PartnerId')->references('Id')->on('Partner')->cascadeOnDelete();
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->nullOnDelete();
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi')->nullOnDelete();
        });

        // Aturan tanpa PartnerId adalah aturan bawaan programnya; yang berisi PartnerId mengalahkannya.
        Schema::create('AturanKomisiPartner', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('ProgramPartnerId', 26);
            $table->char('PartnerId', 26)->nullable();
            $table->string('Nama', 190);
            $table->string('Jenis', 20);
            $table->decimal('Nilai', 12, 2)->default(0);
            $table->unsignedInteger('MaksPembayaran')->nullable();
            $table->boolean('Aktif')->default(true);
            $table->dateTime('BerlakuDari', 6)->nullable();
            $table->dateTime('BerlakuSampai', 6)->nullable();
            $table->dateTime('DibuatPada', 6);
            $table->dateTime('DiperbaruiPada', 6);

            $table->index(['ProgramPartnerId', 'PartnerId', 'Aktif'], 'IdxAturanKomisiBerlaku');
            $table->foreign('ProgramPartnerId')->references('Id')->on('ProgramPartner')->cascadeOnDelete();
            $table->foreign('PartnerId')->references('Id')->on('Partner')->cascadeOnDelete();
        });

        Schema::create('KomisiPartner', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('PartnerId', 26);
            $table->char('LeadPartnerId', 26);
            $table->char('AturanKomisiPartnerId', 26)->nullable();
            $table->char('PayoutPartnerId', 26)->nullable();
            $table->char('OrganisasiId', 26);
            $table->char('LanggananId', 26)->nullable();
            // Satu pembayaran melahirkan paling banyak satu komisi; indeks inilah penjaganya.
            $table->char('PembayaranId', 26)->unique('UnqKomisiPartnerPembayaran');
            $table->decimal('JumlahPembayaran', 14, 2);
            $table->decimal('Jumlah', 14, 2);
            $table->string('Status', 20);
            $table->string('Catatan', 500)->nullable();
            $table->dateTime('DibayarPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6);
            $table->dateTime('DiperbaruiPada', 6);

            $table->index(['PartnerId', 'Status'], 'IdxKomisiPartnerPemilik');
            $table->index(['Status', 'DibuatPada'], 'IdxKomisiPartnerAntre');
            $table->foreign('PartnerId')->references('Id')->on('Partner')->cascadeOnDelete();
            $table->foreign('LeadPartnerId')->references('Id')->on('LeadPartner')->cascadeOnDelete();
            $table->foreign('AturanKomisiPartnerId')->references('Id')->on('AturanKomisiPartner')->nullOnDelete();
        });

        Schema::create('PayoutPartner', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('PartnerId', 26);
            $table->string('Nomor', 40)->unique('UnqPayoutPartnerNomor');
            $table->decimal('Jumlah', 14, 2);
            $table->unsignedInteger('JumlahKomisi')->default(0);
            $table->string('Status', 20);
            $table->string('ReferensiPembayaran', 190)->nullable();
            $table->string('Catatan', 500)->nullable();
            $table->dateTime('DibayarPada', 6)->nullable();
            $table->dateTime('DibuatPada', 6);
            $table->dateTime('DiperbaruiPada', 6);

            $table->index(['PartnerId', 'Status'], 'IdxPayoutPartnerPemilik');
            $table->foreign('PartnerId')->references('Id')->on('Partner')->cascadeOnDelete();
        });

        Schema::table('KomisiPartner', function (Blueprint $table): void {
            $table->foreign('PayoutPartnerId')->references('Id')->on('PayoutPartner')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('KomisiPartner', function (Blueprint $table): void {
            $table->dropForeign(['PayoutPartnerId']);
        });

        Schema::dropIfExists('PayoutPartner');
        Schema::dropIfExists('KomisiPartner');
        Schema::dropIfExists('AturanKomisiPartner');
        Schema::dropIfExists('LeadPartner');
        Schema::dropIfExists('Partner');
        Schema::dropIfExists('ProgramPartner');
    }
};
