<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Services\PemakaianUnitPengelola;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanUnitOrganisasiRequest extends FormRequest
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
        /** @var UnitOrganisasi|null $unit */
        $unit = $this->route('unit');
        $unitId = $unit?->Id;

        return [
            'Kode' => ['nullable', 'string', 'max:50',
                Rule::unique('UnitOrganisasi', 'Kode')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->whereNull('DihapusPada')->ignore($unitId, 'Id')],
            'Nama' => ['required', 'string', 'max:180'],
            'Jenis' => ['required', 'string', 'max:60'],
            'IndukId' => ['nullable', 'string',
                Rule::exists('UnitOrganisasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'Email' => ['nullable', 'email', 'max:180'],
            'Telepon' => ['nullable', 'string', 'max:50'],
            'Status' => ['required', 'string', Rule::in(['Aktif', 'Nonaktif'])],
            'Urutan' => ['nullable', 'integer'],
            // Unit pengelola pemeliharaan (PRD 8.21). `sometimes`: pemanggil yang tidak
            // mengirimnya tidak diam-diam mencabut tanda yang sudah ada.
            'MengelolaAset' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Penolakan mencabut tanda Mengelola Aset tampil di isiannya, bukan halaman galat.
     *
     * UbahUnitOrganisasi tetap memeriksa ulang sebagai penjaga terakhir.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $unit = $this->route('unit');

                if (! $unit instanceof UnitOrganisasi || ! $unit->MengelolaAset || ! $this->has('MengelolaAset') || $this->boolean('MengelolaAset')) {
                    return;
                }

                $alasan = app(PemakaianUnitPengelola::class)->alasanTolakCabut($unit->Id);

                if ($alasan !== null) {
                    $validator->errors()->add('MengelolaAset', $alasan);
                }
            },
        ];
    }
}
