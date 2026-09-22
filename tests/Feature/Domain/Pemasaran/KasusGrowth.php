<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Domain\Enums\SiklusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\StatusTagihanLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PembayaranLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AttributionPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiPengunjung;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/** Dasar test growth: satu perjalanan lengkap dan dua yang berhenti di tengah, ditulis langsung ke tabelnya. */
abstract class KasusGrowth extends KasusPemasaran
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-06-15 09:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /** Seluruh perjalanan terjadi dalam satu hari, supaya metrik harian dapat diuji utuh. */
    public const HARI_PERJALANAN = 2;

    /** Satu pengunjung iklan berjalan sampai bayar, dua pengunjung organik berhenti lebih awal. */
    protected function semaiPerjalanan(): void
    {
        $hari = $this->hariPerjalanan();

        $sampaiBayar = $this->buatPengunjung('iklan', $hari, 'Desktop', '/demo');
        $sampaiLead = $this->buatPengunjung('organik', $hari, 'Ponsel', '/harga');
        $this->buatPengunjung('organik', $hari, 'Desktop', '/blog');

        $prospekBayar = $this->buatProspekUntuk($sampaiBayar, 'bayar@pabrik.test', skor: 60);
        $this->buatProspekUntuk($sampaiLead, 'lead@pabrik.test', skor: 10);

        $this->catatDemo($sampaiBayar);

        $organisasi = $this->buatOrganisasi();
        $this->buatTrial($organisasi, $prospekBayar, $sampaiBayar, teraktivasi: true, konversi: true);
        $this->bayar($organisasi);

        // Trial kedua berhenti sebelum aktivasi; tanpa ini tiap tahap bernilai sama dan penyalinan angka tidak ketahuan.
        $this->buatTrial($this->buatOrganisasi(), null, $sampaiLead);
    }

    protected function hariPerjalanan(): CarbonImmutable
    {
        return CarbonImmutable::now()->subDays(self::HARI_PERJALANAN)->setTime(9, 0);
    }

    protected function buatPengunjung(
        string $channel,
        ?CarbonImmutable $pada = null,
        string $perangkat = 'Desktop',
        string $landing = '/',
        ?string $kampanyeId = null,
    ): string {
        $pengenal = (string) Str::ulid();
        $waktu = $pada ?? $this->hariPerjalanan();

        SesiPengunjung::create([
            'PengenalPengunjung' => $pengenal,
            'Perangkat' => $perangkat,
            'LandingUrl' => $landing,
            'Host' => 'publik.test',
            'DimulaiPada' => $waktu,
            'TerakhirAktifPada' => $waktu,
        ]);

        AttributionPemasaran::create([
            'PengenalPengunjung' => $pengenal,
            'SumberPertama' => $channel,
            'MediumPertama' => 'cpc',
            'LandingPertama' => $landing,
            'SentuhanPertamaPada' => $waktu,
            'KampanyeIdPertama' => $kampanyeId,
            'SumberTerakhir' => $channel,
            'MediumTerakhir' => 'cpc',
            'LandingTerakhir' => $landing,
            'SentuhanTerakhirPada' => $waktu,
        ]);

        return $pengenal;
    }

    protected function buatProspekUntuk(string $pengenal, string $email, int $skor = 0): Prospek
    {
        return Prospek::create([
            'PengenalPengunjung' => $pengenal,
            'Nama' => 'Prospek '.Str::random(4),
            'Email' => $email,
            'Sumber' => SumberProspek::Website->value,
            'Skor' => $skor,
            'AktivitasTerakhirPada' => $this->hariPerjalanan(),
        ]);
    }

    protected function catatDemo(string $pengenal): void
    {
        EventPemasaran::create([
            'PengenalPengunjung' => $pengenal,
            'Jenis' => KatalogPeristiwaPemasaran::DEMO_DIMULAI,
            'TerjadiPada' => $this->hariPerjalanan()->addHour(),
        ]);
    }

    protected function buatOrganisasi(): Organisasi
    {
        $organisasi = Organisasi::create([
            'Kode' => 'ORG-GROWTH-'.uniqid(),
            'Nama' => 'Organisasi Growth',
            'Status' => 'Aktif',
        ]);

        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        return $organisasi;
    }

    protected function buatTrial(
        Organisasi $organisasi,
        ?Prospek $prospek,
        ?string $pengenal,
        bool $teraktivasi = false,
        bool $konversi = false,
    ): Trial {
        $mulai = $this->hariPerjalanan()->addHours(2);

        return Trial::create([
            'OrganisasiId' => $organisasi->Id,
            'ProspekId' => $prospek?->Id,
            'PengenalPengunjung' => $pengenal,
            'Status' => $konversi ? StatusTrial::Konversi->value : StatusTrial::Setup->value,
            'MulaiPada' => $mulai,
            'BerakhirPada' => $mulai->addDays(14),
            'TeraktivasiPada' => $teraktivasi ? $mulai->addHour() : null,
            'KonversiPada' => $konversi ? $mulai->addHours(2) : null,
            'HariPerpanjangan' => 0,
        ]);
    }

    protected function bayar(Organisasi $organisasi, float $jumlah = 250_000): void
    {
        $dibayar = $this->hariPerjalanan()->addHours(4);

        $paket = PaketLangganan::create([
            'Kode' => 'PKT-'.uniqid(),
            'Nama' => 'Paket Growth',
            'HargaBulanan' => $jumlah,
            'HargaTahunan' => $jumlah * 10,
            'MataUang' => 'IDR',
            'Aktif' => true,
        ]);

        $langganan = Langganan::create([
            'OrganisasiId' => $organisasi->Id,
            'PaketLanggananId' => $paket->Id,
            'Siklus' => SiklusLangganan::Bulanan->value,
            'MulaiPada' => $dibayar->toDateString(),
            'BerakhirPada' => $dibayar->addMonth()->toDateString(),
            'Status' => StatusLangganan::Aktif->value,
        ]);

        $tagihan = TagihanLangganan::create([
            'OrganisasiId' => $organisasi->Id,
            'LanggananId' => $langganan->Id,
            'Nomor' => 'INV-'.Str::upper(Str::random(8)),
            'PeriodeMulai' => $dibayar->toDateString(),
            'PeriodeSelesai' => $dibayar->addMonth()->toDateString(),
            'JatuhTempo' => $dibayar->addDays(14)->toDateString(),
            'Subtotal' => $jumlah,
            'Pajak' => 0,
            'Total' => $jumlah,
            'MataUang' => 'IDR',
            'Status' => StatusTagihanLangganan::Lunas->value,
        ]);

        PembayaranLangganan::create([
            'OrganisasiId' => $organisasi->Id,
            'TagihanLanggananId' => $tagihan->Id,
            'PenyediaPembayaran' => 'TransferManual',
            'IdPeristiwaPenyedia' => (string) Str::ulid(),
            'Metode' => 'Transfer',
            'Jumlah' => $jumlah,
            'Status' => StatusPembayaranLangganan::Berhasil->value,
            'DibayarPada' => $dibayar,
        ]);
    }
}
