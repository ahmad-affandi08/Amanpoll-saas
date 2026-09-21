<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Requests;

use App\Domain\SiklusAset\Domain\Enums\MetodePengajuanPenghapusanAset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPengajuanPenghapusanAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'Alasan' => ['required', 'string'],
            'MetodePenghapusan' => ['nullable', 'string', Rule::enum(MetodePengajuanPenghapusanAset::class)],
        ];
    }
}
