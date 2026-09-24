<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/** Beranda Mode Lapangan untuk pelapor (DESIGN §36.7, papan pelapor layar 02). */
final class LapanganPelaporBerandaController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Lapangan/Pelapor/Beranda');
    }
}
