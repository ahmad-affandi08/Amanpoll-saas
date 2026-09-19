<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use DateTimeInterface;
use Illuminate\Support\Str;

final class BuatKunciApi
{
    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly LayananAudit $layananAudit,
    ) {}

    /**
     * @param list<string>|null $cakupan
     * @param list<string>|null $alamatIpDiizinkan
     * @return array{kunciApi: KunciApi, tokenMentah: string} token mentah hanya tersedia di sini, tidak pernah disimpan.
     */
    public function jalankan(
        Pengguna $pembuat,
        string $nama,
        ?array $cakupan,
        ?DateTimeInterface $kadaluarsaPada,
        ?array $alamatIpDiizinkan,
    ): array {
        $prefix = Str::random(12);
        $rahasia = Str::random(40);
        $tokenMentah = "{$prefix}.{$rahasia}";

        $kunciApi = KunciApi::create([
            'OrganisasiId' => $this->konteks->wajibId(),
            'Nama' => $nama,
            'AwalanKunci' => $prefix,
            'HashKunci' => hash('sha256', $tokenMentah),
            'Cakupan' => $cakupan,
            'AlamatIpDiizinkan' => $alamatIpDiizinkan,
            'KadaluarsaPada' => $kadaluarsaPada,
            'Status' => 'Aktif',
            'DibuatOleh' => $pembuat->Id,
        ]);

        $this->layananAudit->catat(
            aksi: 'KunciApi.Dibuat',
            jenisEntitas: 'KunciApi',
            entitasId: $kunciApi->Id,
            dataSesudah: $kunciApi->only(['Id', 'Nama', 'AwalanKunci', 'Cakupan', 'AlamatIpDiizinkan', 'KadaluarsaPada', 'Status']),
        );

        return ['kunciApi' => $kunciApi, 'tokenMentah' => $tokenMentah];
    }
}
