<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\StatusJadwalSosial;
use App\Domain\Pemasaran\Domain\Enums\StatusKontenSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DistribusiKontenSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\JadwalKontenSosial;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Menjadwalkan, menjadwalkan ulang, dan membatalkan penerbitan satu distribusi (MARKETING.md 18). */
final class PenjadwalKontenSosial
{
    public function __construct(private readonly TransaksiDatabase $transaksi) {}

    public function jadwalkan(DistribusiKontenSosial $distribusi, CarbonImmutable $pada): JadwalKontenSosial
    {
        $this->pastikanSiapDijadwalkan($distribusi, $pada);

        return $this->transaksi->jalankan(function () use ($distribusi, $pada): JadwalKontenSosial {
            // Rencana lama dibatalkan lebih dulu; dua rencana menunggu berarti dua posting.
            $this->batalkanYangMenunggu($distribusi, 'Digantikan jadwal baru.');

            $jadwal = JadwalKontenSosial::query()->updateOrCreate(
                ['DistribusiKontenSosialId' => $distribusi->Id, 'JadwalPada' => $pada],
                ['Status' => StatusJadwalSosial::Menunggu, 'DijalankanPada' => null, 'Catatan' => null],
            );

            $this->pindahkan($distribusi, StatusKontenSosial::Terjadwal);

            return $jadwal;
        });
    }

    /** Membatalkan jadwal mengembalikan distribusinya ke draf, bukan meninggalkannya menggantung. */
    public function batalkan(DistribusiKontenSosial $distribusi, string $alasan = 'Dibatalkan operator.'): void
    {
        $this->transaksi->jalankan(function () use ($distribusi, $alasan): void {
            $this->batalkanYangMenunggu($distribusi, $alasan);

            if ($distribusi->Status === StatusKontenSosial::Terjadwal) {
                $this->pindahkan($distribusi, StatusKontenSosial::Draf);
            }
        });
    }

    /** Distribusi hanya boleh berpindah menurut peta transisinya, dari mana pun perintahnya datang. */
    public function pindahkan(DistribusiKontenSosial $distribusi, StatusKontenSosial $tujuan): void
    {
        $asal = $distribusi->Status;

        if ($asal === $tujuan) {
            return;
        }

        if (! $asal->bolehPindahKe($tujuan)) {
            $sah = implode(', ', array_map(
                fn (StatusKontenSosial $satu): string => $satu->value,
                $asal->tujuanSah(),
            )) ?: 'tidak ke mana-mana';

            throw new AturanBisnisDilanggar(
                "Distribusi berstatus {$asal->value} hanya dapat berpindah ke {$sah}.",
            );
        }

        $distribusi->Status = $tujuan;
        $distribusi->save();
    }

    /** @return list<JadwalKontenSosial> */
    public function jatuhTempo(?CarbonImmutable $pada = null, int $batas = 200): array
    {
        $baris = JadwalKontenSosial::query()
            ->where('Status', StatusJadwalSosial::Menunggu->value)
            ->where('JadwalPada', '<=', $pada ?? CarbonImmutable::now())
            ->orderBy('JadwalPada')
            ->limit($batas)
            ->get()
            ->all();

        return array_values($baris);
    }

    private function pastikanSiapDijadwalkan(DistribusiKontenSosial $distribusi, CarbonImmutable $pada): void
    {
        if ($distribusi->Channel->wajibMedia() && ($distribusi->MediaUrl ?? '') === '') {
            throw new AturanBisnisDilanggar(
                "Channel {$distribusi->Channel->value} menuntut media; caption saja akan ditolak penyedianya.",
            );
        }

        if ($pada->lessThan(CarbonImmutable::now()->subMinute())) {
            throw new AturanBisnisDilanggar('Jadwal penerbitan tidak dapat diletakkan di masa lalu.');
        }
    }

    private function batalkanYangMenunggu(DistribusiKontenSosial $distribusi, string $alasan): void
    {
        JadwalKontenSosial::query()
            ->where('DistribusiKontenSosialId', $distribusi->Id)
            ->where('Status', StatusJadwalSosial::Menunggu->value)
            ->update(['Status' => StatusJadwalSosial::Dibatalkan->value, 'Catatan' => $alasan]);
    }
}
