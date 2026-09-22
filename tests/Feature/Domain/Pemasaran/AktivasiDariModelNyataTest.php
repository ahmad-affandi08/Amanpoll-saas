<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemasaran\Domain\Enums\ButirAktivasi;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
use Illuminate\Support\Str;

/**
 * Checklist aktivasi terisi dari model domain yang sungguhan (MARKETING.md 12).
 *
 * Diuji lewat pembuatan barisnya, bukan lewat aksi pencatatnya, supaya
 * pemasangan observer ikut terbukti — observer yang lupa didaftarkan akan lolos
 * dari test yang memanggil aksinya langsung.
 */
final class AktivasiDariModelNyataTest extends KasusTrial
{
    public function test_lokasi_mencentang_butirnya(): void
    {
        $trial = $this->mulaiTrial();

        $this->buatLokasi();

        $this->assertButirSelesai($trial, ButirAktivasi::LokasiDibuat);
    }

    public function test_aset_mencentang_butirnya(): void
    {
        $trial = $this->mulaiTrial();

        $this->buatAset();

        $this->assertButirSelesai($trial, ButirAktivasi::AsetPertama);
    }

    public function test_pengguna_baru_mencentang_butir_undangan(): void
    {
        $trial = $this->mulaiTrial();

        $this->buatPengguna();

        $this->assertButirSelesai($trial, ButirAktivasi::PenggunaDiundang);
    }

    public function test_perintah_kerja_mencentang_butirnya(): void
    {
        $trial = $this->mulaiTrial();

        $this->buatPerintahKerja();

        $this->assertButirSelesai($trial, ButirAktivasi::PerintahKerjaPertama);
    }

    public function test_rencana_pemeliharaan_mencentang_butir_preventif(): void
    {
        $trial = $this->mulaiTrial();

        $this->buatRencanaPemeliharaan();

        $this->assertButirSelesai($trial, ButirAktivasi::PreventifPertama);
    }

    public function test_pekerjaan_nyata_menuntaskan_aktivasi(): void
    {
        $trial = $this->mulaiTrial($this->buatProspek());

        $this->buatLokasi();
        $this->buatAset();
        $this->buatPerintahKerja();

        $this->assertSame(StatusTrial::Teraktivasi, $trial->fresh()?->Status);
        $this->assertTrue(EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::TRIAL_TERAKTIVASI)
            ->where('OrganisasiId', $trial->OrganisasiId)
            ->exists());
    }

    public function test_setiap_butir_mencatat_peristiwanya_sendiri(): void
    {
        $this->mulaiTrial();

        $this->buatLokasi();
        $this->buatAset();
        $this->buatPengguna();
        $this->buatPerintahKerja();
        $this->buatRencanaPemeliharaan();

        foreach ([
            KatalogPeristiwaPemasaran::LOKASI_PERTAMA_DIBUAT,
            KatalogPeristiwaPemasaran::ASET_PERTAMA_DIBUAT,
            KatalogPeristiwaPemasaran::PENGGUNA_PERTAMA_DIUNDANG,
            KatalogPeristiwaPemasaran::PERINTAH_KERJA_PERTAMA_DIBUAT,
            KatalogPeristiwaPemasaran::PREVENTIVE_PERTAMA_DIBUAT,
        ] as $jenis) {
            $this->assertTrue(
                EventPemasaran::query()->where('Jenis', $jenis)->exists(),
                "Peristiwa {$jenis} tidak tercatat.",
            );
        }
    }

    public function test_baris_kedua_tidak_mencatat_peristiwa_kedua(): void
    {
        $this->mulaiTrial();

        $this->buatAset();
        $this->buatAset();

        $this->assertSame(1, EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::ASET_PERTAMA_DIBUAT)
            ->count());
    }

    private function assertButirSelesai(Trial $trial, ButirAktivasi $butir): void
    {
        $this->assertContains($butir->value, $trial->fresh(['butir'])?->butirSelesai() ?? []);
    }

    private function buatLokasi(): Lokasi
    {
        return Lokasi::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'LOK-'.Str::random(6),
            'Nama' => 'Lokasi Uji',
        ]);
    }

    private function buatAset(): Aset
    {
        $kategori = KategoriAset::firstOrCreate(
            ['OrganisasiId' => $this->organisasi->Id, 'Kode' => 'KAT-UJI'],
            ['Nama' => 'Kategori Uji'],
        );

        return Aset::create([
            'OrganisasiId' => $this->organisasi->Id,
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-'.Str::random(6),
            'Nama' => 'Aset Uji',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
            'KodeQr' => (string) Str::ulid(),
        ]);
    }

    private function buatPengguna(): Pengguna
    {
        return Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Teknisi Uji',
            'Email' => 'teknisi+'.Str::random(6).'@pabrik.test',
            'KataSandi' => 'rahasia-panjang',
            'Status' => 'Aktif',
        ]);
    }

    private function buatPerintahKerja(): PerintahKerja
    {
        return PerintahKerja::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nomor' => 'WO-'.Str::random(6),
            'Judul' => 'Pekerjaan uji',
            'Jenis' => 'Korektif',
            'Status' => 'Baru',
            'Prioritas' => 'Normal',
        ]);
    }

    private function buatRencanaPemeliharaan(): RencanaPemeliharaan
    {
        return RencanaPemeliharaan::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'RPM-'.Str::random(6),
            'Nama' => 'Rencana Uji',
        ]);
    }
}
