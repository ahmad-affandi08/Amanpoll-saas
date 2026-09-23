<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Domain\SiklusAset\Domain\Enums\JenisPermintaanMutasiAset;
use App\Domain\SiklusAset\Domain\Enums\StatusPermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class BuatPermintaanMutasiAset
{
    private const JENIS_DOKUMEN = 'PermintaanMutasiAset';

    public function __construct(
        private readonly LayananNomorDokumen $layananNomorDokumen,
        private readonly KonteksOrganisasi $konteksOrganisasi,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data, string $dimintaOleh): PermintaanMutasiAset
    {
        if (($data['LokasiTujuanId'] ?? null) === null && ($data['UnitTujuanId'] ?? null) === null) {
            throw new AturanBisnisDilanggar('Mutasi harus memiliki lokasi tujuan atau unit tujuan.');
        }

        $this->pastikanJenisnyaKonsisten($data);

        $data['Nomor'] = $this->layananNomorDokumen->berikutnya($this->konteksOrganisasi->wajibId(), self::JENIS_DOKUMEN);
        $data['DimintaOleh'] = $dimintaOleh;
        $data['DimintaPada'] = now();
        $data['Status'] = StatusPermintaanMutasiAset::Draft->value;

        /** @var PermintaanMutasiAset $permintaan */
        $permintaan = PermintaanMutasiAset::create($data);

        return $permintaan;
    }

    /**
     * Reposisi dan Akuisisi baru berarti kalau isian tujuannya sesuai namanya.
     *
     * Tanpa pemeriksaan ini keduanya hanya jadi label bebas: reposisi yang
     * sebenarnya memindahkan aset ke unit lain akan lolos dan membuat laporan
     * perpindahan antar unit kehilangan barisnya.
     *
     * @param  array<string, mixed>  $data
     */
    private function pastikanJenisnyaKonsisten(array $data): void
    {
        $jenis = JenisPermintaanMutasiAset::tryFrom((string) ($data['JenisMutasi'] ?? ''));

        if ($jenis === JenisPermintaanMutasiAset::Reposisi) {
            if (($data['LokasiTujuanId'] ?? null) === null) {
                throw new AturanBisnisDilanggar('Reposisi harus menyebutkan lokasi tujuan.');
            }

            $unitAsal = $data['UnitAsalId'] ?? null;
            $unitTujuan = $data['UnitTujuanId'] ?? null;

            if ($unitTujuan !== null && $unitAsal !== null && $unitTujuan !== $unitAsal) {
                throw new AturanBisnisDilanggar('Reposisi hanya memindahkan aset di dalam unit yang sama; gunakan Antar Unit untuk pindah unit.');
            }
        }

        if ($jenis === JenisPermintaanMutasiAset::Akuisisi && ($data['UnitTujuanId'] ?? null) === null) {
            throw new AturanBisnisDilanggar('Akuisisi harus menyebutkan unit tujuan yang menerima aset.');
        }
    }
}
