<?php

declare(strict_types=1);

namespace Tests\Feature\Keamanan;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAkses;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Riwayat audit tidak boleh ditulis ulang lewat kode aplikasi (24).
 */
final class CatatanHanyaTambahTest extends KasusKeamanan
{
    public function test_catatan_audit_tidak_dapat_diubah(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-AUDIT-UBAH');

        $this->dalamOrganisasi($organisasi, function (): void {
            $catatan = CatatanAudit::create([
                'Aksi' => 'Aset.Dibuat',
                'JenisEntitas' => 'Aset',
                'EntitasId' => null,
            ]);

            $this->expectException(AturanBisnisDilanggar::class);
            $catatan->update(['Aksi' => 'Aset.Dihapus']);
        });
    }

    public function test_catatan_audit_tidak_dapat_dihapus(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-AUDIT-HAPUS');

        $this->dalamOrganisasi($organisasi, function (): void {
            $catatan = CatatanAudit::create([
                'Aksi' => 'Aset.Dibuat',
                'JenisEntitas' => 'Aset',
                'EntitasId' => null,
            ]);

            $this->expectException(AturanBisnisDilanggar::class);
            $catatan->delete();
        });
    }

    public function test_catatan_akses_tidak_dapat_diubah(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-AKSES-UBAH');

        $this->dalamOrganisasi($organisasi, function (): void {
            $catatan = CatatanAkses::create([
                'Jenis' => 'Login',
                'Berhasil' => false,
                'AlasanGagal' => 'Kata sandi salah.',
            ]);

            $this->expectException(AturanBisnisDilanggar::class);
            $catatan->update(['Berhasil' => true]);
        });
    }

    public function test_baris_audit_tetap_utuh_setelah_percobaan_pengubahan(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-AUDIT-UTUH');

        $this->dalamOrganisasi($organisasi, function (): void {
            $catatan = CatatanAudit::create([
                'Aksi' => 'Aset.Dibuat',
                'JenisEntitas' => 'Aset',
                'EntitasId' => null,
            ]);

            try {
                $catatan->update(['Aksi' => 'Aset.Dihapus']);
            } catch (AturanBisnisDilanggar) {
                // Diharapkan; yang diperiksa adalah keadaan barisnya.
            }

            $this->assertSame('Aset.Dibuat', CatatanAudit::query()->whereKey($catatan->Id)->value('Aksi'));
        });
    }
}
