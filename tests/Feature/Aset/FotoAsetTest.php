<?php

declare(strict_types=1);

namespace Tests\Feature\Aset;

use App\Domain\Aset\Application\Actions\TambahFotoAset;
use App\Domain\Aset\Application\Services\GaleriFotoAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Kolaborasi\Application\Actions\LampirkanBerkas;
use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Infrastructure\Kompresi\MetodeKompresi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Lapangan\KasusPelapor;

/**
 * Galeri foto aset (PRD 8.4 "Foto Aset", TASK 42.02): foto utama otomatis dan
 * pengganti, batas 10, izin tambah/hapus/utama, akses lihat per kategori
 * lampiran, batas tenant, daftar tanpa N+1, dan jalur lampiran umum yang tidak
 * dapat melangkahi galeri.
 */
final class FotoAsetTest extends KasusPelapor
{
    private Lokasi $gedung;

    private Aset $aset;

    private Pengguna $pengelola;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->gedung = $this->lokasi('Gedung A');
        $this->aset = $this->aset('Genset Gedung A', $this->gedung);
        $this->pengelola = $this->penggunaMeja(['Aset.Lihat', 'Aset.Ubah']);
    }

    public function test_foto_pertama_menjadi_utama_tersimpan_webp_berthumbnail_dan_teraudit(): void
    {
        $this->actingAs($this->pengelola)
            ->post(route('aset.foto.store', $this->aset), ['Foto' => [$this->foto('depan.jpg'), $this->foto('samping.jpg')]])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $foto = $this->galeri($this->aset);
        $this->assertCount(2, $foto);
        $this->assertSame($foto[0]->BerkasId, $this->utama($this->aset), 'Foto pertama otomatis menjadi utama.');

        $berkas = $this->dalamOrganisasi(fn () => Berkas::query()->findOrFail($foto[0]->BerkasId));
        $this->assertSame(MetodeKompresi::GambarUlang, $berkas->MetodeKompresi);
        $this->assertSame('image/webp', $berkas->JenisMime);
        $this->assertNotNull($berkas->LokasiThumbnail);
        Storage::disk('local')->assertExists($berkas->LokasiThumbnail);

        $thumbnail = $this->actingAs($this->pengelola)->get(route('kolaborasi.berkas.thumbnail', $berkas));
        $thumbnail->assertOk()->assertHeader('Content-Type', 'image/webp');
        $ukuran = getimagesizefromstring($thumbnail->streamedContent());
        $this->assertSame([480, 360], $ukuran === false ? null : [$ukuran[0], $ukuran[1]], 'Thumbnail 480 px, bukan ukuran penuh.');

        $audit = $this->dalamOrganisasi(fn () => CatatanAudit::query()->where('Aksi', 'Aset.FotoDitambahkan')->where('EntitasId', $this->aset->Id)->firstOrFail());
        $this->assertSame($foto[0]->BerkasId, $audit->DataSesudah['FotoUtamaBerkasId']);
        $this->assertCount(2, $audit->DataSesudah['BerkasId']);

        $this->actingAs($this->pengelola)->get(route('aset.show', $this->aset))
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Aset/Show')
                ->where('aset.FotoUtamaBerkasId', $foto[0]->BerkasId)
                ->where('aset.FotoUtamaThumbnailUrl', "/kolaborasi/berkas/{$foto[0]->BerkasId}/thumbnail")
                ->has('foto', 2)
                ->where('foto.0.Utama', true)
                ->where('foto.1.Utama', false)
                ->where('bolehTambahFoto', true)
                ->where('bolehKelolaFoto', true));
    }

    public function test_galeri_paling_banyak_sepuluh_foto(): void
    {
        $this->tambahFoto($this->aset, 9);

        $this->actingAs($this->pengelola)
            ->post(route('aset.foto.store', $this->aset), ['Foto' => [$this->foto('a.jpg'), $this->foto('b.jpg')]])
            ->assertSessionHasErrors(['Foto' => 'Galeri aset paling banyak 10 foto; tersisa tempat untuk 1 foto lagi.']);
        $this->assertCount(9, $this->galeri($this->aset));

        $this->actingAs($this->pengelola)
            ->post(route('aset.foto.store', $this->aset), ['Foto' => [$this->foto('ke-10.jpg')]])
            ->assertSessionHasNoErrors();
        $this->assertCount(10, $this->galeri($this->aset));

        $this->actingAs($this->pengelola)
            ->postJson(route('aset.foto.store', $this->aset), ['Foto' => [$this->foto('ke-11.jpg')]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['Foto' => 'Galeri aset ini sudah berisi 10 foto, batas paling banyak. Hapus foto lama lebih dulu.']);
        $this->assertCount(10, $this->galeri($this->aset));
    }

    /** Batas diperiksa ulang di Action (di bawah kunci baris aset), dan berkas yang sudah tertulis dibersihkan. */
    public function test_action_menolak_foto_kesebelas_tanpa_meninggalkan_berkas(): void
    {
        $this->tambahFoto($this->aset, 10);
        $jumlahBerkas = $this->dalamOrganisasi(fn () => Berkas::query()->count());

        try {
            $this->tambahFoto($this->aset, 1);
            $this->fail('Foto ke-11 seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringContainsString('batas paling banyak', $galat->getMessage());
        }

        $this->assertSame($jumlahBerkas, $this->dalamOrganisasi(fn () => Berkas::query()->count()));
        $this->assertCount(10, $this->galeri($this->aset));
    }

    public function test_menghapus_foto_utama_memindahkan_utama_ke_foto_berikutnya_lalu_kosong(): void
    {
        $this->tambahFoto($this->aset, 3);
        [$pertama, $kedua, $ketiga] = array_map(fn (LampiranEntitas $satu): string => $satu->BerkasId, $this->galeri($this->aset));

        $this->actingAs($this->pengelola)->delete(route('aset.foto.destroy', [$this->aset, $kedua]))->assertSessionHasNoErrors();
        $this->assertSame($pertama, $this->utama($this->aset), 'Menghapus foto biasa tidak mengubah foto utama.');

        $this->actingAs($this->pengelola)->delete(route('aset.foto.destroy', [$this->aset, $pertama]))->assertSessionHasNoErrors();
        $this->assertSame($ketiga, $this->utama($this->aset), 'Foto utama pindah ke foto tersisa.');
        $this->assertTrue($this->dalamOrganisasi(fn () => Berkas::withTrashed()->findOrFail($pertama)->trashed()));

        $this->actingAs($this->pengelola)->delete(route('aset.foto.destroy', [$this->aset, $ketiga]))->assertSessionHasNoErrors();
        $this->assertNull($this->utama($this->aset));
        $this->assertCount(0, $this->galeri($this->aset));

        $audit = $this->dalamOrganisasi(fn () => CatatanAudit::query()->where('Aksi', 'Aset.FotoDihapus')->where('DataSebelum->BerkasId', $pertama)->firstOrFail());
        $this->assertSame(['FotoUtamaBerkasId' => $ketiga], $audit->DataSesudah);
    }

    public function test_jadikan_utama_hanya_untuk_foto_galeri_aset_itu(): void
    {
        $this->tambahFoto($this->aset, 2);
        $kedua = $this->galeri($this->aset)[1]->BerkasId;
        $asetLain = $this->aset('Pompa Air', $this->gedung);
        $this->tambahFoto($asetLain, 1);
        $fotoAsetLain = $this->galeri($asetLain)[0]->BerkasId;

        $this->actingAs($this->pengelola)->put(route('aset.foto.utama', [$this->aset, $kedua]))->assertSessionHasNoErrors();
        $this->assertSame($kedua, $this->utama($this->aset));
        $this->assertTrue($this->dalamOrganisasi(fn () => CatatanAudit::query()->where('Aksi', 'Aset.FotoUtamaDiubah')->where('DataSesudah->FotoUtamaBerkasId', $kedua)->exists()));

        $this->actingAs($this->pengelola)->putJson(route('aset.foto.utama', [$this->aset, $fotoAsetLain]))->assertNotFound();
        $this->actingAs($this->pengelola)->deleteJson(route('aset.foto.destroy', [$this->aset, $fotoAsetLain]))->assertNotFound();
        $this->assertSame($kedua, $this->utama($this->aset));
        $this->assertCount(1, $this->galeri($asetLain));
    }

    public function test_tanpa_aset_ubah_tidak_boleh_menambah_menghapus_atau_mengganti_utama(): void
    {
        $this->tambahFoto($this->aset, 2);
        [$pertama, $kedua] = array_map(fn (LampiranEntitas $satu): string => $satu->BerkasId, $this->galeri($this->aset));
        $pembaca = $this->penggunaMeja(['Aset.Lihat']);

        $this->actingAs($pembaca)->postJson(route('aset.foto.store', $this->aset), ['Foto' => [$this->foto('x.jpg')]])->assertForbidden();
        $this->actingAs($pembaca)->deleteJson(route('aset.foto.destroy', [$this->aset, $pertama]))->assertForbidden();
        $this->actingAs($pembaca)->putJson(route('aset.foto.utama', [$this->aset, $kedua]))->assertForbidden();

        $this->assertCount(2, $this->galeri($this->aset));
        $this->assertSame($pertama, $this->utama($this->aset));
        $this->actingAs($pembaca)->get(route('aset.show', $this->aset))
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->has('foto', 2)
                ->where('bolehTambahFoto', false)
                ->where('bolehKelolaFoto', false));
    }

    public function test_teknisi_yang_ditugaskan_pada_tiket_aktif_aset_boleh_menambah_tetapi_tidak_menghapus(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        // Aset ini bukan aset utama tiketnya: daftar aset tiket juga dihitung.
        $this->tiket($teknisi, [$this->aset('Panel LVMDP', $this->gedung), $this->aset], 'Dikerjakan', 'Diterima');

        $this->actingAs($teknisi)
            ->postJson(route('aset.foto.store', $this->aset), ['Foto' => [$this->foto('dari-hp.jpg')]])
            ->assertCreated()
            ->assertJsonPath('FotoUtamaThumbnailUrl', fn (string $url): bool => str_ends_with($url, '/thumbnail'));
        $berkasId = $this->galeri($this->aset)[0]->BerkasId;
        $this->assertSame($berkasId, $this->utama($this->aset));

        $this->actingAs($teknisi)->deleteJson(route('aset.foto.destroy', [$this->aset, $berkasId]))->assertForbidden();
        $this->actingAs($teknisi)->putJson(route('aset.foto.utama', [$this->aset, $berkasId]))->assertForbidden();
        // Jalur berkas umum juga tidak membuka penghapusan foto galeri bagi pengunggahnya.
        $this->actingAs($teknisi)->deleteJson(route('kolaborasi.berkas.destroy', $berkasId))->assertUnprocessable();
        $this->assertCount(1, $this->galeri($this->aset));
    }

    public function test_teknisi_tanpa_penugasan_aktif_ditolak_dengan_pesan_yang_dapat_dipahami(): void
    {
        $tanpaTugas = $this->penggunaDenganPeran(['TEKNISI']);
        $penugasanSelesai = $this->penggunaDenganPeran(['TEKNISI']);
        $tiketSelesai = $this->penggunaDenganPeran(['TEKNISI']);
        $asetLain = $this->penggunaDenganPeran(['TEKNISI']);
        $this->tiket($penugasanSelesai, [$this->aset], 'Dikerjakan', 'Selesai');
        $this->tiket($tiketSelesai, [$this->aset], 'MenungguVerifikasi', 'Diterima');
        $this->tiket($asetLain, [$this->aset('Lift Barang', $this->gedung)], 'Dikerjakan', 'Diterima');

        foreach ([$tanpaTugas, $penugasanSelesai, $tiketSelesai, $asetLain] as $teknisi) {
            $this->actingAs($teknisi)
                ->postJson(route('aset.foto.store', $this->aset), ['Foto' => [$this->foto('x.jpg')]])
                ->assertForbidden()
                ->assertJsonPath('pesan', 'Anda tidak berhak menambah foto aset ini. Teknisi hanya dapat menambah foto selama ditugaskan pada perintah kerja aktif untuk aset itu.');
        }

        $this->assertCount(0, $this->galeri($this->aset));
        $this->assertSame(0, $this->dalamOrganisasi(fn () => Berkas::query()->count()), 'Berkas ditolak sebelum ditulis.');
    }

    public function test_pelapor_di_lingkup_melihat_thumbnail_foto_aset_tetapi_tidak_lampiran_lain(): void
    {
        $this->tambahFoto($this->aset, 1);
        $foto = $this->galeri($this->aset)[0]->BerkasId;
        $dokumen = $this->lampiran($this->aset, 'Dokumen');
        $gedungLain = $this->lokasi('Gedung B');
        $asetLuarLingkup = $this->aset('Chiller Gedung B', $gedungLain);
        $this->tambahFoto($asetLuarLingkup, 1);
        $fotoLuarLingkup = $this->galeri($asetLuarLingkup)[0]->BerkasId;
        $pelapor = $this->pelaporDi($this->gedung);

        // Pengguna lapangan murni: `<img>` thumbnail tidak dialihkan ke Mode Lapangan.
        $this->actingAs($pelapor)->get(route('kolaborasi.berkas.thumbnail', $foto))->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->actingAs($pelapor)->get(route('kolaborasi.berkas.unduh', $foto))->assertOk();
        $this->actingAs($pelapor)->get(route('kolaborasi.berkas.thumbnail', $dokumen))->assertForbidden();
        $this->actingAs($pelapor)->get(route('kolaborasi.berkas.unduh', $dokumen))->assertForbidden();
        $this->actingAs($pelapor)->get(route('kolaborasi.berkas.thumbnail', $fotoLuarLingkup))->assertForbidden();
        // Melihat bukan mengelola: daftar lampiran dan hapus tetap tertutup.
        $this->actingAs($pelapor)->getJson(route('kolaborasi.lampiran.index', ['jenisEntitas' => 'Aset', 'entitasId' => $this->aset->Id]))->assertForbidden();
        $this->actingAs($pelapor)->deleteJson(route('kolaborasi.berkas.destroy', $foto))->assertForbidden();
    }

    public function test_organisasi_lain_tidak_dapat_melihat_atau_mengubah_galeri(): void
    {
        $this->tambahFoto($this->aset, 1);
        $foto = $this->galeri($this->aset)[0]->BerkasId;
        $asli = $this->organisasi;
        $this->organisasi = Organisasi::create(['Kode' => 'ORG-LAIN-'.uniqid(), 'Nama' => 'Organisasi Lain', 'Status' => 'Aktif']);
        $penyusup = $this->penggunaMeja(['Aset.Lihat', 'Aset.Ubah']);
        $this->organisasi = $asli;

        $this->actingAs($penyusup)->get(route('kolaborasi.berkas.thumbnail', $foto))->assertNotFound();
        $this->actingAs($penyusup)->postJson(route('aset.foto.store', $this->aset), ['Foto' => [$this->foto('x.jpg')]])->assertNotFound();
        $this->actingAs($penyusup)->deleteJson(route('aset.foto.destroy', [$this->aset, $foto]))->assertNotFound();
        $this->actingAs($penyusup)->putJson(route('aset.foto.utama', [$this->aset, $foto]))->assertNotFound();
        $this->assertCount(1, $this->galeri($this->aset));
    }

    public function test_daftar_aset_memuat_thumbnail_foto_utama_tanpa_kueri_per_baris(): void
    {
        foreach (['Genset B', 'Pompa C'] as $nama) {
            $this->tambahFoto($this->aset($nama, $this->gedung), 1);
        }
        $this->tambahFoto($this->aset, 1);
        $sedikit = $this->hitungKueriDaftarAset();

        foreach (['Chiller D', 'Lift E', 'AC F', 'Panel G'] as $nama) {
            $this->tambahFoto($this->aset($nama, $this->gedung), 1);
        }
        $banyak = $this->hitungKueriDaftarAset();

        $this->assertSame($sedikit, $banyak, 'Jumlah kueri daftar aset tidak bertambah per aset berfoto.');
        $this->actingAs($this->pengelola)->get(route('aset.index'))
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->has('aset.data', 7)
                ->where('aset.data', fn ($baris): bool => collect($baris)->every(
                    fn (array $satu): bool => $satu['FotoUtamaThumbnailUrl'] === "/kolaborasi/berkas/{$satu['FotoUtamaBerkasId']}/thumbnail",
                )));
    }

    /** Label QR tidak punya halaman publik: resolvernya wajib masuk, dan foto tidak pernah terbuka bagi tamu. */
    public function test_halaman_qr_tanpa_masuk_tidak_memuat_foto(): void
    {
        $this->tambahFoto($this->aset, 1);
        $foto = $this->galeri($this->aset)[0]->BerkasId;

        $pindai = $this->get(route('aset.pindai', $this->aset->KodeQr));
        $pindai->assertRedirect(route('login'));
        $this->assertStringNotContainsString($foto, (string) $pindai->getContent());
        $this->get(route('kolaborasi.berkas.thumbnail', $foto))->assertRedirect(route('login'));
        $this->get(route('kolaborasi.berkas.unduh', $foto))->assertRedirect(route('login'));
    }

    public function test_jalur_lampiran_umum_tidak_dapat_melangkahi_galeri_foto(): void
    {
        $this->tambahFoto($this->aset, 1);
        $foto = $this->galeri($this->aset)[0];
        $dokumen = $this->lampiran($this->aset, 'Dokumen');

        $this->actingAs($this->pengelola)
            ->post(route('kolaborasi.berkas.store'), [
                'Berkas' => $this->foto('samping.jpg'), 'JenisEntitas' => 'Aset', 'EntitasId' => $this->aset->Id, 'Kategori' => GaleriFotoAset::KATEGORI,
            ])
            ->assertSessionHasErrors('Kategori');
        $this->actingAs($this->pengelola)->deleteJson(route('kolaborasi.berkas.destroy', $foto->BerkasId))->assertUnprocessable();
        $this->actingAs($this->pengelola)->deleteJson(route('kolaborasi.lampiran.destroy', $foto->Id))->assertUnprocessable();

        $this->actingAs($this->pengelola)
            ->getJson(route('kolaborasi.lampiran.index', ['jenisEntitas' => 'Aset', 'entitasId' => $this->aset->Id]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.BerkasId', $dokumen);
        $this->assertCount(1, $this->galeri($this->aset));
        $this->assertSame($foto->BerkasId, $this->utama($this->aset));
    }

    private function foto(string $nama): UploadedFile
    {
        return UploadedFile::fake()->image($nama, 640, 480);
    }

    private function tambahFoto(Aset $aset, int $jumlah): void
    {
        $this->dalamOrganisasi(fn () => app(TambahFotoAset::class)->jalankan(
            $aset,
            array_map(fn (int $i): UploadedFile => $this->foto("foto-{$i}.jpg"), range(1, $jumlah)),
            $this->pengelola->Id,
        ));
    }

    /** @return list<LampiranEntitas> */
    private function galeri(Aset $aset): array
    {
        return array_values($this->dalamOrganisasi(fn () => app(GaleriFotoAset::class)->kueri($aset->Id)->get()->all()));
    }

    private function utama(Aset $aset): ?string
    {
        $nilai = $this->dalamOrganisasi(fn () => Aset::query()->whereKey($aset->Id)->value('FotoUtamaBerkasId'));

        return is_string($nilai) ? $nilai : null;
    }

    /** Lampiran kategori lain pada aset yang sama, lewat jalur Kolaborasi biasa. */
    private function lampiran(Aset $aset, string $kategori): string
    {
        return $this->dalamOrganisasi(function () use ($aset, $kategori): string {
            $berkas = app(PenyimpanBerkas::class)->simpanUnggahan(UploadedFile::fake()->image('kuitansi.png', 600, 600), $this->pengelola->Id);
            app(LampirkanBerkas::class)->jalankan('Aset', $aset->Id, $berkas->Id, $kategori, null, $this->pengelola->Id);

            return $berkas->Id;
        });
    }

    /**
     * Tiket berisi satu atau beberapa aset (aset pertama sebagai utama), dengan teknisi ditugaskan.
     *
     * @param  list<Aset>  $aset
     */
    private function tiket(Pengguna $teknisi, array $aset, string $status, string $statusPenugasan): PerintahKerja
    {
        return $this->dalamOrganisasi(function () use ($teknisi, $aset, $status, $statusPenugasan): PerintahKerja {
            $tiket = PerintahKerja::create([
                'Nomor' => 'PK-FOTO-'.uniqid(), 'Jenis' => 'Korektif', 'Judul' => 'Genset tidak menyala',
                'Prioritas' => 'Tinggi', 'Status' => $status,
            ]);
            foreach ($aset as $i => $satu) {
                PerintahKerjaAset::create(['PerintahKerjaId' => $tiket->Id, 'AsetId' => $satu->Id, 'Utama' => $i === 0]);
            }
            PenugasanPerintahKerja::create([
                'PerintahKerjaId' => $tiket->Id, 'PenggunaId' => $teknisi->Id, 'Status' => $statusPenugasan, 'DitugaskanPada' => now()->subHour(),
            ]);

            return $tiket;
        });
    }

    private function hitungKueriDaftarAset(): int
    {
        // Permintaan pertama mengisi cache izin dan sesi; yang diukur permintaan sesudahnya.
        $this->actingAs($this->pengelola)->get(route('aset.index'))->assertOk();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('aset.index'))->assertOk();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }
}
