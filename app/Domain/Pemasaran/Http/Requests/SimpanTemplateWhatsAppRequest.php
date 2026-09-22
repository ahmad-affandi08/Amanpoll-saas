<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Application\Services\PerenderTemplateWhatsApp;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateWhatsAppPemasaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SimpanTemplateWhatsAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $template = $this->route('template');
        $id = $template instanceof TemplateWhatsAppPemasaran ? $template->getKey() : null;

        return [
            'Kode' => [
                'required', 'string', 'max:80',
                Rule::unique('TemplateWhatsAppPemasaran', 'Kode')->ignore($id, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:190'],
            'Bahasa' => ['required', 'string', 'max:20'],
            'Kategori' => ['required', 'string', 'max:40'],
            'IsiTeks' => ['required', 'string', 'max:4000'],
            'Aktif' => ['required', 'boolean'],
        ];
    }

    /**
     * Variabel salah ketik kembali sebagai galat field, bukan halaman galat.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $asing = app(PerenderTemplateWhatsApp::class)->variabelAsing((string) $this->input('IsiTeks'));

            if ($asing !== []) {
                $validator->errors()->add(
                    'IsiTeks',
                    'Variabel tidak dikenal: '.implode(', ', $asing).'.',
                );
            }
        }];
    }
}
