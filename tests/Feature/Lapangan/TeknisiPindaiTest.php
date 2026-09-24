<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Platform\Application\Services\PenentuModeLapangan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Http\Middleware\ArahkanPenggunaLapangan;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

/**
 * Pindai QR, "Aset ditemukan", tab Aset, dan Riwayat aset teknisi (TASK 39.06, PRD 10).
 *
 * Resolusi label `/aset/pindai/{kode}` mengikuti tampilan pemindainya: mode Teknisi ke
 * lembar "Aset ditemukan", mode Pelapor ke langkah lapor dengan aset terisi, pengguna
 * dasbor ke halaman aset seperti sebelumnya.
 */
final class TeknisiPindaiTest extends KasusTeknisi
{
    public function test_label_qr_mengarahkan_teknisi_ke_lembar_aset_ditemukan(): void
    {
        $aset = $this->buatAset();

        $this->actingAs($this->penggunaDenganPeran(['TEKNISI']))
            ->get("/aset/pindai/{$aset->KodeQr}")
            ->assertRedirect("/lapangan/teknisi/pindai?aset={$aset->Id}");
    }

    public function test_label_qr_mengarahkan_pelapor_ke_langkah_lapor_dengan_aset_terisi(): void
    {
        $aset = $this->buatAset();

        $this->actingAs($this->penggunaDenganPeran(['PELAPOR']))
            ->get("/aset/pindai/{$aset->KodeAset}")
            ->assertRedirect("/lapangan/pelapor/lapor?aset={$aset->Id}");
    }

    public function test_label_qr_tetap_membuka_halaman_aset_bagi_pengguna_dasbor(): void
    {
        $aset = $this->buatAset();

        $this->actingAs($this->penggunaMeja(['Aset.Lihat']))
            ->get("/aset/pindai/{$aset->KodeQr}")
            ->assertRedirect("/aset/{$aset->Id}");
    }

    public function test_pengguna_campuran_ikut_pilihan_tampilan_di_perangkatnya(): void
    {
        $aset = $this->buatAset();
        $campuran = $this->penggunaMeja(['Aset.Lihat'], $this->penggunaDenganPeran(['TEKNISI']));
        app(PenentuModeLapangan::class)->bersihkanCache($this->organisasi->Id, $campuran->Id);

        $this->actingAs($campuran)->get("/aset/pindai/{$aset->KodeQr}")->assertRedirect("/aset/{$aset->Id}");
        $this->actingAs($campuran)
            ->withCookie(ArahkanPenggunaLapangan::COOKIE_TAMPILAN, ArahkanPenggunaLapangan::TAMPILAN_LAPANGAN)
            ->get("/aset/pindai/{$aset->KodeQr}")
            ->assertRedirect("/lapangan/teknisi/pindai?aset={$aset->Id}");
    }

    public function test_label_qr_tenant_lain_tidak_dikenal(): void
    {
        $tiketLain = $this->tiketOrganisasiLain();
        $kodeLain = $this->dalamOrganisasiLainKodeQr($tiketLain->Id);

        $this->actingAs($this->penggunaDenganPeran(['TEKNISI']))->get("/aset/pindai/{$kodeLain}")->assertNotFound();
    }

    public function test_lembar_aset_ditemukan_membawa_tiket_milik_teknisi_dan_aksi_sesuai_izin(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $aset = $this->buatAset();
        $milikku = $this->buatTiket($teknisi, StatusPerintahKerja::Dikerjakan, $aset);
        $this->buatTiket($this->penggunaDenganPeran(['TEKNISI']), aset: $aset, lain: ['Prioritas' => 'Kritis']);

        $this->actingAs($teknisi)->get("/lapangan/teknisi/pindai?kode={$aset->KodeQr}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->component('Lapangan/Teknisi/Pindai')
                ->where('galatPindai', null)
                ->where('asetDitemukan.Id', $aset->Id)
                ->where('asetDitemukan.Kondisi', 'Rusak')
                ->where('asetDitemukan.TiketSaya.Id', $milikku->Id)
                ->where('asetDitemukan.BolehLapor', true)
                ->where('asetDitemukan.BolehLihatRiwayat', true));
    }

    public function test_kode_tak_dikenal_aset_tenant_lain_dan_tanpa_izin_tidak_membocorkan_aset(): void
    {
        $asetLain = $this->dalamOrganisasiLainAset();
        $aset = $this->buatAset();

        $this->actingAs($this->penggunaDenganPeran(['TEKNISI']))->get('/lapangan/teknisi/pindai?kode=TIDAK-ADA')
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->where('asetDitemukan', null)
                ->where('galatPindai', 'Kode TIDAK-ADA tidak cocok dengan aset mana pun.'));
        $this->actingAs($this->penggunaDenganPeran(['TEKNISI']))->get("/lapangan/teknisi/pindai?aset={$asetLain}")
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia->where('asetDitemukan', null)->where('galatPindai', 'Aset itu tidak ditemukan.'));

        // Teknisi yang perannya dicabut dari Aset.Lihat oleh tenant: lembar menampilkan keadaan tanpa izin.
        $tanpaIzin = $this->teknisiTanpaIzinAset();
        $this->actingAs($tanpaIzin)->get("/lapangan/teknisi/pindai?aset={$aset->Id}")
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia->where('asetDitemukan', null)->where('tanpaIzin', true));
    }

    public function test_tab_aset_berisi_aset_tiket_sendiri_dan_bisa_dicari(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $asetKu = $this->buatAset('Lift Penumpang 3');
        $this->buatTiket($teknisi, aset: $asetKu);
        $asetLain = $this->buatAset('Genset Cummins 500 kVA');
        $this->buatTiket($this->penggunaDenganPeran(['TEKNISI']), aset: $asetLain);

        $this->actingAs($teknisi)->get('/lapangan/teknisi/aset')
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->component('Lapangan/Teknisi/Aset')
                ->where('bolehLihat', true)
                ->has('aset', 1)
                ->where('aset.0.Id', $asetKu->Id));
        $this->actingAs($teknisi)->get("/lapangan/teknisi/aset?cari=genset&aset={$asetLain->Id}")
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->has('aset', 1)
                ->where('aset.0.Id', $asetLain->Id)
                ->where('asetTerpilih.Id', $asetLain->Id)
                ->where('asetTerpilih.TiketSaya', null));
    }

    public function test_tab_aset_tanpa_izin_aset_menampilkan_keadaan_tanpa_izin(): void
    {
        $this->buatAset();

        $this->actingAs($this->teknisiTanpaIzinAset())->get('/lapangan/teknisi/aset')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia->where('bolehLihat', false)->has('aset', 0));
    }

    public function test_riwayat_aset_menyusun_ringkasan_dan_garis_waktu_serta_menjaga_tenant(): void
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $aset = $this->buatAset();
        $this->buatTiket($teknisi, StatusPerintahKerja::Dikerjakan, $aset);
        $this->buatTiket(null, StatusPerintahKerja::Selesai, $aset, ['Jenis' => 'Preventif', 'Prioritas' => 'Normal', 'DiselesaikanPada' => now()->subMonth()]);

        $this->actingAs($teknisi)->get("/lapangan/teknisi/aset/{$aset->Id}/riwayat")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->component('Lapangan/Teknisi/RiwayatAset')
                ->where('ringkasan.PekerjaanTahunIni', 2)
                ->where('ringkasan.PersenBeroperasi', 100)
                ->has('linimasa', 2)
                ->where('linimasa.0.Status', 'Dikerjakan'));

        $asetLain = $this->dalamOrganisasiLainAset();
        $this->actingAs($teknisi)->get("/lapangan/teknisi/aset/{$asetLain}/riwayat")->assertNotFound();
        $this->actingAs($this->teknisiTanpaIzinAset())->get("/lapangan/teknisi/aset/{$aset->Id}/riwayat")->assertForbidden();
    }

    /** Teknisi yang peran TEKNISI-nya dicabut dari `Aset.Lihat` oleh tenant. */
    private function teknisiTanpaIzinAset(): Pengguna
    {
        $teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $this->dalamOrganisasi(fn () => $this->peran('TEKNISI')->peranIzin()->delete());
        app(PemeriksaIzin::class)->bersihkanCache($this->organisasi->Id, $teknisi->Id);

        return $teknisi;
    }

    private function dalamOrganisasiLainAset(): string
    {
        return (string) $this->tiketOrganisasiLain()->aset()->withoutGlobalScopes()->value('Aset.Id');
    }

    private function dalamOrganisasiLainKodeQr(string $perintahKerjaId): string
    {
        return (string) DB::table('Aset')
            ->join('PerintahKerjaAset', 'PerintahKerjaAset.AsetId', '=', 'Aset.Id')
            ->where('PerintahKerjaAset.PerintahKerjaId', $perintahKerjaId)
            ->value('Aset.KodeQr');
    }
}
