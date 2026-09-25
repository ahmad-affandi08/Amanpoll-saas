<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain\ValueObjects;

use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;

/**
 * Penyedia milik organisasi yang terpilih untuk satu kiriman, beserta kredensialnya (PRD 8.23).
 *
 * @template TPenyedia of DeskripsiPenyediaLayanan
 */
final readonly class PengirimOrganisasi
{
    /** @param  TPenyedia  $penyedia */
    public function __construct(
        public DeskripsiPenyediaLayanan $penyedia,
        public KredensialPenyedia $kredensial,
        /** Id baris `PenyediaLayananOrganisasi`, untuk merekam kesehatannya. */
        public string $penyediaId,
    ) {}
}
