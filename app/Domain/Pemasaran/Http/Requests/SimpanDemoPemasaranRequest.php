<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Application\Services\RegistriDatasetDemo;
use App\Domain\Pemasaran\Domain\Enums\FiturDibatasiDemo;
use App\Domain\Pemasaran\Domain\Enums\ModulDemo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DemoPemasaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SimpanDemoPemasaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $demo = $this->route('demo');
        $id = $demo instanceof DemoPemasaran ? $demo->getKey() : null;

        return [
            'Kode' => ['required', 'string', 'max:80', Rule::unique('DemoPemasaran', 'Kode')->ignore($id, 'Id')],
            'Nama' => ['required', 'string', 'max:180'],
            'Aktif' => ['required', 'boolean'],
            'Dataset' => ['required', 'string', Rule::in(app(RegistriDatasetDemo::class)->kode())],
            'ResetIntervalMenit' => ['required', 'integer', 'min:15', 'max:43200'],
            'ModulTampil' => ['present', 'array'],
            'ModulTampil.*' => [Rule::enum(ModulDemo::class), 'distinct'],
            'FiturDibatasi' => ['present', 'array'],
            'FiturDibatasi.*' => [Rule::enum(FiturDibatasiDemo::class), 'distinct'],
            'CtaLabel' => ['nullable', 'string', 'max:120'],
            'CtaUrl' => ['nullable', 'url', 'max:500'],
            'MaksDurasiMenit' => ['required', 'integer', 'min:5', 'max:480'],
            'MaksSesiSerentak' => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }

    /**
     * Demo yang dinyalakan tanpa satu pun modul hanya menampilkan layar kosong kepada calon pelanggan.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $modul = $this->input('ModulTampil');

            if ($this->boolean('Aktif') && (! is_array($modul) || $modul === [])) {
                $validator->errors()->add('ModulTampil', 'Demo yang aktif harus menampilkan setidaknya satu modul.');
            }
        }];
    }
}
