<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Notifikasi\Domain\Enums\KanalNotifikasi;
use App\Domain\Notifikasi\Http\Resources\NotifikasiResource;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Notifikasi dalam aplikasi milik pengguna yang sedang masuk, untuk layar
 * Notifikasi Mode Lapangan. Menandai sudah dibaca memakai endpoint
 * `notifikasi.baca` dan `notifikasi.bacaSemua` yang sama dengan dasbor.
 */
final class LapanganNotifikasiController extends Controller
{
    /** Batas notifikasi yang dibawa ke layar; yang lebih lama tetap ada di dasbor. */
    private const BATAS = 50;

    public function __invoke(Request $request): Response
    {
        $penggunaId = $request->user('web')->Id;

        $kueri = Notifikasi::query()
            ->where('PenggunaId', $penggunaId)
            ->where('Kanal', KanalNotifikasi::InApp->value);

        return Inertia::render('Lapangan/Notifikasi', [
            'notifikasi' => NotifikasiResource::collection(
                (clone $kueri)->latest('DibuatPada')->orderByDesc('Id')->limit(self::BATAS)->get(),
            ),
            'jumlahBelumDibaca' => (clone $kueri)->whereNull('DibacaPada')->count(),
        ]);
    }
}
