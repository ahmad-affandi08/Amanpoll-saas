<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Application\Services\PenjadwalEmailPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusPendaftaranSequence;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanEmail;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LangkahSequenceEmail;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PendaftaranSequence;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SequenceEmailPemasaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Mendaftarkan prospek ke sequence; seluruh langkah dijadwalkan di muka agar kunci idempotensinya ada sejak awal (MARKETING.md 15). */
final class DaftarkanKeSequence
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PenjadwalEmailPemasaran $penjadwal,
        private readonly LayananKonsen $konsen,
    ) {}

    public function jalankan(SequenceEmailPemasaran $sequence, Prospek $prospek): PendaftaranSequence
    {
        if (! $sequence->Aktif) {
            throw new AturanBisnisDilanggar('Sequence ini sedang nonaktif.');
        }

        $email = (string) $prospek->Email;

        if ($email === '') {
            throw new AturanBisnisDilanggar('Prospek tanpa alamat email tidak dapat didaftarkan.');
        }

        if (! $this->konsen->bolehDikirimi($email)) {
            throw new AturanBisnisDilanggar('Prospek ini tidak memiliki consent pemasaran yang aktif.');
        }

        return $this->transaksi->jalankan(function () use ($sequence, $prospek): PendaftaranSequence {
            $adaSebelumnya = PendaftaranSequence::query()
                ->where('SequenceEmailPemasaranId', $sequence->Id)
                ->where('ProspekId', $prospek->Id)
                ->first();

            if ($adaSebelumnya !== null) {
                return $adaSebelumnya;
            }

            $pendaftaran = PendaftaranSequence::create([
                'SequenceEmailPemasaranId' => $sequence->Id,
                'ProspekId' => $prospek->Id,
                'Status' => StatusPendaftaranSequence::Berjalan,
                'DimulaiPada' => CarbonImmutable::now(),
            ]);

            // Template tiap langkah ikut dimuat; tanpa ini sequence berisi N langkah menembak N kueri.
            foreach ($sequence->langkah()->with('template')->get() as $langkah) {
                if ($langkah->Aktif) {
                    $this->penjadwal->jadwalkanLangkah($pendaftaran, $langkah, $prospek);
                }
            }

            return $pendaftaran;
        });
    }

    /** Menghentikan sequence sekaligus membatalkan kiriman yang belum berangkat. */
    public function hentikan(PendaftaranSequence $pendaftaran): void
    {
        $this->transaksi->jalankan(function () use ($pendaftaran): void {
            $pendaftaran->Status = StatusPendaftaranSequence::Dihentikan;
            $pendaftaran->SelesaiPada = CarbonImmutable::now();
            $pendaftaran->save();

            $pendaftaran->pengiriman()
                ->where('Status', StatusPengirimanEmail::Terjadwal->value)
                ->delete();
        });
    }

    /** @return list<LangkahSequenceEmail> */
    public function langkahAktif(SequenceEmailPemasaran $sequence): array
    {
        return array_values($sequence->langkah->filter(
            fn (LangkahSequenceEmail $langkah): bool => $langkah->Aktif,
        )->all());
    }
}
