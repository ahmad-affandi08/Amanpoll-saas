<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Sinkronisasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\KomentarEntitas;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\PenandaSinkronisasi;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Gate 20 — simulasi offline: teknisi bekerja tanpa sinyal, lalu antreannya
 * dikirim (berkali-kali) saat koneksi kembali tanpa menggandakan transaksi.
 */
final class AntrianSinkronisasiOfflineTest extends TestCase
{
    use DatabaseTransactions;

    private const PERANGKAT = 'perangkat-uji-01';

    public function test_gate_20_antrean_offline_dikirim_ulang_tidak_menggandakan_transaksi(): void
    {
        ['teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkanPenugasan();

        // Teknisi menekan "mulai kerja" sekali saat offline.
        $mutasi = [
            'KunciOperasi' => 'op-mulai-kerja-01',
            'Operasi' => 'PerintahKerja.ResponsPenugasan',
            'EntitasId' => $perintahKerja->Id,
            'VersiKlien' => $perintahKerja->Versi,
            'MuatanData' => ['Respons' => 'Terima', 'Catatan' => 'Terima tugas di lapangan'],
        ];

        $pertama = $this->dorong($teknisi, [$mutasi]);
        $pertama->assertOk();
        $this->assertSame('Selesai', $this->statusMutasi($pertama, 'op-mulai-kerja-01'));

        // Koneksi terputus tepat setelah server memproses, klien mengirim ulang.
        $this->dorong($teknisi, [$mutasi])->assertOk();
        $this->dorong($teknisi, [$mutasi])->assertOk();

        $this->assertSame(
            1,
            AntrianSinkronisasi::query()->withoutGlobalScopes()
                ->where('KunciOperasi', 'op-mulai-kerja-01')->count(),
            'Kunci operasi yang sama tidak boleh menghasilkan dua baris antrean.',
        );

        $perintahKerja->refresh();
        $this->assertSame(StatusPerintahKerja::Diterima->value, $perintahKerja->Status);
        $this->assertSame(2, $perintahKerja->Versi, 'Status hanya boleh diterapkan satu kali.');
        $this->assertSame(1, RiwayatStatusPerintahKerja::query()->withoutGlobalScopes()
            ->where('PerintahKerjaId', $perintahKerja->Id)
            ->where('StatusSesudah', StatusPerintahKerja::Diterima->value)
            ->count(), 'Riwayat status hanya boleh tercatat satu kali.');
    }

    public function test_gate_20_mutasi_append_only_tidak_berganda_saat_dikirim_ulang(): void
    {
        ['teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkanPenugasan();
        $this->jadikanDiterima($teknisi, $perintahKerja);

        $mutasi = [
            [
                'KunciOperasi' => 'op-catatan-01',
                'Operasi' => 'PerintahKerja.TambahCatatan',
                'EntitasId' => $perintahKerja->Id,
                'VersiKlien' => null,
                'MuatanData' => ['Isi' => 'Bearing berbunyi, perlu pelumasan ulang.'],
            ],
            [
                'KunciOperasi' => 'op-waktu-01',
                'Operasi' => 'PerintahKerja.CatatWaktuKerja',
                'EntitasId' => $perintahKerja->Id,
                'VersiKlien' => null,
                'MuatanData' => [
                    'MulaiPada' => now()->subHours(2)->toIso8601String(),
                    'SelesaiPada' => now()->subHour()->toIso8601String(),
                    'Catatan' => 'Pelumasan ulang',
                ],
            ],
        ];

        $this->dorong($teknisi, $mutasi)->assertOk();
        $this->dorong($teknisi, $mutasi)->assertOk();
        $this->dorong($teknisi, $mutasi)->assertOk();

        $this->assertSame(1, KomentarEntitas::query()->withoutGlobalScopes()
            ->where('JenisEntitas', 'PerintahKerja')->where('EntitasId', $perintahKerja->Id)->count());
        $this->assertSame(1, WaktuKerja::query()->withoutGlobalScopes()
            ->where('PerintahKerjaId', $perintahKerja->Id)->count());
    }

    public function test_penanda_sinkronisasi_dicatat_per_jenis_entitas_saat_paket_ditarik(): void
    {
        ['teknisi' => $teknisi] = $this->siapkanPenugasan();

        $respons = $this->actingAs($teknisi)->postJson('/offline/paket', $this->amplopPerangkat());
        $respons->assertOk();

        $respons->assertJsonStructure([
            'Perangkat' => ['Id'],
            'Paket' => ['Penugasan', 'Aset', 'DaftarPeriksa', 'Token', 'OperasiDidukung'],
            'Penanda',
            'Antrean',
        ]);

        $penanda = PenandaSinkronisasi::query()->withoutGlobalScopes()->get();
        $this->assertEqualsCanonicalizing(
            ['PerintahKerja', 'Aset', 'PelaksanaanDaftarPeriksa'],
            $penanda->pluck('JenisEntitas')->all(),
        );
        $this->assertNotNull($penanda->first()?->TerakhirSinkronPada);
    }

    public function test_paket_offline_hanya_memuat_penugasan_milik_teknisi_itu_sendiri(): void
    {
        ['organisasi' => $organisasi, 'teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkanPenugasan();
        $teknisiLain = $this->buatPengguna($organisasi);

        $pekerjaanLain = $this->buatPerintahKerja($organisasi, 'WO-LAIN-01');
        PenugasanPerintahKerja::create([
            'OrganisasiId' => $organisasi->Id,
            'PerintahKerjaId' => $pekerjaanLain->Id,
            'PenggunaId' => $teknisiLain->Id,
            'DitugaskanOleh' => $teknisiLain->Id,
            'DitugaskanPada' => now(),
            'Status' => 'Ditugaskan',
        ]);

        $respons = $this->actingAs($teknisi)->postJson('/offline/paket', $this->amplopPerangkat());

        $nomor = array_column($respons->json('Paket.Penugasan'), 'Nomor');
        $this->assertSame([$perintahKerja->Nomor], $nomor);
    }

    public function test_mutasi_pada_pekerjaan_yang_tidak_ditugaskan_ditolak_sebagai_gagal(): void
    {
        ['organisasi' => $organisasi, 'teknisi' => $teknisi] = $this->siapkanPenugasan();
        $pekerjaanOrangLain = $this->buatPerintahKerja($organisasi, 'WO-ORANG-LAIN');

        $respons = $this->dorong($teknisi, [[
            'KunciOperasi' => 'op-curi-01',
            'Operasi' => 'PerintahKerja.ResponsPenugasan',
            'EntitasId' => $pekerjaanOrangLain->Id,
            'VersiKlien' => $pekerjaanOrangLain->Versi,
            'MuatanData' => ['Respons' => 'Terima'],
        ]]);

        $respons->assertOk();
        $this->assertSame('Gagal', $this->statusMutasi($respons, 'op-curi-01'));
        $this->assertSame('AksesDitolak', $this->konflikMutasi($respons, 'op-curi-01')['Alasan']);
        $this->assertSame(StatusPerintahKerja::Ditugaskan->value, $pekerjaanOrangLain->refresh()->Status);
    }

    public function test_operasi_di_luar_daftar_putih_ditolak_tanpa_menahan_antrean(): void
    {
        ['teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkanPenugasan();

        $respons = $this->dorong($teknisi, [
            [
                'KunciOperasi' => 'op-liar-01',
                'Operasi' => 'PerintahKerja.HapusSemua',
                'EntitasId' => $perintahKerja->Id,
                'VersiKlien' => null,
                'MuatanData' => ['apa pun' => true],
            ],
            [
                'KunciOperasi' => 'op-sah-01',
                'Operasi' => 'PerintahKerja.ResponsPenugasan',
                'EntitasId' => $perintahKerja->Id,
                'VersiKlien' => $perintahKerja->Versi,
                'MuatanData' => ['Respons' => 'Terima'],
            ],
        ]);

        $respons->assertOk();
        $this->assertSame('Gagal', $this->statusMutasi($respons, 'op-liar-01'));
        $this->assertSame('OperasiTidakDikenal', $this->konflikMutasi($respons, 'op-liar-01')['Alasan']);

        // Mutasi rusak tidak boleh menahan mutasi berikutnya.
        $this->assertSame('Selesai', $this->statusMutasi($respons, 'op-sah-01'));
    }

    public function test_muatan_yang_tidak_lolos_validasi_server_ditandai_gagal_permanen(): void
    {
        ['teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkanPenugasan();

        $respons = $this->dorong($teknisi, [[
            'KunciOperasi' => 'op-rusak-01',
            'Operasi' => 'PerintahKerja.UbahStatus',
            'EntitasId' => $perintahKerja->Id,
            'VersiKlien' => $perintahKerja->Versi,
            'MuatanData' => ['Status' => 'StatusKarangan'],
        ]]);

        $respons->assertOk();
        $this->assertSame('Gagal', $this->statusMutasi($respons, 'op-rusak-01'));
        $this->assertSame('MuatanTidakValid', $this->konflikMutasi($respons, 'op-rusak-01')['Alasan']);
    }

    public function test_perintah_terjadwal_memproses_antrean_yang_belum_tuntas(): void
    {
        ['teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkanPenugasan();

        $this->dorong($teknisi, [])->assertOk();

        // Perangkat terdaftar tapi mutasinya baru masuk tanpa sempat diproses
        // (mis. koneksi putus tepat setelah server menyimpan antrean).
        $perangkat = PerangkatPengguna::query()
            ->withoutGlobalScopes()
            ->where('PenggunaId', $teknisi->Id)
            ->firstOrFail();

        AntrianSinkronisasi::query()->withoutGlobalScopes()->getQuery()->insert([
            'Id' => (string) Str::ulid(),
            'OrganisasiId' => $perintahKerja->OrganisasiId,
            'PerangkatPenggunaId' => $perangkat->Id,
            'KunciOperasi' => 'op-tertunda-01',
            'JenisEntitas' => 'PerintahKerja',
            'EntitasId' => $perintahKerja->Id,
            'Operasi' => 'PerintahKerja.ResponsPenugasan',
            'VersiKlien' => $perintahKerja->Versi,
            'MuatanData' => json_encode(['Respons' => 'Terima']),
            'Status' => 'Menunggu',
            'Percobaan' => 0,
            'DiterimaPada' => now(),
        ]);

        $this->artisan('sinkronisasi:proses-antrian')->assertSuccessful();

        $this->assertSame('Selesai', AntrianSinkronisasi::query()->withoutGlobalScopes()
            ->where('KunciOperasi', 'op-tertunda-01')->value('Status'));
        $this->assertSame(StatusPerintahKerja::Diterima->value, $perintahKerja->refresh()->Status);
    }

    public function test_mutasi_yang_tertinggal_di_status_diproses_dikembalikan_ke_antrean(): void
    {
        ['teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkanPenugasan();
        $this->dorong($teknisi, [])->assertOk();

        $perangkat = PerangkatPengguna::query()->withoutGlobalScopes()
            ->where('PenggunaId', $teknisi->Id)->firstOrFail();

        // Worker mati mendadak setelah mengklaim mutasi: baris tertinggal di
        // status Diproses dan tidak akan pernah diambil lagi tanpa pemulihan.
        AntrianSinkronisasi::query()->withoutGlobalScopes()->getQuery()->insert([
            'Id' => (string) Str::ulid(),
            'OrganisasiId' => $perintahKerja->OrganisasiId,
            'PerangkatPenggunaId' => $perangkat->Id,
            'KunciOperasi' => 'op-terhenti-01',
            'JenisEntitas' => 'PerintahKerja',
            'EntitasId' => $perintahKerja->Id,
            'Operasi' => 'PerintahKerja.ResponsPenugasan',
            'VersiKlien' => $perintahKerja->Versi,
            'MuatanData' => json_encode(['Respons' => 'Terima']),
            'Status' => 'Diproses',
            'Percobaan' => 1,
            'DiterimaPada' => now()->subHour(),
            'DiprosesPada' => now()->subHour(),
        ]);

        $this->artisan('sinkronisasi:proses-antrian')->assertSuccessful();

        $this->assertSame('Selesai', AntrianSinkronisasi::query()->withoutGlobalScopes()
            ->where('KunciOperasi', 'op-terhenti-01')->value('Status'));
        $this->assertSame(StatusPerintahKerja::Diterima->value, $perintahKerja->refresh()->Status);
    }

    public function test_pengguna_lain_tidak_dapat_menyelesaikan_konflik_milik_perangkat_orang_lain(): void
    {
        ['organisasi' => $organisasi, 'teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkanPenugasan();

        // Pekerjaan berubah di server sehingga mutasi offline jadi konflik.
        $perintahKerja->Versi = 9;
        $perintahKerja->save();

        $respons = $this->dorong($teknisi, [[
            'KunciOperasi' => 'op-konflik-01',
            'Operasi' => 'PerintahKerja.ResponsPenugasan',
            'EntitasId' => $perintahKerja->Id,
            'VersiKlien' => 1,
            'MuatanData' => ['Respons' => 'Terima'],
        ]]);
        $this->assertSame('Konflik', $this->statusMutasi($respons, 'op-konflik-01'));

        $antrianId = collect($respons->json('Antrean'))->firstWhere('KunciOperasi', 'op-konflik-01')['Id'];
        $penyusup = $this->buatPengguna($organisasi);

        $this->actingAs($penyusup)
            ->postJson("/offline/antrian/{$antrianId}/konflik", ['Keputusan' => 'PakaiServer'])
            ->assertForbidden();
    }

    /**
     * @param  list<array<string, mixed>>  $mutasi
     */
    private function dorong(Pengguna $teknisi, array $mutasi): TestResponse
    {
        return $this->actingAs($teknisi)->postJson('/offline/antrian', [
            ...$this->amplopPerangkat(),
            'Mutasi' => $mutasi,
        ]);
    }

    /** @return array<string, string> */
    private function amplopPerangkat(): array
    {
        return [
            'IdentitasPerangkat' => self::PERANGKAT,
            'NamaPerangkat' => 'Ponsel Teknisi',
            'Platform' => 'Android',
        ];
    }

    private function statusMutasi(TestResponse $respons, string $kunciOperasi): string
    {
        return collect($respons->json('Antrean'))->firstWhere('KunciOperasi', $kunciOperasi)['Status'];
    }

    /** @return array<string, mixed> */
    private function konflikMutasi(TestResponse $respons, string $kunciOperasi): array
    {
        return collect($respons->json('Antrean'))->firstWhere('KunciOperasi', $kunciOperasi)['Konflik'];
    }

    private function jadikanDiterima(Pengguna $teknisi, PerintahKerja $perintahKerja): void
    {
        $this->dorong($teknisi, [[
            'KunciOperasi' => 'op-siapkan-diterima',
            'Operasi' => 'PerintahKerja.ResponsPenugasan',
            'EntitasId' => $perintahKerja->Id,
            'VersiKlien' => $perintahKerja->Versi,
            'MuatanData' => ['Respons' => 'Terima'],
        ]])->assertOk();

        $perintahKerja->refresh();
    }

    /**
     * @return array{organisasi: Organisasi, teknisi: Pengguna, perintahKerja: PerintahKerja, aset: Aset}
     */
    private function siapkanPenugasan(): array
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-SYNC-'.uniqid(), 'Nama' => 'Organisasi Sinkronisasi']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $teknisi = $this->buatPengguna($organisasi);
        $perintahKerja = $this->buatPerintahKerja($organisasi, 'WO-SYNC-'.uniqid());

        $kategoriAset = KategoriAset::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Mesin',
            'Kode' => 'KAT-'.uniqid(),
        ]);
        $lokasi = Lokasi::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'LOK-'.uniqid(),
            'Nama' => 'Gedung Utama',
            'Status' => 'Aktif',
        ]);
        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategoriAset->Id,
            'LokasiId' => $lokasi->Id,
            'KodeAset' => 'AST-'.uniqid(),
            'Nama' => 'Pompa Sentrifugal',
            'Status' => Aset::STATUS_AKTIF,
            'Kondisi' => Aset::KONDISI_BAIK,
        ]);
        PerintahKerjaAset::create([
            'OrganisasiId' => $organisasi->Id,
            'PerintahKerjaId' => $perintahKerja->Id,
            'AsetId' => $aset->Id,
            'Utama' => true,
        ]);

        PenugasanPerintahKerja::create([
            'OrganisasiId' => $organisasi->Id,
            'PerintahKerjaId' => $perintahKerja->Id,
            'PenggunaId' => $teknisi->Id,
            'DitugaskanOleh' => $teknisi->Id,
            'DitugaskanPada' => now(),
            'Status' => 'Ditugaskan',
        ]);

        return [
            'organisasi' => $organisasi,
            'teknisi' => $teknisi,
            'perintahKerja' => $perintahKerja->refresh(),
            'aset' => $aset,
        ];
    }

    private function buatPerintahKerja(Organisasi $organisasi, string $nomor): PerintahKerja
    {
        return PerintahKerja::create([
            'OrganisasiId' => $organisasi->Id,
            'Nomor' => $nomor,
            'Judul' => 'Perbaikan pompa',
            'Jenis' => 'Korektif',
            'Status' => StatusPerintahKerja::Ditugaskan->value,
            'Prioritas' => 'Normal',
            'Versi' => 1,
        ]);
    }

    /** @param list<string> $izin */
    private function buatPengguna(Organisasi $organisasi, array $izin = []): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi '.uniqid(),
            'Email' => uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($izin !== []) {
            $peran = Peran::create([
                'OrganisasiId' => $organisasi->Id,
                'Kode' => 'PERAN-'.uniqid(),
                'Nama' => 'Peran Uji',
            ]);
            foreach ($izin as $kode) {
                $modelIzin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Sinkronisasi']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
            }
            PenggunaPeran::create([
                'OrganisasiId' => $organisasi->Id,
                'PenggunaId' => $pengguna->Id,
                'PeranId' => $peran->Id,
            ]);
        }

        return $pengguna;
    }
}
