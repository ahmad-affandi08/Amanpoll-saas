<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Template, sequence, dan pengiriman email pemasaran (MARKETING.md 15). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TemplateEmailPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 80)->unique('UnqTemplateEmailKode');
            $table->string('Nama', 190);
            $table->string('Jenis', 40);
            $table->string('Subjek', 255);
            $table->text('IsiHtml');
            $table->text('IsiTeks')->nullable();
            $table->boolean('Aktif')->default(true);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('SequenceEmailPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 80)->unique('UnqSequenceEmailKode');
            $table->string('Nama', 190);
            $table->string('Keterangan', 500)->nullable();
            $table->boolean('Aktif')->default(false);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('LangkahSequenceEmail', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('SequenceEmailPemasaranId', 26);
            $table->char('TemplateEmailPemasaranId', 26);
            $table->unsignedInteger('Urutan');

            // Hari relatif sejak pendaftaran, bukan tanggal: sequence yang sama dipakai siapa pun, kapan pun.
            $table->unsignedInteger('HariKe');
            $table->boolean('Aktif')->default(true);

            $table->unique(['SequenceEmailPemasaranId', 'Urutan'], 'UnqLangkahSequenceUrutan');
            $table->foreign('SequenceEmailPemasaranId')
                ->references('Id')->on('SequenceEmailPemasaran')->cascadeOnDelete();
            $table->foreign('TemplateEmailPemasaranId')
                ->references('Id')->on('TemplateEmailPemasaran')->restrictOnDelete();
        });

        Schema::create('PendaftaranSequence', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('SequenceEmailPemasaranId', 26);
            $table->char('ProspekId', 26);
            $table->string('Status', 20);
            $table->dateTime('DimulaiPada', 6);
            $table->dateTime('SelesaiPada', 6)->nullable();

            // Satu prospek tidak mendaftar dua kali ke sequence yang sama.
            $table->unique(['SequenceEmailPemasaranId', 'ProspekId'], 'UnqPendaftaranSequence');
            $table->foreign('SequenceEmailPemasaranId')
                ->references('Id')->on('SequenceEmailPemasaran')->cascadeOnDelete();
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->cascadeOnDelete();
        });

        Schema::create('PengirimanEmailPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('ProspekId', 26)->nullable();
            $table->string('Email', 190);
            $table->char('TemplateEmailPemasaranId', 26);
            $table->char('PendaftaranSequenceId', 26)->nullable();
            $table->char('LangkahSequenceEmailId', 26)->nullable();

            // Kunci dari penerima dan langkahnya, bukan waktu atau nomor percobaan, sehingga percobaan kedua menabrak yang pertama.
            $table->string('KunciIdempotensi', 190)->unique('UnqPengirimanEmailIdempotensi');

            $table->string('Status', 20);
            $table->string('Subjek', 255);
            $table->string('IdPesanPenyedia', 190)->nullable();
            $table->unsignedInteger('Percobaan')->default(0);
            $table->string('Galat', 500)->nullable();

            $table->dateTime('JadwalPada', 6);
            $table->dateTime('DikirimPada', 6)->nullable();
            $table->dateTime('DiperbaruiStatusPada', 6)->nullable();

            $table->index(['Status', 'JadwalPada'], 'IdxPengirimanEmailJadwal');
            $table->index(['Email'], 'IdxPengirimanEmailAlamat');
            $table->index(['IdPesanPenyedia'], 'IdxPengirimanEmailPenyedia');
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->nullOnDelete();
            $table->foreign('TemplateEmailPemasaranId')
                ->references('Id')->on('TemplateEmailPemasaran')->restrictOnDelete();
            $table->foreign('PendaftaranSequenceId')
                ->references('Id')->on('PendaftaranSequence')->cascadeOnDelete();
            $table->foreign('LangkahSequenceEmailId')
                ->references('Id')->on('LangkahSequenceEmail')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PengirimanEmailPemasaran');
        Schema::dropIfExists('PendaftaranSequence');
        Schema::dropIfExists('LangkahSequenceEmail');
        Schema::dropIfExists('SequenceEmailPemasaran');
        Schema::dropIfExists('TemplateEmailPemasaran');
    }
};
