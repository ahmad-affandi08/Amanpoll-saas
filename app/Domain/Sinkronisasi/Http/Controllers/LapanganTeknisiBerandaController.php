<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/** Beranda Mode Lapangan untuk teknisi (DESIGN §36.6, papan teknisi layar 03). */
final class LapanganTeknisiBerandaController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Lapangan/Teknisi/Beranda');
    }
}
