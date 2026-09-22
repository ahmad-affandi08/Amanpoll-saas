<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Requests;

use App\Core\Entitas\RegistriEntitas;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanAlurPersetujuanRequest extends FormRequest
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
        $organisasiId = app(KonteksOrganisasi::class)->id();
        /** @var AlurPersetujuan|null $alurPersetujuan */
        $alurPersetujuan = $this->route('alurPersetujuan');

        return [
            'Kode' => [
                'nullable', 'string', 'max:80',
                Rule::unique('AlurPersetujuan', 'Kode')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->ignore($alurPersetujuan?->Id, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:180'],
            'JenisEntitas' => ['required', 'string', Rule::in(app(RegistriEntitas::class)->jenisDikenal())],
            'KondisiAktivasi' => ['nullable', 'array'],
        ];
    }
}
