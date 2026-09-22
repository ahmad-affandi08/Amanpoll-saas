<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Application\Actions\CatatPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\SiklusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\StatusTagihanLangganan;
use App\Domain\Langganan\Domain\ValueObjects\PeristiwaPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Pemasaran\Application\Services\PenerbitKodeReferral;
use App\Domain\Pemasaran\Domain\Enums\JenisRewardReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KodeReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ProgramReferral;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/** Dasar test referral: satu program aktif dan satu pelanggan yang mengajak. */
abstract class KasusReferral extends KasusTrial
{
    protected ProgramReferral $program;

    protected Organisasi $perujuk;

    protected KodeReferral $kodePerujuk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->perujuk = Organisasi::create([
            'Kode' => 'ORG-PERUJUK-'.uniqid(),
            'Nama' => 'Pelanggan Perujuk',
            'Status' => 'Aktif',
        ]);

        $this->program = $this->buatProgram();
        $this->kodePerujuk = app(PenerbitKodeReferral::class)->untuk($this->program, $this->perujuk->Id);
    }

    protected function buatProgram(
        JenisRewardReferral $jenis = JenisRewardReferral::Perpanjangan,
        float $nilai = 30,
        int $hariKedaluwarsa = 90,
        bool $aktif = true,
    ): ProgramReferral {
        return ProgramReferral::create([
            'Kode' => 'program-'.Str::lower(Str::random(6)),
            'Nama' => 'Program Referral',
            'JenisReward' => $jenis->value,
            'NilaiReward' => $nilai,
            'HariKedaluwarsa' => $hariKedaluwarsa,
            'Aktif' => $aktif,
        ]);
    }

    /** Perujuk tenant lain, jadi langganannya dibuat di luar konteks tenant yang sedang aktif. */
    protected function buatLanggananPerujuk(): Langganan
    {
        $konteks = app(KonteksOrganisasi::class);
        $semula = $konteks->id();
        $konteks->bersihkan();

        try {
            return $this->tulisLanggananPerujuk();
        } finally {
            $konteks->tetapkan($semula);
        }
    }

    private function tulisLanggananPerujuk(): Langganan
    {
        return Langganan::create([
            'OrganisasiId' => $this->perujuk->Id,
            'PaketLanggananId' => $this->paket->Id,
            'Siklus' => SiklusLangganan::Bulanan->value,
            'MulaiPada' => CarbonImmutable::now()->toDateString(),
            'BerakhirPada' => CarbonImmutable::now()->addMonth()->toDateString(),
            'UjiCobaSampai' => CarbonImmutable::now()->addDays(14)->toDateString(),
            'Status' => StatusLangganan::UjiCoba->value,
        ]);
    }

    /** Pembayaran sungguhan lewat jalur Langganan, bukan peristiwa yang dipalsukan. */
    protected function bayarUntukOrganisasiUji(): void
    {
        $langganan = Langganan::create([
            'OrganisasiId' => $this->organisasi->Id,
            'PaketLanggananId' => $this->paket->Id,
            'Siklus' => SiklusLangganan::Bulanan->value,
            'MulaiPada' => CarbonImmutable::now()->toDateString(),
            'BerakhirPada' => CarbonImmutable::now()->addMonth()->toDateString(),
            'Status' => StatusLangganan::Aktif->value,
        ]);

        $tagihan = TagihanLangganan::create([
            'OrganisasiId' => $this->organisasi->Id,
            'LanggananId' => $langganan->Id,
            'Nomor' => 'INV-'.Str::upper(Str::random(8)),
            'PeriodeMulai' => CarbonImmutable::now()->toDateString(),
            'PeriodeSelesai' => CarbonImmutable::now()->addMonth()->toDateString(),
            'JatuhTempo' => CarbonImmutable::now()->addDays(14)->toDateString(),
            'Subtotal' => 250_000,
            'Pajak' => 0,
            'Total' => 250_000,
            'MataUang' => 'IDR',
            'Status' => StatusTagihanLangganan::BelumDibayar->value,
        ]);

        app(CatatPembayaranLangganan::class)->dariPeristiwa('TransferManual', new PeristiwaPembayaran(
            idPeristiwa: (string) Str::ulid(),
            nomorTagihan: (string) $tagihan->Nomor,
            jumlah: (float) $tagihan->Total,
            status: StatusPembayaranLangganan::Berhasil,
        ));
    }
}
