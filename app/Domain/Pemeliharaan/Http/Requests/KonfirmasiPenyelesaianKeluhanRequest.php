<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Jawaban pelapor atas "Apakah sudah beres?" beserta penilaiannya (PRD 4.6). */
final class KonfirmasiPenyelesaianKeluhanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Beres' => ['required', 'boolean'],
            'Rating' => ['exclude_unless:Beres,true,1', 'required', 'integer', 'min:1', 'max:5'],
            'Ulasan' => ['required_if_declined:Beres', 'nullable', 'string', 'max:2000'],
            'Versi' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'Rating.required' => 'Beri nilai 1 sampai 5 bintang.',
            'Ulasan.required_if_declined' => 'Ceritakan apa yang masih bermasalah.',
        ];
    }
}
