<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Throwable;

/**
 * FASE 45 temuan #6 dan #7: retensi tabel operasional per potongan.
 *
 * Yang dijaga dua arah: baris berstatus akhir yang lewat masa simpan benar-benar
 * habis (meski lintas beberapa potongan), dan baris berstatus belum akhir atau
 * yang masih dalam masa simpan tidak pernah tersentuh.
 */
final class RetensiTabelOperasionalTest extends TestCase
{
    use DatabaseTransactions;

    private Organisasi $organisasi;

    private Pengguna $pengguna;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-06-15 09:00:00');
        config([
            'amanpoll.retensi.potongan' => 3,
            'amanpoll.retensi.jeda_milidetik' => 0,
            'amanpoll.retensi.batas_detik' => 60,
        ]);

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-RET-'.uniqid(), 'Nama' => 'Organisasi Retensi']);
        $this->pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Pengguna retensi',
            'Email' => uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_catatan_akses_lama_habis_lintas_beberapa_potongan_dan_yang_baru_tetap(): void
    {
        config(['amanpoll.retensi_catatan_akses_hari' => 90]);
        $lama = $this->sisipkanBanyak('CatatanAkses', 10, fn (): array => ['Jenis' => 'Login', 'Berhasil' => 1, 'DibuatPada' => $this->hariLalu(91)]);
        $baru = $this->sisipkanBanyak('CatatanAkses', 2, fn (): array => ['Jenis' => 'Login', 'Berhasil' => 1, 'DibuatPada' => $this->hariLalu(89)]);

        $hapus = $this->hitungPenghapusan('CatatanAkses', fn () => $this->artisan('catatan-akses:bersihkan')->assertSuccessful());

        $this->assertSame(0, DB::table('CatatanAkses')->whereIn('Id', $lama)->count());
        $this->assertSame(2, DB::table('CatatanAkses')->whereIn('Id', $baru)->count());
        // 10 baris, potongan 3: empat pernyataan DELETE (3+3+3+1), bukan satu.
        $this->assertSame(4, $hapus);
    }

    public function test_catatan_akses_berhenti_saat_batas_waktu_habis_dan_dilanjutkan_jalan_berikutnya(): void
    {
        config(['amanpoll.retensi_catatan_akses_hari' => 90, 'amanpoll.retensi.batas_detik' => 0]);
        $lama = $this->sisipkanBanyak('CatatanAkses', 7, fn (): array => ['Jenis' => 'Login', 'Berhasil' => 1, 'DibuatPada' => $this->hariLalu(120)]);

        $this->artisan('catatan-akses:bersihkan')
            ->expectsOutputToContain('Batas waktu tercapai')
            ->assertSuccessful();
        $this->assertSame(4, DB::table('CatatanAkses')->whereIn('Id', $lama)->count());

        config(['amanpoll.retensi.batas_detik' => 60]);
        $this->artisan('catatan-akses:bersihkan')->assertSuccessful();
        $this->assertSame(0, DB::table('CatatanAkses')->whereIn('Id', $lama)->count());
    }

    public function test_pangkas_hanya_menghapus_status_akhir_yang_lewat_retensi(): void
    {
        $dihapus = [];
        $tetap = [];

        // Notifikasi: 90 hari untuk yang dibaca/bukan in-app, 180 hari untuk in-app belum dibaca.
        $dihapus[] = $this->notifikasi('Terkirim', 'Email', 91, dibaca: false);
        $dihapus[] = $this->notifikasi('Gagal', 'WhatsApp', 91, dibaca: false);
        $dihapus[] = $this->notifikasi('Terkirim', 'InApp', 91, dibaca: true);
        $dihapus[] = $this->notifikasi('Terkirim', 'InApp', 181, dibaca: false);
        $tetap[] = $this->notifikasi('Terkirim', 'InApp', 91, dibaca: false);
        $tetap[] = $this->notifikasi('Terkirim', 'Email', 89, dibaca: false);
        $tetap[] = $this->notifikasi('Antri', 'Email', 400, dibaca: false);

        // Outbox: Selesai > 30 hari, Gagal (menyerah) > 90 hari; Menunggu/Diproses tidak pernah.
        $dihapus[] = $this->kotakKeluar('Selesai', 31, 31);
        $dihapus[] = $this->kotakKeluar('Gagal', 91, 91);
        $tetap[] = $this->kotakKeluar('Selesai', 31, 29);
        $tetap[] = $this->kotakKeluar('Gagal', 60, 60);
        $tetap[] = $this->kotakKeluar('Menunggu', 400, null);
        $tetap[] = $this->kotakKeluar('Diproses', 400, null);

        // Webhook: Berhasil > 30 hari, GagalPermanen > 90 hari; Gagal masih akan dicoba ulang.
        $webhook = $this->panggilanBalikWeb();
        $dihapus[] = $this->pengirimanWebhook($webhook, 'Berhasil', 31);
        $dihapus[] = $this->pengirimanWebhook($webhook, 'GagalPermanen', 91);
        $tetap[] = $this->pengirimanWebhook($webhook, 'Berhasil', 29);
        $tetap[] = $this->pengirimanWebhook($webhook, 'GagalPermanen', 89);
        $tetap[] = $this->pengirimanWebhook($webhook, 'Gagal', 400);
        $tetap[] = $this->pengirimanWebhook($webhook, 'Antri', 400);

        // Sinkronisasi offline: Selesai/Dibatalkan > 30 hari; Gagal/Konflik/Menunggu menunggu manusia.
        $perangkat = $this->perangkat();
        $dihapus[] = $this->antrianSinkronisasi($perangkat, 'Selesai', 31);
        $dihapus[] = $this->antrianSinkronisasi($perangkat, 'Dibatalkan', 31);
        $tetap[] = $this->antrianSinkronisasi($perangkat, 'Selesai', 29);
        foreach (['Gagal', 'Konflik', 'Menunggu', 'Diproses'] as $status) {
            $tetap[] = $this->antrianSinkronisasi($perangkat, $status, 400);
        }

        // Kunjungan pemasaran: hanya tayangan anonim > 425 hari dari pengunjung yang tidak pernah menjadi prospek.
        $pengunjungProspek = strtolower((string) Str::ulid());
        DB::table('Prospek')->insert(['Id' => strtolower((string) Str::ulid()), 'Nama' => 'Calon', 'Sumber' => 'Organik', 'PengenalPengunjung' => $pengunjungProspek]);
        $dihapus[] = $this->event(null, 'HalamanDilihat', 426, strtolower((string) Str::ulid()));
        $dihapus[] = $this->event(null, 'HalamanDilihat', 426, null);
        $tetap[] = $this->event(null, 'HalamanDilihat', 426, $pengunjungProspek);
        $tetap[] = $this->event($this->organisasi->Id, 'HalamanDilihat', 426, null);
        $tetap[] = $this->event(null, 'FormulirDikirim', 426, null);
        $tetap[] = $this->event(null, 'HalamanDilihat', 424, null);

        // CatatanAudit append-only: tanpa kebijakan arsip, tidak ada yang dihapus.
        [, $audit] = $this->audit(4000);

        $this->artisan('retensi:pangkas')->assertSuccessful();

        foreach ($dihapus as [$tabel, $id, $keterangan]) {
            $this->assertFalse(DB::table($tabel)->where('Id', $id)->exists(), "Seharusnya dihapus: {$tabel} {$keterangan}");
        }
        foreach ($tetap as [$tabel, $id, $keterangan]) {
            $this->assertTrue(DB::table($tabel)->where('Id', $id)->exists(), "Seharusnya tetap: {$tabel} {$keterangan}");
        }
        $this->assertTrue(DB::table('CatatanAudit')->where('Id', $audit)->exists());
    }

    public function test_pangkas_menghabiskan_banyak_potongan_dan_aturan_null_dimatikan(): void
    {
        config(['amanpoll.retensi.kotak_keluar_selesai_hari' => null]);
        $notifikasi = $this->sisipkanBanyak('Notifikasi', 8, fn (): array => [
            'OrganisasiId' => $this->organisasi->Id,
            'Kanal' => 'Email',
            'JenisPeristiwa' => 'Uji',
            'Isi' => 'Uji',
            'Status' => 'Terkirim',
            'DibuatPada' => $this->hariLalu(100),
        ]);
        [, $outbox] = $this->kotakKeluar('Selesai', 400, 400);

        $hapus = $this->hitungPenghapusan('Notifikasi', fn () => $this->artisan('retensi:pangkas')->assertSuccessful());

        $this->assertSame(0, DB::table('Notifikasi')->whereIn('Id', $notifikasi)->count());
        $this->assertGreaterThanOrEqual(3, $hapus);
        $this->assertTrue(DB::table('KotakKeluarPeristiwa')->where('Id', $outbox)->exists());
    }

    public function test_catatan_audit_diarsipkan_ke_berkas_gzip_sebelum_dihapus_bila_kebijakannya_diisi(): void
    {
        Storage::fake('local');
        config(['amanpoll.retensi.catatan_audit.arsip_setelah_hari' => 3650]);
        $lama = [];
        foreach (range(1, 5) as $ke) {
            [, $lama[]] = $this->audit(3651 + $ke);
        }
        [, $baru] = $this->audit(3649);

        $this->artisan('retensi:pangkas')->assertSuccessful();

        $this->assertSame(0, DB::table('CatatanAudit')->whereIn('Id', $lama)->count());
        $this->assertTrue(DB::table('CatatanAudit')->where('Id', $baru)->exists());

        $berkas = Storage::disk('local')->files('arsip/catatan-audit');
        $this->assertCount(2, $berkas);
        $diarsipkan = [];
        foreach ($berkas as $jalur) {
            $isi = gzdecode((string) Storage::disk('local')->get($jalur));
            $this->assertIsString($isi);
            foreach (array_filter(explode("\n", $isi)) as $baris) {
                $diarsipkan[] = json_decode($baris, true, flags: JSON_THROW_ON_ERROR)['Id'];
            }
        }
        sort($diarsipkan);
        sort($lama);
        $this->assertSame($lama, $diarsipkan);
    }

    public function test_catatan_audit_tidak_dihapus_bila_arsip_gagal_ditulis(): void
    {
        Storage::fake('local');
        config(['amanpoll.retensi.catatan_audit.arsip_setelah_hari' => 3650]);
        // Folder arsipnya ternyata sebuah berkas: penulisan arsip pasti gagal.
        Storage::disk('local')->put('arsip/catatan-audit', 'bukan folder');
        [, $lama] = $this->audit(4000);

        $galat = null;
        try {
            Artisan::call('retensi:pangkas');
        } catch (Throwable $tertangkap) {
            $galat = $tertangkap;
        }

        // Dari disk (folder tak dapat dibuat) atau dari pemeriksaan ukuran berkas; keduanya menyebut arsipnya.
        $this->assertNotNull($galat, 'Arsip yang gagal ditulis seharusnya menghentikan pemangkasan.');
        $this->assertMatchesRegularExpression('/catatan-?audit/i', $galat->getMessage());
        $this->assertTrue(DB::table('CatatanAudit')->where('Id', $lama)->exists());
    }

    private function hariLalu(int $hari): string
    {
        return CarbonImmutable::now()->subDays($hari)->format('Y-m-d H:i:s');
    }

    /**
     * @param  callable(): array<string, mixed>  $atribut
     * @return list<string>
     */
    private function sisipkanBanyak(string $tabel, int $jumlah, callable $atribut): array
    {
        $id = [];
        foreach (range(1, $jumlah) as $ke) {
            $id[] = $satu = strtolower((string) Str::ulid());
            DB::table($tabel)->insert(['Id' => $satu, ...$atribut()]);
        }

        return $id;
    }

    private function hitungPenghapusan(string $tabel, callable $aksi): int
    {
        $jumlah = 0;
        DB::listen(function (QueryExecuted $kueri) use ($tabel, &$jumlah): void {
            if (str_starts_with($kueri->sql, "delete from `{$tabel}`")) {
                $jumlah++;
            }
        });
        $aksi();

        return $jumlah;
    }

    /** @return array{string, string, string} */
    private function notifikasi(string $status, string $kanal, int $hari, bool $dibaca): array
    {
        $id = strtolower((string) Str::ulid());
        DB::table('Notifikasi')->insert([
            'Id' => $id,
            'OrganisasiId' => $this->organisasi->Id,
            'PenggunaId' => $this->pengguna->Id,
            'Kanal' => $kanal,
            'JenisPeristiwa' => 'Uji',
            'Isi' => 'Uji',
            'Status' => $status,
            'DibacaPada' => $dibaca ? $this->hariLalu($hari - 1) : null,
            'DibuatPada' => $this->hariLalu($hari),
        ]);

        return ['Notifikasi', $id, "{$status}/{$kanal}/{$hari} hari/".($dibaca ? 'dibaca' : 'belum dibaca')];
    }

    /** @return array{string, string, string} */
    private function kotakKeluar(string $status, int $hariDibuat, ?int $hariDiproses): array
    {
        $id = strtolower((string) Str::ulid());
        DB::table('KotakKeluarPeristiwa')->insert([
            'Id' => $id,
            'OrganisasiId' => $this->organisasi->Id,
            'NamaPeristiwa' => 'Uji.Terjadi',
            'MuatanData' => '{}',
            'Status' => $status,
            'TersediaPada' => $this->hariLalu($hariDibuat),
            'DiprosesPada' => $hariDiproses === null ? null : $this->hariLalu($hariDiproses),
            'DibuatPada' => $this->hariLalu($hariDibuat),
        ]);

        return ['KotakKeluarPeristiwa', $id, "{$status}/dibuat {$hariDibuat}/diproses ".($hariDiproses ?? '-')];
    }

    private function panggilanBalikWeb(): string
    {
        $id = strtolower((string) Str::ulid());
        DB::table('PanggilanBalikWeb')->insert([
            'Id' => $id,
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Webhook uji',
            'Url' => 'https://contoh.test/webhook',
            'Peristiwa' => '["*"]',
        ]);

        return $id;
    }

    /** @return array{string, string, string} */
    private function pengirimanWebhook(string $webhook, string $status, int $hari): array
    {
        $id = strtolower((string) Str::ulid());
        DB::table('PengirimanPanggilanBalikWeb')->insert([
            'Id' => $id,
            'OrganisasiId' => $this->organisasi->Id,
            'PanggilanBalikWebId' => $webhook,
            'Peristiwa' => 'Uji.Terjadi',
            'MuatanData' => '{}',
            'Status' => $status,
            'DibuatPada' => $this->hariLalu($hari),
        ]);

        return ['PengirimanPanggilanBalikWeb', $id, "{$status}/{$hari} hari"];
    }

    private function perangkat(): string
    {
        $id = strtolower((string) Str::ulid());
        DB::table('PerangkatPengguna')->insert([
            'Id' => $id,
            'OrganisasiId' => $this->organisasi->Id,
            'PenggunaId' => $this->pengguna->Id,
        ]);

        return $id;
    }

    /** @return array{string, string, string} */
    private function antrianSinkronisasi(string $perangkat, string $status, int $hari): array
    {
        $id = strtolower((string) Str::ulid());
        DB::table('AntrianSinkronisasi')->insert([
            'Id' => $id,
            'OrganisasiId' => $this->organisasi->Id,
            'PerangkatPenggunaId' => $perangkat,
            'KunciOperasi' => 'op-'.$id,
            'JenisEntitas' => 'Keluhan',
            'Operasi' => 'Buat',
            'MuatanData' => '{}',
            'Status' => $status,
            'DiterimaPada' => $this->hariLalu($hari),
            'DiprosesPada' => in_array($status, ['Selesai', 'Dibatalkan'], true) ? $this->hariLalu($hari) : null,
        ]);

        return ['AntrianSinkronisasi', $id, "{$status}/{$hari} hari"];
    }

    /** @return array{string, string, string} */
    private function event(?string $organisasiId, string $jenis, int $hari, ?string $pengunjung): array
    {
        $id = strtolower((string) Str::ulid());
        DB::table('EventPemasaran')->insert([
            'Id' => $id,
            'OrganisasiId' => $organisasiId,
            'PengenalPengunjung' => $pengunjung,
            'Jenis' => $jenis,
            'TerjadiPada' => $this->hariLalu($hari),
        ]);

        return ['EventPemasaran', $id, "{$jenis}/{$hari} hari/".($organisasiId === null ? 'anonim' : 'organisasi').'/'.($pengunjung ?? 'tanpa pengunjung')];
    }

    /** @return array{string, string, string} */
    private function audit(int $hari): array
    {
        $id = strtolower((string) Str::ulid());
        DB::table('CatatanAudit')->insert([
            'Id' => $id,
            'OrganisasiId' => $this->organisasi->Id,
            'Aksi' => 'Uji.Dicatat',
            'JenisEntitas' => 'Uji',
            'DibuatPada' => $this->hariLalu($hari),
        ]);

        return ['CatatanAudit', $id, "{$hari} hari"];
    }
}
