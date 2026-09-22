<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Application\Actions\DaftarkanKeSequence;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PendaftaranSequence;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SequenceEmailPemasaran;

/** Sequence onboarding ditunjuk lewat setelan; tanpa setelan atau consent, trial tetap berjalan (MARKETING.md 15). */
final class PendaftarSequenceTrial
{
    public function __construct(
        private readonly LayananKonfigurasiPemasaran $konfigurasi,
        private readonly DaftarkanKeSequence $pendaftaran,
        private readonly LayananKonsen $konsen,
    ) {}

    public function daftarkan(?Prospek $prospek): ?PendaftaranSequence
    {
        $email = (string) $prospek?->Email;
        $kode = trim((string) $this->konfigurasi->ambil(KatalogKonfigurasiPemasaran::EMAIL_SEQUENCE_TRIAL));

        if ($prospek === null || $email === '' || $kode === '') {
            return null;
        }

        if (! $this->konsen->bolehDikirimi($email)) {
            return null;
        }

        $sequence = SequenceEmailPemasaran::query()
            ->where('Kode', $kode)
            ->where('Aktif', true)
            ->first();

        if ($sequence === null) {
            return null;
        }

        return $this->pendaftaran->jalankan($sequence, $prospek);
    }
}
