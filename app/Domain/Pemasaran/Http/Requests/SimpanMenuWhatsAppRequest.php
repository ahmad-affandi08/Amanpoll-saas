<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanMenuWhatsAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Menu' => ['present', 'array', 'max:50'],
            'Menu.*.Kunci' => ['required', 'string', 'max:20', 'distinct'],
            'Menu.*.Label' => ['required', 'string', 'max:190'],
            'Menu.*.Balasan' => ['required', 'string', 'max:2000'],
            'Menu.*.Aktif' => ['required', 'boolean'],
        ];
    }
}
