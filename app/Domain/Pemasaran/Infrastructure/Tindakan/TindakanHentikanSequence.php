<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Tindakan;

use App\Domain\Pemasaran\Application\Actions\DaftarkanKeSequence;
use App\Domain\Pemasaran\Domain\Contracts\TindakanOtomasi;
use App\Domain\Pemasaran\Domain\Enums\StatusPendaftaranSequence;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PendaftaranSequence;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SequenceEmailPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Mengeluarkan prospek dari sequence sekaligus membatalkan kiriman yang belum berangkat. */
final class TindakanHentikanSequence implements TindakanOtomasi
{
    public function __construct(private readonly DaftarkanKeSequence $pendaftaran) {}

    public function kode(): string
    {
        return 'HentikanSequence';
    }

    public function label(): string
    {
        return 'Keluarkan dari sequence';
    }

    /** @return array<string, mixed> */
    public function aturan(): array
    {
        return ['SequenceKode' => ['required', 'string', 'exists:SequenceEmailPemasaran,Kode']];
    }

    /** @param array<string, mixed> $konfigurasi */
    public function jalankan(KonteksOtomasi $konteks, array $konfigurasi): string
    {
        $prospek = $konteks->wajibProspek();
        $kode = (string) ($konfigurasi['SequenceKode'] ?? '');

        $sequence = SequenceEmailPemasaran::query()->where('Kode', $kode)->first();

        if ($sequence === null) {
            throw new AturanBisnisDilanggar("Sequence {$kode} tidak ada.");
        }

        $pendaftaran = PendaftaranSequence::query()
            ->where('SequenceEmailPemasaranId', $sequence->Id)
            ->where('ProspekId', $prospek->Id)
            ->where('Status', StatusPendaftaranSequence::Berjalan->value)
            ->first();

        if ($pendaftaran === null) {
            return "Prospek tidak sedang berjalan di sequence {$kode}.";
        }

        $this->pendaftaran->hentikan($pendaftaran);

        return "Prospek dikeluarkan dari sequence {$kode}.";
    }
}
