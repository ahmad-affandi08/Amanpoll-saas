<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Langganan;

use App\Domain\Aset\Application\Actions\BuatAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Shared\Domain\Exceptions\LanggananTidakMengizinkan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Gate 22 — tenant yang kedaluwarsa atau kehabisan kuota tidak dapat menembus
 * pembatasan lewat API langsung.
 *
 * Tes ini sengaja tidak melewati satu pun halaman: ia memakai kunci API dan
 * memanggil endpoint API persis seperti yang dilakukan integrator, karena di
 * situlah pembatasan yang hanya hidup di UI akan runtuh.
 */
final class Gate22BypassApiTest extends KasusLangganan
{
    public function test_gate_22_tenant_kedaluwarsa_ditolak_saat_menulis_lewat_api(): void
    {
        $paket = $this->buatPaketLengkap();
        $this->buatLangganan(
            $paket,
            StatusLangganan::Aktif,
            // Lewat tanggal akhir dan lewat masa tenggang.
            berakhirPada: CarbonImmutable::now()->subDays(60)->toDateString(),
        );

        $token = $this->buatKunciApi(['Keluhan.Kelola']);

        $respons = $this->kirimKeluhanApi($token);

        $respons->assertStatus(402);
        $respons->assertJsonPath('kode_error', 'LANGGANAN_TIDAK_MENGIZINKAN');
        $this->assertSame(0, Keluhan::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $this->organisasi->Id)->count());
    }

    public function test_gate_22_tenant_dalam_masa_tenggang_masih_boleh_menulis_lewat_api(): void
    {
        $paket = $this->buatPaketLengkap();
        $this->buatLangganan(
            $paket,
            StatusLangganan::Aktif,
            berakhirPada: CarbonImmutable::now()->subDays(2)->toDateString(),
        );

        $this->siapkanPolaNomor('Keluhan', 'KLH');
        $token = $this->buatKunciApi(['Keluhan.Kelola']);

        $this->kirimKeluhanApi($token)->assertCreated();
    }

    public function test_gate_22_tenant_yang_uji_coba_awalnya_habis_ditolak_lewat_api(): void
    {
        // Organisasi yang tidak pernah diberi paket berhenti sendiri setelah
        // uji coba awalnya lewat — gagal ke arah tertutup, bukan menjadi
        // pelanggan gratis selamanya.
        $this->mundurkanPembuatanOrganisasi(365);

        $token = $this->buatKunciApi(['Keluhan.Kelola']);

        $this->kirimKeluhanApi($token)->assertStatus(402);
    }

    public function test_tenant_baru_dapat_langsung_bekerja_selama_uji_coba_awal(): void
    {
        // Tenant baru tidak boleh terkunci hanya karena admin platform belum
        // sempat menetapkan paketnya.
        $this->siapkanPolaNomor('Keluhan', 'KLH');
        $token = $this->buatKunciApi(['Keluhan.Kelola']);

        $this->kirimKeluhanApi($token)->assertCreated();
    }

    public function test_gate_22_batas_kuota_ditegakkan_pada_jalur_use_case_bukan_hanya_ui(): void
    {
        // Satu aset saja yang boleh ada, dan satu sudah terpakai.
        $paket = $this->buatPaket('Paket Batas', [
            KatalogFitur::BATAS_ASET => ['Diizinkan' => true, 'BatasNilai' => 1.0],
        ]);
        $this->buatLangganan($paket);
        $this->buatAset();

        $this->expectException(LanggananTidakMengizinkan::class);

        app(BuatAset::class)->jalankan([
            'KategoriAsetId' => $this->kategoriAsetId(),
            'KodeAset' => 'AST-LEBIH-'.uniqid(),
            'Nama' => 'Aset melebihi kuota',
            'Status' => StatusAset::Aktif->value,
        ], (string) $this->buatPengguna()->Id);
    }

    public function test_gate_22_modul_yang_tidak_dibeli_tidak_dapat_dibaca_lewat_rutenya(): void
    {
        $paket = $this->buatPaket('Paket Dasar', [
            KatalogFitur::MODUL_KALIBRASI => ['Diizinkan' => false],
        ]);
        $this->buatLangganan($paket);

        $pengguna = $this->buatPengguna(['Kalibrasi.Kelola']);

        // Izin perannya lengkap; yang menolak adalah paketnya, dan penolakan itu
        // berlaku untuk GET juga — bukan hanya untuk tombol yang disembunyikan.
        $this->actingAs($pengguna)->get('/kalibrasi')->assertStatus(402);
    }

    public function test_gate_22_modul_yang_dibeli_tetap_dapat_dibuka(): void
    {
        $paket = $this->buatPaket('Paket Plus', [
            KatalogFitur::MODUL_KALIBRASI => ['Diizinkan' => true],
        ]);
        $this->buatLangganan($paket);

        $pengguna = $this->buatPengguna(['Kalibrasi.Kelola']);

        $this->actingAs($pengguna)->get('/kalibrasi')->assertOk();
    }

    public function test_gate_22_rute_integrasi_ikut_tertutup_tanpa_modulnya(): void
    {
        $paket = $this->buatPaket('Paket Tanpa Integrasi', [
            KatalogFitur::MODUL_INTEGRASI => ['Diizinkan' => false],
        ]);
        $this->buatLangganan($paket);

        $pengguna = $this->buatPengguna(['Integrasi.Kelola']);

        // Kunci API adalah pintu masuk integrasi; menutup modulnya tanpa menutup
        // halaman ini akan menyisakan jalan memutar yang sah.
        $this->actingAs($pengguna)->get('/integrasi')->assertStatus(402);
        $this->actingAs($pengguna)->get('/platform/kunci-api')->assertStatus(402);
    }

    public function test_log_audit_tidak_ikut_digerbangi_paket(): void
    {
        $paket = $this->buatPaket('Paket Tanpa Integrasi', [
            KatalogFitur::MODUL_INTEGRASI => ['Diizinkan' => false],
        ]);
        $this->buatLangganan($paket);

        // Jejak audit adalah fungsi akuntabilitas inti, bukan modul berbayar.
        $this->actingAs($this->buatPengguna(['Audit.Lihat']))
            ->get('/integrasi-audit/audit')
            ->assertOk();
    }

    public function test_tenant_kedaluwarsa_tetap_dapat_membaca_datanya(): void
    {
        $paket = $this->buatPaketLengkap();
        $this->buatLangganan(
            $paket,
            StatusLangganan::Aktif,
            berakhirPada: CarbonImmutable::now()->subDays(60)->toDateString(),
        );

        $pengguna = $this->buatPengguna(['Aset.Lihat']);

        // Kebijakan baca-saja: data lama tetap terlihat supaya tenant tidak
        // terkunci dari jalan keluarnya sendiri.
        $this->actingAs($pengguna)->get('/aset')->assertOk();
    }

    public function test_tenant_kedaluwarsa_tetap_dapat_membuka_halaman_langganannya(): void
    {
        $paket = $this->buatPaketLengkap();
        $this->buatLangganan(
            $paket,
            StatusLangganan::Aktif,
            berakhirPada: CarbonImmutable::now()->subDays(60)->toDateString(),
        );

        $this->actingAs($this->buatPengguna(['Pengaturan.Kelola']))->get(route('langganan.index'))->assertOk();
    }

    /** @param list<string> $cakupan */
    private function buatKunciApi(array $cakupan): string
    {
        $awalan = Str::lower(Str::random(12));
        $token = $awalan.'.'.Str::random(40);

        DB::table('KunciApi')->insert([
            'Id' => (string) Str::ulid(),
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Kunci Uji',
            'AwalanKunci' => $awalan,
            'HashKunci' => hash('sha256', $token),
            'Cakupan' => json_encode($cakupan),
            'Status' => 'Aktif',
            'DibuatPada' => now(),
        ]);

        return $token;
    }

    private function kirimKeluhanApi(string $token): TestResponse
    {
        // Kunci API ini tidak punya pengguna pemilik, jadi pelapornya disebut
        // eksplisit seperti yang dilakukan integrator sungguhan.
        $pelapor = $this->buatPengguna();

        return $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Idempotency-Key' => (string) Str::ulid(),
        ])->postJson('/api/v1/keluhan', [
            'KategoriKeluhanId' => $this->kategoriKeluhanId(),
            'PelaporId' => $pelapor->Id,
            'Judul' => 'Lampu koridor mati',
            'Deskripsi' => 'Lampu di koridor lantai dua tidak menyala sejak pagi.',
        ]);
    }

    private function kategoriKeluhanId(): string
    {
        return (string) KategoriKeluhan::firstOrCreate(
            ['OrganisasiId' => $this->organisasi->Id, 'Kode' => 'KAT-KLH-LNG'],
            ['Nama' => 'Kategori Keluhan Langganan', 'Aktif' => true],
        )->Id;
    }

    private function buatAset(): Aset
    {
        return Aset::create([
            'OrganisasiId' => $this->organisasi->Id,
            'KategoriAsetId' => $this->kategoriAsetId(),
            'KodeAset' => 'AST-'.uniqid(),
            'Nama' => 'Aset uji',
            'Status' => StatusAset::Aktif->value,
        ]);
    }

    private function kategoriAsetId(): string
    {
        return (string) KategoriAset::firstOrCreate(
            ['OrganisasiId' => $this->organisasi->Id, 'Kode' => 'KAT-LNG'],
            ['Nama' => 'Kategori Langganan'],
        )->Id;
    }
}
