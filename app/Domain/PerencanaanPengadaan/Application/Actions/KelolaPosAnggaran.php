<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\ValueObjects\Uang;

final class KelolaPosAnggaran
{
    public function __construct(
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function buat(Anggaran $anggaran, array $data): PosAnggaran
    {
        $this->pastikanDapatDiubah($anggaran);

        return $this->transaksi->jalankan(function () use ($anggaran, $data): PosAnggaran {
            $anggaranTerkunci = Anggaran::query()->lockForUpdate()->findOrFail($anggaran->Id);
            $induk = $this->temukanInduk($anggaranTerkunci, $data['IndukId'] ?? null);
            $jumlah = Uang::dariString((string) $data['Jumlah']);

            $this->pastikanKodeUnik($anggaranTerkunci, (string) $data['Kode']);
            $this->pastikanAlokasiTersedia($anggaranTerkunci, $induk, $jumlah);

            $pos = PosAnggaran::create([
                'OrganisasiId' => $this->konteksOrganisasi->wajibId(),
                'AnggaranId' => $anggaranTerkunci->Id,
                'IndukId' => $induk?->Id,
                'Kode' => $data['Kode'],
                'Nama' => $data['Nama'],
                'Jumlah' => $jumlah->keString(),
                'Terpakai' => '0.00',
                'Ditahan' => '0.00',
            ]);

            $this->audit->catat('PosAnggaran.Dibuat', 'PosAnggaran', $pos->Id, dataSesudah: $pos->toArray());

            return $pos;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbarui(PosAnggaran $posAnggaran, array $data): PosAnggaran
    {
        $this->pastikanDapatDiubah($posAnggaran->anggaran()->firstOrFail());

        return $this->transaksi->jalankan(function () use ($posAnggaran, $data): PosAnggaran {
            $pos = PosAnggaran::query()->lockForUpdate()->findOrFail($posAnggaran->Id);
            $anggaran = Anggaran::query()->lockForUpdate()->findOrFail($pos->AnggaranId);
            $induk = $this->temukanInduk($anggaran, array_key_exists('IndukId', $data) ? $data['IndukId'] : $pos->IndukId);

            if ($induk?->Id === $pos->Id || ($induk && $this->adalahTurunan($induk, $pos->Id))) {
                throw new AturanBisnisDilanggar('Induk pos anggaran tidak boleh membentuk siklus.');
            }

            $jumlah = Uang::dariString((string) ($data['Jumlah'] ?? $pos->Jumlah));
            $terpakaiDanDitahan = Uang::dariString((string) $pos->Terpakai)
                ->tambah(Uang::dariString((string) $pos->Ditahan));
            if ($terpakaiDanDitahan->lebihBesarDari($jumlah)) {
                throw new AturanBisnisDilanggar('Nilai pos tidak boleh lebih kecil dari realisasi dan komitmen berjalan.');
            }

            $this->pastikanKodeUnik($anggaran, (string) ($data['Kode'] ?? $pos->Kode), $pos->Id);
            $this->pastikanAlokasiTersedia($anggaran, $induk, $jumlah, $pos->Id);

            $alokasiAnak = Uang::dariString((string) PosAnggaran::query()->where('IndukId', $pos->Id)->sum('Jumlah'));
            if ($alokasiAnak->lebihBesarDari($jumlah)) {
                throw new AturanBisnisDilanggar('Nilai pos tidak boleh lebih kecil dari total alokasi anaknya.');
            }

            $sebelum = $pos->toArray();
            $pos->fill([
                'IndukId' => $induk?->Id,
                'Kode' => $data['Kode'] ?? $pos->Kode,
                'Nama' => $data['Nama'] ?? $pos->Nama,
                'Jumlah' => $jumlah->keString(),
            ])->save();

            $this->audit->catat('PosAnggaran.Diperbarui', 'PosAnggaran', $pos->Id, $sebelum, $pos->fresh()->toArray());

            return $pos->fresh();
        });
    }

    public function hapus(PosAnggaran $posAnggaran): void
    {
        $this->pastikanDapatDiubah($posAnggaran->anggaran()->firstOrFail());

        if ($posAnggaran->anak()->exists() || $posAnggaran->transaksi()->exists() || $posAnggaran->rencanaPengadaan()->exists()) {
            throw new AturanBisnisDilanggar('Pos anggaran yang sudah memiliki anak atau transaksi tidak dapat dihapus.');
        }

        $sebelum = $posAnggaran->toArray();
        $posAnggaran->delete();
        $this->audit->catat('PosAnggaran.Dihapus', 'PosAnggaran', $posAnggaran->Id, dataSebelum: $sebelum);
    }

    private function pastikanDapatDiubah(Anggaran $anggaran): void
    {
        if (! in_array($anggaran->Status, [StatusAnggaran::Draft->value, StatusAnggaran::Ditolak->value], true)) {
            throw new AturanBisnisDilanggar('Struktur pos hanya dapat diubah saat anggaran masih draft atau ditolak.');
        }
    }

    private function temukanInduk(Anggaran $anggaran, ?string $indukId): ?PosAnggaran
    {
        if ($indukId === null || $indukId === '') {
            return null;
        }

        return PosAnggaran::query()
            ->where('AnggaranId', $anggaran->Id)
            ->findOrFail($indukId);
    }

    private function pastikanKodeUnik(Anggaran $anggaran, string $kode, ?string $kecualiId = null): void
    {
        $ada = PosAnggaran::query()
            ->where('AnggaranId', $anggaran->Id)
            ->where('Kode', $kode)
            ->when($kecualiId, fn ($query) => $query->where('Id', '!=', $kecualiId))
            ->exists();

        if ($ada) {
            throw new AturanBisnisDilanggar("Kode pos anggaran '{$kode}' sudah digunakan.");
        }
    }

    private function pastikanAlokasiTersedia(Anggaran $anggaran, ?PosAnggaran $induk, Uang $jumlah, ?string $kecualiId = null): void
    {
        $saudara = PosAnggaran::query()
            ->where('AnggaranId', $anggaran->Id)
            ->when($induk, fn ($query) => $query->where('IndukId', $induk->Id), fn ($query) => $query->whereNull('IndukId'))
            ->when($kecualiId, fn ($query) => $query->where('Id', '!=', $kecualiId))
            ->sum('Jumlah');

        $batas = Uang::dariString((string) ($induk instanceof PosAnggaran ? $induk->Jumlah : $anggaran->Jumlah));
        if (Uang::dariString((string) $saudara)->tambah($jumlah)->lebihBesarDari($batas)) {
            throw new AturanBisnisDilanggar($induk
                ? 'Total alokasi anak melebihi nilai pos induk.'
                : 'Total alokasi pos utama melebihi total anggaran.');
        }
    }

    private function adalahTurunan(PosAnggaran $calonInduk, string $posId): bool
    {
        $saatIni = $calonInduk;
        while ($saatIni->IndukId !== null) {
            if ($saatIni->IndukId === $posId) {
                return true;
            }

            $saatIni = PosAnggaran::query()->find($saatIni->IndukId);
            if (! $saatIni) {
                return false;
            }
        }

        return false;
    }
}
