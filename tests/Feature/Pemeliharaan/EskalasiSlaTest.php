<?php

declare(strict_types=1);

namespace Tests\Feature\Pemeliharaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\EskalasiTingkatLayanan;
use App\Domain\Pemeliharaan\Application\Services\LayananEskalasiSla;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Pemeliharaan\Jobs\ProsesEskalasiKeluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class EskalasiSlaTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_perintah_mengantrekan_pemeriksaan_per_organisasi_aktif(): void
    {
        Queue::fake();
        $aktif = Organisasi::create(['Kode' => 'ORG-AKTIF', 'Nama' => 'Aktif', 'Status' => 'Aktif']);
        Organisasi::create(['Kode' => 'ORG-NONAKTIF', 'Nama' => 'Nonaktif', 'Status' => 'Nonaktif']);

        $this->artisan('keluhan:proses-eskalasi-sla')->assertSuccessful();

        Queue::assertPushed(
            ProsesEskalasiKeluhan::class,
            fn (ProsesEskalasiKeluhan $job): bool => $job->OrganisasiId === $aktif->Id,
        );
        Queue::assertPushed(ProsesEskalasiKeluhan::class, 1);
    }

    public function test_due_soon_dan_breach_dikirim_melalui_queue_tanpa_duplikasi(): void
    {
        Queue::fake();
        $organisasi = Organisasi::create(['Kode' => 'ORG-ESK', 'Nama' => 'Organisasi Eskalasi']);
        $penerima = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Penerima Eskalasi',
            'Email' => 'eskalasi@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $lokasi = Lokasi::create(['Kode' => 'LOK-ESK', 'Nama' => 'Lokasi', 'Status' => 'Aktif']);
        $tingkatLayanan = TingkatLayanan::create(['Kode' => 'SLA-ESK', 'Nama' => 'SLA Eskalasi']);
        $kategori = KategoriKeluhan::create([
            'Kode' => 'KAT-ESK',
            'Nama' => 'Kategori Eskalasi',
            'TingkatLayananId' => $tingkatLayanan->Id,
        ]);
        EskalasiTingkatLayanan::create([
            'TingkatLayananId' => $tingkatLayanan->Id,
            'Tahap' => 1,
            'Pemicu' => EskalasiTingkatLayanan::PEMICU_MENJELANG,
            'SetelahMenit' => 60,
            'PenggunaId' => $penerima->Id,
            'Kanal' => ['InApp'],
            'Aktif' => true,
        ]);
        EskalasiTingkatLayanan::create([
            'TingkatLayananId' => $tingkatLayanan->Id,
            'Tahap' => 2,
            'Pemicu' => EskalasiTingkatLayanan::PEMICU_TERLEWATI,
            'SetelahMenit' => 0,
            'PenggunaId' => $penerima->Id,
            'Kanal' => ['InApp'],
            'Aktif' => true,
        ]);
        $batas = CarbonImmutable::parse('2026-09-20 12:00:00', 'Asia/Jakarta');
        Keluhan::create([
            'Nomor' => 'KLH-001',
            'KategoriKeluhanId' => $kategori->Id,
            'TingkatLayananId' => $tingkatLayanan->Id,
            'LokasiId' => $lokasi->Id,
            'Judul' => 'Keluhan dengan SLA',
            'Deskripsi' => 'Menunggu penyelesaian.',
            'Prioritas' => 'Tinggi',
            'Status' => 'Diproses',
            'PelaporId' => $penerima->Id,
            'DilaporkanPada' => $batas->subHours(2),
            'DiresponsPada' => $batas->subHour(),
            'BatasPenyelesaianPada' => $batas,
        ]);

        CarbonImmutable::setTestNow($batas->subMinutes(30));
        $this->assertSame(1, app(LayananEskalasiSla::class)->proses());
        $this->assertSame(0, app(LayananEskalasiSla::class)->proses());
        $this->assertDatabaseHas('Notifikasi', [
            'PenggunaId' => $penerima->Id,
            'JenisPeristiwa' => 'Keluhan.Sla.Mendekati',
            'JenisEntitas' => 'Keluhan',
        ]);

        CarbonImmutable::setTestNow($batas->addMinute());
        $this->assertSame(1, app(LayananEskalasiSla::class)->proses());
        $this->assertSame(0, app(LayananEskalasiSla::class)->proses());
        $this->assertDatabaseHas('Notifikasi', [
            'PenggunaId' => $penerima->Id,
            'JenisPeristiwa' => 'Keluhan.Sla.Terlewati',
            'JenisEntitas' => 'Keluhan',
        ]);
        $this->assertDatabaseCount('Notifikasi', 2);
    }
}
