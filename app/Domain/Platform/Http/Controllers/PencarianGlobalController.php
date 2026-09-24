<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Services\LayananPencarianGlobal;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Sumber data kotak cari global di header; dipanggil lewat XHR, bukan kunjungan Inertia. */
final class PencarianGlobalController extends Controller
{
    public function __invoke(Request $request, LayananPencarianGlobal $layanan): JsonResponse
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        return response()->json([
            'kelompok' => $layanan->cari($request->string('q')->toString(), $request->user('web')),
        ]);
    }
}
