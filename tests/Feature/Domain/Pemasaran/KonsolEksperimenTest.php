<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Domain\Enums\MetrikEksperimen;
use App\Domain\Pemasaran\Domain\Enums\StatusEksperimen;
use App\Domain\Pemasaran\Domain\Enums\TargetEksperimen;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksperimenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VarianEksperimen;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Konsol eksperimen beserta penjaganya (MARKETING.md 22). */
final class KonsolEksperimenTest extends KasusEksperimen
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->nyalakanFitur(KatalogFiturPlatform::EKSPERIMEN);
    }

    public function test_konsol_tertutup_saat_flag_eksperimen_mati(): void
    {
        $this->matikanFitur(KatalogFiturPlatform::EKSPERIMEN);

        $this->actingAs($this->pengelola(), 'platform')
            ->get(route('pemasaran.eksperimen.index'))
            ->assertNotFound();
    }

    public function test_eksperimen_baru_lahir_sebagai_draf(): void
    {
        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.eksperimen.store'), $this->muatan())
            ->assertRedirect();

        $eksperimen = EksperimenPemasaran::query()->firstOrFail();

        $this->assertSame(StatusEksperimen::Draf, $eksperimen->Status);
        $this->assertSame(2, $eksperimen->varian()->count());
    }

    /** Satu varian saja bukan eksperimen; tidak ada yang dibandingkan. */
    public function test_eksperimen_dengan_satu_varian_ditolak(): void
    {
        $muatan = $this->muatan();
        $muatan['Varian'] = [array_values($muatan['Varian'])[0]];

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.eksperimen.store'), $muatan)
            ->assertSessionHasErrors('Varian');
    }

    public function test_tanpa_varian_kontrol_ditolak(): void
    {
        $muatan = $this->muatan();
        $muatan['Varian'][0]['Kontrol'] = false;

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.eksperimen.store'), $muatan)
            ->assertSessionHasErrors('Varian');
    }

    public function test_dua_varian_kontrol_ditolak(): void
    {
        $muatan = $this->muatan();
        $muatan['Varian'][1]['Kontrol'] = true;

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.eksperimen.store'), $muatan)
            ->assertSessionHasErrors('Varian');
    }

    public function test_seluruh_bobot_nol_ditolak(): void
    {
        $muatan = $this->muatan();
        $muatan['Varian'][0]['Bobot'] = 0;
        $muatan['Varian'][1]['Bobot'] = 0;

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.eksperimen.store'), $muatan)
            ->assertSessionHasErrors('Varian');
    }

    /** Varian yang sudah punya peserta tidak dapat dihapus; angkanya akan kehilangan arti. */
    public function test_varian_berpeserta_tidak_dapat_dihapus(): void
    {
        $eksperimen = $this->buatEksperimen();
        $this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'B'), 3);

        $muatan = $this->muatan();
        $muatan['Kode'] = $eksperimen->Kode;
        $muatan['Varian'] = [
            ['Kode' => 'A', 'Nama' => 'Kontrol', 'Bobot' => 1, 'Kontrol' => true],
            ['Kode' => 'C', 'Nama' => 'Varian baru', 'Bobot' => 1, 'Kontrol' => false],
        ];

        $this->expectException(AturanBisnisDilanggar::class);

        $this->withoutExceptionHandling()
            ->actingAs($this->pengelola(), 'platform')
            ->put(route('pemasaran.eksperimen.update', $eksperimen), $muatan);
    }

    public function test_transisi_status_di_luar_peta_ditolak(): void
    {
        $eksperimen = $this->buatEksperimen(status: StatusEksperimen::Draf);

        $this->expectException(AturanBisnisDilanggar::class);

        $this->withoutExceptionHandling()
            ->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.eksperimen.status', $eksperimen), [
                'Status' => StatusEksperimen::Selesai->value,
            ]);
    }

    public function test_transisi_status_yang_sah_diterima(): void
    {
        $eksperimen = $this->buatEksperimen(status: StatusEksperimen::Draf);

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.eksperimen.status', $eksperimen), [
                'Status' => StatusEksperimen::Aktif->value,
            ])
            ->assertRedirect();

        $segar = $eksperimen->fresh();

        $this->assertSame(StatusEksperimen::Aktif, $segar?->Status);
        $this->assertNotNull($segar?->MulaiPada);
    }

    /** Konsol menyebut alasan pemenang belum boleh dinyatakan, bukan hanya menyembunyikan tombolnya. */
    public function test_konsol_menyebut_alasan_pemenang_belum_dapat_dinyatakan(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 100);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'A'), 5), 1);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'B'), 5), 5);

        $props = $this->actingAs($this->pengelola(), 'platform')
            ->get(route('pemasaran.eksperimen.index'))
            ->viewData('page')['props'];

        $penilaian = $props['eksperimen'][0]['Penilaian'];

        $this->assertFalse($penilaian['BolehDinyatakan']);
        $this->assertStringContainsString('Sampel minimum', (string) $penilaian['Alasan']);
    }

    /** Rute nyatakan pemenang pun menolak bila ambangnya belum tercapai. */
    public function test_rute_pemenang_menolak_di_bawah_ambang(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 100);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'B'), 5), 5);

        $this->expectException(AturanBisnisDilanggar::class);

        $this->withoutExceptionHandling()
            ->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.eksperimen.pemenang', $eksperimen));
    }

    public function test_admin_tanpa_izin_eksperimen_ditolak(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PEMASARAN_LIHAT]), 'platform')
            ->get(route('pemasaran.eksperimen.index'))
            ->assertForbidden();
    }

    /** Konsol menampilkan peserta dan hasil tiap varian, bukan hanya rasionya. */
    public function test_konsol_menampilkan_peserta_dan_hasil_tiap_varian(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 2);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'B'), 4), 1);

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.eksperimen.hitung', $eksperimen))
            ->assertRedirect();

        $props = $this->actingAs($this->pengelola(), 'platform')
            ->get(route('pemasaran.eksperimen.index'))
            ->viewData('page')['props'];

        $varian = collect($props['eksperimen'][0]['Varian'])->firstWhere('Kode', 'B');

        $this->assertSame(4, $varian['Peserta']);
        $this->assertSame(1, $varian['Hasil'][MetrikEksperimen::Ctr->value]['Pembilang']);
        $this->assertSame(0.25, $varian['Hasil'][MetrikEksperimen::Ctr->value]['Rasio']);
    }

    /** Varian yang bobotnya diubah tetap dapat disimpan selama tidak dihapus. */
    public function test_bobot_varian_dapat_diubah_walau_sudah_berpeserta(): void
    {
        $eksperimen = $this->buatEksperimen();
        $this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'B'), 3);

        $muatan = $this->muatan();
        $muatan['Kode'] = $eksperimen->Kode;
        $muatan['Varian'][1]['Bobot'] = 9;

        $this->actingAs($this->pengelola(), 'platform')
            ->put(route('pemasaran.eksperimen.update', $eksperimen), $muatan)
            ->assertRedirect();

        $this->assertSame(9, VarianEksperimen::query()
            ->where('EksperimenPemasaranId', $eksperimen->Id)
            ->where('Kode', 'B')
            ->value('Bobot'));
    }

    /** @return array<string, mixed> */
    private function muatan(): array
    {
        return [
            'Kode' => 'cta-baru',
            'Nama' => 'CTA baru',
            'Target' => TargetEksperimen::Cta->value,
            'MetrikUtama' => MetrikEksperimen::Ctr->value,
            'Hipotesis' => 'CTA kata kerja menaikkan klik.',
            'MinimumSampel' => 100,
            'Varian' => [
                ['Kode' => 'A', 'Nama' => 'Kontrol', 'Bobot' => 1, 'Kontrol' => true],
                ['Kode' => 'B', 'Nama' => 'Varian angka', 'Bobot' => 1, 'Kontrol' => false],
            ],
        ];
    }

    private function pengelola(): AdminPlatform
    {
        return $this->buatAdmin([KatalogIzinPemasaran::EKSPERIMEN_KELOLA]);
    }
}
