<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Integrasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\IntegrasiAudit\Application\Services\LayananPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Domain\Enums\StatusPengirimanPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PengirimanPanggilanBalikWeb;
use App\Domain\Kepatuhan\Application\Actions\KelolaIntegrasiEksternal;
use App\Domain\Kepatuhan\Application\Actions\KelolaPemetaanDataEksternal;
use App\Domain\Kepatuhan\Application\Services\LayananSinkronisasiEksternal;
use App\Domain\Kepatuhan\Domain\Enums\ArahSinkronisasiEksternal;
use App\Domain\Kepatuhan\Domain\Enums\StatusIntegrasiEksternal;
use App\Domain\Kepatuhan\Domain\Enums\StatusSinkronisasiEksternal;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\PemetaanDataEksternal;
use App\Domain\Kepatuhan\Jobs\JalankanSinkronisasiEksternal;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Infrastructure\Keamanan\PenjagaUrlKeluar;
use App\Shared\Infrastructure\Keamanan\UrlKeluarDitolak;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as PermintaanHttp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Dukungan\PenyelesaiDnsPalsu;
use Tests\TestCase;

/** FASE 19 — konfigurasi integrasi, pemetaan data, sinkronisasi, dan panggilan balik web. */
final class IntegrasiEksternalTest extends TestCase
{
    use DatabaseTransactions;

    public function test_19_01_kredensial_integrasi_disimpan_terenkripsi(): void
    {
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks, ['Konfigurasi' => ['Token' => 'rahasia-sekali']]);

        // Nilai di kolom tidak boleh terbaca sebagai teks biasa.
        $mentah = (string) DB::table('IntegrasiEksternal')->where('Id', $integrasi->Id)->value('KonfigurasiTerenkripsi');
        $this->assertStringNotContainsString('rahasia-sekali', $mentah);

        // Tetapi aplikasi masih dapat membacanya kembali.
        $this->assertSame('rahasia-sekali', $integrasi->refresh()->KonfigurasiTerenkripsi['Token']);
    }

    public function test_19_01_kredensial_tidak_terhapus_saat_form_tidak_mengirim_konfigurasi(): void
    {
        $konteks = $this->siapkanKonteks();
        $aksi = app(KelolaIntegrasiEksternal::class);
        $integrasi = $this->buatIntegrasi($konteks, ['Konfigurasi' => ['Token' => 'token-lama']]);

        $aksi->ubah($integrasi, [
            'Kode' => $integrasi->Kode,
            'Nama' => 'Nama Baru',
            'Jenis' => 'ERP',
            'UrlDasar' => 'https://erp.test/api',
        ]);

        $this->assertSame('token-lama', $integrasi->refresh()->KonfigurasiTerenkripsi['Token']);
        $this->assertSame('Nama Baru', $integrasi->Nama);
    }

    public function test_19_01_audit_integrasi_hanya_mencatat_nama_kunci_bukan_nilainya(): void
    {
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks, ['Konfigurasi' => ['Token' => 'jangan-bocor']]);

        $audit = DB::table('CatatanAudit')
            ->where('JenisEntitas', 'IntegrasiEksternal')
            ->where('EntitasId', $integrasi->Id)
            ->orderByDesc('DibuatPada')
            ->first();

        $this->assertNotNull($audit);
        $isi = (string) ($audit->DataSesudah ?? '');
        $this->assertStringNotContainsString('jangan-bocor', $isi);
        $this->assertStringContainsString('Token', $isi);
    }

    public function test_19_01_integrasi_dengan_pemetaan_tidak_dapat_dihapus(): void
    {
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks);
        app(KelolaPemetaanDataEksternal::class)->petakan($integrasi, [
            'JenisEntitas' => 'Aset',
            'EntitasId' => (string) Str::ulid(),
            'KodeEksternal' => 'EXT-1',
        ]);

        $this->assertThrows(
            fn () => app(KelolaIntegrasiEksternal::class)->hapus($integrasi->refresh()),
            AturanBisnisDilanggar::class,
        );
    }

    public function test_19_02_kode_eksternal_ganda_ditandai_konflik_dan_dapat_diselesaikan(): void
    {
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks);
        $aksi = app(KelolaPemetaanDataEksternal::class);

        $pertama = $aksi->petakan($integrasi, [
            'JenisEntitas' => 'Aset',
            'EntitasId' => (string) Str::ulid(),
            'KodeEksternal' => 'EXT-SAMA',
        ]);
        $this->assertFalse($aksi->berkonflik($pertama));

        $kedua = $aksi->petakan($integrasi, [
            'JenisEntitas' => 'Aset',
            'EntitasId' => (string) Str::ulid(),
            'KodeEksternal' => 'EXT-SAMA',
        ]);
        $this->assertTrue($aksi->berkonflik($kedua));

        // Menyelesaikan konflik dengan mempertahankan pemetaan kedua melepas yang pertama.
        $aksi->selesaikanKonflik($kedua, pertahankan: true);
        $this->assertNull(PemetaanDataEksternal::query()->find($pertama->Id));
        $this->assertFalse($aksi->berkonflik($kedua->refresh()));
    }

    public function test_19_02_pemetaan_entitas_yang_sama_diperbarui_bukan_diduplikasi(): void
    {
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks);
        $aksi = app(KelolaPemetaanDataEksternal::class);
        $entitasId = (string) Str::ulid();

        $aksi->petakan($integrasi, ['JenisEntitas' => 'Aset', 'EntitasId' => $entitasId, 'KodeEksternal' => 'EXT-1']);
        $aksi->petakan($integrasi, ['JenisEntitas' => 'Aset', 'EntitasId' => $entitasId, 'KodeEksternal' => 'EXT-2']);

        $this->assertSame(1, $integrasi->pemetaan()->count());
        $this->assertSame('EXT-2', $integrasi->pemetaan()->first()->KodeEksternal);
    }

    public function test_19_03_sinkronisasi_mencatat_status_sesuai_hasilnya(): void
    {
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks);
        $layanan = app(LayananSinkronisasiEksternal::class);

        $berhasil = $layanan->selesaikan($layanan->mulai($integrasi, 'Aset', ArahSinkronisasiEksternal::Tarik->value), 10, 0);
        $this->assertSame(StatusSinkronisasiEksternal::Berhasil->value, $berhasil->Status);
        $this->assertSame(10, $berhasil->JumlahData);

        $sebagian = $layanan->selesaikan($layanan->mulai($integrasi, 'Aset', ArahSinkronisasiEksternal::Dorong->value), 7, 3);
        $this->assertSame(StatusSinkronisasiEksternal::Sebagian->value, $sebagian->Status);

        $gagal = $layanan->selesaikan($layanan->mulai($integrasi, 'Aset', ArahSinkronisasiEksternal::Tarik->value), 0, 5, 'Bearer abcdef gagal');
        $this->assertSame(StatusSinkronisasiEksternal::Gagal->value, $gagal->Status);

        // Pesan kegagalan tidak boleh menyimpan kredensial mentah.
        $this->assertStringNotContainsString('abcdef', (string) $gagal->PesanKesalahan);
        $this->assertStringContainsString('disamarkan', (string) $gagal->PesanKesalahan);
    }

    public function test_19_03_sinkronisasi_ditolak_pada_integrasi_nonaktif(): void
    {
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks);
        app(KelolaIntegrasiEksternal::class)->ubahStatus($integrasi, StatusIntegrasiEksternal::Nonaktif->value);

        $this->assertThrows(
            fn () => app(LayananSinkronisasiEksternal::class)->mulai($integrasi->refresh(), 'Aset', ArahSinkronisasiEksternal::Tarik->value),
            AturanBisnisDilanggar::class,
        );
    }

    public function test_19_03_sinkronisasi_dimasukkan_ke_antrean_bukan_dijalankan_langsung(): void
    {
        Queue::fake();
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks);

        $sinkronisasi = app(LayananSinkronisasiEksternal::class)
            ->antrikan($integrasi, 'Aset', ArahSinkronisasiEksternal::Tarik->value);

        $this->assertSame(StatusSinkronisasiEksternal::Diproses->value, $sinkronisasi->Status);
        Queue::assertPushed(JalankanSinkronisasiEksternal::class);
    }

    public function test_19_03_adapter_rest_menghitung_data_yang_ditarik(): void
    {
        Http::fake(['*' => Http::response(['data' => [['Id' => 1], ['Id' => 2]], 'gagal' => [['Id' => 3]]], 200)]);
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks);
        $layanan = app(LayananSinkronisasiEksternal::class);

        $hasil = $layanan->jalankan($layanan->mulai($integrasi, 'Aset', ArahSinkronisasiEksternal::Tarik->value));

        $this->assertSame(StatusSinkronisasiEksternal::Sebagian->value, $hasil->Status);
        $this->assertSame(2, $hasil->JumlahBerhasil);
        $this->assertSame(1, $hasil->JumlahGagal);
        $this->assertNotNull($integrasi->refresh()->TerakhirSinkronPada);

        // Arah menentukan metode HTTP yang dipakai adapter.
        Http::assertSent(fn ($request): bool => $request->method() === 'GET'
            && $request->url() === 'https://erp.test/api/aset');
    }

    public function test_19_03_kegagalan_sinkronisasi_dilempar_supaya_job_mencoba_ulang(): void
    {
        Http::fake(['*' => Http::response('galat', 500)]);
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks);
        $layanan = app(LayananSinkronisasiEksternal::class);
        $sinkronisasi = $layanan->mulai($integrasi, 'Aset', ArahSinkronisasiEksternal::Dorong->value);

        $this->assertThrows(fn () => $layanan->jalankan($sinkronisasi), AturanBisnisDilanggar::class);
        $this->assertSame(StatusSinkronisasiEksternal::Diproses->value, $sinkronisasi->refresh()->Status);

        // Percobaan terakhir job menutup baris sinkronisasi sebagai gagal.
        (new JalankanSinkronisasiEksternal($sinkronisasi->Id))->failed(new AturanBisnisDilanggar('Bearer abc gagal'));
        $sinkronisasi->refresh();
        $this->assertSame(StatusSinkronisasiEksternal::Gagal->value, $sinkronisasi->Status);
        $this->assertStringNotContainsString('abc', (string) $sinkronisasi->PesanKesalahan);
    }

    public function test_19_01_uji_koneksi_gagal_menandai_integrasi_bermasalah(): void
    {
        Http::fake(['*' => Http::response('tidak tersedia', 503)]);
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks);

        $hasil = app(LayananSinkronisasiEksternal::class)->ujiKoneksi($integrasi);

        $this->assertFalse($hasil['berhasil']);
        $this->assertSame(503, $hasil['status']);
        $this->assertSame(StatusIntegrasiEksternal::Bermasalah->value, $integrasi->refresh()->Status);
    }

    public function test_19_05_pengiriman_ditandatangani_dan_dicatat(): void
    {
        Http::fake(['*' => Http::response('diterima', 200)]);
        $konteks = $this->siapkanKonteks();
        $webhook = $this->buatWebhook($konteks, ['Uji.*'], 'rahasia-penandatanganan');
        $pengiriman = $this->buatPengiriman($konteks, $webhook);

        $layanan = app(LayananPanggilanBalikWeb::class);
        $this->assertTrue($layanan->kirim($pengiriman));

        $pengiriman->refresh();
        $this->assertSame(StatusPengirimanPanggilanBalikWeb::Berhasil->value, $pengiriman->Status);
        $this->assertSame(200, $pengiriman->StatusHttp);
        $this->assertNotNull($pengiriman->DikirimPada);

        Http::assertSent(function ($request) use ($layanan): bool {
            $harapan = $layanan->tandaTangan($request->body(), 'rahasia-penandatanganan');

            return $request->hasHeader(LayananPanggilanBalikWeb::HEADER_TANDA_TANGAN, $harapan)
                && $request->hasHeader(LayananPanggilanBalikWeb::HEADER_PERISTIWA, 'Uji.Peristiwa');
        });
    }

    public function test_19_05_pengiriman_gagal_dijadwalkan_ulang_lalu_menyerah_permanen(): void
    {
        Http::fake(['*' => Http::response('galat', 500)]);
        $konteks = $this->siapkanKonteks();
        $webhook = $this->buatWebhook($konteks, ['*'], 'rahasia-penandatanganan');
        $pengiriman = $this->buatPengiriman($konteks, $webhook);
        $layanan = app(LayananPanggilanBalikWeb::class);

        $this->assertFalse($layanan->kirim($pengiriman));
        $pengiriman->refresh();
        $this->assertSame(StatusPengirimanPanggilanBalikWeb::Gagal->value, $pengiriman->Status);
        $this->assertSame(1, $pengiriman->Percobaan);
        $this->assertNotNull($pengiriman->JadwalCobaLagiPada);

        // Jeda percobaan berikutnya lebih panjang daripada percobaan sebelumnya.
        $jedaPertama = $pengiriman->JadwalCobaLagiPada;
        $layanan->kirim($pengiriman->refresh());
        $this->assertTrue($pengiriman->refresh()->JadwalCobaLagiPada?->greaterThan($jedaPertama));

        // Setelah batas percobaan habis, status menjadi gagal permanen.
        for ($i = 0; $i < PengirimanPanggilanBalikWeb::BATAS_PERCOBAAN; $i++) {
            $layanan->kirim($pengiriman->refresh());
        }
        $this->assertSame(StatusPengirimanPanggilanBalikWeb::GagalPermanen->value, $pengiriman->refresh()->Status);
        $this->assertNull($pengiriman->JadwalCobaLagiPada);
    }

    public function test_19_04_webhook_nonaktif_tidak_menerima_pengiriman(): void
    {
        Http::fake();
        $konteks = $this->siapkanKonteks();
        $webhook = $this->buatWebhook($konteks, ['*'], 'rahasia-penandatanganan');
        $webhook->Aktif = false;
        $webhook->save();
        $pengiriman = $this->buatPengiriman($konteks, $webhook);

        $this->assertFalse(app(LayananPanggilanBalikWeb::class)->kirim($pengiriman));
        $this->assertSame(StatusPengirimanPanggilanBalikWeb::GagalPermanen->value, $pengiriman->refresh()->Status);
        Http::assertNothingSent();
    }

    public function test_19_04_rahasia_webhook_disimpan_terenkripsi(): void
    {
        $konteks = $this->siapkanKonteks();
        $webhook = $this->buatWebhook($konteks, ['*'], 'rahasia-yang-panjang');

        $mentah = (string) DB::table('PanggilanBalikWeb')->where('Id', $webhook->Id)->value('Rahasia');
        $this->assertStringNotContainsString('rahasia-yang-panjang', $mentah);
        $this->assertSame('rahasia-yang-panjang', $webhook->refresh()->Rahasia);
    }

    public function test_endpoint_integrasi_menegakkan_izin_dan_isolasi_tenant(): void
    {
        $konteks = $this->siapkanKonteks();

        $tanpaIzin = $this->buatPengguna($konteks['organisasi'], []);
        $this->actingAs($tanpaIzin)->get(route('integrasi.index'))->assertForbidden();

        $organisasiLain = $this->buatOrganisasi('LAIN');
        app(KonteksOrganisasi::class)->tetapkan($organisasiLain->Id);
        $integrasiLain = IntegrasiEksternal::create([
            'Kode' => 'EXT-LAIN',
            'Nama' => 'Integrasi Tenant Lain',
            'Jenis' => 'ERP',
            'Status' => StatusIntegrasiEksternal::Aktif->value,
        ]);

        app(KonteksOrganisasi::class)->tetapkan($konteks['organisasi']->Id);
        $this->actingAs($konteks['pengguna'])
            ->get(route('integrasi.show', $integrasiLain->Id))
            ->assertNotFound();
    }

    public function test_halaman_operasional_fase_19_dapat_dirender(): void
    {
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks);
        $webhook = $this->buatWebhook($konteks, ['*'], 'rahasia-penandatanganan');

        $this->actingAs($konteks['pengguna']);
        $this->get(route('integrasi.index'))->assertOk();
        $this->get(route('integrasi.show', $integrasi))->assertOk();
        $this->get(route('integrasi.panggilan-balik.pengiriman', $webhook))->assertOk();
    }

    public function test_simpan_webhook_ke_jaringan_internal_ditolak_dengan_pesan_indonesia(): void
    {
        $konteks = $this->siapkanKonteks();
        $this->dnsPalsu()->petakan('erp.rumahsakit.test', ['192.168.10.5']);

        foreach (['http://127.0.0.1:80/hook', 'https://erp.rumahsakit.test/hook', 'http://169.254.169.254/latest/meta-data/'] as $url) {
            $this->post(route('integrasi.panggilan-balik.store'), [
                'Nama' => 'Endpoint Internal',
                'Url' => $url,
                'Rahasia' => 'rahasia-penandatanganan',
                'Peristiwa' => ['*'],
                'Aktif' => true,
            ])->assertSessionHasErrors(['Url' => UrlKeluarDitolak::PESAN_JARINGAN_INTERNAL]);
        }

        $this->assertSame(0, PanggilanBalikWeb::query()->withoutGlobalScopes()->where('OrganisasiId', $konteks['organisasi']->Id)->count());

        // Pembanding: alamat publik diterima formulir yang sama.
        $this->post(route('integrasi.panggilan-balik.store'), [
            'Nama' => 'Endpoint Publik',
            'Url' => 'https://penerima.contoh.co.id/hook',
            'Rahasia' => 'rahasia-penandatanganan',
            'Peristiwa' => ['*'],
            'Aktif' => true,
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, PanggilanBalikWeb::query()->withoutGlobalScopes()->where('OrganisasiId', $konteks['organisasi']->Id)->count());
    }

    public function test_simpan_integrasi_dengan_url_dasar_internal_ditolak(): void
    {
        $this->siapkanKonteks();

        $this->post(route('integrasi.store'), [
            'Kode' => 'ERP-INTERNAL',
            'Nama' => 'ERP Internal',
            'Jenis' => 'ERP',
            'UrlDasar' => 'http://10.0.0.7/api',
        ])->assertSessionHasErrors(['UrlDasar' => UrlKeluarDitolak::PESAN_JARINGAN_INTERNAL]);

        $this->assertFalse(IntegrasiEksternal::query()->withoutGlobalScopes()->where('Kode', 'ERP-INTERNAL')->exists());
    }

    /** DNS diarahkan ke jaringan internal SESUDAH webhook disimpan: pengiriman ditolak tanpa permintaan keluar. */
    public function test_kirim_webhook_ke_host_yang_kini_internal_tidak_mengirim_dan_gagal_permanen(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $konteks = $this->siapkanKonteks();
        $webhook = $this->buatWebhook($konteks, ['*'], 'rahasia-penandatanganan');
        $pengiriman = $this->buatPengiriman($konteks, $webhook);
        $this->dnsPalsu()->petakan('contoh.test', ['127.0.0.1']);

        $this->assertFalse(app(LayananPanggilanBalikWeb::class)->kirim($pengiriman));

        Http::assertNothingSent();
        $pengiriman->refresh();
        $this->assertSame(StatusPengirimanPanggilanBalikWeb::GagalPermanen->value, $pengiriman->Status);
        $this->assertNull($pengiriman->StatusHttp);
        $this->assertSame(PenjagaUrlKeluar::PESAN_DITOLAK_SAAT_KIRIM.': '.UrlKeluarDitolak::PESAN_JARINGAN_INTERNAL, $pengiriman->Respons);
        $this->assertStringNotContainsString('127.0.0.1', (string) $pengiriman->Respons);
    }

    public function test_kirim_webhook_ke_host_yang_belum_ditemukan_dijadwalkan_ulang_tanpa_permintaan(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $konteks = $this->siapkanKonteks();
        $webhook = $this->buatWebhook($konteks, ['*'], 'rahasia-penandatanganan');
        $pengiriman = $this->buatPengiriman($konteks, $webhook);
        $this->dnsPalsu()->petakan('contoh.test', []);

        $this->assertFalse(app(LayananPanggilanBalikWeb::class)->kirim($pengiriman));

        Http::assertNothingSent();
        $pengiriman->refresh();
        $this->assertSame(StatusPengirimanPanggilanBalikWeb::Gagal->value, $pengiriman->Status);
        $this->assertNotNull($pengiriman->JadwalCobaLagiPada);
    }

    public function test_kirim_webhook_memakai_ip_tersemat_dan_tidak_mengikuti_redirect(): void
    {
        $opsiTerkirim = [];
        Http::preventStrayRequests();
        Http::fake(function (PermintaanHttp $permintaan, array $opsi) use (&$opsiTerkirim) {
            $opsiTerkirim[] = $opsi;

            return Http::response('', 307, ['Location' => 'http://169.254.169.254/latest/meta-data/']);
        });
        $konteks = $this->siapkanKonteks();
        $pengiriman = $this->buatPengiriman($konteks, $this->buatWebhook($konteks, ['*'], 'rahasia-penandatanganan'));

        $this->assertFalse(app(LayananPanggilanBalikWeb::class)->kirim($pengiriman));

        Http::assertSentCount(1);
        $this->assertSame(['contoh.test:443:'.PenyelesaiDnsPalsu::ALAMAT_PUBLIK], $opsiTerkirim[0]['curl'][CURLOPT_RESOLVE] ?? null);
        $this->assertSame(307, $pengiriman->refresh()->StatusHttp);
        $this->assertSame(StatusPengirimanPanggilanBalikWeb::Gagal->value, $pengiriman->Status);
    }

    /** Balasan yang ditampilkan ke tenant dipangkas, dan isi non-teks tidak disimpan sama sekali. */
    public function test_respons_webhook_yang_disimpan_dipangkas_dan_hanya_teks(): void
    {
        Http::preventStrayRequests();
        Http::fake(['contoh.test/*' => Http::sequence()
            ->push(str_repeat('a', 3000)."\x07", 500, ['Content-Type' => 'text/plain; charset=utf-8'])
            ->push("\x89PNG\r\n\x1a\nrahasia-biner", 500, ['Content-Type' => 'image/png'])]);
        $konteks = $this->siapkanKonteks();
        $webhook = $this->buatWebhook($konteks, ['*'], 'rahasia-penandatanganan');
        $layanan = app(LayananPanggilanBalikWeb::class);

        $teks = $this->buatPengiriman($konteks, $webhook);
        $layanan->kirim($teks);
        $this->assertSame(str_repeat('a', LayananPanggilanBalikWeb::BATAS_CUPLIKAN_RESPONS), $teks->refresh()->Respons);

        $biner = $this->buatPengiriman($konteks, $webhook);
        $layanan->kirim($biner);
        $this->assertSame('(Isi balasan bertipe image/png tidak disimpan.)', $biner->refresh()->Respons);
    }

    public function test_uji_koneksi_ke_alamat_internal_ditolak_tanpa_permintaan_keluar(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks);
        // Ditulis langsung ke basis data, melewati validasi formulir, seperti data lama sebelum penjaga ada.
        $integrasi->UrlDasar = 'http://[::ffff:127.0.0.1]:80/admin';
        $integrasi->save();

        $hasil = app(LayananSinkronisasiEksternal::class)->ujiKoneksi($integrasi);

        $this->assertFalse($hasil['berhasil']);
        $this->assertStringStartsWith(PenjagaUrlKeluar::PESAN_DITOLAK_SAAT_KIRIM, $hasil['pesan']);
        $this->assertSame(StatusIntegrasiEksternal::Bermasalah->value, $integrasi->refresh()->Status);
        Http::assertNothingSent();
    }

    public function test_adapter_rest_menolak_host_yang_kini_meresolusi_ke_ip_privat(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $konteks = $this->siapkanKonteks();
        $integrasi = $this->buatIntegrasi($konteks);
        $layanan = app(LayananSinkronisasiEksternal::class);
        $sinkronisasi = $layanan->mulai($integrasi, 'Aset', ArahSinkronisasiEksternal::Tarik->value);
        $this->dnsPalsu()->petakan('erp.test', ['172.20.0.3']);

        $this->assertThrows(fn () => $layanan->jalankan($sinkronisasi), UrlKeluarDitolak::class);
        Http::assertNothingSent();
    }

    /**
     * @return array{organisasi: Organisasi, pengguna: Pengguna}
     */
    private function siapkanKonteks(): array
    {
        $organisasi = $this->buatOrganisasi('INT');
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $pengguna = $this->buatPengguna($organisasi, ['Integrasi.Kelola']);
        $this->actingAs($pengguna);

        return ['organisasi' => $organisasi, 'pengguna' => $pengguna];
    }

    /**
     * @param  array{organisasi: Organisasi, pengguna: Pengguna}  $konteks
     * @param  array<string, mixed>  $tambahan
     */
    private function buatIntegrasi(array $konteks, array $tambahan = []): IntegrasiEksternal
    {
        app(KonteksOrganisasi::class)->tetapkan($konteks['organisasi']->Id);

        return app(KelolaIntegrasiEksternal::class)->buat(array_merge([
            'Kode' => 'ERP-'.Str::upper(Str::random(4)),
            'Nama' => 'Sistem ERP',
            'Jenis' => 'ERP',
            'UrlDasar' => 'https://erp.test/api',
            'MetodeAutentikasi' => 'Bearer',
        ], $tambahan));
    }

    /**
     * @param  array{organisasi: Organisasi, pengguna: Pengguna}  $konteks
     * @param  list<string>  $peristiwa
     */
    private function buatWebhook(array $konteks, array $peristiwa, string $rahasia): PanggilanBalikWeb
    {
        app(KonteksOrganisasi::class)->tetapkan($konteks['organisasi']->Id);

        return PanggilanBalikWeb::create([
            'Nama' => 'Endpoint Uji',
            'Url' => 'https://contoh.test/webhook',
            'Rahasia' => $rahasia,
            'Peristiwa' => $peristiwa,
            'Aktif' => true,
        ]);
    }

    /**
     * @param  array{organisasi: Organisasi, pengguna: Pengguna}  $konteks
     */
    private function buatPengiriman(array $konteks, PanggilanBalikWeb $webhook): PengirimanPanggilanBalikWeb
    {
        return PengirimanPanggilanBalikWeb::create([
            'OrganisasiId' => $konteks['organisasi']->Id,
            'PanggilanBalikWebId' => $webhook->Id,
            'Peristiwa' => 'Uji.Peristiwa',
            'MuatanData' => ['IdPeristiwa' => (string) Str::ulid(), 'Data' => ['nilai' => 1]],
            'Status' => StatusPengirimanPanggilanBalikWeb::Antri->value,
        ]);
    }

    private function buatOrganisasi(string $kode): Organisasi
    {
        return Organisasi::create([
            'Kode' => $kode.'-'.uniqid(),
            'Nama' => 'Organisasi '.$kode.' '.uniqid(),
            'Status' => 'Aktif',
        ]);
    }

    /**
     * @param  list<string>  $izin
     */
    private function buatPengguna(Organisasi $organisasi, array $izin): Pengguna
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna.'.uniqid().'@amanpoll.test',
            'KataSandi' => 'password',
            'Status' => 'Aktif',
        ]);

        if ($izin === []) {
            return $pengguna;
        }

        $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran Integrasi']);
        foreach ($izin as $kodeIzin) {
            $modelIzin = Izin::firstOrCreate(['Kode' => $kodeIzin], ['Nama' => $kodeIzin, 'Modul' => 'Integrasi']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
        }
        PenggunaPeran::create(['OrganisasiId' => $organisasi->Id, 'PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        return $pengguna;
    }
}
