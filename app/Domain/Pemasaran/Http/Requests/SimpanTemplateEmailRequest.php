<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Application\Services\PerenderTemplateEmail;
use App\Domain\Pemasaran\Domain\Enums\JenisTemplateEmail;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateEmailPemasaran;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class SimpanTemplateEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $template = $this->route('template');
        $id = $template instanceof TemplateEmailPemasaran ? $template->getKey() : null;

        return [
            'Kode' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('TemplateEmailPemasaran', 'Kode')->ignore($id, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:190'],
            'Jenis' => ['required', new Enum(JenisTemplateEmail::class)],
            'Subjek' => ['required', 'string', 'max:255'],
            'IsiHtml' => ['required', 'string', 'max:60000'],
            'IsiTeks' => ['nullable', 'string', 'max:60000'],
            'Aktif' => ['boolean'],
        ];
    }

    /**
     * Variabel salah ketik dilaporkan sebagai galat field, bukan halaman error.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $perender = app(PerenderTemplateEmail::class);

                $asing = $perender->variabelAsing(
                    (string) $this->input('Subjek'),
                    (string) $this->input('IsiHtml'),
                    (string) $this->input('IsiTeks'),
                );

                if ($asing !== []) {
                    $validator->errors()->add('IsiHtml', 'Variabel tidak dikenal: '.implode(', ', $asing)
                        .'. Yang tersedia: '.implode(', ', $perender->variabelDikenal()).'.');
                }
            },
        ];
    }
}
