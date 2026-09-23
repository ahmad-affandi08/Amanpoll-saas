<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KalenderOrganisasi;
use App\Domain\Aset\Application\Actions\BuatAset;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPenerimaanPembelian;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPenerimaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenerimaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\Persediaan\Application\Actions\PostingMutasiStok;
use App\Domain\Persediaan\Domain\Enums\JenisMutasiStok;
use App\Domain\Persediaan\Domain\Enums\StatusMutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\DetailMutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Str;

final class CatatPenerimaanPembelian
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PostingMutasiStok $postingMutasiStok,
        private readonly BuatAset $buatAset,
        private readonly LayananAudit $audit,
        private readonly KalenderOrganisasi $kalender,
    ) {}

    /** @param array<string, mixed> $data */
    public function jalankan(PesananPembelian $po, array $data, string $penggunaId): PenerimaanPembelian
    {
        if (! in_array($po->Status, [StatusPesananPembelian::Dikirim->value, StatusPesananPembelian::DiterimaSebagian->value], true)) {
            throw new AturanBisnisDilanggar('Penerimaan hanya dapat dicatat untuk PO yang sudah dikirim dan belum diterima penuh.');
        }

        return $this->transaksi->jalankan(function () use ($po, $data, $penggunaId): PenerimaanPembelian {
            $terkunci = PesananPembelian::query()->lockForUpdate()->findOrFail($po->Id);
            $penerimaan = PenerimaanPembelian::create([
                'OrganisasiId' => $terkunci->OrganisasiId,
                'Nomor' => $data['Nomor'] ?? 'RCV-'.$this->kalender->sekarang($terkunci->OrganisasiId)->format('Ym').'-'.Str::upper(Str::random(6)),
                'PesananPembelianId' => $terkunci->Id,
                'GudangId' => $data['GudangId'] ?? null,
                'TanggalTerima' => $data['TanggalTerima'] ?? now(),
                'NomorSuratJalan' => $data['NomorSuratJalan'] ?? null,
                'DiterimaOleh' => $penggunaId,
                'Status' => StatusPenerimaanPembelian::Diterima->value,
                'Catatan' => $data['Catatan'] ?? null,
            ]);

            $detailDiterima = [];
            foreach ($data['Detail'] as $baris) {
                $detailPo = DetailPesananPembelian::query()->whereKey($baris['DetailPesananPembelianId'])->lockForUpdate()->firstOrFail();
                if ($detailPo->PesananPembelianId !== $terkunci->Id) {
                    throw new AturanBisnisDilanggar('Detail penerimaan tidak termasuk dalam PO ini.');
                }

                $sudahDiterima = DetailPenerimaanPembelian::query()
                    ->where('DetailPesananPembelianId', $detailPo->Id)
                    ->sum('JumlahDiterima');
                $jumlahDiterima = (string) $baris['JumlahDiterima'];
                $jumlahDitolak = (string) ($baris['JumlahDitolak'] ?? '0');
                if ($this->skalaEmpat((string) $sudahDiterima) + $this->skalaEmpat($jumlahDiterima) > $this->skalaEmpat((string) $detailPo->Jumlah)) {
                    throw new AturanBisnisDilanggar("Jumlah penerimaan {$detailPo->Deskripsi} melebihi jumlah PO.");
                }
                if ($this->skalaEmpat($jumlahDiterima) <= 0 || $this->skalaEmpat($jumlahDitolak) < 0) {
                    throw new AturanBisnisDilanggar('Jumlah diterima harus positif dan jumlah ditolak tidak boleh negatif.');
                }

                $serial = array_values(array_filter($baris['NomorSeri'] ?? [], fn ($nilai): bool => trim((string) $nilai) !== ''));
                if ($detailPo->JenisItem === 'Aset') {
                    $jumlahUnit = $this->skalaEmpat($jumlahDiterima);
                    if ($jumlahUnit % 10000 !== 0 || count($serial) !== intdiv($jumlahUnit, 10000)) {
                        throw new AturanBisnisDilanggar('Setiap unit aset yang diterima wajib memiliki tepat satu nomor seri.');
                    }
                }

                $detail = DetailPenerimaanPembelian::create([
                    'OrganisasiId' => $terkunci->OrganisasiId,
                    'PenerimaanPembelianId' => $penerimaan->Id,
                    'DetailPesananPembelianId' => $detailPo->Id,
                    'SukuCadangId' => $detailPo->SukuCadangId,
                    'JumlahDipesan' => $detailPo->Jumlah,
                    'JumlahDiterima' => $jumlahDiterima,
                    'JumlahDitolak' => $jumlahDitolak,
                    'Kondisi' => $baris['Kondisi'],
                    'NomorSeriJson' => $serial,
                    'Catatan' => $baris['Catatan'] ?? null,
                ]);
                $detailDiterima[] = [$detailPo, $detail, $serial];
            }

            $this->integrasikanStok($penerimaan, $detailDiterima, $penggunaId);
            $this->integrasikanAset($terkunci, $penerimaan, $detailDiterima, $penggunaId);
            $terkunci->Status = $this->sudahDiterimaPenuh($terkunci)
                ? StatusPesananPembelian::DiterimaPenuh->value
                : StatusPesananPembelian::DiterimaSebagian->value;
            $terkunci->save();
            $this->audit->catat('PenerimaanPembelian.Dicatat', 'PenerimaanPembelian', $penerimaan->Id, dataSesudah: ['PesananPembelianId' => $terkunci->Id, 'StatusPO' => $terkunci->Status]);

            return $penerimaan->load('detail');
        });
    }

    /** @param list<array{0: DetailPesananPembelian, 1: DetailPenerimaanPembelian, 2: list<mixed>}> $detailDiterima */
    private function integrasikanStok(PenerimaanPembelian $penerimaan, array $detailDiterima, string $penggunaId): void
    {
        $detailStok = array_filter($detailDiterima, fn (array $baris): bool => $baris[0]->JenisItem === 'SukuCadang');
        if ($detailStok === []) {
            return;
        }
        if ($penerimaan->GudangId === null) {
            throw new AturanBisnisDilanggar('Gudang wajib dipilih ketika menerima item suku cadang.');
        }

        $mutasi = MutasiStok::create([
            'OrganisasiId' => $penerimaan->OrganisasiId,
            'Nomor' => 'RCV-STK-'.Str::upper(Str::random(10)),
            'Jenis' => JenisMutasiStok::Penerimaan->value,
            'GudangTujuanId' => $penerimaan->GudangId,
            'ReferensiJenis' => 'PenerimaanPembelian',
            'ReferensiId' => $penerimaan->Id,
            'Tanggal' => $penerimaan->TanggalTerima,
            'Status' => StatusMutasiStok::Draft->value,
            'Catatan' => "Penerimaan {$penerimaan->Nomor}",
            'DibuatOleh' => $penggunaId,
        ]);
        foreach ($detailStok as [$detailPo, $detail]) {
            DetailMutasiStok::create([
                'OrganisasiId' => $penerimaan->OrganisasiId,
                'MutasiStokId' => $mutasi->Id,
                'SukuCadangId' => $detailPo->SukuCadangId,
                'Jumlah' => $detail->JumlahDiterima,
                'HargaSatuan' => $detailPo->HargaSatuan,
            ]);
        }
        $this->postingMutasiStok->jalankan($mutasi, $penggunaId);
    }

    /** @param list<array{0: DetailPesananPembelian, 1: DetailPenerimaanPembelian, 2: list<mixed>}> $detailDiterima */
    private function integrasikanAset(PesananPembelian $po, PenerimaanPembelian $penerimaan, array $detailDiterima, string $penggunaId): void
    {
        foreach ($detailDiterima as [$detailPo, $detail, $serial]) {
            if ($detailPo->JenisItem !== 'Aset') {
                continue;
            }
            // Deskripsi unik per permintaan pembelian membuat penelusuran balik PO ke item PR deterministik.
            $asal = DetailPenawaranPenyedia::query()
                ->with('detailPermintaanPembelian.asetReferensi')
                ->where('PenawaranPenyediaId', $po->PenawaranPenyediaId)
                ->where('Deskripsi', $detailPo->Deskripsi)
                ->first()?->detailPermintaanPembelian?->asetReferensi;
            if (! $asal instanceof Aset) {
                throw new AturanBisnisDilanggar("Referensi kategori aset untuk {$detailPo->Deskripsi} tidak ditemukan.");
            }

            foreach ($serial as $nomorSeri) {
                $this->buatAset->jalankan([
                    'OrganisasiId' => $po->OrganisasiId,
                    'UnitOrganisasiId' => $asal->UnitOrganisasiId,
                    'KategoriAsetId' => $asal->KategoriAsetId,
                    'ModelAsetId' => $asal->ModelAsetId,
                    'PenyediaId' => $po->PenyediaId,
                    'KodeAset' => $po->Nomor.'-'.Str::upper(Str::random(8)),
                    'Nama' => $detailPo->Deskripsi,
                    'NomorSeri' => (string) $nomorSeri,
                    'TanggalPerolehan' => $penerimaan->TanggalTerima->toDateString(),
                    'HargaPerolehan' => $detailPo->HargaSatuan,
                    'MataUang' => $po->MataUang,
                    'SumberDana' => "PO {$po->Nomor}",
                    'Status' => StatusAset::Aktif->value,
                    'Kondisi' => $detail->Kondisi === 'Baik' ? KondisiAset::Baik->value : KondisiAset::PerluPerhatian->value,
                    'TingkatKritis' => TingkatKritisAset::Normal->value,
                    'Catatan' => "Dibuat otomatis dari penerimaan {$penerimaan->Nomor}.",
                ], $penggunaId);
            }
        }
    }

    private function sudahDiterimaPenuh(PesananPembelian $po): bool
    {
        return $po->detail()->get()->every(function (DetailPesananPembelian $detail): bool {
            $diterima = DetailPenerimaanPembelian::query()->where('DetailPesananPembelianId', $detail->Id)->sum('JumlahDiterima');

            return $this->skalaEmpat((string) $diterima) >= $this->skalaEmpat((string) $detail->Jumlah);
        });
    }

    private function skalaEmpat(string $nilai): int
    {
        [$bulat, $pecahan] = array_pad(explode('.', $nilai, 2), 2, '');

        return ((int) $bulat * 10000) + (int) substr(str_pad($pecahan, 4, '0'), 0, 4);
    }
}
