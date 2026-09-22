<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\PersyaratanKepatuhan;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\StandarKepatuhan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Standar kepatuhan sepenuhnya data milik organisasi. */
final class KelolaStandarKepatuhan
{
    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function buat(array $data): StandarKepatuhan
    {
        return $this->transaksi->jalankan(function () use ($data): StandarKepatuhan {
            $standar = StandarKepatuhan::create([
                'OrganisasiId' => $this->konteks->wajibId(),
                'Kode' => $data['Kode'],
                'Nama' => $data['Nama'],
                'Penerbit' => $data['Penerbit'] ?? null,
                'VersiStandar' => $data['VersiStandar'] ?? null,
                'JenisIndustri' => $data['JenisIndustri'] ?? null,
                'Deskripsi' => $data['Deskripsi'] ?? null,
                'Aktif' => $data['Aktif'] ?? true,
            ]);
            $this->audit->catat('StandarKepatuhan.Dibuat', 'StandarKepatuhan', $standar->Id, dataSesudah: $standar->toArray());

            return $standar;
        });
    }

    /** @param array<string, mixed> $data */
    public function ubah(StandarKepatuhan $standar, array $data): StandarKepatuhan
    {
        $sebelum = $standar->toArray();
        $standar->fill([
            'Kode' => $data['Kode'],
            'Nama' => $data['Nama'],
            'Penerbit' => $data['Penerbit'] ?? null,
            'VersiStandar' => $data['VersiStandar'] ?? null,
            'JenisIndustri' => $data['JenisIndustri'] ?? null,
            'Deskripsi' => $data['Deskripsi'] ?? null,
            'Aktif' => $data['Aktif'] ?? true,
        ]);
        $standar->save();
        $this->audit->catat('StandarKepatuhan.Diubah', 'StandarKepatuhan', $standar->Id, dataSebelum: $sebelum, dataSesudah: $standar->toArray());

        return $standar->refresh();
    }

    public function hapus(StandarKepatuhan $standar): void
    {
        if ($standar->persyaratan()->exists()) {
            throw new AturanBisnisDilanggar('Standar yang masih memiliki persyaratan tidak dapat dihapus.');
        }

        $id = $standar->Id;
        $sebelum = $standar->toArray();
        $standar->delete();
        $this->audit->catat('StandarKepatuhan.Dihapus', 'StandarKepatuhan', $id, dataSebelum: $sebelum);
    }

    /** @param array<string, mixed> $data */
    public function tambahPersyaratan(StandarKepatuhan $standar, array $data): PersyaratanKepatuhan
    {
        if (! $standar->Aktif) {
            throw new AturanBisnisDilanggar('Persyaratan hanya dapat ditambahkan pada standar yang aktif.');
        }
        if ($standar->persyaratan()->where('Kode', $data['Kode'])->exists()) {
            throw new AturanBisnisDilanggar("Kode persyaratan {$data['Kode']} sudah dipakai pada standar ini.");
        }

        $persyaratan = PersyaratanKepatuhan::create([
            'OrganisasiId' => $standar->OrganisasiId,
            'StandarKepatuhanId' => $standar->Id,
            'Kode' => $data['Kode'],
            'Nama' => $data['Nama'],
            'Deskripsi' => $data['Deskripsi'] ?? null,
            'BuktiYangDiperlukan' => $data['BuktiYangDiperlukan'] ?? null,
            'IntervalHari' => $data['IntervalHari'] ?? null,
        ]);
        $this->audit->catat('PersyaratanKepatuhan.Ditambahkan', 'StandarKepatuhan', $standar->Id, dataSesudah: $persyaratan->toArray());

        return $persyaratan;
    }

    public function hapusPersyaratan(StandarKepatuhan $standar, PersyaratanKepatuhan $persyaratan): void
    {
        if ($persyaratan->StandarKepatuhanId !== $standar->Id) {
            throw new AturanBisnisDilanggar('Persyaratan bukan bagian dari standar ini.');
        }
        if ($persyaratan->kepatuhanAset()->exists()) {
            throw new AturanBisnisDilanggar('Persyaratan yang sudah ditugaskan ke aset tidak dapat dihapus.');
        }

        $sebelum = $persyaratan->toArray();
        $persyaratan->delete();
        $this->audit->catat('PersyaratanKepatuhan.Dihapus', 'StandarKepatuhan', $standar->Id, dataSebelum: $sebelum);
    }
}
