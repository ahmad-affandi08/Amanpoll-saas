<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\CatatProspek;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Domain\KatalogTahapPipeline;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AttributionPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\OrganisasiProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RiwayatTahapProspek;
use Illuminate\Support\Str;

/**
 * Pembuatan prospek dari seluruh sumber (MARKETING.md 5.1, 36).
 */
final class ProspekDibuatTest extends KasusProspek
{
    public function test_prospek_baru_masuk_tahap_awal_dan_tercatat_riwayatnya(): void
    {
        $prospek = $this->catat(['Nama' => 'Budi', 'Email' => 'budi@contoh.test']);

        $this->assertSame(KatalogTahapPipeline::BARU, $prospek->tahap?->Kode);
        $this->assertSame(1, RiwayatTahapProspek::query()->where('ProspekId', $prospek->Id)->count());
    }

    public function test_prospek_dengan_email_sama_digabungkan_bukan_diduplikasi(): void
    {
        $this->catat(['Nama' => 'Budi', 'Email' => 'budi@contoh.test']);
        $this->catat(['Nama' => 'Budi Santoso', 'Email' => 'budi@contoh.test', 'Telepon' => '0811']);

        $this->assertSame(1, Prospek::query()->count());

        $prospek = Prospek::query()->firstOrFail();
        $this->assertSame('Budi Santoso', $prospek->Nama);
        $this->assertSame('0811', $prospek->Telepon);
    }

    public function test_prospek_dengan_pengenal_pengunjung_sama_digabungkan(): void
    {
        $pengenal = (string) Str::ulid();

        $this->catat(['Nama' => 'Tanpa Email'], $pengenal);
        $this->catat(['Nama' => 'Tanpa Email Lagi'], $pengenal);

        $this->assertSame(1, Prospek::query()->count());
    }

    public function test_dua_orang_berbeda_tidak_digabungkan(): void
    {
        $this->catat(['Nama' => 'Budi', 'Email' => 'budi@contoh.test']);
        $this->catat(['Nama' => 'Siti', 'Email' => 'siti@contoh.test']);

        $this->assertSame(2, Prospek::query()->count());
    }

    public function test_sumber_prospek_tidak_berubah_saat_ia_kembali(): void
    {
        $this->catat(['Nama' => 'Budi', 'Email' => 'budi@contoh.test'], sumber: SumberProspek::Website);
        $this->catat(['Nama' => 'Budi', 'Email' => 'budi@contoh.test'], sumber: SumberProspek::Manual);

        $this->assertSame(SumberProspek::Website->value, Prospek::query()->value('Sumber'));
    }

    public function test_perusahaan_dipakai_ulang_untuk_prospek_dari_tempat_yang_sama(): void
    {
        $this->catat(['Nama' => 'Budi', 'Email' => 'budi@pabrik.test', 'Perusahaan' => 'PT Pabrik']);
        $this->catat(['Nama' => 'Siti', 'Email' => 'siti@pabrik.test', 'Perusahaan' => 'PT Pabrik']);

        $this->assertSame(1, OrganisasiProspek::query()->count());
        $this->assertSame(2, Prospek::query()->whereNotNull('OrganisasiProspekId')->count());
    }

    public function test_kampanye_prospek_diambil_dari_first_touch(): void
    {
        $kampanye = Kampanye::create([
            'Kode' => 'iklan-awal',
            'Nama' => 'Iklan Awal',
            'Objective' => 'Lead',
            'Status' => 'Aktif',
        ]);

        $pengenal = (string) Str::ulid();
        AttributionPemasaran::create([
            'PengenalPengunjung' => $pengenal,
            'SumberPertama' => 'google',
            'KampanyePertama' => 'iklan-awal',
            'KampanyeIdPertama' => $kampanye->Id,
            'SentuhanPertamaPada' => now()->subDays(5),
            'SumberTerakhir' => 'direct',
            'KampanyeTerakhir' => null,
            'SentuhanTerakhirPada' => now(),
        ]);

        $prospek = $this->catat(['Nama' => 'Budi', 'Email' => 'budi@contoh.test'], $pengenal);

        $this->assertSame($kampanye->Id, $prospek->KampanyeId);
    }

    public function test_pembuatan_prospek_mencatat_peristiwa_formulir_dikirim(): void
    {
        $this->catat(['Nama' => 'Budi', 'Email' => 'budi@contoh.test'], (string) Str::ulid());

        $this->assertTrue(
            EventPemasaran::query()->where('Jenis', KatalogPeristiwaPemasaran::FORMULIR_DIKIRIM)->exists(),
        );
    }

    /** @param array<string, mixed> $data */
    private function catat(array $data, ?string $pengenal = null, ?SumberProspek $sumber = null): Prospek
    {
        return app(CatatProspek::class)->jalankan($data, $sumber ?? SumberProspek::Website, $pengenal);
    }
}
