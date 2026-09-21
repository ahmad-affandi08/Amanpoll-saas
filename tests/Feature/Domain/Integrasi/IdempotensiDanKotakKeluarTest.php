<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Integrasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Core\Peristiwa\LayananKotakKeluar;
use App\Domain\IntegrasiAudit\Application\Services\LayananPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Domain\Enums\StatusKotakKeluarPeristiwa;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\KotakKeluarPeristiwa;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PengirimanPanggilanBalikWeb;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Gate 19 — satu endpoint kritis dan satu peristiwa eksternal terbukti
 * idempotent: diulang berapa kali pun, efeknya tetap satu kali.
 */
final class IdempotensiDanKotakKeluarTest extends TestCase
{
    use DatabaseTransactions;

    public function test_gate_19_endpoint_kritis_diulang_hanya_membuat_satu_keluhan(): void
    {
        $konteks = $this->siapkanKonteks();
        $muatan = $this->muatanKeluhan($konteks);
        $kunci = (string) Str::uuid();

        $pertama = $this->kirimKeluhan($konteks, $muatan, $kunci);
        $pertama->assertCreated();
        $nomor = $pertama->json('Nomor');

        $kedua = $this->kirimKeluhan($konteks, $muatan, $kunci);
        $kedua->assertCreated();
        $kedua->assertHeader('Idempotency-Replayed', 'true');

        // Respons kedua identik dan tidak ada keluhan kedua yang tercipta.
        $this->assertSame($pertama->json('Id'), $kedua->json('Id'));
        $this->assertSame($nomor, $kedua->json('Nomor'));
        $this->assertSame(1, $this->jumlahKeluhan($konteks));
    }

    public function test_gate_19_kunci_sama_dengan_muatan_berbeda_ditolak_sebagai_konflik(): void
    {
        $konteks = $this->siapkanKonteks();
        $kunci = (string) Str::uuid();

        $this->kirimKeluhan($konteks, $this->muatanKeluhan($konteks), $kunci)->assertCreated();

        $berbeda = $this->kirimKeluhan(
            $konteks,
            array_merge($this->muatanKeluhan($konteks), ['Judul' => 'Laporan yang berbeda']),
            $kunci,
        );

        $berbeda->assertStatus(409);
        $this->assertSame(1, $this->jumlahKeluhan($konteks));
    }

    public function test_kunci_idempotensi_berbeda_membuat_keluhan_baru(): void
    {
        $konteks = $this->siapkanKonteks();
        $muatan = $this->muatanKeluhan($konteks);

        $this->kirimKeluhan($konteks, $muatan, (string) Str::uuid())->assertCreated();
        $this->kirimKeluhan($konteks, $muatan, (string) Str::uuid())->assertCreated();

        $this->assertSame(2, $this->jumlahKeluhan($konteks));
    }

    public function test_endpoint_kritis_menolak_permintaan_tanpa_kunci_idempotensi(): void
    {
        $konteks = $this->siapkanKonteks();

        $this->withHeaders($this->header($konteks))
            ->postJson('/api/v1/keluhan', $this->muatanKeluhan($konteks))
            ->assertStatus(400);

        $this->assertSame(0, $this->jumlahKeluhan($konteks));
    }

    public function test_permintaan_gagal_tidak_mengunci_kunci_idempotensi(): void
    {
        $konteks = $this->siapkanKonteks();
        $kunci = (string) Str::uuid();

        // Muatan tidak valid: kategori keluhan kosong.
        $this->kirimKeluhan($konteks, ['Judul' => 'Tanpa kategori', 'Deskripsi' => 'Uji'], $kunci)
            ->assertStatus(422);

        // Kunci yang sama masih boleh dipakai setelah permintaan gagal.
        $this->kirimKeluhan($konteks, $this->muatanKeluhan($konteks), $kunci)->assertCreated();
        $this->assertSame(1, $this->jumlahKeluhan($konteks));
    }

    public function test_gate_19_peristiwa_eksternal_diproses_dua_kali_hanya_mengirim_sekali(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
        $konteks = $this->siapkanKonteks();
        $webhook = $this->buatWebhook($konteks, ['Keluhan.*']);

        $this->kirimKeluhan($konteks, $this->muatanKeluhan($konteks), (string) Str::uuid())->assertCreated();

        $peristiwa = KotakKeluarPeristiwa::query()
            ->withoutGlobalScopes()
            ->where('OrganisasiId', $konteks['organisasi']->Id)
            ->where('NamaPeristiwa', 'Keluhan.Dibuat')
            ->firstOrFail();

        $layanan = app(LayananPanggilanBalikWeb::class);
        $layanan->terbitkan($peristiwa);
        $layanan->terbitkan($peristiwa);

        $this->assertSame(1, PengirimanPanggilanBalikWeb::query()
            ->withoutGlobalScopes()
            ->where('PanggilanBalikWebId', $webhook->Id)
            ->where('Peristiwa', 'Keluhan.Dibuat')
            ->count());
    }

    public function test_worker_kotak_keluar_menandai_selesai_dan_tidak_memproses_ulang(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
        $konteks = $this->siapkanKonteks();
        $this->buatWebhook($konteks, ['*']);
        $this->kirimKeluhan($konteks, $this->muatanKeluhan($konteks), (string) Str::uuid())->assertCreated();

        $this->artisan('outbox:proses')->assertSuccessful();

        $peristiwa = KotakKeluarPeristiwa::query()
            ->withoutGlobalScopes()
            ->where('OrganisasiId', $konteks['organisasi']->Id)
            ->firstOrFail();
        $this->assertSame(StatusKotakKeluarPeristiwa::Selesai->value, $peristiwa->Status);
        $this->assertNotNull($peristiwa->DiprosesPada);

        // Menjalankan worker lagi tidak membuat pengiriman kedua.
        $this->artisan('outbox:proses')->assertSuccessful();
        $this->assertSame(1, PengirimanPanggilanBalikWeb::query()
            ->withoutGlobalScopes()
            ->where('OrganisasiId', $konteks['organisasi']->Id)
            ->count());
    }

    public function test_peristiwa_hilang_bila_transaksi_bisnisnya_dibatalkan(): void
    {
        $konteks = $this->siapkanKonteks();
        $sebelum = KotakKeluarPeristiwa::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $konteks['organisasi']->Id)->count();

        try {
            DB::transaction(function () use ($konteks): void {
                app(LayananKotakKeluar::class)->catat('Uji.Dibatalkan', ['Organisasi' => $konteks['organisasi']->Id]);
                throw new \RuntimeException('Batalkan transaksi bisnis.');
            });
        } catch (\RuntimeException) {
            // diharapkan
        }

        $this->assertSame($sebelum, KotakKeluarPeristiwa::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $konteks['organisasi']->Id)->count());
    }

    /**
     * @return array{organisasi: Organisasi, pengguna: Pengguna, kategori: KategoriKeluhan, token: string}
     */
    private function siapkanKonteks(): array
    {
        $organisasi = Organisasi::create([
            'Kode' => 'INT-'.uniqid(),
            'Nama' => 'Organisasi Integrasi '.uniqid(),
            'Status' => 'Aktif',
        ]);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna Integrasi',
            'Email' => 'integrasi.'.uniqid().'@amanpoll.test',
            'KataSandi' => 'password',
            'Status' => 'Aktif',
        ]);

        NomorDokumen::create([
            'OrganisasiId' => $organisasi->Id,
            'JenisDokumen' => 'Keluhan',
            'Awalan' => 'KLH',
            'FormatNomor' => '{Awalan}-{Nomor:5}',
            'NomorTerakhir' => 0,
            'ResetPeriode' => 'TidakAda',
        ]);

        $kategori = KategoriKeluhan::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'KAT-'.uniqid(),
            'Nama' => 'Gangguan Umum',
            'PrioritasBawaan' => 'Normal',
            'Aktif' => true,
        ]);

        $token = 'amp'.Str::lower(Str::random(6)).'.'.Str::random(32);
        KunciApi::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Kunci Integrasi',
            'AwalanKunci' => explode('.', $token)[0],
            'HashKunci' => hash('sha256', $token),
            'Cakupan' => ['Keluhan.Kelola'],
            'Status' => 'Aktif',
            'DibuatOleh' => $pengguna->Id,
        ]);

        return ['organisasi' => $organisasi, 'pengguna' => $pengguna, 'kategori' => $kategori, 'token' => $token];
    }

    /**
     * @param  array<string, mixed>  $konteks
     * @return array<string, string>
     */
    private function header(array $konteks): array
    {
        return ['Authorization' => 'Bearer '.$konteks['token'], 'Accept' => 'application/json'];
    }

    /**
     * @param  array<string, mixed>  $konteks
     * @return array<string, mixed>
     */
    private function muatanKeluhan(array $konteks): array
    {
        return [
            'Judul' => 'Lampu koridor mati',
            'Deskripsi' => 'Dilaporkan lewat aplikasi pelaporan eksternal.',
            'KategoriKeluhanId' => $konteks['kategori']->Id,
        ];
    }

    /**
     * @param  array<string, mixed>  $konteks
     * @param  array<string, mixed>  $muatan
     */
    private function kirimKeluhan(array $konteks, array $muatan, string $kunci): TestResponse
    {
        return $this->withHeaders(array_merge($this->header($konteks), ['Idempotency-Key' => $kunci]))
            ->postJson('/api/v1/keluhan', $muatan);
    }

    /**
     * @param  array<string, mixed>  $konteks
     * @param  list<string>  $peristiwa
     */
    private function buatWebhook(array $konteks, array $peristiwa): PanggilanBalikWeb
    {
        app(KonteksOrganisasi::class)->tetapkan($konteks['organisasi']->Id);

        return PanggilanBalikWeb::create([
            'OrganisasiId' => $konteks['organisasi']->Id,
            'Nama' => 'Endpoint Uji',
            'Url' => 'https://contoh.test/webhook',
            'Rahasia' => 'rahasia-uji',
            'Peristiwa' => $peristiwa,
            'Aktif' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function jumlahKeluhan(array $konteks): int
    {
        return Keluhan::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $konteks['organisasi']->Id)
            ->count();
    }
}
