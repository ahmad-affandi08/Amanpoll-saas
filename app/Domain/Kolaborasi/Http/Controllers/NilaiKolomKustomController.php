<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Controllers;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Kolaborasi\Application\Services\LayananNilaiKolomKustom;
use App\Domain\Kolaborasi\Http\Requests\SimpanNilaiKolomKustomRequest;
use App\Domain\Kolaborasi\Http\Resources\NilaiKolomKustomResource;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\DefinisiKolomKustom;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\NilaiKolomKustom;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class NilaiKolomKustomController extends Controller
{
    public function __construct(private readonly RegistriEntitas $registriEntitas) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'jenisEntitas' => ['required', 'string'],
            'entitasId' => ['required', 'string'],
        ]);

        $this->registriEntitas->cariEntitas($data['jenisEntitas'], $data['entitasId']);
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $data['jenisEntitas']);

        $nilai = NilaiKolomKustom::query()
            ->with('definisiKolomKustom')
            ->where('JenisEntitas', $data['jenisEntitas'])
            ->where('EntitasId', $data['entitasId'])
            ->limit(BatasDaftar::MAKS)
            ->get();

        return NilaiKolomKustomResource::collection($nilai);
    }

    public function store(SimpanNilaiKolomKustomRequest $request, LayananNilaiKolomKustom $layanan): RedirectResponse
    {
        $data = $request->validated();

        $definisi = DefinisiKolomKustom::query()
            ->where('Id', $data['DefinisiKolomKustomId'])
            ->where('JenisEntitas', $data['JenisEntitas'])
            ->first();

        if (! $definisi) {
            throw new DataTidakDitemukan('Definisi kolom kustom tidak ditemukan.');
        }

        $this->registriEntitas->cariEntitas($data['JenisEntitas'], $data['EntitasId']);
        $this->registriEntitas->pastikanBolehKelola($request->user('web'), $data['JenisEntitas']);

        $layanan->simpan($definisi, $data['EntitasId'], $data['Nilai'] ?? null);

        return back()->with('sukses', 'Nilai kolom kustom berhasil disimpan.');
    }
}
