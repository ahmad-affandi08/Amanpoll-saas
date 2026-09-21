<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

final class GantiKataSandiRequest extends FormRequest
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
            'KataSandiLama' => ['required', 'string'],
            'KataSandiBaru' => ['required', 'string', Password::min(8), 'confirmed'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if (! Hash::check((string) $this->input('KataSandiLama'), (string) $this->user('web')->KataSandi)) {
                $validator->errors()->add('KataSandiLama', 'Kata sandi lama tidak sesuai.');
            }
        });
    }
}
