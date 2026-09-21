<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Controllers;

use App\Domain\Notifikasi\Domain\Enums\KanalNotifikasi;
use App\Domain\Notifikasi\Http\Resources\NotifikasiResource;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AksesDitolak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class NotifikasiController extends Controller
{
    public function ringkasan(Request $request): JsonResponse
    {
        $penggunaId = $request->user('web')->Id;

        $terbaru = Notifikasi::query()
            ->where('PenggunaId', $penggunaId)
            ->where('Kanal', KanalNotifikasi::InApp->value)
            ->latest('DibuatPada')
            ->limit(20)
            ->get();

        $jumlahBelumDibaca = Notifikasi::query()
            ->where('PenggunaId', $penggunaId)
            ->where('Kanal', KanalNotifikasi::InApp->value)
            ->whereNull('DibacaPada')
            ->count();

        return response()->json([
            'data' => NotifikasiResource::collection($terbaru)->resolve(),
            'jumlahBelumDibaca' => $jumlahBelumDibaca,
        ]);
    }

    public function baca(Notifikasi $notifikasi, Request $request): RedirectResponse
    {
        if ($notifikasi->PenggunaId !== $request->user('web')->Id) {
            throw new AksesDitolak('Notifikasi ini bukan milik Anda.');
        }

        if (! $notifikasi->DibacaPada) {
            $notifikasi->DibacaPada = now()->toImmutable();
            $notifikasi->save();
        }

        return back();
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        Notifikasi::query()
            ->where('PenggunaId', $request->user('web')->Id)
            ->whereNull('DibacaPada')
            ->update(['DibacaPada' => now()]);

        return back();
    }
}
