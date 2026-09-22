<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Izin\PemeriksaIzinPlatform;
use App\Domain\Pemasaran\Application\Services\PemeriksaFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ringkasan Growth & Marketing (MARKETING.md 4).
 *
 * Sebelum ada angka pertumbuhan yang sungguhan di FASE 37, halaman ini
 * menjawab pertanyaan yang justru paling sering ditanyakan saat modul dirilis
 * bertahap: modul mana yang sudah hidup, dan apa yang boleh saya buka.
 */
final class RingkasanPemasaranController extends Controller
{
    public function __construct(
        private readonly PemeriksaFiturPlatform $fitur,
        private readonly PemeriksaIzinPlatform $izin,
    ) {}

    public function __invoke(Request $request): Response
    {
        $admin = $request->user('platform');
        $status = $this->fitur->status();

        return Inertia::render('Pemasaran/Ringkasan', [
            'modul' => array_map(
                fn (string $kode): array => [
                    'Kode' => $kode,
                    'Nama' => KatalogFiturPlatform::semua()[$kode]['nama'],
                    'Keterangan' => KatalogFiturPlatform::semua()[$kode]['keterangan'],
                    'Aktif' => $status[$kode] ?? false,
                ],
                KatalogFiturPlatform::kode(),
            ),
            'izinSaya' => $admin === null ? [] : $this->izin->daftarKode($admin),
            'superAdmin' => $admin?->SuperAdmin === true,
        ]);
    }
}
