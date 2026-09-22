<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananProgramPartner;
use App\Domain\Pemasaran\Domain\Enums\JenisKomisiPartner;
use App\Domain\Pemasaran\Domain\Enums\JenisPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusPartner;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AturanKomisiPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Partner;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\Hash;

/** Konsol partner: izin, flag fitur, dan penjaga saat menyimpan partner maupun aturan komisinya. */
final class KonsolPartnerTest extends KasusPartner
{
    private const AKAR = 'http://localhost/admin-platform/pemasaran/partner';

    public function test_konsol_tertutup_saat_flag_fitur_partner_mati(): void
    {
        $this->matikanFitur(KatalogFiturPlatform::PARTNER);

        $this->aktingSebagai([KatalogIzinPemasaran::PARTNER_LIHAT])
            ->get(self::AKAR)
            ->assertNotFound();
    }

    public function test_izin_melihat_saja_tidak_boleh_menyimpan_partner(): void
    {
        $this->aktingSebagai([KatalogIzinPemasaran::PARTNER_LIHAT])
            ->post(self::AKAR, $this->isiPartner())
            ->assertForbidden();
    }

    public function test_partner_tersimpan_dengan_izin_kelola(): void
    {
        $isi = $this->isiPartner();

        $this->aktingSebagai([KatalogIzinPemasaran::PARTNER_KELOLA])
            ->post(self::AKAR, $isi)
            ->assertRedirect();

        $this->assertSame(1, Partner::query()->where('EmailPic', $isi['EmailPic'])->count());
    }

    /** Partner tanpa kata sandi tidak akan pernah dapat masuk portalnya sendiri. */
    public function test_partner_baru_wajib_diberi_kata_sandi(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananProgramPartner::class)->simpanPartner(null, $this->dataPartner(['KataSandi' => null]));
    }

    public function test_jenis_di_luar_daftar_tertutup_ditolak(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananProgramPartner::class)->simpanPartner(null, $this->dataPartner(['Jenis' => 'Waralaba']));
    }

    public function test_status_di_luar_daftar_ditolak(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananProgramPartner::class)->simpanPartner(null, $this->dataPartner(['Status' => 'Dibekukan']));
    }

    /** Menyunting data partner tidak boleh diam-diam mengganti kata sandinya. */
    public function test_menyunting_tanpa_kata_sandi_tidak_mengubah_kata_sandi_lama(): void
    {
        $sebelum = (string) $this->partner->KataSandi;

        app(LayananProgramPartner::class)->simpanPartner(
            $this->partner,
            $this->dataPartner(['KataSandi' => null, 'EmailPic' => (string) $this->partner->EmailPic]),
        );

        $this->assertSame($sebelum, (string) $this->partner->fresh()?->KataSandi);
    }

    public function test_kata_sandi_partner_disimpan_dalam_bentuk_hash(): void
    {
        $partner = app(LayananProgramPartner::class)
            ->simpanPartner(null, $this->dataPartner(['KataSandi' => 'kata-sandi-panjang']));

        $this->assertNotSame('kata-sandi-panjang', (string) $partner->KataSandi);
        $this->assertTrue(Hash::check('kata-sandi-panjang', (string) $partner->KataSandi));
    }

    public function test_email_pic_ganda_ditolak(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananProgramPartner::class)->simpanPartner(
            null,
            $this->dataPartner(['EmailPic' => (string) $this->partner->EmailPic]),
        );
    }

    public function test_komisi_persentase_di_atas_seratus_ditolak(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananProgramPartner::class)->simpanAturan(null, $this->dataAturan(['Nilai' => 120.0]));
    }

    public function test_komisi_bernilai_nol_ditolak(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananProgramPartner::class)->simpanAturan(null, $this->dataAturan(['Nilai' => 0.0]));
    }

    /** Aturan khusus partner hanya masuk akal bila partnernya memang anggota program itu. */
    public function test_aturan_khusus_partner_di_program_lain_ditolak(): void
    {
        $programLain = $this->buatProgramPartner();
        $partnerLain = $this->buatPartner($programLain);

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananProgramPartner::class)->simpanAturan(
            null,
            $this->dataAturan(['PartnerId' => $partnerLain->Id]),
        );
    }

    public function test_aturan_tersimpan_untuk_partner_seprogram(): void
    {
        app(LayananProgramPartner::class)->simpanAturan(
            null,
            $this->dataAturan(['PartnerId' => $this->partner->Id]),
        );

        $this->assertSame(1, AturanKomisiPartner::query()->where('PartnerId', $this->partner->Id)->count());
    }

    /** @param list<string> $izin */
    private function aktingSebagai(array $izin): self
    {
        $this->actingAs($this->buatAdmin($izin), 'platform');

        return $this;
    }

    /** @param array<string, mixed> $ganti */
    private function isiPartner(array $ganti = []): array
    {
        return array_merge([
            'ProgramPartnerId' => $this->programPartner->Id,
            'NamaPerusahaan' => 'PT Mitra Baru',
            'Jenis' => JenisPartner::Consultant->value,
            'NamaPic' => 'Sari',
            'EmailPic' => 'sari+'.uniqid().'@mitra.test',
            'TeleponPic' => '08111111111',
            'Status' => StatusPartner::Aktif->value,
            'KataSandi' => 'kata-sandi-panjang',
        ], $ganti);
    }

    /** @param array<string, mixed> $ganti */
    private function dataPartner(array $ganti = []): array
    {
        return array_merge([
            ...$this->isiPartner(),
            'ReferensiPerjanjian' => null,
            'ReferensiPayout' => null,
        ], $ganti);
    }

    /** @param array<string, mixed> $ganti */
    private function dataAturan(array $ganti = []): array
    {
        return array_merge([
            'ProgramPartnerId' => $this->programPartner->Id,
            'PartnerId' => null,
            'Nama' => 'Komisi standar',
            'Jenis' => JenisKomisiPartner::Persentase->value,
            'Nilai' => 10.0,
            'MaksPembayaran' => null,
            'Aktif' => true,
            'BerlakuDari' => null,
            'BerlakuSampai' => null,
        ], $ganti);
    }
}
