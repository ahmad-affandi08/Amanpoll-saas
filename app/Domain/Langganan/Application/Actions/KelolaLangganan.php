<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Langganan\Application\Services\LayananKebijakanTenggang;
use App\Domain\Langganan\Application\Services\LayananLangganan;
use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Domain\Langganan\Domain\Enums\SiklusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/**
 * Siklus hidup langganan satu organisasi (22.04).
 *
 * Satu organisasi hanya memiliki satu baris langganan yang berlaku; pergantian
 * paket mengubah baris itu, bukan membuat baris kedua. Itu disengaja: dua baris
 * aktif berarti dua jawaban entitlement untuk tenant yang sama, dan yang mana
 * yang menang akan bergantung pada urutan query. Sesuai PRD, pergantian paket
 * tidak pernah menyentuh data tenant — hanya haknya yang berubah.
 */
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

            $langganan->fill([
                'OrganisasiId' => $organisasiId,
                'PaketLanggananId' => $paket->Id,
                'Siklus' => $siklus->value,
                'MulaiPada' => $mulai->toDateString(),
                'BerakhirPada' => $berakhir?->toDateString(),
                'UjiCobaSampai' => $ujiCoba?->toDateString(),
                'Status' => $ujiCoba !== null ? StatusLangganan::UjiCoba->value : StatusLangganan::Aktif->value,
            ]);
            // Memulai ulang membatalkan pembatalan sebelumnya; menyisakan
            // BatalPada akan membuat langganan hidup tetapi tercatat batal.
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

            return $langganan->refresh();
        });
    }

    /**
     * Memperpanjang satu periode. Titik tolaknya adalah tanggal berakhir yang
     * ada bila masih di depan, supaya pembayaran lebih awal menambah waktu
     * alih-alih membuang sisa periode yang sudah dibayar.
     */
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
            // Perpanjangan mengakhiri uji coba: periode yang dibayar bukan lagi
            // percobaan.
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
     * Pembatalan bawaannya berlaku di akhir periode: pelanggan sudah membayar
     * sampai tanggal itu, jadi mencabutnya seketika akan mengambil kembali
     * sesuatu yang sudah dibayar. Pembatalan segera disediakan terpisah untuk
     * kasus pelanggaran ketentuan.
     */
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

            return $langganan->refresh();
        });
    }

    /**
     * Menyelaraskan kolom Status dengan status efektif hari ini. Dipakai oleh
     * perintah harian; nilai yang dibaca aplikasi tetap dihitung ulang, jadi
     * ini demi laporan dan notifikasi, bukan demi penegakan.
     */
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

        // Periode berbayar dihitung dari akhir uji coba, bukan dari tanggal
        // mulai, supaya uji coba tidak diam-diam memakan periode berbayar.
        return $siklus->akhirPeriodeSetelah($ujiCoba ?? $mulai);
    }
}
