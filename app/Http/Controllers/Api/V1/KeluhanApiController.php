<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Application\Actions\BuatKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\PrioritasKeluhan;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Endpoint kritis untuk sistem eksternal yang melaporkan keluhan. Dilindungi
 * kunci idempotensi supaya percobaan ulang akibat jaringan tidak membuat
 * keluhan ganda (Gate 19).
 */
final class KeluhanApiController extends Controller
{
    public function store(Request $request, BuatKeluhan $aksi, KonteksOrganisasi $konteks): JsonResponse
    {
        $organisasiId = $konteks->wajibId();

        $data = $request->validate([
            'Judul' => ['required', 'string', 'max:220'],
            'Deskripsi' => ['required', 'string', 'max:5000'],
            'KategoriKeluhanId' => ['required', 'string', Rule::exists('KategoriKeluhan', 'Id')->where('OrganisasiId', $organisasiId)],
            'AsetId' => ['nullable', 'string', Rule::exists('Aset', 'Id')->where('OrganisasiId', $organisasiId)],
            'LokasiId' => ['nullable', 'string', Rule::exists('Lokasi', 'Id')->where('OrganisasiId', $organisasiId)],
            'PelaporId' => ['nullable', 'string', Rule::exists('Pengguna', 'Id')->where('OrganisasiId', $organisasiId)],
            'Prioritas' => ['nullable', Rule::enum(PrioritasKeluhan::class)],
        ]);

        $pelaporId = $data['PelaporId'] ?? $this->pemilikKunciApi($request);
        abort_if(
            $pelaporId === null,
            422,
            'PelaporId wajib diisi karena kunci API ini tidak memiliki pengguna pemilik.',
        );

        $keluhan = $aksi->jalankan([
            'Judul' => $data['Judul'],
            'Deskripsi' => $data['Deskripsi'],
            'KategoriKeluhanId' => $data['KategoriKeluhanId'],
            'AsetId' => $data['AsetId'] ?? null,
            'LokasiId' => $data['LokasiId'] ?? null,
            'Prioritas' => $data['Prioritas'] ?? null,
            'Sumber' => 'Api',
        ], $pelaporId);

        return response()->json([
            'Id' => $keluhan->Id,
            'Nomor' => $keluhan->Nomor,
            'Status' => $keluhan->Status,
            'Prioritas' => $keluhan->Prioritas,
            'DilaporkanPada' => $keluhan->DilaporkanPada->toIso8601String(),
        ], 201);
    }

    private function pemilikKunciApi(Request $request): ?string
    {
        $kunciApiId = $request->attributes->get('KunciApiId');
        if (! is_string($kunciApiId)) {
            return null;
        }

        $pemilik = DB::table('KunciApi')->where('Id', $kunciApiId)->value('DibuatOleh');

        return is_string($pemilik) ? $pemilik : null;
    }
}
