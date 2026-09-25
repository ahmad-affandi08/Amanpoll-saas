<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\Persetujuan\Application\Actions\AjukanPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\ValueObjects\Uang;

final class KelolaAnggaran
{
    public function __construct(
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly TransaksiDatabase $transaksi,
        private readonly AjukanPermintaanPersetujuan $ajukanPersetujuan,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data): Anggaran
    {
        return $this->transaksi->jalankan(function () use ($data): Anggaran {
            $organisasiId = $this->konteksOrganisasi->wajibId();
            if (filled($data['Kode'] ?? null)) {
                $this->pastikanKodeUnik($organisasiId, (string) $data['Kode'], (int) $data['Tahun']);
            }

            $anggaran = Anggaran::create([
                'OrganisasiId' => $organisasiId,
                'UnitOrganisasiId' => $data['UnitOrganisasiId'] ?? null,
                'Kode' => $data['Kode'] ?? null,
                'Nama' => $data['Nama'],
                'Tahun' => $data['Tahun'],
                'MataUang' => $data['MataUang'] ?? 'IDR',
                'Jumlah' => $data['Jumlah'],
                'Status' => StatusAnggaran::Draft->value,
            ]);

            $this->audit->catat('Anggaran.Dibuat', 'Anggaran', $anggaran->Id, dataSesudah: $anggaran->toArray());

            return $anggaran;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbarui(Anggaran $anggaran, array $data): Anggaran
    {
        if (! in_array($anggaran->Status, [StatusAnggaran::Draft->value, StatusAnggaran::Ditolak->value], true)) {
            throw new AturanBisnisDilanggar('Hanya anggaran draft atau ditolak yang dapat diubah.');
        }

        return $this->transaksi->jalankan(function () use ($anggaran, $data): Anggaran {
            $kode = (string) ($data['Kode'] ?? $anggaran->Kode);
            $tahun = (int) ($data['Tahun'] ?? $anggaran->Tahun);
            $this->pastikanKodeUnik($anggaran->OrganisasiId, $kode, $tahun, $anggaran->Id);

            $jumlah = Uang::dariString((string) ($data['Jumlah'] ?? $anggaran->Jumlah));
            $alokasiAkar = Uang::dariString((string) PosAnggaran::query()
                ->where('AnggaranId', $anggaran->Id)
                ->whereNull('IndukId')
                ->sum('Jumlah'));

            if ($alokasiAkar->lebihBesarDari($jumlah)) {
                throw new AturanBisnisDilanggar('Total anggaran tidak boleh lebih kecil dari alokasi pos tingkat utama.');
            }

            $sebelum = $anggaran->toArray();
            $anggaran->fill([
                'UnitOrganisasiId' => array_key_exists('UnitOrganisasiId', $data) ? $data['UnitOrganisasiId'] : $anggaran->UnitOrganisasiId,
                'Kode' => $kode,
                'Nama' => $data['Nama'] ?? $anggaran->Nama,
                'Tahun' => $tahun,
                'MataUang' => $data['MataUang'] ?? $anggaran->MataUang,
                'Jumlah' => $jumlah->keString(),
                'Status' => StatusAnggaran::Draft->value,
            ])->save();

            $this->audit->catat('Anggaran.Diperbarui', 'Anggaran', $anggaran->Id, $sebelum, $anggaran->fresh()->toArray());

            return $anggaran->fresh();
        });
    }

    public function ajukan(Anggaran $anggaran, string $penggunaId): Anggaran
    {
        if ($anggaran->Status !== StatusAnggaran::Draft->value) {
            throw new AturanBisnisDilanggar('Hanya anggaran draft yang dapat diajukan.');
        }

        if (! $anggaran->posAnggaran()->exists()) {
            throw new AturanBisnisDilanggar('Tambahkan minimal satu pos anggaran sebelum mengajukan anggaran.');
        }

        return $this->transaksi->jalankan(function () use ($anggaran, $penggunaId): Anggaran {
            $alur = AlurPersetujuan::query()
                ->where('JenisEntitas', 'Anggaran')
                ->where('Aktif', true)
                ->first();

            if ($alur) {
                $this->ajukanPersetujuan->jalankan($alur, $anggaran->Id, ['Jumlah' => $anggaran->Jumlah, 'Nilai' => $anggaran->Jumlah], $penggunaId);
                $anggaran->Status = StatusAnggaran::MenungguPersetujuan->value;
            } else {
                $anggaran->Status = StatusAnggaran::Aktif->value;
            }

            $anggaran->save();
            $this->audit->catat('Anggaran.Diajukan', 'Anggaran', $anggaran->Id, dataSesudah: ['Status' => $anggaran->Status]);

            return $anggaran->refresh();
        });
    }

    public function hapus(Anggaran $anggaran): void
    {
        if (! in_array($anggaran->Status, [StatusAnggaran::Draft->value, StatusAnggaran::Ditolak->value], true)) {
            throw new AturanBisnisDilanggar('Hanya anggaran draft atau ditolak yang dapat dihapus.');
        }

        if ($anggaran->posAnggaran()->exists()) {
            throw new AturanBisnisDilanggar('Anggaran yang sudah memiliki pos tidak dapat dihapus.');
        }

        $sebelum = $anggaran->toArray();
        $anggaran->delete();
        $this->audit->catat('Anggaran.Dihapus', 'Anggaran', $anggaran->Id, dataSebelum: $sebelum);
    }

    private function pastikanKodeUnik(string $organisasiId, string $kode, int $tahun, ?string $kecualiId = null): void
    {
        $ada = Anggaran::query()
            ->where('OrganisasiId', $organisasiId)
            ->where('Kode', $kode)
            ->where('Tahun', $tahun)
            ->when($kecualiId, fn ($query) => $query->where('Id', '!=', $kecualiId))
            ->exists();

        if ($ada) {
            throw new AturanBisnisDilanggar("Kode anggaran '{$kode}' sudah digunakan pada periode {$tahun}.");
        }
    }
}
