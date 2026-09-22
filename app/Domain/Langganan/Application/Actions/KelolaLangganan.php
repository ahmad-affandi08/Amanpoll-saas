<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Langganan\Application\Services\LayananKebijakanTenggang;
use App\Domain\Langganan\Application\Services\LayananLangganan;
use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Domain\Langganan\Domain\Enums\SiklusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Domain\Events\PeristiwaLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

/** Siklus hidup langganan satu organisasi (22.04). */
final class KelolaLangganan
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
        private readonly LayananLangganan $layananLangganan,
        private readonly LayananKebijakanTenggang $kebijakan,
        private readonly PemeriksaEntitlement $entitlement,
    ) {}

    /**
     * Memulai langganan untuk organisasi yang belum punya, atau mengganti paket
     * organisasi yang sudah punya.
     *
     * @param  array<string, mixed>  $data
     */
    public function mulai(string $organisasiId, array $data): Langganan
    {
        return $this->transaksi->jalankan(function () use ($organisasiId, $data): Langganan {
            $paket = PaketLangganan::query()->findOrFail((string) $data['PaketLanggananId']);
            if (! $paket->Aktif) {
                throw new AturanBisnisDilanggar('Paket yang dipilih sedang nonaktif dan tidak dapat dilanggan.');
            }

            $siklus = SiklusLangganan::from((string) ($data['Siklus'] ?? SiklusLangganan::Bulanan->value));
            $mulai = isset($data['MulaiPada'])
                ? CarbonImmutable::parse((string) $data['MulaiPada'])->startOfDay()
                : CarbonImmutable::now()->startOfDay();

            $ujiCoba = $this->tanggalUjiCoba($data, $mulai);
            $berakhir = $this->tanggalBerakhir($data, $siklus, $mulai, $ujiCoba);

            $langganan = $this->layananLangganan->untukOrganisasi($organisasiId) ?? new Langganan;
            $baru = ! $langganan->exists;
            $paketSebelum = $baru ? null : $langganan->paketLangganan;

            $langganan->fill([
                'OrganisasiId' => $organisasiId,
                'PaketLanggananId' => $paket->Id,
                'Siklus' => $siklus->value,
                'MulaiPada' => $mulai->toDateString(),
                'BerakhirPada' => $berakhir?->toDateString(),
                'UjiCobaSampai' => $ujiCoba?->toDateString(),
                'Status' => $ujiCoba !== null ? StatusLangganan::UjiCoba->value : StatusLangganan::Aktif->value,
            ]);
            // Memulai ulang membatalkan pembatalan sebelumnya.
            $langganan->BatalPada = null;
            $langganan->save();

            $this->audit->catat(
                $baru ? 'Langganan.Dimulai' : 'Langganan.PaketDiganti',
                'Langganan',
                $langganan->Id,
                dataSesudah: [
                    'OrganisasiId' => $organisasiId,
                    'PaketLanggananId' => $paket->Id,
                    'Siklus' => $siklus->value,
                    'BerakhirPada' => $berakhir?->toDateString(),
                ],
            );

            $this->entitlement->bersihkanCache($organisasiId);

            Event::dispatch(new PeristiwaLangganan(
                $baru ? PeristiwaLangganan::LANGGANAN_DIBUAT : $this->kodePerubahanPaket($paketSebelum, $paket),
                $organisasiId,
                $langganan->Id,
                ['PaketLanggananId' => $paket->Id, 'Siklus' => $siklus->value],
            ));

            return $langganan->refresh();
        });
    }

    /** Naik atau turun paket dibedakan dari harga bulanannya. */
    private function kodePerubahanPaket(?PaketLangganan $sebelum, PaketLangganan $sesudah): string
    {
        $lama = (float) ($sebelum->HargaBulanan ?? 0);

        return (float) $sesudah->HargaBulanan >= $lama
            ? PeristiwaLangganan::UPGRADE_DILAKUKAN
            : PeristiwaLangganan::DOWNGRADE_DILAKUKAN;
    }

    /** Memperpanjang satu periode. */
    public function perpanjang(Langganan $langganan, ?CarbonImmutable $pada = null): Langganan
    {
        return $this->transaksi->jalankan(function () use ($langganan, $pada): Langganan {
            $pada ??= CarbonImmutable::now();
            $siklus = SiklusLangganan::from((string) $langganan->Siklus);

            $berakhir = $langganan->BerakhirPada === null
                ? null
                : CarbonImmutable::parse($langganan->BerakhirPada)->startOfDay();

            $titikTolak = $berakhir !== null && $berakhir->greaterThan($pada->startOfDay())
                ? $berakhir
                : $pada->startOfDay();

            $langganan->BerakhirPada = $siklus->akhirPeriodeSetelah($titikTolak);
            $langganan->Status = StatusLangganan::Aktif->value;
            // Perpanjangan mengakhiri uji coba: periode yang dibayar bukan lagi percobaan.
            $langganan->UjiCobaSampai = null;
            $langganan->save();

            $this->audit->catat('Langganan.Diperpanjang', 'Langganan', $langganan->Id, dataSesudah: [
                'BerakhirPada' => $langganan->BerakhirPada?->toDateString(),
            ]);

            $this->entitlement->bersihkanCache((string) $langganan->OrganisasiId);

            return $langganan->refresh();
        });
    }

    /**
     * Memperpanjang masa uji coba, bukan periode berbayar.
     *
     * Dipisah dari perpanjang(): yang itu mengakhiri uji coba karena periodenya
     * sudah dibayar, sedangkan ini justru menundanya.
     */
    public function perpanjangUjiCoba(Langganan $langganan, int $hari): Langganan
    {
        if ($hari < 1) {
            throw new AturanBisnisDilanggar('Perpanjangan uji coba minimal satu hari.');
        }

        return $this->transaksi->jalankan(function () use ($langganan, $hari): Langganan {
            $sebelum = $langganan->UjiCobaSampai;
            $titikTolak = $sebelum === null
                ? CarbonImmutable::now()->startOfDay()
                : CarbonImmutable::parse($sebelum)->startOfDay();

            // Uji coba yang sudah lewat diperpanjang dari hari ini, bukan dari tanggal mati.
            if ($titikTolak->lessThan(CarbonImmutable::now()->startOfDay())) {
                $titikTolak = CarbonImmutable::now()->startOfDay();
            }

            $langganan->UjiCobaSampai = $titikTolak->addDays($hari);
            $langganan->Status = StatusLangganan::UjiCoba->value;
            $langganan->save();

            $this->audit->catat(
                'Langganan.UjiCobaDiperpanjang',
                'Langganan',
                $langganan->Id,
                dataSebelum: ['UjiCobaSampai' => $sebelum?->toDateString()],
                dataSesudah: ['UjiCobaSampai' => $langganan->UjiCobaSampai?->toDateString(), 'Hari' => $hari],
            );

            $this->entitlement->bersihkanCache((string) $langganan->OrganisasiId);

            return $langganan->refresh();
        });
    }

    /** Pembatalan bawaannya berlaku di akhir periode. */
    public function batalkan(Langganan $langganan, bool $segera = false): Langganan
    {
        return $this->transaksi->jalankan(function () use ($langganan, $segera): Langganan {
            $sekarang = CarbonImmutable::now();

            $langganan->BatalPada = $sekarang;

            if ($segera) {
                $langganan->Status = StatusLangganan::Dibatalkan->value;
                $langganan->BerakhirPada = $sekarang;
                $langganan->UjiCobaSampai = null;
            }

            $langganan->save();

            $this->audit->catat('Langganan.Dibatalkan', 'Langganan', $langganan->Id, dataSesudah: [
                'Segera' => $segera,
                'BerakhirPada' => $langganan->BerakhirPada?->toDateString(),
            ]);

            $this->entitlement->bersihkanCache((string) $langganan->OrganisasiId);

            Event::dispatch(new PeristiwaLangganan(
                PeristiwaLangganan::LANGGANAN_DIBATALKAN,
                (string) $langganan->OrganisasiId,
                $langganan->Id,
                ['Segera' => $segera],
            ));

            return $langganan->refresh();
        });
    }

    /** Menyelaraskan kolom Status dengan status efektif hari ini. */
    public function segarkanStatus(Langganan $langganan, ?CarbonImmutable $pada = null): ?StatusLangganan
    {
        $efektif = $this->layananLangganan->statusEfektif($langganan, $pada);
        if ((string) $langganan->Status === $efektif->value) {
            return null;
        }

        $sebelum = (string) $langganan->Status;
        $langganan->Status = $efektif->value;
        $langganan->save();

        $this->audit->catat('Langganan.StatusBerubah', 'Langganan', $langganan->Id,
            dataSebelum: ['Status' => $sebelum],
            dataSesudah: ['Status' => $efektif->value],
        );

        $this->entitlement->bersihkanCache((string) $langganan->OrganisasiId);

        return $efektif;
    }

    /** @param array<string, mixed> $data */
    private function tanggalUjiCoba(array $data, CarbonImmutable $mulai): ?CarbonImmutable
    {
        if (array_key_exists('UjiCobaSampai', $data) && $data['UjiCobaSampai'] !== null) {
            $sampai = CarbonImmutable::parse((string) $data['UjiCobaSampai'])->startOfDay();
            if ($sampai->lessThan($mulai)) {
                throw new AturanBisnisDilanggar('Akhir uji coba tidak boleh mendahului tanggal mulai langganan.');
            }

            return $sampai;
        }

        if (! (bool) ($data['DenganUjiCoba'] ?? false)) {
            return null;
        }

        $hari = $this->kebijakan->hariUjiCoba();

        return $hari > 0 ? $mulai->addDays($hari) : null;
    }

    /** @param array<string, mixed> $data */
    private function tanggalBerakhir(
        array $data,
        SiklusLangganan $siklus,
        CarbonImmutable $mulai,
        ?CarbonImmutable $ujiCoba,
    ): ?CarbonImmutable {
        if (array_key_exists('BerakhirPada', $data)) {
            return $data['BerakhirPada'] === null
                ? null
                : CarbonImmutable::parse((string) $data['BerakhirPada'])->startOfDay();
        }

        // Periode berbayar dihitung dari akhir uji coba, bukan dari tanggal mulai.
        return $siklus->akhirPeriodeSetelah($ujiCoba ?? $mulai);
    }
}
