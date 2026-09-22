<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kolaborasi\Domain\Repositories\BerkasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

final class UnggahBerkas
{
    public function __construct(
        private readonly BerkasRepository $berkasRepository,
        private readonly KonteksOrganisasi $konteks,
    ) {}

    public function jalankan(UploadedFile $berkas, ?string $pengunggahId): Berkas
    {
        $disk = (string) config('amanpoll.disk_berkas', 'local');
        // Nama file dari klien tidak pernah dipakai untuk path fisik -- ULID + ekstensi hasil deteksi mime.
        $namaPenyimpanan = (string) Str::ulid().'.'.$berkas->extension();
        $direktori = 'berkas/'.$this->konteks->wajibId();

        $path = $berkas->storeAs($direktori, $namaPenyimpanan, $disk);
        if ($path === false) {
            throw new RuntimeException('Gagal menyimpan berkas.');
        }

        return $this->berkasRepository->simpan(new Berkas([
            'NamaAsli' => $berkas->getClientOriginalName(),
            'NamaPenyimpanan' => $namaPenyimpanan,
            'MediaPenyimpanan' => $disk,
            'LokasiPenyimpanan' => $path,
            'JenisMime' => $berkas->getMimeType(),
            'UkuranByte' => $berkas->getSize(),
            'HashSha256' => hash_file('sha256', $berkas->getRealPath()),
            'DiunggahOleh' => $pengunggahId,
        ]));
    }
}
