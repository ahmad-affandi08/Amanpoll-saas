<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Pemasaran\Application\Actions\CatatProspek;
use App\Domain\Pemasaran\Application\Services\LayananAturanSkorProspek;
use App\Domain\Pemasaran\Application\Services\PenghitungSkorProspek;
use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaSkor;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AturanSkorProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KonfigurasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SkorProspek;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Str;

/** Aturan bobot skor sebagai tabel (MARKETING.md 5.4, 24). */
final class AturanSkorProspekTest extends KasusProspek
{
    public function test_tabel_disemai_dengan_bobot_bawaan(): void
    {
        $tersimpan = AturanSkorProspek::query()->pluck('Bobot', 'Peristiwa')->all();

        $this->assertSame(KatalogPeristiwaSkor::bobotBawaan(), array_map(intval(...), $tersimpan));
    }

    public function test_bobot_tidak_lagi_tersimpan_di_konfigurasi(): void
    {
        $this->assertFalse(KonfigurasiPemasaran::query()->where('Kunci', 'skor.aturan')->exists());
    }

    public function test_sinyal_yang_tidak_dikenal_ditolak_saat_disimpan(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananAturanSkorProspek::class)->simpan(null, [
            'Peristiwa' => 'HargaDilihatt',
            'Bobot' => 5,
        ]);
    }

    public function test_sinyal_yang_tidak_dikenal_ditolak_lewat_konsol(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_KELOLA]), 'platform')
            ->post(route('pemasaran.prospek.aturanSkor.store'), [
                'Peristiwa' => 'SalahKetik',
                'Bobot' => 5,
            ])
            ->assertSessionHasErrors('Peristiwa');
    }

    public function test_aturan_nonaktif_tidak_menyumbang(): void
    {
        [$prospek, $pengenal] = $this->buatProspek();
        $this->peristiwa($pengenal, KatalogPeristiwaPemasaran::HARGA_DILIHAT);

        $sebelum = app(PenghitungSkorProspek::class)->hitungUlang($prospek);

        $this->ubahAturan(KatalogPeristiwaPemasaran::HARGA_DILIHAT, ['Aktif' => false]);

        $this->assertSame($sebelum - 5, app(PenghitungSkorProspek::class)->hitungUlang($prospek));
    }

    public function test_perubahan_bobot_langsung_berlaku(): void
    {
        [$prospek, $pengenal] = $this->buatProspek();
        $this->peristiwa($pengenal, KatalogPeristiwaPemasaran::HARGA_DILIHAT);

        $sebelum = app(PenghitungSkorProspek::class)->hitungUlang($prospek);

        $aturan = $this->aturan(KatalogPeristiwaPemasaran::HARGA_DILIHAT);
        app(LayananAturanSkorProspek::class)->simpan($aturan, [
            'Peristiwa' => $aturan->Peristiwa,
            'Bobot' => 50,
        ]);

        $this->assertSame($sebelum + 45, app(PenghitungSkorProspek::class)->hitungUlang($prospek));
    }

    /** Inilah kegagalan yang dulu tidak bergejala. */
    public function test_sinyal_tertunda_tidak_menyumbang_meski_aktif(): void
    {
        $aturan = $this->aturan(KatalogPeristiwaSkor::EMAIL_BOUNCE);

        $this->assertTrue($aturan->Aktif);
        $this->assertSame(KatalogPeristiwaSkor::ASAL_TERTUNDA, $aturan->asal());
        $this->assertFalse($aturan->berlaku());
        $this->assertArrayNotHasKey(
            KatalogPeristiwaSkor::EMAIL_BOUNCE,
            app(LayananAturanSkorProspek::class)->bobotBerlaku(),
        );
    }

    public function test_konsol_menandai_aturan_yang_belum_berlaku(): void
    {
        $props = $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_LIHAT]), 'platform')
            ->get(route('pemasaran.prospek.aturanSkor.index'))
            ->viewData('page')['props'];

        $tertunda = array_values(array_filter(
            $props['aturan'],
            fn (array $satu): bool => $satu['Peristiwa'] === KatalogPeristiwaSkor::EMAIL_BOUNCE,
        ));

        $this->assertCount(1, $tertunda);
        $this->assertFalse($tertunda[0]['Berlaku']);
    }

    public function test_rincian_skor_menunjuk_aturan_yang_menghasilkannya(): void
    {
        [$prospek, $pengenal] = $this->buatProspek();
        $this->peristiwa($pengenal, KatalogPeristiwaPemasaran::HARGA_DILIHAT);

        app(PenghitungSkorProspek::class)->hitungUlang($prospek);

        $rincian = SkorProspek::query()
            ->where('ProspekId', $prospek->Id)
            ->where('Peristiwa', KatalogPeristiwaPemasaran::HARGA_DILIHAT)
            ->firstOrFail();

        $this->assertSame(
            $this->aturan(KatalogPeristiwaPemasaran::HARGA_DILIHAT)->Id,
            $rincian->AturanSkorProspekId,
        );
        $this->assertSame(KatalogPeristiwaPemasaran::HARGA_DILIHAT, $rincian->aturan?->Peristiwa);
    }

    public function test_menghapus_aturan_tidak_menghapus_rincian_skor_lama(): void
    {
        [$prospek, $pengenal] = $this->buatProspek();
        $this->peristiwa($pengenal, KatalogPeristiwaPemasaran::HARGA_DILIHAT);
        app(PenghitungSkorProspek::class)->hitungUlang($prospek);

        app(LayananAturanSkorProspek::class)->hapus($this->aturan(KatalogPeristiwaPemasaran::HARGA_DILIHAT));

        $rincian = SkorProspek::query()
            ->where('ProspekId', $prospek->Id)
            ->where('Peristiwa', KatalogPeristiwaPemasaran::HARGA_DILIHAT)
            ->firstOrFail();

        $this->assertNull($rincian->AturanSkorProspekId);
        $this->assertSame(5, $rincian->Bobot);
    }

    public function test_perubahan_aturan_tercatat_di_audit(): void
    {
        $aturan = $this->aturan(KatalogPeristiwaPemasaran::HARGA_DILIHAT);

        app(LayananAturanSkorProspek::class)->simpan($aturan, [
            'Peristiwa' => $aturan->Peristiwa,
            'Bobot' => 7,
        ]);

        $catatan = CatatanAudit::query()
            ->withoutGlobalScopes()
            ->where('Aksi', 'AturanSkorProspek.Diubah')
            ->where('EntitasId', $aturan->Id)
            ->firstOrFail();

        $this->assertSame(5, $catatan->DataSebelum['Bobot'] ?? null);
        $this->assertSame(7, $catatan->DataSesudah['Bobot'] ?? null);
    }

    public function test_penghapusan_aturan_tercatat_di_audit(): void
    {
        $aturan = $this->aturan(KatalogPeristiwaPemasaran::HARGA_DILIHAT);

        app(LayananAturanSkorProspek::class)->hapus($aturan);

        $this->assertTrue(CatatanAudit::query()
            ->withoutGlobalScopes()
            ->where('Aksi', 'AturanSkorProspek.Dihapus')
            ->where('EntitasId', $aturan->Id)
            ->exists());
    }

    public function test_izin_lihat_tidak_cukup_untuk_mengubah_bobot(): void
    {
        $aturan = $this->aturan(KatalogPeristiwaPemasaran::HARGA_DILIHAT);

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_LIHAT]), 'platform')
            ->put(route('pemasaran.prospek.aturanSkor.update', $aturan->Id), [
                'Peristiwa' => $aturan->Peristiwa,
                'Bobot' => 99,
            ])
            ->assertForbidden();

        $this->assertSame(5, $aturan->fresh()?->Bobot);
    }

    public function test_admin_tanpa_izin_tidak_dapat_melihat_aturan(): void
    {
        $this->actingAs($this->buatAdmin(), 'platform')
            ->get(route('pemasaran.prospek.aturanSkor.index'))
            ->assertForbidden();
    }

    public function test_satu_sinyal_hanya_boleh_punya_satu_aturan(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_KELOLA]), 'platform')
            ->post(route('pemasaran.prospek.aturanSkor.store'), [
                'Peristiwa' => KatalogPeristiwaPemasaran::HARGA_DILIHAT,
                'Bobot' => 5,
            ])
            ->assertSessionHasErrors('Peristiwa');
    }

    /** @param array<string, mixed> $ubahan */
    private function ubahAturan(string $peristiwa, array $ubahan): void
    {
        AturanSkorProspek::query()->where('Peristiwa', $peristiwa)->update($ubahan);
        app(LayananAturanSkorProspek::class)->buangCache();
    }

    private function aturan(string $peristiwa): AturanSkorProspek
    {
        return AturanSkorProspek::query()->where('Peristiwa', $peristiwa)->firstOrFail();
    }

    /** @return array{0: Prospek, 1: string} */
    private function buatProspek(): array
    {
        $pengenal = (string) Str::ulid();

        $prospek = app(CatatProspek::class)->jalankan(
            ['Nama' => 'Budi', 'Email' => 'budi@contoh.test'],
            SumberProspek::Website,
            $pengenal,
        );

        return [$prospek, $pengenal];
    }

    private function peristiwa(string $pengenal, string $jenis): void
    {
        app(PerekamEventPemasaran::class)->catat($jenis, pengenalPengunjung: $pengenal);
    }
}
