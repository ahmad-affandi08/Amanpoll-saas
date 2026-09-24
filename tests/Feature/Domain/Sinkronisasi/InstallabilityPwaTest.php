<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Sinkronisasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\PenandaSinkronisasi;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\ExecutableFinder;
use Tests\TestCase;

/** 20.01/20.02 — aset yang membuat aplikasi dapat dipasang. */
final class InstallabilityPwaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_manifest_memenuhi_syarat_installability(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('site.webmanifest')), true);

        $this->assertIsArray($manifest);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['name']);
        $this->assertNotEmpty($manifest['short_name']);

        $ukuran = array_column($manifest['icons'], 'sizes');
        $this->assertContains('192x192', $ukuran);
        $this->assertContains('512x512', $ukuran);

        $maskable = array_filter($manifest['icons'], fn (array $ikon): bool => ($ikon['purpose'] ?? '') === 'maskable');
        $this->assertNotEmpty($maskable, 'Manifest harus menyediakan ikon maskable.');

        foreach ($manifest['icons'] as $ikon) {
            $this->assertFileExists(public_path(ltrim($ikon['src'], '/')));
        }
    }

    public function test_service_worker_dan_halaman_cadangan_offline_tersedia(): void
    {
        // Keduanya disajikan langsung oleh web server sebagai berkas statis (aturan `!-f` pada .htaccess).
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));

        $halamanOffline = (string) file_get_contents(public_path('offline.html'));
        $this->assertStringContainsString('Perangkat sedang tidak terhubung', $halamanOffline);
        $this->assertStringNotContainsString('@vite', $halamanOffline, 'Halaman cadangan tidak boleh bergantung pada bundel build.');

        $blade = (string) file_get_contents(resource_path('views/app.blade.php'));
        $this->assertStringContainsString('rel="manifest"', $blade);
    }

    public function test_service_worker_tidak_menyimpan_respons_yang_berisi_data_tenant(): void
    {
        $sw = (string) file_get_contents(public_path('sw.js'));

        // Hanya permintaan GET yang boleh diproses, dan hanya aset statis serta layar Mode Lapangan yang boleh masuk cache runtime.
        $this->assertStringContainsString("permintaan.method !== 'GET'", $sw);
        $this->assertStringContainsString('POLA_ASET_STATIS', $sw);
        $this->assertStringContainsString('POLA_HALAMAN_LAPANGAN = /^\/lapangan(\/|$)/', $sw);
        $kode = (string) preg_replace('#/\*.*?\*/|//[^\n]*#s', '', $sw);
        foreach (['/offline/paket', '/offline/antrian', '/api/'] as $endpointData) {
            $this->assertStringNotContainsString(
                $endpointData,
                $kode,
                'Service worker tidak boleh menyentuh endpoint data sama sekali.',
            );
        }

        // Cache runtime dikunci per organisasi + pengguna (20.02).
        $this->assertStringContainsString('AWALAN_RUNTIME', $sw);
        $this->assertStringContainsString('kunciKonteks', $sw);
    }

    /**
     * Perilaku cache service worker dijalankan sungguhan di Node dengan `caches` dan
     * `fetch` tiruan (FASE 39): layar Mode Lapangan teknisi dan pelapor tersimpan per
     * konteks organisasi + pengguna dan tersaji tanpa sinyal; dasbor, endpoint data,
     * pengalihan, kunjungan parsial, dan tamu tidak pernah tersimpan; logout membuangnya.
     */
    public function test_service_worker_menyimpan_layar_mode_lapangan_per_konteks_dan_menyajikannya_offline(): void
    {
        $node = (new ExecutableFinder)->find('node');
        if ($node === null) {
            $this->markTestSkipped('Biner node tidak tersedia untuk menjalankan service worker.');
        }

        $skrip = tempnam(sys_get_temp_dir(), 'sw-uji-').'.mjs';
        file_put_contents($skrip, str_replace('__SW__', json_encode(public_path('sw.js')), self::HARNESS_SW));

        try {
            $proses = Process::run([$node, $skrip]);
        } finally {
            @unlink($skrip);
        }

        $this->assertTrue($proses->successful(), $proses->errorOutput());
        $hasil = json_decode($proses->output(), true);
        $this->assertIsArray($hasil, $proses->output());

        $this->assertTrue($hasil['tamuTidakMenyimpanLapangan'], 'Tanpa konteks pengguna, layar lapangan tidak boleh disimpan.');
        $this->assertTrue($hasil['navigasiTeknisiTersimpan'], 'Beranda teknisi harus tersimpan di cache konteksnya.');
        $this->assertTrue($hasil['navigasiPelaporTersimpan'], 'Layar pelapor (beranda, lapor, laporan) harus tersimpan.');
        $this->assertTrue($hasil['inertiaTersimpanTerpisah'], 'Kunjungan Inertia disimpan di kunci tersendiri.');
        $this->assertFalse($hasil['inertiaParsialTersimpan'], 'Kunjungan Inertia parsial tidak boleh menimpa props utuh.');
        $this->assertFalse($hasil['dasborTersimpan'], 'Halaman dasbor tidak boleh disimpan.');
        $this->assertFalse($hasil['inertiaDasborAsetTersimpan'], 'Kunjungan Inertia ke halaman aset dasbor tidak boleh disimpan sebagai aset statis.');
        $this->assertTrue($hasil['ikon3dTersimpan'], 'Ikon 3D Mode Lapangan disimpan sebagai aset statis.');
        $this->assertFalse($hasil['jsonDitangani'], 'Permintaan data JSON dibiarkan lewat tanpa cache.');
        $this->assertFalse($hasil['postDitangani'], 'Permintaan tulis tidak pernah ditangani service worker.');
        $this->assertFalse($hasil['pengalihanTersimpan'], 'Respons pengalihan (mis. sesi habis) tidak boleh disimpan.');
        $this->assertSame('beranda teknisi', $hasil['offlineMenyajikanSalinan']);
        $this->assertSame('/lapangan/pelapor', $hasil['pintuMasukOfflineDialihkanKe'], 'Pintu masuk offline menuju beranda lapangan yang terakhir dibuka.');
        $this->assertTrue($hasil['konteksBertahanSetelahWorkerMati'], 'Kunci konteks dibaca ulang dari cache meta setelah worker dimatikan.');
        $this->assertSame([], $hasil['cacheRuntimeSetelahLogout'], 'Logout (BERSIHKAN) membuang seluruh cache runtime.');
        $this->assertSame('cadangan', $hasil['offlineSetelahLogout'], 'Setelah logout, layar lapangan tidak lagi tersaji dari cache.');
        $this->assertSame([], $hasil['cacheRuntimeSesudahWorkerHidupLagi'], 'Kunci konteks lama tidak boleh hidup lagi setelah logout.');
    }

    /** Mode Lapangan menggantikan ruang kerja teknisi; jalur lamanya tetap hidup lewat pengalihan (PRD 8.20). */
    public function test_jalur_teknisi_offline_dialihkan_ke_mode_lapangan(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->get('/offline/teknisi')
            ->assertRedirect('/lapangan');
    }

    public function test_halaman_teknisi_offline_menolak_tamu(): void
    {
        $this->get('/offline/teknisi')->assertRedirect('/login');
    }

    public function test_melepas_perangkat_membuang_penanda_dan_menonaktifkan_perangkat(): void
    {
        $pengguna = $this->buatPengguna();
        $amplop = ['IdentitasPerangkat' => 'perangkat-logout-01', 'NamaPerangkat' => 'Ponsel', 'Platform' => 'Android'];

        $this->actingAs($pengguna)->postJson('/offline/paket', $amplop)->assertOk();
        $this->assertGreaterThan(0, PenandaSinkronisasi::query()->withoutGlobalScopes()->count());

        $this->actingAs($pengguna)
            ->postJson('/offline/perangkat/lepas', $amplop)
            ->assertOk()
            ->assertJson(['Dilepas' => true]);

        $perangkat = PerangkatPengguna::query()->withoutGlobalScopes()
            ->where('PenggunaId', $pengguna->Id)->firstOrFail();
        $this->assertSame('Nonaktif', $perangkat->Status);
        $this->assertSame(0, PenandaSinkronisasi::query()->withoutGlobalScopes()
            ->where('PerangkatPenggunaId', $perangkat->Id)->count());
    }

    public function test_perangkat_yang_sama_tidak_didaftarkan_dua_kali(): void
    {
        $pengguna = $this->buatPengguna();
        $amplop = ['IdentitasPerangkat' => 'perangkat-tetap-01', 'NamaPerangkat' => 'Ponsel', 'Platform' => 'Android'];

        $pertama = $this->actingAs($pengguna)->postJson('/offline/paket', $amplop);
        $kedua = $this->actingAs($pengguna)->postJson('/offline/paket', $amplop);

        $this->assertSame($pertama->json('Perangkat.Id'), $kedua->json('Perangkat.Id'));
        $this->assertSame(1, PerangkatPengguna::query()->withoutGlobalScopes()
            ->where('PenggunaId', $pengguna->Id)->count());
    }

    public function test_ringkasan_sinkronisasi_hanya_menghitung_perangkat_milik_pengguna(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->getJson('/offline/ringkasan')
            ->assertOk()
            ->assertJson(['Menunggu' => 0, 'Konflik' => 0, 'Gagal' => 0]);
    }

    private function buatPengguna(): Pengguna
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-PWA-'.uniqid(), 'Nama' => 'Organisasi PWA']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        return Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi PWA',
            'Email' => uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
    }

    /**
     * Menjalankan `public/sw.js` di Node dengan `self`, `caches`, dan `fetch` tiruan,
     * lalu mencetak hasil tiap skenario sebagai JSON.
     */
    private const HARNESS_SW = <<<'JS'
        import { readFileSync } from 'node:fs';
        import vm from 'node:vm';

        const ASAL = 'https://dasbor.amanpoll.test';
        const penyimpanan = new Map();
        const buatCache = (nama) => {
          if (!penyimpanan.has(nama)) penyimpanan.set(nama, new Map());
          const isi = penyimpanan.get(nama);
          const kunci = (k) => new URL(typeof k === 'string' ? k : k.url, ASAL).href;
          return {
            async put(k, r) { isi.set(kunci(k), r); },
            async match(k, opsi = {}) {
              const dicari = kunci(k);
              if (isi.has(dicari)) return isi.get(dicari).clone();
              if (opsi.ignoreSearch) {
                const tanpa = dicari.split('?')[0];
                for (const [k2, r] of isi) if (k2.split('?')[0] === tanpa) return r.clone();
              }
              return undefined;
            },
            async delete(k) { return isi.delete(kunci(k)); },
            async addAll() {},
          };
        };
        const caches = {
          async open(nama) { return buatCache(nama); },
          async keys() { return [...penyimpanan.keys()]; },
          async delete(nama) { return penyimpanan.delete(nama); },
        };

        let jaringanHidup = true;
        const respons = (teks, { status = 200, redirected = false, inertia = false } = {}) => {
          const r = new Response(teks, { status, headers: inertia ? { 'X-Inertia': 'true' } : {} });
          Object.defineProperty(r, 'type', { value: 'basic' });
          Object.defineProperty(r, 'redirected', { value: redirected });
          return r;
        };
        let jawaban = () => respons('ok');
        const fetchTiruan = async () => {
          if (!jaringanHidup) throw new TypeError('Failed to fetch');
          return jawaban();
        };

        const pendengar = {};
        const buatWorker = () => {
          const self = {
            location: new URL(ASAL),
            registration: { active: true },
            clients: { claim: async () => {} },
            skipWaiting: async () => {},
            addEventListener: (jenis, fn) => { pendengar[jenis] = fn; },
          };
          vm.runInNewContext(readFileSync(__SW__, 'utf8'), {
            self, caches, fetch: fetchTiruan, Response, Request, URL, Promise, setTimeout, console,
          });
        };

        const pesan = async (data) => {
          let tunggu = Promise.resolve();
          pendengar.message({ data, waitUntil: (p) => { tunggu = p; } });
          await tunggu;
        };
        const ambil = async (jalur, { mode = 'cors', method = 'GET', headers = {} } = {}) => {
          let dijawab = null;
          const request = { url: ASAL + jalur, method, mode, headers: new Headers(headers) };
          pendengar.fetch({ request, respondWith: (p) => { dijawab = p; } });
          return dijawab ? await dijawab : null;
        };
        const runtime = () => [...penyimpanan.keys()].filter((n) => n.startsWith('amanpoll-runtime-'));
        const tersimpan = (jalur) => runtime().some((n) => penyimpanan.get(n).has(ASAL + jalur));

        buatWorker();
        const hasil = {};

        await ambil('/lapangan/teknisi', { mode: 'navigate' });
        hasil.tamuTidakMenyimpanLapangan = runtime().length === 0 || !tersimpan('/lapangan/teknisi');

        await pesan({ type: 'TETAPKAN_KONTEKS', kunci: 'org-1:pengguna-1' });
        jawaban = () => respons('beranda teknisi');
        await ambil('/lapangan/teknisi', { mode: 'navigate' });
        hasil.navigasiTeknisiTersimpan = penyimpanan.get('amanpoll-runtime-v3-org-1:pengguna-1')?.has(ASAL + '/lapangan/teknisi') ?? false;

        jawaban = () => respons('pelapor');
        for (const jalur of ['/lapangan/pelapor', '/lapangan/pelapor/lapor', '/lapangan/pelapor/laporan']) await ambil(jalur, { mode: 'navigate' });
        hasil.navigasiPelaporTersimpan = ['/lapangan/pelapor', '/lapangan/pelapor/lapor', '/lapangan/pelapor/laporan'].every(tersimpan);

        jawaban = () => respons('{"component":"Lapangan/Teknisi/Tugas"}', { inertia: true });
        await ambil('/lapangan/teknisi/tugas', { headers: { 'X-Inertia': 'true' } });
        hasil.inertiaTersimpanTerpisah = tersimpan('/lapangan/teknisi/tugas?__inertia=1') && !tersimpan('/lapangan/teknisi/tugas');
        await ambil('/lapangan/teknisi/aset', { headers: { 'X-Inertia': 'true', 'X-Inertia-Partial-Data': 'asetTerpilih' } });
        hasil.inertiaParsialTersimpan = tersimpan('/lapangan/teknisi/aset?__inertia=1');

        jawaban = () => respons('dasbor');
        await ambil('/aset', { mode: 'navigate' });
        hasil.dasborTersimpan = tersimpan('/aset');
        jawaban = () => respons('{"component":"Aset/Show"}', { inertia: true });
        await ambil('/aset/01ASETDASBOR', { headers: { 'X-Inertia': 'true' } });
        hasil.inertiaDasborAsetTersimpan = tersimpan('/aset/01ASETDASBOR') || tersimpan('/aset/01ASETDASBOR?__inertia=1');
        jawaban = () => respons('png');
        await ambil('/images/3d/wrench.png');
        hasil.ikon3dTersimpan = tersimpan('/images/3d/wrench.png');
        hasil.jsonDitangani = (await ambil('/offline/ringkasan', { headers: { Accept: 'application/json' } })) !== null;
        hasil.postDitangani = (await ambil('/lapangan/tampilan', { method: 'POST', mode: 'navigate' })) !== null;

        jawaban = () => respons('login', { redirected: true });
        await ambil('/lapangan/akun', { mode: 'navigate' });
        hasil.pengalihanTersimpan = tersimpan('/lapangan/akun');

        jaringanHidup = false;
        hasil.offlineMenyajikanSalinan = await (await ambil('/lapangan/teknisi', { mode: 'navigate' })).text();
        const pintu = await ambil('/lapangan', { mode: 'navigate' });
        hasil.pintuMasukOfflineDialihkanKe = new URL(pintu.headers.get('Location'), ASAL).pathname;

        buatWorker();
        hasil.konteksBertahanSetelahWorkerMati = (await (await ambil('/lapangan/teknisi', { mode: 'navigate' })).text()) === 'beranda teknisi';

        penyimpanan.set('amanpoll-kerangka-v3', new Map([[ASAL + '/offline.html', respons('cadangan')]]));
        await pesan({ type: 'BERSIHKAN' });
        hasil.cacheRuntimeSetelahLogout = runtime();
        hasil.offlineSetelahLogout = await (await ambil('/lapangan/teknisi', { mode: 'navigate' })).text();

        // Worker dimatikan browser setelah logout, lalu pengguna berikutnya membuka layar sebelum konteksnya terkirim.
        buatWorker();
        jaringanHidup = true;
        jawaban = () => respons('milik pengguna berikutnya');
        await ambil('/lapangan/teknisi', { mode: 'navigate' });
        hasil.cacheRuntimeSesudahWorkerHidupLagi = runtime();

        console.log(JSON.stringify(hasil));
        JS;
}
