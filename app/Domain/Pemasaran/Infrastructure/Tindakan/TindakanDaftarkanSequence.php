<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Tindakan;

use App\Domain\Pemasaran\Application\Actions\DaftarkanKeSequence;
use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Domain\Contracts\TindakanOtomasi;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SequenceEmailPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Mendaftarkan prospek ke sequence; tanpa consent aksinya dilewati, bukan digagalkan. */
final class TindakanDaftarkanSequence implements TindakanOtomasi
{
    public function __construct(
        private readonly DaftarkanKeSequence $pendaftaran,
        private readonly LayananKonsen $konsen,
    ) {}

    public function kode(): string
    {
        return 'DaftarkanSequence';
    }

    public function label(): string
    {
        return 'Daftarkan ke sequence';
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

        $sequence = SequenceEmailPemasaran::query()->where('Kode', $kode)->where('Aktif', true)->first();

        if ($sequence === null) {
            throw new AturanBisnisDilanggar("Sequence {$kode} tidak ada atau nonaktif.");
        }

        if (! $this->konsen->bolehDikirimi((string) $prospek->Email)) {
            return "Prospek tidak memiliki consent aktif; pendaftaran ke {$kode} dilewati.";
        }

        $this->pendaftaran->jalankan($sequence, $prospek);

        return "Prospek didaftarkan ke sequence {$kode}.";
    }
}
