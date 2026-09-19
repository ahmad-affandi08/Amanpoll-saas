<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Services;

use App\Domain\Kolaborasi\Domain\Repositories\NilaiKolomKustomRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\DefinisiKolomKustom;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\NilaiKolomKustom;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\Validator;

final class LayananNilaiKolomKustom
{
    public function __construct(private readonly NilaiKolomKustomRepository $nilaiKolomKustomRepository) {}

    public function simpan(DefinisiKolomKustom $definisi, string $entitasId, mixed $nilai): NilaiKolomKustom
    {
        $this->validasiNilai($definisi, $nilai);

        $baris = NilaiKolomKustom::query()
            ->where('DefinisiKolomKustomId', $definisi->Id)
            ->where('JenisEntitas', $definisi->JenisEntitas)
            ->where('EntitasId', $entitasId)
            ->first() ?? new NilaiKolomKustom([
                'DefinisiKolomKustomId' => $definisi->Id,
                'JenisEntitas' => $definisi->JenisEntitas,
                'EntitasId' => $entitasId,
            ]);

        $baris->Nilai = $nilai;

        return $this->nilaiKolomKustomRepository->simpan($baris);
    }

    private function validasiNilai(DefinisiKolomKustom $definisi, mixed $nilai): void
    {
        if ($definisi->Wajib && ($nilai === null || $nilai === '')) {
            throw new AturanBisnisDilanggar("Kolom '{$definisi->Label}' wajib diisi.");
        }

        if ($nilai === null) {
            return;
        }

        match ($definisi->TipeData) {
            'Angka' => $this->pastikan(is_numeric($nilai), $definisi, 'harus berupa angka'),
            'Boolean' => $this->pastikan(is_bool($nilai), $definisi, 'harus berupa boolean'),
            'Tanggal' => $this->pastikan(is_string($nilai) && strtotime($nilai) !== false, $definisi, 'harus berupa tanggal yang valid'),
            'Pilihan' => $this->pastikan(in_array($nilai, $definisi->Pilihan ?? [], true), $definisi, 'harus salah satu dari opsi yang tersedia'),
            'PilihanGanda' => $this->pastikan(
                is_array($nilai) && array_diff($nilai, $definisi->Pilihan ?? []) === [],
                $definisi,
                'harus berupa subset dari opsi yang tersedia',
            ),
            default => null,
        };

        if ($definisi->AturanValidasi) {
            $validator = Validator::make(['Nilai' => $nilai], ['Nilai' => $definisi->AturanValidasi]);
            if ($validator->fails()) {
                throw new AturanBisnisDilanggar("Kolom '{$definisi->Label}': ".$validator->errors()->first('Nilai'));
            }
        }
    }

    private function pastikan(bool $kondisi, DefinisiKolomKustom $definisi, string $pesan): void
    {
        if (!$kondisi) {
            throw new AturanBisnisDilanggar("Kolom '{$definisi->Label}' {$pesan}.");
        }
    }
}
