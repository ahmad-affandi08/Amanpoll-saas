<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Template, pengiriman, dan menu WhatsApp (MARKETING.md 16). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TemplateWhatsAppPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 80)->unique('UnqTemplateWaKode');
            $table->string('Nama', 190);
            $table->string('Bahasa', 20)->default('id');
            $table->string('Kategori', 40);
            $table->text('IsiTeks');

            // Status persetujuan penyedia, bukan sakelar kami sendiri; tanpa Disetujui tidak ada yang berangkat.
            $table->string('StatusPersetujuan', 20)->default('Draf');
            $table->string('IdTemplatePenyedia', 190)->nullable();
            $table->string('AlasanPenolakan', 500)->nullable();
            $table->dateTime('DiperiksaPada', 6)->nullable();

            $table->boolean('Aktif')->default(true);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->index(['StatusPersetujuan'], 'IdxTemplateWaStatus');
        });

        Schema::create('PengirimanWhatsAppPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('ProspekId', 26)->nullable();
            $table->string('Nomor', 32);
            $table->char('TemplateWhatsAppPemasaranId', 26);

            // Kunci dari penerima dan langkahnya, sehingga percobaan kedua menabrak yang pertama.
            $table->string('KunciIdempotensi', 190)->unique('UnqPengirimanWaIdempotensi');

            $table->string('Status', 20);
            $table->text('IsiTeks');
            $table->string('IdPesanPenyedia', 190)->nullable();
            $table->unsignedInteger('Percobaan')->default(0);
            $table->string('Galat', 500)->nullable();

            $table->dateTime('JadwalPada', 6);
            $table->dateTime('DikirimPada', 6)->nullable();
            $table->dateTime('DiperbaruiStatusPada', 6)->nullable();

            $table->index(['Status', 'JadwalPada'], 'IdxPengirimanWaJadwal');
            // Frequency cap membaca kiriman satu nomor pada satu jendela waktu.
            $table->index(['Nomor', 'DikirimPada'], 'IdxPengirimanWaNomor');
            $table->index(['IdPesanPenyedia'], 'IdxPengirimanWaPenyedia');
            $table->foreign('ProspekId')->references('Id')->on('Prospek')->nullOnDelete();
            $table->foreign('TemplateWhatsAppPemasaranId')
                ->references('Id')->on('TemplateWhatsAppPemasaran')->restrictOnDelete();
        });

        // Menu bagian 16 adalah daftar berurutan yang boleh berubah tanpa rilis, jadi ia baris data, bukan kode.
        Schema::create('MenuWhatsAppPemasaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kunci', 20);
            $table->unsignedSmallInteger('Urutan')->default(0);
            $table->string('Label', 190);
            $table->text('Balasan');
            $table->boolean('Aktif')->default(true);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->unique(['Kunci'], 'UnqMenuWaKunci');
            $table->index(['Aktif', 'Urutan'], 'IdxMenuWaUrutan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MenuWhatsAppPemasaran');
        Schema::dropIfExists('PengirimanWhatsAppPemasaran');
        Schema::dropIfExists('TemplateWhatsAppPemasaran');
    }
};
