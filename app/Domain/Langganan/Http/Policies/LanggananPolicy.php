<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/**
 * Halaman langganan tenant memuat paket, pemakaian, dan seluruh riwayat
 * tagihan; membayar tagihan mengikat organisasi secara finansial. Keduanya
 * urusan pengelola, bukan setiap pengguna yang kebetulan dapat masuk.
 */
final class LanggananPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }

    public function view(Pengguna $pengguna, Langganan $langganan): bool
    {
        return $this->viewAny($pengguna);
    }

    public function bayar(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }
}
