<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\Enums\StatusKomisiPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusPayoutPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KomisiPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Partner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PayoutPartner;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/**
 * Mengumpulkan komisi yang sudah disetujui menjadi satu payout.
 *
 * Transfernya sendiri terjadi di luar aplikasi: Billing hanya mengenal uang yang
 * masuk, dan tidak ada kanal untuk mengeluarkannya. Yang dicatat di sini adalah
 * referensi transfer yang dimasukkan admin platform, bukan mutasi Billing
 * (MARKETING.md 21).
 */
final class LayananPayoutPartner
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /** Payout lahir dari komisi yang ada, bukan dari angka yang diketik: jumlahnya dijumlahkan dari barisnya. */
    public function susun(Partner $partner): PayoutPartner
    {
        return $this->transaksi->jalankan(function () use ($partner): PayoutPartner {
            $komisi = KomisiPartner::query()
                ->where('PartnerId', $partner->Id)
                ->where('Status', StatusKomisiPartner::Disetujui->value)
                ->whereNull('PayoutPartnerId')
                ->lockForUpdate()
                ->get();

            if ($komisi->isEmpty()) {
                throw new AturanBisnisDilanggar('Tidak ada komisi disetujui yang menunggu dibayarkan.');
            }

            $payout = PayoutPartner::create([
                'PartnerId' => $partner->Id,
                'Nomor' => $this->nomorBaru(),
                'Jumlah' => round((float) $komisi->sum(fn (KomisiPartner $satu): float => (float) $satu->Jumlah), 2),
                'JumlahKomisi' => $komisi->count(),
                'Status' => StatusPayoutPartner::Draf,
            ]);

            KomisiPartner::query()
                ->whereIn('Id', $komisi->pluck('Id')->all())
                ->update(['PayoutPartnerId' => $payout->Id]);

            $this->audit->catat('PayoutPartner.Disusun', 'PayoutPartner', $payout->Id, dataSesudah: [
                'PartnerId' => $partner->Id,
                'Nomor' => $payout->Nomor,
                'Jumlah' => (float) $payout->Jumlah,
                'JumlahKomisi' => $payout->JumlahKomisi,
            ]);

            return $payout;
        });
    }

    /** Referensi transfer wajib diisi: tanpa itu tidak ada bukti uangnya benar-benar berpindah. */
    public function tandaiDibayar(PayoutPartner $payout, string $referensi, ?string $catatan = null): PayoutPartner
    {
        if ($payout->Status->final()) {
            throw new AturanBisnisDilanggar('Payout ini sudah ditandai dibayar.');
        }

        return $this->transaksi->jalankan(function () use ($payout, $referensi, $catatan): PayoutPartner {
            $payout->Status = StatusPayoutPartner::Dibayar;
            $payout->ReferensiPembayaran = mb_substr($referensi, 0, 190);
            $payout->Catatan = $catatan === null ? null : mb_substr($catatan, 0, 500);
            $payout->DibayarPada = CarbonImmutable::now();
            $payout->save();

            KomisiPartner::query()
                ->where('PayoutPartnerId', $payout->Id)
                ->update([
                    'Status' => StatusKomisiPartner::Dibayar->value,
                    'DibayarPada' => $payout->DibayarPada,
                ]);

            $this->audit->catat('PayoutPartner.Dibayar', 'PayoutPartner', $payout->Id, dataSesudah: [
                'PartnerId' => $payout->PartnerId,
                'Nomor' => $payout->Nomor,
                'ReferensiPembayaran' => $payout->ReferensiPembayaran,
            ]);

            return $payout;
        });
    }

    /** Komisinya dilepas kembali ke antrean supaya tidak ikut mati bersama payout yang batal. */
    public function batalkan(PayoutPartner $payout, string $alasan): PayoutPartner
    {
        if ($payout->Status->final()) {
            throw new AturanBisnisDilanggar('Payout yang sudah dibayar tidak dapat dibatalkan.');
        }

        return $this->transaksi->jalankan(function () use ($payout, $alasan): PayoutPartner {
            KomisiPartner::query()
                ->where('PayoutPartnerId', $payout->Id)
                ->update(['PayoutPartnerId' => null]);

            $payout->Status = StatusPayoutPartner::Gagal;
            $payout->Catatan = mb_substr($alasan, 0, 500);
            $payout->JumlahKomisi = 0;
            $payout->save();

            $this->audit->catat('PayoutPartner.Dibatalkan', 'PayoutPartner', $payout->Id, dataSesudah: [
                'PartnerId' => $payout->PartnerId,
                'Alasan' => $payout->Catatan,
            ]);

            return $payout;
        });
    }

    private function nomorBaru(): string
    {
        $awalan = 'PYT-'.CarbonImmutable::now()->format('Ymd').'-';
        $urut = PayoutPartner::query()->where('Nomor', 'like', $awalan.'%')->count() + 1;

        return $awalan.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }
}
