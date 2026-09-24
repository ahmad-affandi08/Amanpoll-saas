<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Berkas impor aset untuk pratinjau, konfirmasi, dan unduhan daftar galat (PRD 8.4).
 *
 * Ketiga langkah menerima berkas yang sama; tidak ada yang disimpan di antaranya.
 */
final class ImporAsetRequest extends FormRequest
{
    public const UKURAN_MAKS_KB = 5120;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // `extensions` menentukan pembacanya; `mimes` memeriksa isi berkasnya,
            // bukan nama yang diberi pengunggah. XLSX adalah arsip zip, dan
            // sebagian berkas Excel memang dikenali sebagai zip biasa.
            'Berkas' => ['required', 'file', 'max:'.self::UKURAN_MAKS_KB, 'extensions:csv,xlsx', 'mimes:csv,txt,xlsx,zip'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'Berkas.extensions' => 'Berkas harus CSV atau XLSX. Unduh templat bila ragu.',
            'Berkas.mimes' => 'Isi berkas bukan CSV atau XLSX. Simpan ulang dari Excel sebagai CSV atau XLSX.',
            'Berkas.max' => 'Ukuran berkas paling besar 5 MB.',
        ];
    }

    /** @return 'csv'|'xlsx' */
    public function formatBerkas(): string
    {
        return strtolower((string) $this->berkas()?->getClientOriginalExtension()) === 'xlsx' ? 'xlsx' : 'csv';
    }

    public function namaBerkas(): string
    {
        return mb_substr((string) $this->berkas()?->getClientOriginalName(), 0, 255);
    }

    public function jalurBerkas(): string
    {
        return (string) $this->berkas()?->getRealPath();
    }

    private function berkas(): ?UploadedFile
    {
        $berkas = $this->file('Berkas');

        return $berkas instanceof UploadedFile ? $berkas : null;
    }
}
