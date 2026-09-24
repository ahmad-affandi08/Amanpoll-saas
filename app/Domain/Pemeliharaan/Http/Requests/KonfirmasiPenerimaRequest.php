<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Domain\Pemeliharaan\Domain\Enums\HasilKonfirmasiPenerima;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Jawaban penerima dari akunnya sendiri (PRD 8.22): pelapor atau hasil pindai QR.
 *
 * `TandaTangan` hanya dikirim bila penerima belum punya tanda tangan tersimpan
 * ("gambar sekali lalu tersimpan"); aturan unggahannya sama dengan profil.
 */
final class KonfirmasiPenerimaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Hasil' => ['required', Rule::enum(HasilKonfirmasiPenerima::class)],
            'Alasan' => ['required_if:Hasil,'.HasilKonfirmasiPenerima::MasihBermasalah->value, 'nullable', 'string', 'max:2000'],
            'Ulasan' => ['nullable', 'string', 'max:2000'],
            'Penilaian' => ['nullable', 'integer', 'min:1', 'max:5'],
            'TandaTangan' => ['nullable', 'file', 'mimes:png,webp,jpg,jpeg', 'max:512'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'Alasan.required_if' => 'Ceritakan apa yang masih bermasalah.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['TandaTangan' => 'tanda tangan', 'Penilaian' => 'penilaian'];
    }
}
