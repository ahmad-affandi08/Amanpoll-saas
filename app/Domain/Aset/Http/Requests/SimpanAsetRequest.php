<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanAsetRequest extends FormRequest
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
        /** @var Aset|null $aset */
        $aset = $this->route('aset');
        $asetId = $aset?->Id;

        return [
            'KodeAset' => ['required', 'string', 'max:100',
                Rule::unique('Aset', 'KodeAset')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->whereNull('DihapusPada')->ignore($asetId, 'Id')],
            'Nama' => ['required', 'string', 'max:200'],
            'KategoriAsetId' => ['required', 'string',
                Rule::exists('KategoriAset', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'ModelAsetId' => ['nullable', 'string',
                Rule::exists('ModelAset', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'PenyediaId' => ['nullable', 'string',
                Rule::exists('Penyedia', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'UnitOrganisasiId' => ['nullable', 'string',
                Rule::exists('UnitOrganisasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'LokasiId' => ['nullable', 'string',
                Rule::exists('Lokasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'NomorSeri' => ['nullable', 'string', 'max:160'],
            'NomorInventaris' => ['nullable', 'string', 'max:160'],
            'NomorRegistrasiEksternal' => ['nullable', 'string', 'max:160'],
            'TanggalPerolehan' => ['nullable', 'date'],
            'TanggalMulaiOperasi' => ['nullable', 'date'],
            'TanggalAkhirOperasi' => ['nullable', 'date', 'after_or_equal:TanggalMulaiOperasi'],
            'HargaPerolehan' => ['nullable', 'numeric', 'min:0'],
            'NilaiResidu' => ['nullable', 'numeric', 'min:0'],
            'MataUang' => ['nullable', 'string', 'size:3'],
            'SumberDana' => ['nullable', 'string', 'max:120'],
            'MetodePenyusutan' => ['nullable', 'string', 'max:40'],
            'UmurManfaatBulan' => ['nullable', 'integer', 'min:1'],
            'Status' => ['required', 'string', Rule::enum(StatusAset::class)],
            'Kondisi' => ['required', 'string', Rule::enum(KondisiAset::class)],
            'TingkatKritis' => ['required', 'string', Rule::enum(TingkatKritisAset::class)],
            'NfcUid' => ['nullable', 'string', 'max:255'],
            'KodeBatang' => ['nullable', 'string', 'max:255'],
            'Catatan' => ['nullable', 'string'],
            'Versi' => ['nullable', 'integer'],
        ];
    }
}
