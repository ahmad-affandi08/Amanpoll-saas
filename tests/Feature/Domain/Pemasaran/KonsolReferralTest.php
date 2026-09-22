<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananProgramReferral;
use App\Domain\Pemasaran\Domain\Enums\JenisRewardReferral;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPemicuOtomasi;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KodeReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ProgramReferral;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Konsol referral: izin, validasi program, dan penerbitan kode (MARKETING.md 20, 26). */
final class KonsolReferralTest extends KasusReferral
{
    private const AKAR = '/admin-platform/pemasaran/referral';

    public function test_melihat_konsol_referral_menuntut_izin(): void
    {
        $this->aktingSebagai([])->get(self::AKAR)->assertForbidden();
        $this->aktingSebagai([KatalogIzinPemasaran::REFERRAL_LIHAT])->get(self::AKAR)->assertOk();
    }

    public function test_izin_melihat_saja_tidak_boleh_menyimpan_program(): void
    {
        $this->aktingSebagai([KatalogIzinPemasaran::REFERRAL_LIHAT])
            ->post(self::AKAR, $this->isiProgram())
            ->assertForbidden();
    }

    public function test_program_tersimpan_dengan_izin_kelola(): void
    {
        $this->aktingSebagai([KatalogIzinPemasaran::REFERRAL_KELOLA])
            ->post(self::AKAR, $this->isiProgram())
            ->assertRedirect();

        $this->assertSame(1, ProgramReferral::query()->where('Kode', 'ajak-teman')->count());
    }

    /** Program yang menjanjikan imbalan yang tidak dapat diberikan lebih buruk daripada tidak ada. */
    public function test_imbalan_yang_belum_didukung_billing_ditolak_saat_menyimpan(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananProgramReferral::class)->simpan(null, [
            'Kode' => 'kredit-dulu',
            'Nama' => 'Kredit Dulu',
            'JenisReward' => JenisRewardReferral::Kredit->value,
            'NilaiReward' => 100_000,
            'HariKedaluwarsa' => 90,
            'Aktif' => true,
        ]);
    }

    public function test_jenis_yang_didukung_hanya_perpanjangan_dan_kustom(): void
    {
        $this->assertEqualsCanonicalizing(
            [JenisRewardReferral::Perpanjangan->value, JenisRewardReferral::Kustom->value],
            app(LayananProgramReferral::class)->jenisDidukung(),
        );
    }

    public function test_menerbitkan_kode_lewat_konsol_mengembalikan_kode_yang_sama(): void
    {
        $this->aktingSebagai([KatalogIzinPemasaran::REFERRAL_KELOLA])
            ->post(self::AKAR."/{$this->program->Kode}/kode", ['OrganisasiId' => $this->perujuk->Id])
            ->assertRedirect();

        $this->assertSame(1, KodeReferral::query()
            ->where('ProgramReferralId', $this->program->Id)
            ->where('OrganisasiId', $this->perujuk->Id)
            ->count());
    }

    public function test_kode_program_ganda_ditolak(): void
    {
        $this->aktingSebagai([KatalogIzinPemasaran::REFERRAL_KELOLA])
            ->post(self::AKAR, $this->isiProgram())
            ->assertRedirect();

        $this->aktingSebagai([KatalogIzinPemasaran::REFERRAL_KELOLA])
            ->post(self::AKAR, $this->isiProgram())
            ->assertSessionHasErrors('Kode');
    }

    /** Pemicu otomasi ReferralTerdaftar kini punya sumbernya, bukan lagi janji kosong. */
    public function test_pemicu_referral_terdaftar_sudah_berlaku(): void
    {
        $this->assertTrue(KatalogPemicuOtomasi::berlaku('ReferralTerdaftar'));
        $this->assertSame(
            KatalogPeristiwaPemasaran::REFERRAL_MENJADI_LEAD,
            KatalogPemicuOtomasi::peristiwa('ReferralTerdaftar'),
        );
    }

    /** @param list<string> $izin */
    private function aktingSebagai(array $izin): self
    {
        $this->actingAs($this->buatAdmin($izin), 'platform');

        return $this;
    }

    /** @param array<string, mixed> $ganti */
    private function isiProgram(array $ganti = []): array
    {
        return array_merge([
            'Kode' => 'ajak-teman',
            'Nama' => 'Ajak Teman',
            'JenisReward' => JenisRewardReferral::Perpanjangan->value,
            'NilaiReward' => 30,
            'HariKedaluwarsa' => 90,
            'Aktif' => true,
        ], $ganti);
    }
}
