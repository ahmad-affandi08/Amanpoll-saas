<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenyediaPermintaanPenawaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPenawaran;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Support\Str;

final class KelolaPermintaanPenawaran
{
    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananNomorDokumen $nomorDokumen,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function buat(PermintaanPembelian $permintaan, array $data, string $penggunaId): PermintaanPenawaran
    {
        if ($permintaan->Status !== PermintaanPembelian::STATUS_DISETUJUI) {
            throw new AturanBisnisDilanggar('RFQ hanya dapat dibuat dari permintaan pembelian yang disetujui.');
        }

        return $this->transaksi->jalankan(function () use ($permintaan, $data, $penggunaId): PermintaanPenawaran {
            try {
                $nomor = $this->nomorDokumen->berikutnya($this->konteks->wajibId(), 'PermintaanPenawaran');
            } catch (DataTidakDitemukan) {
                $nomor = 'RFQ-'.now()->format('Ym').'-'.Str::upper(Str::random(6));
            }

            $rfq = PermintaanPenawaran::create([
                'OrganisasiId' => $permintaan->OrganisasiId,
                'Nomor' => $nomor,
                'PermintaanPembelianId' => $permintaan->Id,
                'TanggalDibuka' => now(),
                'BatasPenawaran' => $data['BatasPenawaran'] ?? null,
                'Status' => PermintaanPenawaran::STATUS_DRAFT,
                'Catatan' => $data['Catatan'] ?? null,
                'DibuatOleh' => $penggunaId,
            ]);

            foreach ($data['PenyediaIds'] as $penyediaId) {
                $penyedia = Penyedia::query()->whereKey($penyediaId)->firstOrFail();
                if ($penyedia->Status !== 'Aktif') {
                    throw new AturanBisnisDilanggar("Penyedia {$penyedia->Nama} tidak aktif.");
                }
                PenyediaPermintaanPenawaran::create([
                    'OrganisasiId' => $rfq->OrganisasiId,
                    'PermintaanPenawaranId' => $rfq->Id,
                    'PenyediaId' => $penyedia->Id,
                    'Status' => 'Diundang',
                ]);
            }

            $this->audit->catat('PermintaanPenawaran.Dibuat', 'PermintaanPenawaran', $rfq->Id, dataSesudah: $rfq->toArray());

            return $rfq;
        });
    }

    public function buka(PermintaanPenawaran $rfq): PermintaanPenawaran
    {
        if ($rfq->Status !== PermintaanPenawaran::STATUS_DRAFT || ! $rfq->penyediaDiundang()->exists()) {
            throw new AturanBisnisDilanggar('RFQ draft harus memiliki minimal satu penyedia sebelum dibuka.');
        }
        if ($rfq->BatasPenawaran !== null && $rfq->BatasPenawaran->isPast()) {
            throw new AturanBisnisDilanggar('Batas penawaran harus berada di masa depan.');
        }

        $rfq->Status = PermintaanPenawaran::STATUS_DIBUKA;
        $rfq->TanggalDibuka = now()->toImmutable();
        $rfq->save();
        $rfq->penyediaDiundang()->update(['DikirimPada' => now(), 'Status' => 'Dikirim']);
        $this->audit->catat('PermintaanPenawaran.Dibuka', 'PermintaanPenawaran', $rfq->Id, dataSesudah: ['Status' => $rfq->Status]);

        return $rfq->refresh();
    }
}
