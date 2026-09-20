<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class BuatMutasiStok
{
    private const JENIS_DOKUMEN = 'MutasiStok';

    private const JENIS_VALID = [
        MutasiStok::JENIS_PENERIMAAN,
        MutasiStok::JENIS_PENGELUARAN,
        MutasiStok::JENIS_TRANSFER,
        MutasiStok::JENIS_ADJUSTMENT,
        MutasiStok::JENIS_RETURN,
    ];

    public function __construct(
        private readonly LayananNomorDokumen $layananNomorDokumen,
        private readonly KonteksOrganisasi $konteksOrganisasi,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data, string $dibuatOleh): MutasiStok
    {
        $jenis = $data['Jenis'];

        $this->pastikanGudangValid($jenis, $data['GudangAsalId'] ?? null, $data['GudangTujuanId'] ?? null);

        if ($jenis === MutasiStok::JENIS_ADJUSTMENT && trim((string) ($data['Catatan'] ?? '')) === '') {
            throw new AturanBisnisDilanggar('Penyesuaian stok harus menyertakan alasan pada kolom Catatan.');
        }

        $data['Nomor'] = $this->layananNomorDokumen->berikutnya($this->konteksOrganisasi->wajibId(), self::JENIS_DOKUMEN);
        $data['DibuatOleh'] = $dibuatOleh;
        $data['Tanggal'] = $data['Tanggal'] ?? now();
        $data['Status'] = MutasiStok::STATUS_DRAFT;

        return MutasiStok::create($data);
    }

    private function pastikanGudangValid(string $jenis, ?string $gudangAsalId, ?string $gudangTujuanId): void
    {
        if (! in_array($jenis, self::JENIS_VALID, true)) {
            throw new AturanBisnisDilanggar('Jenis mutasi stok tidak dikenal.');
        }

        $pesan = 'Kombinasi gudang asal/tujuan tidak sesuai untuk jenis mutasi ini.';

        match ($jenis) {
            MutasiStok::JENIS_PENERIMAAN, MutasiStok::JENIS_RETURN => $gudangTujuanId === null
                ? throw new AturanBisnisDilanggar($pesan) : null,
            MutasiStok::JENIS_PENGELUARAN, MutasiStok::JENIS_ADJUSTMENT => $gudangAsalId === null
                ? throw new AturanBisnisDilanggar($pesan) : null,
            MutasiStok::JENIS_TRANSFER => ($gudangAsalId === null || $gudangTujuanId === null)
                ? throw new AturanBisnisDilanggar($pesan) : null,
        };
    }
}
