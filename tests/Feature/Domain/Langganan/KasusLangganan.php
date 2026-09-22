<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Langganan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Domain\Langganan\Domain\Enums\SiklusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\FiturPaket;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Carbon\CarbonImmutable;
use Database\Seeders\FiturPaketSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Dasar bersama tes domain Langganan (22.01–22.06). */
abstract class KasusLangganan extends TestCase
{
    use DatabaseTransactions;

    protected Organisasi $organisasi;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-06-15 09:00:00');
        $this->seed(FiturPaketSeeder::class);

        $this->organisasi = Organisasi::create([
            'Kode' => 'ORG-LNG-'.uniqid(),
            'Nama' => 'Organisasi Langganan',
            'Status' => 'Aktif',
        ]);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
    }

    /** Pola nomor dokumen adalah data pengaturan tenant, bukan bagian dari langganan. */
    protected function siapkanPolaNomor(string $jenisDokumen, string $awalan): void
    {
        DB::table('NomorDokumen')->insert([
            'Id' => (string) Str::ulid(),
            'OrganisasiId' => $this->organisasi->Id,
            'JenisDokumen' => $jenisDokumen,
            'Awalan' => $awalan,
            'FormatNomor' => '{Awalan}-{Nomor:5}',
            'NomorTerakhir' => 0,
            'ResetPeriode' => 'TidakAda',
            'DibuatPada' => now(),
            'DiperbaruiPada' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /**
     * Konteks organisasi dilepas oleh middleware setelah tiap permintaan HTTP,
     * jadi ditetapkan ulang agar asersi berikutnya tetap melihat data tenant
     * yang sama. Cache entitlement juga dibersihkan karena tes sering mengubah
     * langganan di antara dua permintaan.
     *
     * {@inheritDoc}
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $respons = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        return $respons;
    }

    /**
     * @param  array<string, array{Diizinkan?: bool, BatasNilai?: float|null}>  $fitur
     */
    protected function buatPaket(string $nama, array $fitur = []): PaketLangganan
    {
        $paket = PaketLangganan::create([
            'Kode' => 'PKT-'.uniqid(),
            'Nama' => $nama,
            'HargaBulanan' => 500_000,
            'HargaTahunan' => 5_000_000,
            'MataUang' => 'IDR',
            'Aktif' => true,
        ]);

        foreach ($fitur as $kode => $nilai) {
            $master = FiturPaket::query()->where('Kode', $kode)->firstOrFail();
            PaketFitur::create([
                'PaketLanggananId' => $paket->Id,
                'FiturPaketId' => $master->Id,
                'Diizinkan' => $nilai['Diizinkan'] ?? true,
                'BatasNilai' => $nilai['BatasNilai'] ?? null,
            ]);
        }

        return $paket;
    }

    /** Paket yang membuka semua modul tanpa batas jumlah. */
    protected function buatPaketLengkap(): PaketLangganan
    {
        $fitur = [];
        foreach (KatalogFitur::kode() as $kode) {
            $fitur[$kode] = ['Diizinkan' => true, 'BatasNilai' => null];
        }

        return $this->buatPaket('Paket Lengkap', $fitur);
    }

    protected function buatLangganan(
        PaketLangganan $paket,
        StatusLangganan $status = StatusLangganan::Aktif,
        ?string $berakhirPada = null,
        ?string $ujiCobaSampai = null,
    ): Langganan {
        $langganan = Langganan::create([
            'OrganisasiId' => $this->organisasi->Id,
            'PaketLanggananId' => $paket->Id,
            'Siklus' => SiklusLangganan::Bulanan->value,
            'MulaiPada' => CarbonImmutable::now()->subMonth()->toDateString(),
            'BerakhirPada' => $berakhirPada ?? CarbonImmutable::now()->addMonth()->toDateString(),
            'UjiCobaSampai' => $ujiCobaSampai,
            'Status' => $status->value,
        ]);

        app(PemeriksaEntitlement::class)->bersihkanCache((string) $this->organisasi->Id);

        return $langganan;
    }

    /** @param list<string> $izin */
    protected function buatPengguna(array $izin = []): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($izin === []) {
            return $pengguna;
        }

        $peran = Peran::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'PERAN-'.uniqid(),
            'Nama' => 'Peran Langganan',
        ]);

        foreach ($izin as $kode) {
            $modelIzin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Langganan']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
        }

        PenggunaPeran::create([
            'OrganisasiId' => $this->organisasi->Id,
            'PenggunaId' => $pengguna->Id,
            'PeranId' => $peran->Id,
        ]);

        return $pengguna;
    }

    /** Permintaan ke konsol platform berjalan tanpa konteks organisasi, persis seperti di produksi. */
    protected function sebagaiAdminPlatform(?AdminPlatform $admin = null): static
    {
        app(KonteksOrganisasi::class)->bersihkan();

        return $this->actingAs($admin ?? $this->buatAdminPlatform(), 'platform');
    }

    protected function buatAdminPlatform(): AdminPlatform
    {
        return AdminPlatform::create([
            'Nama' => 'Admin Platform',
            'Email' => uniqid().'@platform.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
    }

    /** Memundurkan tanggal pembuatan organisasi sehingga uji coba awalnya sudah lewat. */
    protected function mundurkanPembuatanOrganisasi(int $hari): void
    {
        DB::table('Organisasi')
            ->where('Id', $this->organisasi->Id)
            ->update(['DibuatPada' => CarbonImmutable::now()->subDays($hari)]);

        $this->segarkanEntitlement();
    }

    protected function segarkanEntitlement(): void
    {
        app(PemeriksaEntitlement::class)->bersihkanCache((string) $this->organisasi->Id);
    }
}
