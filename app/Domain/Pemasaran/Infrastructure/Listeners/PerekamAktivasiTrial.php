<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Listeners;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemasaran\Application\Actions\CatatAktivasiTrial;
use App\Domain\Pemasaran\Domain\Enums\ButirAktivasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
use Illuminate\Database\Eloquent\Model;

/**
 * Menandai butir aktivasi dari pekerjaan nyata di aplikasi (MARKETING.md 12).
 *
 * Checklist tidak pernah dicentang manusia. Ia terisi karena orangnya benar-benar
 * membuat lokasi, aset, dan perintah kerja — itulah yang membedakan aktivasi dari
 * sekadar mendaftar.
 */
final class PerekamAktivasiTrial
{
    private const PETA = [
        Lokasi::class => ButirAktivasi::LokasiDibuat,
        Aset::class => ButirAktivasi::AsetPertama,
        Pengguna::class => ButirAktivasi::PenggunaDiundang,
        PerintahKerja::class => ButirAktivasi::PerintahKerjaPertama,
        RencanaPemeliharaan::class => ButirAktivasi::PreventifPertama,
    ];

    /** @var array<string, bool> */
    private array $punyaTrial = [];

    public function __construct(private readonly CatatAktivasiTrial $aksi) {}

    public function created(Model $model): void
    {
        $butir = self::PETA[$model::class] ?? null;
        $organisasiId = $model->getAttribute('OrganisasiId');

        if ($butir === null || ! is_string($organisasiId) || $organisasiId === '') {
            return;
        }

        if (! $this->punyaTrialBerjalan($organisasiId)) {
            return;
        }

        $this->aksi->jalankan($organisasiId, $butir);
    }

    /** Diingat per permintaan: mayoritas organisasi tidak sedang trial, dan mereka tidak perlu membayar query. */
    private function punyaTrialBerjalan(string $organisasiId): bool
    {
        return $this->punyaTrial[$organisasiId] ??= Trial::query()
            ->where('OrganisasiId', $organisasiId)
            ->exists();
    }
}
