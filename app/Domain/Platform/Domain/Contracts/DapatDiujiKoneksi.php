<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Contracts;

use App\Domain\Platform\Domain\ValueObjects\HasilUjiKoneksi;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;

/** Penyedia yang punya panggilan murah untuk memastikan kredensialnya benar (PRD 8.23). */
interface DapatDiujiKoneksi
{
    public function ujiKoneksi(KredensialPenyedia $kredensial): HasilUjiKoneksi;
}
