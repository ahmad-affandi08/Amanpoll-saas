<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;

/**
 * "Minta suku cadang" teknisi berlingkup mengikuti lingkup gudang (PRD 8.21).
 *
 * Keputusan: teknisi hanya meminta dari gudang yang terlihat olehnya -- sama dengan
 * pengguna dasbor, karena permintaannya dikirim ke endpoint reservasi yang sama.
 * Teknisi IT yang butuh barang gudang IPSRS meminta lewat koordinator yang melihat
 * gudang itu; gudang pusat bersama dibuat terlihat dengan menaruhnya di ruangan
 * atau unit pengelola yang masuk lingkup teknisinya.
 */
final class TeknisiSukuCadangLingkupGudangTest extends KasusTeknisi
{
    public function test_teknisi_berlingkup_it_hanya_melihat_dan_meminta_dari_gudang_it(): void
    {
        [$it, $ipsrs] = $this->dalamOrganisasi(fn (): array => [
            UnitOrganisasi::create(['Kode' => 'IT', 'Nama' => 'Instalasi IT', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]),
            UnitOrganisasi::create(['Kode' => 'IPS', 'Nama' => 'IPSRS', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]),
        ]);
        [$kabel, $gudangIt, $gudangIpsrs] = $this->dalamOrganisasi(function () use ($it, $ipsrs): array {
            $kabel = SukuCadang::create(['Kode' => 'SC-KBL', 'Nama' => 'Kabel LAN', 'SatuanDasar' => 'Meter', 'StokMinimum' => 0, 'Status' => 'Aktif']);
            $gudangIt = Gudang::create(['Kode' => 'GDG-IT', 'Nama' => 'Gudang IT', 'Status' => 'Aktif', 'UnitPengelolaId' => $it->Id]);
            $gudangIpsrs = Gudang::create(['Kode' => 'GDG-IPS', 'Nama' => 'Gudang IPSRS', 'Status' => 'Aktif', 'UnitPengelolaId' => $ipsrs->Id]);

            foreach ([$gudangIt, $gudangIpsrs] as $gudang) {
                StokSukuCadang::create(['GudangId' => $gudang->Id, 'SukuCadangId' => $kabel->Id, 'JumlahTersedia' => 5, 'JumlahDipesan' => 0, 'JumlahDitahan' => 0]);
            }

            return [$kabel, $gudangIt, $gudangIpsrs];
        });
        $teknisiIt = $this->penggunaDenganPeran(['TEKNISI'], unitOrganisasiId: $it->Id);
        $tiket = $this->buatTiket($teknisiIt, StatusPerintahKerja::Dikerjakan, lain: ['UnitPengelolaId' => $it->Id]);

        $this->actingAs($teknisiIt)
            ->getJson("/lapangan/teknisi/tugas/{$tiket->Id}/suku-cadang?cari=kabel")
            ->assertOk()
            ->assertJsonCount(1, 'data.0.Stok')
            ->assertJsonPath('data.0.Stok.0.NamaGudang', 'Gudang IT');
        $this->actingAs($teknisiIt)
            ->postJson("/pemeliharaan/perintah-kerja/{$tiket->Id}/reservasi-suku-cadang", [
                'GudangId' => $gudangIpsrs->Id, 'SukuCadangId' => $kabel->Id, 'Jumlah' => 1,
            ])
            ->assertJsonValidationErrors(['GudangId' => 'Gudang yang dipilih tidak ditemukan atau di luar lingkup akses Anda.']);
        $this->actingAs($teknisiIt)
            ->postJson("/pemeliharaan/perintah-kerja/{$tiket->Id}/reservasi-suku-cadang", [
                'GudangId' => $gudangIt->Id, 'SukuCadangId' => $kabel->Id, 'Jumlah' => 1,
            ])
            ->assertRedirect();

        $this->assertSame([$gudangIt->Id], $this->dalamOrganisasi(fn () => ReservasiSukuCadang::query()->pluck('GudangId')->all()));
    }
}
