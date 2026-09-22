<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PenerbitKontenSosial;
use App\Domain\Pemasaran\Application\Services\PenjadwalKontenSosial;
use App\Domain\Pemasaran\Domain\Enums\ChannelSosial;
use App\Domain\Pemasaran\Domain\Enums\StatusJadwalSosial;
use App\Domain\Pemasaran\Domain\Enums\StatusKontenSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\JadwalKontenSosial;
use App\Domain\Pemasaran\Jobs\TerbitkanKontenSosial;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Menjalankan ulang penerbitan tidak pernah menghasilkan posting ganda (Gate 38.02). */
final class JadwalSosialTest extends KasusSosial
{
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-06-15 09:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /** Bagian kedua Gate 38.02: job yang diulang tidak menerbitkan dua kali. */
    public function test_job_yang_diulang_tidak_menghasilkan_posting_ganda(): void
    {
        $jadwal = $this->jadwalkan();

        $job = new TerbitkanKontenSosial($jadwal->Id);
        $job->handle(app(PenerbitKontenSosial::class));
        $job->handle(app(PenerbitKontenSosial::class));
        $job->handle(app(PenerbitKontenSosial::class));

        $this->assertCount(1, $this->penyedia->diterbitkan);
    }

    /** Memanggil penerbit langsung pun tetap sekali; konsol punya tombol terbitkan sendiri. */
    public function test_penerbit_yang_dipanggil_ulang_tidak_menerbitkan_dua_kali(): void
    {
        $jadwal = $this->jadwalkan();
        $penerbit = app(PenerbitKontenSosial::class);

        $this->assertSame(StatusKontenSosial::Terbit, $penerbit->terbitkan($jadwal));
        $this->assertSame(StatusKontenSosial::Terbit, $penerbit->terbitkan($jadwal->fresh()));

        $this->assertCount(1, $this->penyedia->diterbitkan);
    }

    /** Percobaan hanya bertambah sekali; klaimnya memang cuma berhasil sekali. */
    public function test_percobaan_hanya_bertambah_sekali(): void
    {
        $jadwal = $this->jadwalkan();
        $penerbit = app(PenerbitKontenSosial::class);

        $penerbit->terbitkan($jadwal);
        $penerbit->terbitkan($jadwal->fresh());

        $this->assertSame(1, $jadwal->distribusi?->fresh()?->Percobaan);
    }

    /** Percobaan menghitung, bukan menyimpan angka tetap: percobaan kedua harus terbaca sebagai dua. */
    public function test_percobaan_bertambah_pada_penerbitan_berikutnya(): void
    {
        $jadwal = $this->jadwalkan();
        $penerbit = app(PenerbitKontenSosial::class);
        $penjadwal = app(PenjadwalKontenSosial::class);

        $this->penyedia->gagalkan = true;
        $penerbit->terbitkan($jadwal);

        $distribusi = $jadwal->distribusi?->fresh();
        $this->assertNotNull($distribusi);
        $this->assertSame(1, $distribusi->Percobaan);

        $penjadwal->pindahkan($distribusi, StatusKontenSosial::Draf);
        $penjadwal->pindahkan($distribusi, StatusKontenSosial::Review);
        $this->penyedia->gagalkan = false;
        $penerbit->terbitkan($penjadwal->jadwalkan($distribusi, CarbonImmutable::now()));

        $this->assertSame(2, $distribusi->fresh()?->Percobaan);
    }

    /** Jadwal yang sudah dijalankan tidak dijemput lagi oleh perintah terjadwal. */
    public function test_jadwal_yang_sudah_dijalankan_tidak_dijemput_lagi(): void
    {
        $jadwal = $this->jadwalkan();
        app(PenerbitKontenSosial::class)->terbitkan($jadwal);

        $this->assertSame(StatusJadwalSosial::Dijalankan, $jadwal->fresh()?->Status);
        $this->assertSame([], app(PenjadwalKontenSosial::class)->jatuhTempo());
    }

    /** Menjadwalkan ulang membatalkan rencana lama; dua rencana menunggu berarti dua posting. */
    public function test_penjadwalan_ulang_membatalkan_rencana_lama(): void
    {
        $distribusi = $this->buatDistribusi($this->buatKonten());
        $penjadwal = app(PenjadwalKontenSosial::class);

        $lama = $penjadwal->jadwalkan($distribusi, CarbonImmutable::now()->addHour());
        $baru = $penjadwal->jadwalkan($distribusi, CarbonImmutable::now()->addHours(3));

        $this->assertSame(StatusJadwalSosial::Dibatalkan, $lama->fresh()?->Status);
        $this->assertSame(StatusJadwalSosial::Menunggu, $baru->fresh()?->Status);
        $this->assertSame(1, JadwalKontenSosial::query()
            ->where('Status', StatusJadwalSosial::Menunggu->value)->count());
    }

    /** Rencana yang sudah dibatalkan tidak boleh diterbitkan lewat jalur belakang. */
    public function test_rencana_yang_dibatalkan_tidak_menerbitkan_apa_pun(): void
    {
        $distribusi = $this->buatDistribusi($this->buatKonten());
        $penjadwal = app(PenjadwalKontenSosial::class);

        $lama = $penjadwal->jadwalkan($distribusi, CarbonImmutable::now()->addHour());
        $penjadwal->jadwalkan($distribusi, CarbonImmutable::now()->addHours(3));

        app(PenerbitKontenSosial::class)->terbitkan($lama->fresh());
        app(PenerbitKontenSosial::class)->terbitkan($lama->fresh());

        // Rencana lama masih menunjuk distribusi yang sama, jadi klaimnya hanya berhasil sekali.
        $this->assertCount(1, $this->penyedia->diterbitkan);
    }

    /** Membatalkan jadwal mengembalikan distribusinya ke draf, bukan meninggalkannya menggantung. */
    public function test_pembatalan_mengembalikan_distribusinya_ke_draf(): void
    {
        $distribusi = $this->buatDistribusi($this->buatKonten());
        $penjadwal = app(PenjadwalKontenSosial::class);

        $jadwal = $penjadwal->jadwalkan($distribusi, CarbonImmutable::now()->addHour());
        $penjadwal->batalkan($distribusi->fresh());

        $this->assertSame(StatusJadwalSosial::Dibatalkan, $jadwal->fresh()?->Status);
        $this->assertSame(StatusKontenSosial::Draf, $distribusi->fresh()?->Status);
        $this->assertSame([], $penjadwal->jatuhTempo(CarbonImmutable::now()->addDay()));
    }

    /** Jadwal di masa lalu adalah salah ketik, bukan perintah menerbitkan sekarang. */
    public function test_jadwal_di_masa_lalu_ditolak(): void
    {
        $distribusi = $this->buatDistribusi($this->buatKonten());

        $this->expectException(AturanBisnisDilanggar::class);

        app(PenjadwalKontenSosial::class)->jadwalkan($distribusi, CarbonImmutable::now()->subHour());
    }

    /** Yang belum jatuh tempo tidak ikut diantrekan. */
    public function test_hanya_jadwal_yang_jatuh_tempo_yang_dijemput(): void
    {
        $konten = $this->buatKonten();
        $penjadwal = app(PenjadwalKontenSosial::class);

        $penjadwal->jadwalkan($this->buatDistribusi($konten), CarbonImmutable::now());
        $penjadwal->jadwalkan(
            $this->buatDistribusi($konten, ChannelSosial::Facebook),
            CarbonImmutable::now()->addDays(2),
        );

        $this->assertCount(1, $penjadwal->jatuhTempo());
        $this->assertCount(2, $penjadwal->jatuhTempo(CarbonImmutable::now()->addDays(3)));
    }

    /** Job yang menyerah melepaskan distribusinya dari Diproses, bukan meninggalkannya tersangkut. */
    public function test_job_yang_menyerah_melepaskan_distribusinya(): void
    {
        $jadwal = $this->jadwalkan();
        $distribusi = $jadwal->distribusi;
        $this->assertNotNull($distribusi);

        $distribusi->Status = StatusKontenSosial::Diproses;
        $distribusi->save();

        (new TerbitkanKontenSosial($jadwal->Id))->failed(new \RuntimeException('Penyedia tidak menjawab.'));

        $this->assertSame(StatusKontenSosial::Gagal, $distribusi->fresh()?->Status);
    }

    /** Yang gagal dapat dijadwalkan ulang setelah captionnya diperbaiki. */
    public function test_distribusi_gagal_dapat_dijadwalkan_ulang_lewat_draf(): void
    {
        $jadwal = $this->jadwalkan();
        $this->penyedia->gagalkan = true;
        app(PenerbitKontenSosial::class)->terbitkan($jadwal);

        $distribusi = $jadwal->distribusi?->fresh();
        $this->assertNotNull($distribusi);
        $this->assertSame(StatusKontenSosial::Gagal, $distribusi->Status);

        $penjadwal = app(PenjadwalKontenSosial::class);
        $penjadwal->pindahkan($distribusi, StatusKontenSosial::Draf);
        $penjadwal->pindahkan($distribusi, StatusKontenSosial::Review);
        $penjadwal->jadwalkan($distribusi, CarbonImmutable::now()->addHour());

        $this->assertSame(StatusKontenSosial::Terjadwal, $distribusi->fresh()?->Status);
    }

    private function jadwalkan(): JadwalKontenSosial
    {
        return app(PenjadwalKontenSosial::class)->jadwalkan(
            $this->buatDistribusi($this->buatKonten($this->buatKampanye())),
            CarbonImmutable::now(),
        );
    }
}
