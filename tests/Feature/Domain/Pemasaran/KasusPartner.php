<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Core\Host\PetaHost;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Application\Actions\CatatPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\SiklusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\StatusTagihanLangganan;
use App\Domain\Langganan\Domain\ValueObjects\PeristiwaPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Pemasaran\Domain\Enums\JenisKomisiPartner;
use App\Domain\Pemasaran\Domain\Enums\JenisPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusPartner;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AturanKomisiPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Partner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ProgramPartner;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Support\Str;

/** Dasar test partner: satu program aktif, satu partner aktif, dan aturan komisi bawaannya. */
abstract class KasusPartner extends KasusTrial
{
    protected ProgramPartner $programPartner;

    protected Partner $partner;

    protected string $hostPartner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nyalakanFitur(KatalogFiturPlatform::PARTNER);
        $this->hostPartner = (string) app(PetaHost::class)->partner();

        $this->programPartner = $this->buatProgramPartner();
        $this->partner = $this->buatPartner($this->programPartner);
        $this->buatAturan($this->programPartner, persentase: 10);
    }

    protected function buatProgramPartner(bool $aktif = true, int $hariAtribusi = 180): ProgramPartner
    {
        return ProgramPartner::create([
            'Kode' => 'partner-'.Str::lower(Str::random(6)),
            'Nama' => 'Program Partner',
            'HariAtribusi' => $hariAtribusi,
            'Aktif' => $aktif,
        ]);
    }

    protected function buatPartner(
        ProgramPartner $program,
        StatusPartner $status = StatusPartner::Aktif,
        string $sandi = 'kata-sandi-partner',
    ): Partner {
        return Partner::create([
            'ProgramPartnerId' => $program->Id,
            'Kode' => Str::upper(Str::random(8)),
            'NamaPerusahaan' => 'Mitra '.Str::random(5),
            'Jenis' => JenisPartner::SystemIntegrator->value,
            'NamaPic' => 'Rina',
            'EmailPic' => 'rina+'.uniqid().'@mitra.test',
            'KataSandi' => $sandi,
            'Status' => $status->value,
        ]);
    }

    protected function buatAturan(
        ProgramPartner $program,
        float $persentase = 10,
        ?Partner $partner = null,
        ?int $maksPembayaran = null,
    ): AturanKomisiPartner {
        return AturanKomisiPartner::create([
            'ProgramPartnerId' => $program->Id,
            'PartnerId' => $partner?->Id,
            'Nama' => 'Komisi '.$persentase.'%',
            'Jenis' => JenisKomisiPartner::Persentase->value,
            'Nilai' => $persentase,
            'MaksPembayaran' => $maksPembayaran,
            'Aktif' => true,
        ]);
    }

    /** @return array<string, string> */
    protected function isiLead(array $ganti = []): array
    {
        return array_merge([
            'NamaPerusahaan' => 'PT Pabrik Maju',
            'NamaKontak' => 'Andi',
            'Email' => 'andi+'.uniqid().'@pabrik.test',
            'Telepon' => '08123456789',
            'Catatan' => 'Butuh modul kalibrasi.',
        ], $ganti);
    }

    /** Masuk portal lewat host partner, bukan lewat guard yang dipasang langsung. */
    protected function aktingSebagaiPartner(Partner $partner): self
    {
        $this->actingAs($partner, 'partner');

        return $this;
    }

    protected function urlPortal(string $jalur = '/'): string
    {
        return 'http://'.$this->hostPartner.$jalur;
    }

    /**
     * Pembayaran sungguhan lewat jalur Langganan, bukan peristiwa yang dipalsukan.
     *
     * @return string Id pembayaran yang lahir dari tagihan ini.
     */
    protected function bayarUntukOrganisasi(
        Organisasi $organisasi,
        float $jumlah = 250_000,
        StatusPembayaranLangganan $status = StatusPembayaranLangganan::Berhasil,
    ): string {
        $konteks = app(KonteksOrganisasi::class);
        $semula = $konteks->id();
        $konteks->tetapkan($organisasi->Id);

        try {
            return $this->tulisPembayaran($organisasi, $jumlah, $status);
        } finally {
            $konteks->tetapkan($semula);
        }
    }

    private function tulisPembayaran(
        Organisasi $organisasi,
        float $jumlah,
        StatusPembayaranLangganan $status,
    ): string {
        $langganan = Langganan::query()->where('OrganisasiId', $organisasi->Id)->first() ?? Langganan::create([
            'OrganisasiId' => $organisasi->Id,
            'PaketLanggananId' => $this->paket->Id,
            'Siklus' => SiklusLangganan::Bulanan->value,
            'MulaiPada' => now()->toDateString(),
            'BerakhirPada' => now()->addMonth()->toDateString(),
            'Status' => StatusLangganan::Aktif->value,
        ]);

        $tagihan = TagihanLangganan::create([
            'OrganisasiId' => $organisasi->Id,
            'LanggananId' => $langganan->Id,
            'Nomor' => 'INV-'.Str::upper(Str::random(8)),
            'PeriodeMulai' => now()->toDateString(),
            'PeriodeSelesai' => now()->addMonth()->toDateString(),
            'JatuhTempo' => now()->addDays(14)->toDateString(),
            'Subtotal' => $jumlah,
            'Pajak' => 0,
            'Total' => $jumlah,
            'MataUang' => 'IDR',
            'Status' => StatusTagihanLangganan::BelumDibayar->value,
        ]);

        $pembayaran = app(CatatPembayaranLangganan::class)->dariPeristiwa('TransferManual', new PeristiwaPembayaran(
            idPeristiwa: (string) Str::ulid(),
            nomorTagihan: (string) $tagihan->Nomor,
            jumlah: $jumlah,
            status: $status,
        ));

        return (string) $pembayaran->Id;
    }
}
