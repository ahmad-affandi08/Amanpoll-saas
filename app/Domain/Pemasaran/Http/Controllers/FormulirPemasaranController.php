<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Services\BerkasLeadMagnet;
use App\Domain\Pemasaran\Domain\Enums\JenisFieldFormulir;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Http\Requests\SimpanBerkasLeadMagnetRequest;
use App\Domain\Pemasaran\Http\Requests\SimpanFormulirPemasaranRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FieldFormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanFormulir;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Form builder di konsol platform (MARKETING.md 10). */
final class FormulirPemasaranController extends Controller
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    public function index(): Response
    {
        $formulir = FormulirPemasaran::query()
            ->with('field')
            ->withCount('pengiriman')
            ->orderBy('Nama')
            ->get();

        return Inertia::render('Pemasaran/Formulir/Index', [
            'formulir' => $formulir->map(fn (FormulirPemasaran $satu): array => $this->ringkas($satu))->all(),
            'pilihan' => [
                'Jenis' => array_column(JenisFieldFormulir::cases(), 'value'),
                'Sumber' => array_column(SumberProspek::cases(), 'value'),
                'Kampanye' => Kampanye::query()
                    ->orderBy('Nama')
                    ->get()
                    ->map(fn (Kampanye $satu): array => ['Id' => $satu->Id, 'Nama' => $satu->Nama])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function show(FormulirPemasaran $formulir): Response
    {
        $formulir->load('field');

        $pengiriman = PengirimanFormulir::query()
            ->with('prospek')
            ->where('FormulirPemasaranId', $formulir->Id)
            ->orderByDesc('DikirimPada')
            ->limit(100)
            ->get();

        return Inertia::render('Pemasaran/Formulir/Show', [
            'formulir' => $this->ringkas($formulir),
            'pengiriman' => $pengiriman->map(fn (PengirimanFormulir $satu): array => [
                'Id' => $satu->Id,
                'Data' => $satu->Data,
                'Persetujuan' => $satu->Persetujuan,
                'ProspekId' => $satu->ProspekId,
                'ProspekNama' => $satu->prospek?->Nama,
                'DikirimPada' => $satu->DikirimPada->toIso8601String(),
            ])->all(),
        ]);
    }

    public function store(SimpanFormulirPemasaranRequest $request): RedirectResponse
    {
        $formulir = $this->simpan(null, $request->validated());

        $this->audit->catat('FormulirPemasaran.Dibuat', 'FormulirPemasaran', $formulir->Id, dataSesudah: [
            'Kode' => $formulir->Kode,
            'Nama' => $formulir->Nama,
        ]);

        return back()->with('sukses', 'Formulir berhasil dibuat.');
    }

    public function update(SimpanFormulirPemasaranRequest $request, FormulirPemasaran $formulir): RedirectResponse
    {
        $sebelum = ['Kode' => $formulir->Kode, 'Nama' => $formulir->Nama, 'Aktif' => $formulir->Aktif];
        $formulir = $this->simpan($formulir, $request->validated());

        $this->audit->catat(
            'FormulirPemasaran.Diubah',
            'FormulirPemasaran',
            $formulir->Id,
            dataSebelum: $sebelum,
            dataSesudah: ['Kode' => $formulir->Kode, 'Nama' => $formulir->Nama, 'Aktif' => $formulir->Aktif],
        );

        return back()->with('sukses', 'Formulir berhasil diperbarui.');
    }

    public function simpanBerkas(
        SimpanBerkasLeadMagnetRequest $request,
        FormulirPemasaran $formulir,
        BerkasLeadMagnet $berkas,
    ): RedirectResponse {
        $berkas->simpan($formulir, $request->berkas());

        $this->audit->catat('FormulirPemasaran.BerkasDiunggah', 'FormulirPemasaran', $formulir->Id, dataSesudah: [
            'Kode' => $formulir->Kode,
            'NamaBerkas' => $formulir->BerkasNamaAsli,
            'UkuranByte' => $formulir->BerkasUkuranByte,
        ]);

        return back()->with('sukses', 'Berkas lead magnet berhasil diunggah.');
    }

    public function hapusBerkas(FormulirPemasaran $formulir, BerkasLeadMagnet $berkas): RedirectResponse
    {
        $sebelum = $formulir->BerkasNamaAsli;
        $berkas->hapus($formulir);

        $this->audit->catat(
            'FormulirPemasaran.BerkasDihapus',
            'FormulirPemasaran',
            $formulir->Id,
            dataSebelum: ['NamaBerkas' => $sebelum],
        );

        return back()->with('sukses', 'Berkas lead magnet dihapus.');
    }

    /**
     * Field ditulis ulang seluruhnya setiap penyimpanan. Aman karena jawaban
     * yang sudah masuk disimpan terpisah pada `PengirimanFormulir`: menghapus
     * satu field tidak menghapus apa pun yang pernah dikirim orang.
     *
     * @param  array<string, mixed>  $data
     */
    private function simpan(?FormulirPemasaran $formulir, array $data): FormulirPemasaran
    {
        return $this->transaksi->jalankan(function () use ($formulir, $data): FormulirPemasaran {
            $atribut = [
                'Kode' => $data['Kode'],
                'Nama' => $data['Nama'],
                'PesanSukses' => $data['PesanSukses'] ?? null,
                'UrlRedirect' => $data['UrlRedirect'] ?? null,
                'Sumber' => $data['Sumber'],
                'KampanyeId' => $data['KampanyeId'] ?? null,
                'Tag' => $data['Tag'] ?? [],
                'PemicuOtomasi' => $data['PemicuOtomasi'] ?? null,
                'UrlWebhook' => $data['UrlWebhook'] ?? null,
                'WajibPersetujuan' => (bool) ($data['WajibPersetujuan'] ?? true),
                'CaptchaAktif' => (bool) ($data['CaptchaAktif'] ?? false),
                'Aktif' => (bool) ($data['Aktif'] ?? true),
            ];

            if ($formulir === null) {
                $formulir = FormulirPemasaran::create($atribut);
            } else {
                $formulir->update($atribut);
            }

            /** @var list<array<string, mixed>> $field */
            $field = $data['Field'] ?? [];

            FieldFormulirPemasaran::query()->where('FormulirPemasaranId', $formulir->Id)->delete();

            foreach ($field as $urutan => $satu) {
                FieldFormulirPemasaran::create([
                    'FormulirPemasaranId' => $formulir->Id,
                    'Kode' => $satu['Kode'],
                    'Label' => $satu['Label'],
                    'Jenis' => $satu['Jenis'],
                    'Wajib' => (bool) ($satu['Wajib'] ?? false),
                    'Urutan' => $urutan,
                    'Pilihan' => $satu['Pilihan'] ?? null,
                    'Placeholder' => $satu['Placeholder'] ?? null,
                    'Bantuan' => $satu['Bantuan'] ?? null,
                ]);
            }

            return $formulir;
        });
    }

    /** @return array<string, mixed> */
    private function ringkas(FormulirPemasaran $formulir): array
    {
        return [
            'Id' => $formulir->Id,
            'Kode' => $formulir->Kode,
            'Nama' => $formulir->Nama,
            'PesanSukses' => $formulir->PesanSukses,
            'UrlRedirect' => $formulir->UrlRedirect,
            'Sumber' => $formulir->Sumber,
            'KampanyeId' => $formulir->KampanyeId,
            'Tag' => $formulir->Tag ?? [],
            'PemicuOtomasi' => $formulir->PemicuOtomasi,
            'UrlWebhook' => $formulir->UrlWebhook,
            'WajibPersetujuan' => $formulir->WajibPersetujuan,
            'CaptchaAktif' => $formulir->CaptchaAktif,
            'Aktif' => $formulir->Aktif,
            'BerkasNamaAsli' => $formulir->BerkasNamaAsli,
            'BerkasUkuranByte' => $formulir->BerkasUkuranByte,
            'PunyaBerkas' => $formulir->punyaBerkas(),
            'JumlahPengiriman' => (int) ($formulir->pengiriman_count ?? 0),
            'Field' => $formulir->field
                ->map(fn (FieldFormulirPemasaran $field): array => [
                    'Kode' => $field->Kode,
                    'Label' => $field->Label,
                    'Jenis' => $field->Jenis->value,
                    'Wajib' => $field->Wajib,
                    'Pilihan' => $field->Pilihan ?? [],
                    'Placeholder' => $field->Placeholder,
                    'Bantuan' => $field->Bantuan,
                ])
                ->values()
                ->all(),
        ];
    }
}
