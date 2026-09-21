<?php

declare(strict_types=1);

namespace Tests\Feature\Keamanan;

use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Penetrasi lintas tenant lewat rute aplikasi yang sebenarnya (24).
 *
 * Berbeda dari RouteModelBindingTenantTest yang membuktikan mekanismenya pada
 * satu rute sintetis, test ini menembak URL yang benar-benar dilayani produk:
 * pemegang izin penuh di organisasi A mencoba membaca dan mengubah milik
 * organisasi B.
 */
final class PenetrasiLintasTenantTest extends KasusKeamanan
{
    private Organisasi $organisasiA;

    private Organisasi $organisasiB;

    private Pengguna $penyerang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasiA = $this->buatOrganisasi('ORG-PEN-A');
        $this->organisasiB = $this->buatOrganisasi('ORG-PEN-B');

        // Penyerang bukan pengguna berizin rendah: ia administrator penuh di
        // organisasinya sendiri. Yang diuji adalah batas tenant, bukan batas peran.
        $this->penyerang = $this->buatPengguna($this->organisasiA, [
            'Aset.Lihat', 'Aset.Ubah', 'Aset.Hapus', 'Stok.Kelola', 'Kontrak.Kelola',
        ]);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function rutePembacaan(): array
    {
        return [
            'aset milik organisasi lain' => ['aset', '/aset/%s'],
            'suku cadang milik organisasi lain' => ['sukuCadang', '/suku-cadang/%s'],
            'kontrak milik organisasi lain' => ['kontrak', '/kontrak/%s'],
        ];
    }

    #[DataProvider('rutePembacaan')]
    public function test_pembacaan_lintas_tenant_ditolak(string $jenis, string $polaUrl): void
    {
        $idKorban = $this->buatMilikOrganisasiB($jenis);

        $this->actingAs($this->penyerang)
            ->get(sprintf($polaUrl, $idKorban))
            ->assertNotFound();
    }

    /**
     * Kontrol terhadap positif palsu: 404 di atas hanya bermakna bila rutenya
     * memang ada dan melayani data milik sendiri. Tanpa test ini, salah ketik
     * URL akan lulus sebagai "isolasi tenant berhasil".
     */
    #[DataProvider('rutePembacaan')]
    public function test_rute_yang_sama_melayani_data_milik_sendiri(string $jenis, string $polaUrl): void
    {
        $idSendiri = $this->dalamOrganisasi(
            $this->organisasiA,
            fn (): string => $this->buatMilik($jenis),
        );

        $this->actingAs($this->penyerang)
            ->get(sprintf($polaUrl, $idSendiri))
            ->assertOk();
    }

    public function test_perubahan_aset_lintas_tenant_ditolak(): void
    {
        $idKorban = $this->buatMilikOrganisasiB('aset');

        $this->actingAs($this->penyerang)
            ->put("/aset/{$idKorban}", ['Nama' => 'Diambil alih'])
            ->assertNotFound();

        $namaAsli = $this->dalamOrganisasi(
            $this->organisasiB,
            fn (): ?string => Aset::query()->whereKey($idKorban)->value('Nama'),
        );

        $this->assertSame('Aset Korban', $namaAsli);
    }

    public function test_penghapusan_gudang_lintas_tenant_ditolak(): void
    {
        $idKorban = $this->buatMilikOrganisasiB('gudang');

        $this->actingAs($this->penyerang)
            ->delete("/gudang/{$idKorban}")
            ->assertNotFound();

        $masihAda = $this->dalamOrganisasi(
            $this->organisasiB,
            fn (): bool => Gudang::query()->whereKey($idKorban)->exists(),
        );

        $this->assertTrue($masihAda);
    }

    public function test_daftar_aset_tidak_memuat_milik_organisasi_lain(): void
    {
        $idKorban = $this->buatMilikOrganisasiB('aset');

        $respons = $this->actingAs($this->penyerang)->get('/aset');

        $respons->assertOk();
        $this->assertStringNotContainsString($idKorban, $respons->getContent() ?: '');
    }

    private function buatMilikOrganisasiB(string $jenis): string
    {
        return $this->dalamOrganisasi($this->organisasiB, fn (): string => $this->buatMilik($jenis));
    }

    private function buatMilik(string $jenis): string
    {
        return match ($jenis) {
            'aset' => $this->buatAsetKorban()->Id,
            'gudang' => Gudang::create([
                'Kode' => 'GDG-KORBAN',
                'Nama' => 'Gudang Korban',
                'Status' => 'Aktif',
            ])->Id,
            'sukuCadang' => SukuCadang::create([
                'Kode' => 'SPR-KORBAN',
                'Nama' => 'Suku Cadang Korban',
                'SatuanDasar' => 'Unit',
                'Status' => 'Aktif',
            ])->Id,
            'kontrak' => Kontrak::create([
                'Nomor' => 'KTR-KORBAN',
                'Nama' => 'Kontrak Korban',
                'Jenis' => 'Layanan',
                'MulaiPada' => '2026-01-01',
                'BerakhirPada' => '2026-12-31',
                'Status' => 'Aktif',
            ])->Id,
        };
    }

    private function buatAsetKorban(): Aset
    {
        $kategori = KategoriAset::create(['Kode' => 'KAT-KORBAN', 'Nama' => 'Kategori Korban']);

        return Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-KORBAN',
            'Nama' => 'Aset Korban',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
            'KodeQr' => (string) Str::ulid(),
            'Versi' => 1,
        ]);
    }
}
