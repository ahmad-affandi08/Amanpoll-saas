<?php

declare(strict_types=1);

namespace Tests\Feature\AlurKritis;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\KomentarEntitas;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * FASE 26.03 — alur kritis sinkronisasi offline yang idempoten.
 *
 * Teknisi menarik paket offline, bekerja tanpa sinyal, lalu mendorong
 * antreannya. Koneksi putus tepat setelah server memproses, sehingga klien
 * mengirim ulang antrean yang sama — sekali utuh, sekali bercampur mutasi
 * baru. Setiap efek harus tetap tercatat satu kali per kunci operasi.
 */
final class AlurSinkronisasiOfflineTest extends TestCase
{
    use RefreshDatabase;

    private const KATA_SANDI = 'rahasia-panjang-01';

    private const PERANGKAT = [
        'IdentitasPerangkat' => 'ponsel-teknisi-alur-01',
        'NamaPerangkat' => 'Ponsel Lapangan',
        'Platform' => 'Android',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 03:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_antrean_offline_yang_dikirim_ulang_hanya_diterapkan_sekali_per_kunci_operasi(): void
    {
        ['organisasi' => $organisasi, 'teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkanPenugasan();
        app(KonteksOrganisasi::class)->bersihkan();

        // Langkah 1 — teknisi masuk dan menarik paket offline sebelum turun ke lapangan.
        $this->post(route('login.store'), [
            'Email' => $teknisi->Email,
            'KataSandi' => self::KATA_SANDI,
        ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));

        $paket = $this->postJson(route('offline.paket'), self::PERANGKAT)->assertOk();
        $this->assertSame([$perintahKerja->Nomor], array_column($paket->json('Paket.Penugasan'), 'Nomor'));
        $this->assertSame(1, $this->jumlahPerangkat($teknisi));
        $this->assertSame(0, $this->jumlahAntrean());

        // Langkah 2 — pekerjaan lapangan yang terkumpul selama offline.
        $antreanOffline = [
            [
                'KunciOperasi' => 'op-terima-01',
                'Operasi' => 'PerintahKerja.ResponsPenugasan',
                'EntitasId' => $perintahKerja->Id,
                'VersiKlien' => $perintahKerja->Versi,
                'MuatanData' => ['Respons' => 'Terima', 'Catatan' => 'Berangkat ke lokasi'],
            ],
            [
                'KunciOperasi' => 'op-catatan-01',
                'Operasi' => 'PerintahKerja.TambahCatatan',
                'EntitasId' => $perintahKerja->Id,
                'VersiKlien' => null,
                'MuatanData' => ['Isi' => 'Bearing aus, diganti sementara.'],
            ],
            [
                'KunciOperasi' => 'op-waktu-01',
                'Operasi' => 'PerintahKerja.CatatWaktuKerja',
                'EntitasId' => $perintahKerja->Id,
                'VersiKlien' => null,
                'MuatanData' => [
                    'MulaiPada' => '2026-09-01T01:00:00+00:00',
                    'SelesaiPada' => '2026-09-01T02:00:00+00:00',
                    'Catatan' => 'Penggantian bearing',
                ],
            ],
        ];

        $pertama = $this->dorong($antreanOffline);
        foreach (['op-terima-01', 'op-catatan-01', 'op-waktu-01'] as $kunci) {
            $this->assertSame('Selesai', $this->statusMutasi($pertama, $kunci), "Mutasi {$kunci} harus diterapkan.");
        }

        $efekSetelahKirimPertama = $this->efek($perintahKerja);
        $this->assertSame([
            'antrean' => 3,
            'status' => StatusPerintahKerja::Diterima->value,
            'versi' => 2,
            'riwayatDiterima' => 1,
            'catatan' => 1,
            'waktuKerja' => 1,
            'auditDiterapkan' => 3,
        ], $efekSetelahKirimPertama);

        // Langkah 3 — balasan tidak sampai ke ponsel; klien mengirim ulang antrean yang persis sama.
        $ulang = $this->dorong($antreanOffline);
        foreach (['op-terima-01', 'op-catatan-01', 'op-waktu-01'] as $kunci) {
            $this->assertSame('Selesai', $this->statusMutasi($ulang, $kunci));
        }
        $this->assertSame($efekSetelahKirimPertama, $this->efek($perintahKerja), 'Pengiriman ulang tidak boleh menambah efek apa pun.');
        $this->assertSame(1, $this->jumlahPerangkat($teknisi), 'Perangkat yang sama tidak boleh terdaftar dua kali.');

        // Langkah 4 — pengiriman berikutnya membawa antrean lama ditambah satu mutasi baru.
        $antreanBerikutnya = [...$antreanOffline, [
            'KunciOperasi' => 'op-catatan-02',
            'Operasi' => 'PerintahKerja.TambahCatatan',
            'EntitasId' => $perintahKerja->Id,
            'VersiKlien' => null,
            'MuatanData' => ['Isi' => 'Uji jalan 15 menit, normal.'],
        ]];
        $campuran = $this->dorong($antreanBerikutnya);
        $this->assertSame('Selesai', $this->statusMutasi($campuran, 'op-catatan-02'));

        $this->assertSame([
            ...$efekSetelahKirimPertama,
            'antrean' => 4,
            'catatan' => 2,
            'auditDiterapkan' => 4,
        ], $this->efek($perintahKerja), 'Hanya mutasi baru yang boleh diterapkan.');

        // Langkah 5 — klien memeriksa status antreannya: empat kunci, semuanya tuntas, tidak ada kembaran.
        $status = $this->postJson(route('offline.antrian.status'), self::PERANGKAT)->assertOk();
        $antrean = collect($status->json('Antrean'));
        $this->assertEqualsCanonicalizing(
            ['op-terima-01', 'op-catatan-01', 'op-waktu-01', 'op-catatan-02'],
            $antrean->pluck('KunciOperasi')->all(),
        );
        $this->assertSame(['Selesai'], $antrean->pluck('Status')->unique()->values()->all());
    }

    /** @param list<array<string, mixed>> $mutasi */
    private function dorong(array $mutasi): TestResponse
    {
        return $this->postJson(route('offline.antrian.dorong'), [...self::PERANGKAT, 'Mutasi' => $mutasi])->assertOk();
    }

    private function statusMutasi(TestResponse $respons, string $kunciOperasi): ?string
    {
        return collect($respons->json('Antrean'))->firstWhere('KunciOperasi', $kunciOperasi)['Status'] ?? null;
    }

    /** @return array{antrean: int, status: string, versi: int, riwayatDiterima: int, catatan: int, waktuKerja: int, auditDiterapkan: int} */
    private function efek(PerintahKerja $perintahKerja): array
    {
        $segar = PerintahKerja::query()->withoutGlobalScopes()->findOrFail($perintahKerja->Id);

        return [
            'antrean' => $this->jumlahAntrean(),
            'status' => (string) $segar->Status,
            'versi' => (int) $segar->Versi,
            'riwayatDiterima' => RiwayatStatusPerintahKerja::query()->withoutGlobalScopes()
                ->where('PerintahKerjaId', $perintahKerja->Id)
                ->where('StatusSesudah', StatusPerintahKerja::Diterima->value)
                ->count(),
            'catatan' => KomentarEntitas::query()->withoutGlobalScopes()
                ->where('JenisEntitas', 'PerintahKerja')
                ->where('EntitasId', $perintahKerja->Id)
                ->count(),
            'waktuKerja' => WaktuKerja::query()->withoutGlobalScopes()
                ->where('PerintahKerjaId', $perintahKerja->Id)
                ->count(),
            'auditDiterapkan' => CatatanAudit::query()->withoutGlobalScopes()
                ->where('Aksi', 'Sinkronisasi.Diterapkan')
                ->where('EntitasId', $perintahKerja->Id)
                ->count(),
        ];
    }

    private function jumlahAntrean(): int
    {
        return AntrianSinkronisasi::query()->withoutGlobalScopes()->count();
    }

    private function jumlahPerangkat(Pengguna $teknisi): int
    {
        return PerangkatPengguna::query()->withoutGlobalScopes()
            ->where('PenggunaId', $teknisi->Id)
            ->count();
    }

    /**
     * @return array{organisasi: Organisasi, teknisi: Pengguna, perintahKerja: PerintahKerja}
     */
    private function siapkanPenugasan(): array
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-OFFLINE', 'Nama' => 'RS Lapangan', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $teknisi = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi Lapangan',
            'Email' => 'teknisi@lapangan.test',
            'KataSandi' => self::KATA_SANDI,
            'Status' => 'Aktif',
        ]);

        $perintahKerja = PerintahKerja::create([
            'OrganisasiId' => $organisasi->Id,
            'Nomor' => 'WO-OFFLINE-0001',
            'Judul' => 'Perbaikan pompa air',
            'Jenis' => 'Korektif',
            'Status' => StatusPerintahKerja::Ditugaskan->value,
            'Prioritas' => 'Normal',
            'Versi' => 1,
        ]);

        $kategori = KategoriAset::create(['OrganisasiId' => $organisasi->Id, 'Kode' => 'KAT-POMPA', 'Nama' => 'Pompa']);
        $lokasi = Lokasi::create(['OrganisasiId' => $organisasi->Id, 'Kode' => 'LOK-PMP', 'Nama' => 'Rumah Pompa', 'Status' => 'Aktif']);
        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategori->Id,
            'LokasiId' => $lokasi->Id,
            'KodeAset' => 'AST-PMP-01',
            'Nama' => 'Pompa Sentrifugal',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
        ]);
        PerintahKerjaAset::create([
            'OrganisasiId' => $organisasi->Id,
            'PerintahKerjaId' => $perintahKerja->Id,
            'AsetId' => $aset->Id,
            'Utama' => true,
        ]);
        PenugasanPerintahKerja::create([
            'OrganisasiId' => $organisasi->Id,
            'PerintahKerjaId' => $perintahKerja->Id,
            'PenggunaId' => $teknisi->Id,
            'DitugaskanOleh' => $teknisi->Id,
            'DitugaskanPada' => now(),
            'Status' => 'Ditugaskan',
        ]);

        return ['organisasi' => $organisasi, 'teknisi' => $teknisi, 'perintahKerja' => $perintahKerja->refresh()];
    }
}
