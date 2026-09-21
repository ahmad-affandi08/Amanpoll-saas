<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Katalog Izin adalah data platform (bukan tenant), jadi hanya dapat
 * dibaca oleh organisasi admin untuk keperluan menyusun Peran -- CRUD-nya
 * dikelola di level platform, bukan oleh tenant.
 */
final class IzinController extends Controller
{
    public function __construct(private readonly PemeriksaIzin $pemeriksaIzin) {}

    public function index(Request $request): JsonResponse
    {
        $penggunaId = (string) $request->user('web')->Id;
        abort_unless(
            $this->pemeriksaIzin->boleh($penggunaId, 'Pengguna.Kelola')
                || $this->pemeriksaIzin->boleh($penggunaId, 'Integrasi.Kelola'),
            403,
        );

        $izin = Izin::query()->orderBy('Modul')->orderBy('Nama')->get(['Id', 'Kode', 'Nama', 'Modul', 'Keterangan']);

        return response()->json(['data' => $izin->groupBy('Modul')]);
    }
}
