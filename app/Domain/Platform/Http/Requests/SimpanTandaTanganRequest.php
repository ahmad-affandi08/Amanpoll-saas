<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Gambar tanda tangan dari pad tanda tangan (PRD 8.22). */
final class SimpanTandaTanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'TandaTangan' => ['required', 'file', 'mimes:png,webp,jpg,jpeg', 'max:512'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['TandaTangan' => 'tanda tangan'];
    }
}
