<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

/**
 * Layar "Pilih versi" teknisi (DESIGN §36.6 layar 18): konflik antrean offline FASE 20
 * disajikan dengan siapa dan kapan tiket diubah di server. Hanya pemilik perangkat
 * yang dapat membukanya.
 */
final class TeknisiKonflikTest extends KasusTeknisi
{
    public function test_konflik_offline_disajikan_dengan_perubahan_terakhir_di_server(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $koordinator = $this->penggunaMeja(['PerintahKerja.Kelola']);
        $tiket = $this->buatTiket($teknisi, aset: $this->buatAset('Genset Cummins 500 kVA'));
        $versiLama = $this->versiTiket($tiket);
        $this->ubahDiKantor($tiket, $koordinator, 'Genset dipakai uji beban sampai sore.');

        $this->antrikanTerima($teknisi, $tiket, $versiLama, 'op-konflik-01')->assertOk();

        $this->actingAs($teknisi)->get('/lapangan/teknisi/konflik/op-konflik-01')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->component('Lapangan/Teknisi/Konflik')
                ->where('antrian.Status', 'Konflik')
                ->where('antrian.Konflik.Alasan', 'VersiBerbeda')
                ->where('tiket.Id', $tiket->Id)
                ->where('tiket.Aset.Nama', 'Genset Cummins 500 kVA')
                ->where('perubahanServer.Status', 'Dijeda')
                ->where('perubahanServer.Catatan', 'Genset dipakai uji beban sampai sore.')
                ->where('perubahanServer.Oleh', $koordinator->Nama));
    }

    public function test_konflik_milik_pengguna_lain_tidak_ditemukan(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $tiket = $this->buatTiket($teknisi);
        $versiLama = $this->versiTiket($tiket);
        $this->ubahDiKantor($tiket, $this->penggunaMeja(['PerintahKerja.Kelola']), 'Diubah koordinator.');
        $this->antrikanTerima($teknisi, $tiket, $versiLama, 'op-konflik-02')->assertOk();

        $this->actingAs($this->penggunaDenganPeran(['TEKNISI']))
            ->get('/lapangan/teknisi/konflik/op-konflik-02')
            ->assertNotFound();
        $this->actingAs($teknisi)->get('/lapangan/teknisi/konflik/op-tidak-ada')->assertNotFound();
    }

    private function ubahDiKantor(PerintahKerja $tiket, Pengguna $oleh, string $catatan): void
    {
        $this->dalamOrganisasi(function () use ($tiket, $oleh, $catatan): void {
            PerintahKerja::query()->whereKey($tiket->Id)->update(['Status' => 'Dijeda', 'Versi' => $tiket->Versi + 1]);
            RiwayatStatusPerintahKerja::create([
                'PerintahKerjaId' => $tiket->Id, 'StatusSebelum' => 'Ditugaskan', 'StatusSesudah' => 'Dijeda',
                'Catatan' => $catatan, 'DiubahOleh' => $oleh->Id, 'DiubahPada' => now(),
            ]);
        });
    }

    private function antrikanTerima(Pengguna $teknisi, PerintahKerja $tiket, int $versi, string $kunci): TestResponse
    {
        return $this->actingAs($teknisi)->postJson('/offline/antrian', [
            'IdentitasPerangkat' => 'hp-'.$teknisi->Id,
            'NamaPerangkat' => 'Ponsel',
            'Platform' => 'Android',
            'Mutasi' => [[
                'KunciOperasi' => $kunci,
                'Operasi' => 'PerintahKerja.ResponsPenugasan',
                'EntitasId' => $tiket->Id,
                'VersiKlien' => $versi,
                'MuatanData' => ['Respons' => 'Terima'],
            ]],
        ]);
    }
}
