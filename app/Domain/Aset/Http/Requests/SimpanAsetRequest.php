<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use App\Core\Izin\PemeriksaLingkupBaris;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Platform\Http\Requests\UnitPengelolaSah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'KodeAset' => ['nullable', 'string', 'max:100',
                Rule::unique('Aset', 'KodeAset')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->ignore($asetId, 'Id')],
            'Nama' => ['required', 'string', 'max:200'],
            'KategoriAsetId' => ['required', 'string',
                Rule::exists('KategoriAset', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'ModelAsetId' => ['nullable', 'string',
                Rule::exists('ModelAset', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            // Nomenklatur standar Kemenkes untuk aset ini; opsional karena
            // katalognya baru terisi setelah impor ASPAK dijalankan.
            'AlkesAspakId' => ['nullable', 'string',
                Rule::exists('AlkesAspak', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'PenyediaId' => ['nullable', 'string',
                Rule::exists('Penyedia', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'UnitOrganisasiId' => ['nullable', 'string',
                Rule::exists('UnitOrganisasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            // Bagian yang memeliharanya (PRD 8.21); nilai tersimpan tetap sah walau unitnya kini nonaktif.
            'UnitPengelolaId' => UnitPengelolaSah::aturan($aset?->UnitPengelolaId),
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

    /**
     * Aset yang disimpan harus tetap terlihat oleh penyimpannya: semantik
     * ScopeLingkup yang sama dengan daftar aset dan impor aset (PRD 8.21), jadi
     * staf berlingkup satu ruangan tidak bisa membuat atau memindahkan aset ke
     * ruangan lain. Pengguna tanpa batas tidak terpengaruh.
     *
     * Kode aset juga unik terhadap aset yang diarsipkan, karena indeks
     * `UqAsetKode` menghitungnya; tanpa itu kode lama berujung galat basis data.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $pengguna = $this->user();

                if ($pengguna === null || $validator->errors()->hasAny(['LokasiId', 'UnitOrganisasiId', 'UnitPengelolaId'])) {
                    return;
                }

                /** @var Aset|null $aset */
                $aset = $this->route('aset');
                $calon = $aset !== null ? $aset->replicate() : new Aset;
                $calon->setAttribute('OrganisasiId', $aset->OrganisasiId ?? app(KonteksOrganisasi::class)->wajibId());

                foreach (['LokasiId', 'UnitOrganisasiId', 'UnitPengelolaId'] as $ruas) {
                    if ($this->exists($ruas)) {
                        $calon->setAttribute($ruas, $this->input($ruas));
                    }
                }

                if (! app(PemeriksaLingkupBaris::class)->mencakup((string) $pengguna->getAuthIdentifier(), $calon)) {
                    $validator->errors()->add('LokasiId', 'Di luar lingkup akses Anda. Lokasi, unit organisasi, atau unit pengelolanya harus termasuk lingkup Anda.');
                }
            },
        ];
    }
}
