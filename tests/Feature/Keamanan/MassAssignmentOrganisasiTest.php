<?php

declare(strict_types=1);

namespace Tests\Feature\Keamanan;

use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** `OrganisasiId` ada di `$fillable` hampir seluruh model. */
final class MassAssignmentOrganisasiTest extends KasusKeamanan
{
    public function test_menolak_penulisan_ke_organisasi_lain_lewat_mass_assignment(): void
    {
        $organisasiA = $this->buatOrganisasi('ORG-MA-A');
        $organisasiB = $this->buatOrganisasi('ORG-MA-B');

        $this->dalamOrganisasi($organisasiA, function () use ($organisasiB): void {
            // Ditangkap, bukan expectException: lemparannya akan melompat keluar
            // dari method dan pemeriksaan "barisnya benar-benar tidak tertulis"
            // di bawah tidak pernah dieksekusi. Ditolak di muka tidak sama
            // dengan tidak tertulis -- yang kedua itulah yang dijaga di sini.
            try {
                Lokasi::create([
                    'OrganisasiId' => $organisasiB->Id,
                    'Kode' => 'LOK-SELUNDUPAN',
                    'Nama' => 'Lokasi Selundupan',
                ]);

                $this->fail('Penulisan ke organisasi lain seharusnya ditolak.');
            } catch (AturanBisnisDilanggar) {
                // diharapkan
            }
        });

        $adaDiB = $this->dalamOrganisasi(
            $organisasiB,
            fn (): bool => Lokasi::query()->where('Kode', 'LOK-SELUNDUPAN')->exists(),
        );

        $this->assertFalse($adaDiB);
    }

    public function test_organisasi_id_yang_sama_dengan_konteks_tetap_diterima(): void
    {
        $organisasi = $this->buatOrganisasi('ORG-MA-SAMA');

        $lokasi = $this->dalamOrganisasi($organisasi, fn (): Lokasi => Lokasi::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'LOK-SAH',
            'Nama' => 'Lokasi Sah',
        ]));

        $this->assertSame($organisasi->Id, $lokasi->OrganisasiId);
    }

    public function test_tanpa_konteks_organisasi_id_eksplisit_tetap_diizinkan(): void
    {
        // Jalur lintas tenant yang sah.
        $organisasi = $this->buatOrganisasi('ORG-MA-KONSOL');

        $lokasi = Lokasi::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'LOK-KONSOL',
            'Nama' => 'Lokasi Konsol',
        ]);

        $this->assertSame($organisasi->Id, $lokasi->OrganisasiId);
    }
}
