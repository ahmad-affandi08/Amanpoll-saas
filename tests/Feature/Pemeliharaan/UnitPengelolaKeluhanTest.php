<?php

declare(strict_types=1);

namespace Tests\Feature\Pemeliharaan;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Notifikasi\Domain\Enums\PemicuEskalasiTingkatLayanan;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\EskalasiTingkatLayanan;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Domain\Pemeliharaan\Application\Actions\BuatKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Pemeliharaan\Jobs\ProsesEskalasiKeluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Lapangan\KasusPelapor;

/**
 * Keluhan dan unit pengelola (PRD 8.21, TASK 40.03).
 *
 * Rumah sakit dengan dua bagian pemeliharaan, IPSRS dan IT. Printer rusak di
 * ruang ICU dilaporkan dengan kategori IT: keluhannya harus masuk antrean IT
 * dari jalur mana pun, terlihat koordinator IT, tidak terlihat dan tidak
 * dikabarkan kepada koordinator IPSRS.
 */
final class UnitPengelolaKeluhanTest extends KasusPelapor
{
    private UnitOrganisasi $it;

    private UnitOrganisasi $ipsrs;

    private UnitOrganisasi $unitIcu;

    private Lokasi $icu;

    private KategoriKeluhan $kategoriIt;

    /** Anak kategori IT tanpa unit sendiri: unitnya diwarisi dari induk. */
    private KategoriKeluhan $kategoriPrinter;

    private KategoriKeluhan $kategoriUmum;

    private Aset $printer;

    private Pengguna $pelapor;

    private Pengguna $koordinatorIt;

    private Pengguna $koordinatorIpsrs;

    /** Koordinator tanpa batas lingkup (mis. kepala instalasi pemeliharaan). */
    private Pengguna $koordinatorPusat;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->dalamOrganisasi(function (): void {
            $this->it = UnitOrganisasi::create(['Kode' => 'IT', 'Nama' => 'Instalasi IT', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
            $this->ipsrs = UnitOrganisasi::create(['Kode' => 'IPS', 'Nama' => 'IPSRS', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
            $this->unitIcu = UnitOrganisasi::create(['Kode' => 'ICU', 'Nama' => 'ICU', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
            $this->icu = Lokasi::create(['Kode' => 'R-ICU', 'Nama' => 'Ruang ICU', 'UnitOrganisasiId' => $this->unitIcu->Id, 'Status' => 'Aktif']);
        });

        $this->kategoriIt = $this->kategori('Perangkat IT', ['UnitPengelolaId' => $this->it->Id]);
        $this->kategoriPrinter = $this->kategori('Printer', ['IndukId' => $this->kategoriIt->Id]);
        $this->kategoriUmum = $this->kategori('Umum');
        $this->printer = $this->aset('Printer ICU', $this->icu);

        $this->pelapor = $this->pelaporDi($this->icu);
        $this->koordinatorIt = $this->penggunaDenganPeran(['KOORDINATOR-PEMELIHARAAN'], $this->it->Id);
        $this->koordinatorIpsrs = $this->penggunaDenganPeran(['KOORDINATOR-PEMELIHARAAN'], $this->ipsrs->Id);
        $this->koordinatorPusat = $this->penggunaDenganPeran(['KOORDINATOR-PEMELIHARAAN']);
    }

    public function test_keluhan_printer_icu_berkategori_it_masuk_antrean_it_dari_ketiga_jalur(): void
    {
        $staf = $this->penggunaMeja();

        // Dasbor.
        $this->actingAs($staf)->post('/pemeliharaan/keluhan', [
            'KategoriKeluhanId' => $this->kategoriPrinter->Id,
            'AsetId' => $this->printer->Id,
            'LokasiId' => $this->icu->Id,
            'Judul' => 'Printer ICU dari dasbor',
            'Deskripsi' => 'Kertas macet.',
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        // Mode Lapangan, online.
        $this->actingAs($this->pelapor)->post('/lapangan/pelapor/lapor', [
            ...$this->muatanLapangan('kunci-online'),
            'Judul' => 'Printer ICU dari lapangan',
        ])->assertSessionDoesntHaveErrors();

        // Mode Lapangan, antrean offline.
        $this->actingAs($this->pelapor)->postJson('/offline/antrian', [
            'IdentitasPerangkat' => 'hp-icu',
            'Mutasi' => [[
                'KunciOperasi' => 'mutasi-offline',
                'Operasi' => 'Keluhan.Buat',
                'EntitasId' => null,
                'VersiKlien' => null,
                'MuatanData' => [...$this->muatanLapangan('kunci-offline'), 'Judul' => 'Printer ICU dari offline'],
            ]],
        ])->assertOk()->assertJsonPath('Antrean.0.Status', 'Selesai');

        $hasil = $this->dalamOrganisasi(fn () => Keluhan::query()->orderBy('Judul')->pluck('UnitPengelolaId', 'Judul')->all());

        $this->assertSame([
            'Printer ICU dari dasbor' => $this->it->Id,
            'Printer ICU dari lapangan' => $this->it->Id,
            'Printer ICU dari offline' => $this->it->Id,
        ], $hasil);
    }

    public function test_unit_pengelola_diturunkan_dari_aset_bila_kategori_tidak_menunjuk_unit(): void
    {
        $ac = $this->dalamOrganisasi(fn () => Aset::create([
            'Nama' => 'AC ICU', 'KategoriAsetId' => KategoriAset::query()->firstOrCreate(['Nama' => 'Tata Udara'])->Id,
            'LokasiId' => $this->icu->Id, 'UnitPengelolaId' => $this->ipsrs->Id, 'Status' => 'Aktif', 'Kondisi' => 'Baik',
        ]));

        $dariAset = $this->keluhan($this->pelapor, $this->kategoriUmum, $this->icu, ['AsetId' => $ac->Id]);
        $tanpaSumber = $this->keluhan($this->pelapor, $this->kategoriUmum, $this->icu);
        // Kategori menang atas aset: printer tanpa unit, kategori IT.
        $kategoriMenang = $this->keluhan($this->pelapor, $this->kategoriIt, $this->icu, ['AsetId' => $ac->Id]);

        $this->assertSame($this->ipsrs->Id, $dariAset->UnitPengelolaId);
        $this->assertNull($tanpaSumber->UnitPengelolaId);
        $this->assertSame($this->it->Id, $kategoriMenang->UnitPengelolaId);
    }

    /** Pemanggil tidak dapat memilih antreannya sendiri: unit selalu diturunkan `BuatKeluhan`. */
    public function test_unit_pengelola_dari_pemanggil_diabaikan(): void
    {
        $keluhan = $this->keluhan($this->pelapor, $this->kategoriIt, $this->icu, ['UnitPengelolaId' => $this->ipsrs->Id]);

        $this->assertSame($this->it->Id, $keluhan->UnitPengelolaId);
    }

    public function test_koordinator_it_melihat_keluhan_antrean_it_koordinator_ipsrs_tidak(): void
    {
        $keluhan = $this->keluhan($this->pelapor, $this->kategoriPrinter, $this->icu, ['AsetId' => $this->printer->Id]);

        $this->actingAs($this->koordinatorIt)->get('/pemeliharaan/keluhan')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('keluhan.data.0.Id', $keluhan->Id)
                ->where('keluhan.data.0.UnitPengelola', ['Id' => $this->it->Id, 'Kode' => 'IT', 'Nama' => 'Instalasi IT'])
                ->has('keluhan.data', 1));
        $this->actingAs($this->koordinatorIt)->get("/pemeliharaan/keluhan/{$keluhan->Id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('keluhan.UnitPengelola.Nama', 'Instalasi IT'));

        $this->actingAs($this->koordinatorIpsrs)->get('/pemeliharaan/keluhan')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->has('keluhan.data', 0));
        $this->actingAs($this->koordinatorIpsrs)->get("/pemeliharaan/keluhan/{$keluhan->Id}")->assertNotFound();
    }

    /** Pantau (PRD 8.20) memakai semantik lingkup yang sama dengan daftar: ruangan ATAU unit pengelola. */
    public function test_pantau_mengikuti_lingkup_unit_pengelola(): void
    {
        $keluhan = $this->keluhan($this->pelapor, $this->kategoriIt, $this->icu);
        $rekanIcu = $this->pelaporDi($this->icu);

        $this->dalamOrganisasi(function () use ($keluhan, $rekanIcu): void {
            $this->assertTrue(Gate::forUser($this->koordinatorIt)->allows('pantau', $keluhan));
            $this->assertFalse(Gate::forUser($this->koordinatorIpsrs)->allows('pantau', $keluhan));
            $this->assertTrue(Gate::forUser($rekanIcu)->allows('pantau', $keluhan));
        });
    }

    public function test_daftar_keluhan_dapat_disaring_menurut_unit_pengelola_dan_kategori(): void
    {
        $it = $this->keluhan($this->pelapor, $this->kategoriIt, $this->icu, ['Judul' => 'Antrean IT']);
        $tanpa = $this->keluhan($this->pelapor, $this->kategoriUmum, $this->icu, ['Judul' => 'Tanpa antrean']);
        $printer = $this->keluhan($this->pelapor, $this->kategoriPrinter, $this->icu, ['Judul' => 'Printer']);

        $idDaftar = fn (array $kueri): array => collect($this->actingAs($this->koordinatorPusat)
            ->get('/pemeliharaan/keluhan?'.http_build_query($kueri))
            ->assertOk()
            ->viewData('page')['props']['keluhan']['data'])->pluck('Id')->sort()->values()->all();

        $this->assertSame(collect([$it->Id, $printer->Id])->sort()->values()->all(), $idDaftar(['unitPengelola' => $this->it->Id]));
        $this->assertSame([$tanpa->Id], $idDaftar(['unitPengelola' => 'tanpa']));
        $this->assertSame([], $idDaftar(['unitPengelola' => $this->ipsrs->Id]));
        $this->assertSame([$printer->Id], $idDaftar(['kategori' => $this->kategoriPrinter->Id]));
        $this->assertCount(3, $idDaftar([]));

        $this->actingAs($this->koordinatorPusat)->get('/pemeliharaan/keluhan')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('pakaiUnitPengelola', true)
                ->where('pilihanUnitPengelola', [
                    ['Id' => $this->it->Id, 'Kode' => 'IT', 'Nama' => 'Instalasi IT'],
                    ['Id' => $this->ipsrs->Id, 'Kode' => 'IPS', 'Nama' => 'IPSRS'],
                ])
                ->has('pilihanKategori', 3));
    }

    public function test_routing_kategori_hanya_sampai_ke_pemegang_peran_yang_lingkupnya_mencakup_keluhan(): void
    {
        $peranKoordinator = $this->dalamOrganisasi(fn () => Peran::query()->where('Kode', 'KOORDINATOR-PEMELIHARAAN')->firstOrFail());
        $this->dalamOrganisasi(fn () => $this->kategoriIt->update(['PeranPenanggungJawabId' => $peranKoordinator->Id]));

        $keluhan = $this->keluhan($this->pelapor, $this->kategoriIt, $this->icu);

        $this->assertSame(
            collect([$this->koordinatorIt->Id, $this->koordinatorPusat->Id])->sort()->values()->all(),
            $this->penerimaNotifikasi($keluhan, 'Keluhan.Baru'),
        );
    }

    /** Kategori tanpa peran: koordinator berlingkup unit pengelolanya, bukan yang tanpa batas. */
    public function test_cadangan_routing_ke_koordinator_berlingkup_unit_pengelola(): void
    {
        $keluhan = $this->keluhan($this->pelapor, $this->kategoriIt, $this->icu);

        $this->assertSame([$this->koordinatorIt->Id], $this->penerimaNotifikasi($keluhan, 'Keluhan.Baru'));
    }

    /** Tidak seorang pun berlingkup unit itu: koordinator tanpa batas yang menerima, bukan tidak seorang pun. */
    public function test_cadangan_terakhir_ke_koordinator_tanpa_batas_bila_unit_tidak_punya_koordinator(): void
    {
        $me = $this->dalamOrganisasi(fn () => UnitOrganisasi::create(['Kode' => 'ME', 'Nama' => 'Mekanikal Elektrikal', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]));
        $kategoriMe = $this->kategori('Listrik', ['UnitPengelolaId' => $me->Id]);

        $keluhan = $this->keluhan($this->pelapor, $kategoriMe, $this->icu);

        $this->assertSame([$this->koordinatorPusat->Id], $this->penerimaNotifikasi($keluhan, 'Keluhan.Baru'));
    }

    public function test_eskalasi_sla_dari_cron_tidak_sampai_ke_koordinator_ipsrs(): void
    {
        $peranKoordinator = $this->dalamOrganisasi(fn () => Peran::query()->where('Kode', 'KOORDINATOR-PEMELIHARAAN')->firstOrFail());
        $batas = CarbonImmutable::parse('2026-09-20 12:00:00', 'Asia/Jakarta');
        $keluhan = $this->dalamOrganisasi(function () use ($peranKoordinator, $batas): Keluhan {
            $sla = TingkatLayanan::create(['Kode' => 'SLA-IT', 'Nama' => 'SLA IT']);
            EskalasiTingkatLayanan::create([
                'TingkatLayananId' => $sla->Id, 'Tahap' => 1, 'Pemicu' => PemicuEskalasiTingkatLayanan::Terlewati->value,
                'SetelahMenit' => 0, 'PeranId' => $peranKoordinator->Id, 'Kanal' => ['InApp'], 'Aktif' => true,
            ]);

            return Keluhan::create([
                'Nomor' => 'KLH-ESK', 'KategoriKeluhanId' => $this->kategoriIt->Id, 'TingkatLayananId' => $sla->Id,
                'LokasiId' => $this->icu->Id, 'UnitPengelolaId' => $this->it->Id, 'Judul' => 'Printer ICU', 'Deskripsi' => 'Macet.',
                'Status' => 'Diproses', 'PelaporId' => $this->pelapor->Id, 'DilaporkanPada' => $batas->subHours(2),
                'DiresponsPada' => $batas->subHour(), 'BatasPenyelesaianPada' => $batas,
            ]);
        });

        CarbonImmutable::setTestNow($batas->addMinute());
        try {
            // Seperti cron: job antrean tanpa konteks organisasi dan tanpa pengguna masuk.
            (new ProsesEskalasiKeluhan($this->organisasi->Id))->handle();
        } finally {
            CarbonImmutable::setTestNow();
        }

        $this->assertSame(
            collect([$this->koordinatorIt->Id, $this->koordinatorPusat->Id])->sort()->values()->all(),
            $this->penerimaNotifikasi($keluhan, 'Keluhan.Sla.Terlewati'),
        );
    }

    public function test_koordinator_mengalihkan_keluhan_ke_unit_lain_dengan_alasan(): void
    {
        $kategoriGedung = $this->kategori('Peralatan Gedung', ['UnitPengelolaId' => $this->ipsrs->Id]);
        $keluhan = $this->keluhan($this->pelapor, $kategoriGedung, $this->icu, ['AsetId' => $this->printer->Id]);
        $this->assertSame($this->ipsrs->Id, $keluhan->UnitPengelolaId);

        $this->actingAs($this->koordinatorIpsrs)->put("/pemeliharaan/keluhan/{$keluhan->Id}/unit-pengelola", [
            'UnitPengelolaId' => $this->it->Id,
            'Alasan' => 'Printer adalah perangkat IT.',
            'Versi' => $keluhan->Versi,
        ])->assertSessionDoesntHaveErrors()
            // Keluhannya kini di luar lingkup koordinator IPSRS: dibawa ke daftar, bukan detail yang 404.
            ->assertRedirect('/pemeliharaan/keluhan');

        $sesudah = $this->dalamOrganisasi(fn () => $keluhan->fresh());
        $this->assertSame($this->it->Id, $sesudah->UnitPengelolaId);
        $this->assertSame($keluhan->Versi + 1, $sesudah->Versi);

        $riwayat = $this->dalamOrganisasi(fn () => RiwayatStatusKeluhan::query()->where('KeluhanId', $keluhan->Id)->latest('DiubahPada')->orderByDesc('Id')->firstOrFail());
        $this->assertSame('Baru', $riwayat->StatusSebelum);
        $this->assertSame('Baru', $riwayat->StatusSesudah);
        $this->assertSame($this->koordinatorIpsrs->Id, $riwayat->DiubahOleh);
        $this->assertSame('Dialihkan dari IPSRS ke Instalasi IT. Alasan: Printer adalah perangkat IT.', $riwayat->Catatan);

        $audit = $this->dalamOrganisasi(fn () => CatatanAudit::query()->where('Aksi', 'AlihkanUnitPengelola')->where('EntitasId', $keluhan->Id)->sole());
        $this->assertSame($this->ipsrs->Id, $audit->DataSebelum['UnitPengelolaId']);
        $this->assertSame($this->it->Id, $audit->DataSesudah['UnitPengelolaId']);
        $this->assertSame('Printer adalah perangkat IT.', $audit->DataSesudah['AlasanPengalihan']);

        // Koordinator unit tujuan dikabari; kini ia yang melihatnya.
        $this->assertTrue($this->dalamOrganisasi(fn () => Notifikasi::query()
            ->where('PenggunaId', $this->koordinatorIt->Id)->where('EntitasId', $keluhan->Id)->where('Judul', 'Keluhan Dialihkan')->exists()));
        $this->actingAs($this->koordinatorIt)->get("/pemeliharaan/keluhan/{$keluhan->Id}")->assertOk();
        $this->actingAs($this->koordinatorIpsrs)->get("/pemeliharaan/keluhan/{$keluhan->Id}")->assertNotFound();

        // Garis waktu pantau rekan hanya berisi perubahan status, bukan pengalihan.
        $this->actingAs($this->pelaporDi($this->icu))->get("/lapangan/pelapor/pantau/{$keluhan->Id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->has('riwayat', 1));
    }

    public function test_pengalihan_tanpa_izin_atau_tanpa_alasan_ditolak(): void
    {
        $keluhan = $this->keluhan($this->pelapor, $this->kategoriIt, $this->icu);
        $url = "/pemeliharaan/keluhan/{$keluhan->Id}/unit-pengelola";
        $data = ['UnitPengelolaId' => $this->ipsrs->Id, 'Alasan' => 'Salah antrean.', 'Versi' => $keluhan->Versi];

        // Pelapornya sendiri boleh melihat keluhannya, tetapi tidak memegang Keluhan.Kelola.
        $this->actingAs($this->pelapor)->put($url, $data)->assertForbidden();
        $this->actingAs($this->penggunaMeja())->put($url, $data)->assertForbidden();

        $this->actingAs($this->koordinatorPusat)->put($url, [...$data, 'Alasan' => ''])->assertSessionHasErrors('Alasan');
        $this->actingAs($this->koordinatorPusat)->put($url, [...$data, 'UnitPengelolaId' => $this->unitIcu->Id])
            ->assertSessionHasErrors(['UnitPengelolaId' => 'Unit yang dipilih belum ditandai Mengelola Aset di halaman Unit Organisasi.']);
        $this->actingAs($this->koordinatorPusat)->put($url, [...$data, 'UnitPengelolaId' => $this->it->Id])
            ->assertSessionHasErrors(['UnitPengelolaId' => 'Keluhan ini sudah dikelola unit tersebut.']);

        $this->assertKeluhanTidakDialihkan($keluhan);
    }

    public function test_keluhan_final_tidak_dapat_dialihkan(): void
    {
        $keluhan = $this->keluhan($this->pelapor, $this->kategoriIt, $this->icu, [], [StatusKeluhan::Ditolak]);

        $this->actingAs($this->koordinatorPusat)->get("/pemeliharaan/keluhan/{$keluhan->Id}")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('hambatanPengalihan', 'Keluhan yang sudah ditutup, ditolak, atau dibatalkan tidak dapat dialihkan.'));
        $this->actingAs($this->koordinatorPusat)->put("/pemeliharaan/keluhan/{$keluhan->Id}/unit-pengelola", [
            'UnitPengelolaId' => $this->ipsrs->Id, 'Alasan' => 'Salah antrean.', 'Versi' => $keluhan->Versi,
        ])->assertSessionHasErrors('UnitPengelolaId');

        $this->assertKeluhanTidakDialihkan($keluhan);
    }

    /** Perintah kerja yang mengikuti antrean keluhan ikut dialihkan; satu pekerjaan tidak terbelah. */
    public function test_perintah_kerja_aktif_ikut_dialihkan_bersama_keluhan(): void
    {
        $keluhan = $this->keluhan($this->pelapor, $this->kategoriIt, $this->icu);
        $ikut = $this->perintahKerjaDari($keluhan, $this->it->Id, 'Dikerjakan');
        $sudahDitutup = $this->perintahKerjaDari($keluhan, $this->it->Id, 'Ditutup');

        $this->actingAs($this->koordinatorPusat)->put("/pemeliharaan/keluhan/{$keluhan->Id}/unit-pengelola", [
            'UnitPengelolaId' => $this->ipsrs->Id, 'Alasan' => 'Ternyata kabel daya gedung.', 'Versi' => $keluhan->Versi,
        ])->assertSessionDoesntHaveErrors();

        $this->dalamOrganisasi(function () use ($keluhan, $ikut, $sudahDitutup): void {
            $this->assertSame($this->ipsrs->Id, $keluhan->fresh()->UnitPengelolaId);
            $this->assertSame($this->ipsrs->Id, $ikut->fresh()->UnitPengelolaId);
            $this->assertSame($this->it->Id, $sudahDitutup->fresh()->UnitPengelolaId);
        });
    }

    /** Teknisi IT yang sedang mengerjakan tiketnya akan kehilangan akses: seluruh pengalihan ditolak. */
    public function test_pengalihan_ditolak_bila_perintah_kerjanya_tidak_dapat_ikut_dialihkan(): void
    {
        $keluhan = $this->keluhan($this->pelapor, $this->kategoriIt, $this->icu);
        $perintahKerja = $this->perintahKerjaDari($keluhan, $this->it->Id, 'Dikerjakan');
        $teknisiIt = $this->penggunaDenganPeran(['TEKNISI'], $this->it->Id);
        $this->dalamOrganisasi(fn () => PenugasanPerintahKerja::create([
            'PerintahKerjaId' => $perintahKerja->Id, 'PenggunaId' => $teknisiIt->Id, 'DitugaskanPada' => now(), 'Status' => 'Diterima',
        ]));

        $respons = $this->actingAs($this->koordinatorPusat)->put("/pemeliharaan/keluhan/{$keluhan->Id}/unit-pengelola", [
            'UnitPengelolaId' => $this->ipsrs->Id, 'Alasan' => 'Salah antrean.', 'Versi' => $keluhan->Versi,
        ]);

        $respons->assertSessionHasErrors('UnitPengelolaId');
        $this->assertStringContainsString("Perintah kerja {$perintahKerja->Nomor} ikut dialihkan", (string) session('errors')->first('UnitPengelolaId'));
        $this->assertKeluhanTidakDialihkan($keluhan);
        $this->assertSame($this->it->Id, $this->dalamOrganisasi(fn () => $perintahKerja->fresh()->UnitPengelolaId));
    }

    public function test_kategori_keluhan_menyimpan_unit_pengelola_dan_memperingatkan_kategori_tanpa_unit(): void
    {
        $admin = $this->penggunaMeja(['Pemeliharaan.Kelola']);
        $data = [
            'Nama' => 'Jaringan', 'PrioritasBawaan' => 'Normal', 'AsetWajib' => false, 'Aktif' => true,
            'IndukId' => null, 'TingkatLayananId' => null, 'PeranPenanggungJawabId' => null, 'UnitPengelolaId' => $this->it->Id,
        ];

        $this->actingAs($admin)->post('/pemeliharaan/kategori-keluhan', $data)->assertSessionDoesntHaveErrors();
        $jaringan = $this->dalamOrganisasi(fn () => KategoriKeluhan::query()->where('Nama', 'Jaringan')->sole());
        $this->assertSame($this->it->Id, $jaringan->UnitPengelolaId);

        $this->actingAs($admin)->put("/pemeliharaan/kategori-keluhan/{$jaringan->Id}", [...$data, 'UnitPengelolaId' => $this->unitIcu->Id])
            ->assertSessionHasErrors('UnitPengelolaId');
        $this->assertSame($this->it->Id, $this->dalamOrganisasi(fn () => $jaringan->fresh()->UnitPengelolaId));

        $this->actingAs($admin)->get('/pemeliharaan/kategori-keluhan')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('pakaiUnitPengelola', true)
                // Hanya "Umum": Printer mewarisi IT dari induknya.
                ->where('jumlahTanpaUnitPengelola', 1)
                ->where("unitPengelolaWarisan.{$this->kategoriPrinter->Id}", ['Id' => $this->it->Id, 'Kode' => 'IT', 'Nama' => 'Instalasi IT'])
                ->missing("unitPengelolaWarisan.{$this->kategoriUmum->Id}")
                ->has('pilihanUnitPengelola', 2));
    }

    public function test_organisasi_tanpa_unit_pengelola_tidak_berubah(): void
    {
        $this->dalamOrganisasi(function (): void {
            UnitOrganisasi::query()->update(['MengelolaAset' => false]);
            KategoriKeluhan::query()->update(['UnitPengelolaId' => null]);
        });
        $peranKoordinator = $this->dalamOrganisasi(fn () => Peran::query()->where('Kode', 'KOORDINATOR-PEMELIHARAAN')->firstOrFail());
        $this->dalamOrganisasi(fn () => $this->kategoriIt->update(['PeranPenanggungJawabId' => $peranKoordinator->Id]));
        $koordinatorLain = $this->penggunaDenganPeran(['KOORDINATOR-PEMELIHARAAN']);

        $keluhan = $this->keluhan($this->pelapor, $this->kategoriIt, $this->icu, ['AsetId' => $this->printer->Id]);
        $tanpaPeran = $this->keluhan($this->pelapor, $this->kategoriUmum, $this->icu);

        $this->assertNull($keluhan->UnitPengelolaId);
        // Routing kategori ke pemegang peran tanpa batas seperti sebelumnya; tanpa peran, tanpa notifikasi.
        $this->assertSame(
            collect([$this->koordinatorPusat->Id, $koordinatorLain->Id])->sort()->values()->all(),
            $this->penerimaNotifikasi($keluhan, 'Keluhan.Baru'),
        );
        $this->assertSame([], $this->penerimaNotifikasi($tanpaPeran, 'Keluhan.Baru'));

        $this->actingAs($this->koordinatorPusat)->get('/pemeliharaan/keluhan')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('pakaiUnitPengelola', false)
                ->where('pilihanUnitPengelola', []));
        $this->actingAs($this->koordinatorPusat)->get("/pemeliharaan/keluhan/{$keluhan->Id}")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('pakaiUnitPengelola', false)
                ->where('pilihanUnitPengelola', [])
                ->where('hambatanPengalihan', null));
        $this->actingAs($this->penggunaMeja(['Pemeliharaan.Kelola']))->get('/pemeliharaan/kategori-keluhan')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('pakaiUnitPengelola', false)
                ->where('jumlahTanpaUnitPengelola', 0));
    }

    /** @return array<string, string> */
    private function muatanLapangan(string $kunciLaporan): array
    {
        return [
            'KategoriKeluhanId' => $this->kategoriPrinter->Id,
            'AsetId' => $this->printer->Id,
            'LokasiId' => $this->icu->Id,
            'Judul' => 'Printer macet',
            'Deskripsi' => 'Kertas tersangkut.',
            'KunciLaporan' => $kunciLaporan,
        ];
    }

    /** @return list<string> penerima notifikasi keluhan ini untuk satu jenis peristiwa, terurut */
    private function penerimaNotifikasi(Keluhan $keluhan, string $jenisPeristiwa): array
    {
        return $this->dalamOrganisasi(fn () => Notifikasi::query()
            ->where('JenisEntitas', 'Keluhan')
            ->where('EntitasId', $keluhan->Id)
            ->where('JenisPeristiwa', $jenisPeristiwa)
            ->distinct()
            ->orderBy('PenggunaId')
            ->pluck('PenggunaId')
            ->map(fn ($id): string => (string) $id)
            ->all());
    }

    private function perintahKerjaDari(Keluhan $keluhan, string $unitPengelolaId, string $status): PerintahKerja
    {
        return $this->dalamOrganisasi(fn () => PerintahKerja::create([
            'Nomor' => 'PK-'.uniqid(), 'KeluhanId' => $keluhan->Id, 'Jenis' => 'Korektif', 'Judul' => $keluhan->Judul,
            'Prioritas' => 'Normal', 'Status' => $status, 'LokasiId' => $keluhan->LokasiId, 'UnitPengelolaId' => $unitPengelolaId,
        ]));
    }

    private function assertKeluhanTidakDialihkan(Keluhan $keluhan): void
    {
        $this->dalamOrganisasi(function () use ($keluhan): void {
            $this->assertSame($keluhan->UnitPengelolaId, $keluhan->fresh()->UnitPengelolaId);
            $this->assertSame($keluhan->Versi, $keluhan->fresh()->Versi);
            $this->assertFalse(CatatanAudit::query()->where('Aksi', 'AlihkanUnitPengelola')->where('EntitasId', $keluhan->Id)->exists());
        });
    }
}
