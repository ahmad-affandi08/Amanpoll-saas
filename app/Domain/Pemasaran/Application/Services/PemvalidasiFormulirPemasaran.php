<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\JenisFieldFormulir;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FieldFormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use Illuminate\Support\Facades\Validator;

/**
 * Validasi jawaban formulir yang bentuknya baru diketahui saat dijalankan
 * (MARKETING.md 10).
 *
 * Aturannya disusun dari definisi field, bukan ditulis tetap di satu
 * FormRequest: susunan field berubah tiap kali seseorang menyunting formulir di
 * konsol, dan aturan yang tertinggal di belakang berarti field wajib yang tidak
 * pernah diperiksa.
 *
 * Kunci yang tidak dikenal dibuang, bukan ditolak. Pengunjung tidak menentukan
 * field apa yang ada, jadi kelebihan kunci adalah masalah pengirimnya — tetapi
 * meneruskannya ke penyimpanan berarti menerima data sembarang dari luar.
 */
final class PemvalidasiFormulirPemasaran
{
    /**
     * @param  array<string, mixed>  $jawaban
     * @return array<string, mixed>
     */
    public function jalankan(FormulirPemasaran $formulir, array $jawaban): array
    {
        $formulir->loadMissing('field');

        $aturan = [];
        $label = [];

        foreach ($formulir->field as $field) {
            if ($field->Jenis->terisiOtomatis()) {
                continue;
            }

            $aturan[$field->Kode] = $this->aturanUntuk($field);
            $label[$field->Kode] = $field->Label;

            if ($field->Jenis->butuhPilihan() && $field->Pilihan !== null) {
                $kunci = $field->Jenis === JenisFieldFormulir::PilihanGanda
                    ? $field->Kode.'.*'
                    : $field->Kode;
                $aturan[$kunci] = ['in:'.implode(',', array_map(strval(...), $field->Pilihan))];
            }
        }

        $dikenal = array_intersect_key($jawaban, $label);

        /** @var array<string, mixed> $tervalidasi */
        $tervalidasi = Validator::make($dikenal, $aturan, [], $label)->validate();

        return $tervalidasi;
    }

    /** @return list<string> */
    private function aturanUntuk(FieldFormulirPemasaran $field): array
    {
        // Kotak centang yang wajib berarti harus dicentang, bukan sekadar
        // hadir — bentuk yang dipakai persetujuan.
        $kehadiran = match (true) {
            $field->Wajib && $field->Jenis === JenisFieldFormulir::Persetujuan => 'accepted',
            $field->Wajib && $field->Jenis === JenisFieldFormulir::KotakCentang => 'accepted',
            $field->Wajib => 'required',
            default => 'nullable',
        };

        return [$kehadiran, ...explode('|', $field->Jenis->aturanValidasi())];
    }
}
