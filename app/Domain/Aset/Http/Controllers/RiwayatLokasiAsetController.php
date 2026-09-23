<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\PindahkanLokasiAset;
use App\Domain\Aset\Http\Requests\SimpanRiwayatLokasiAsetRequest;
use App\Domain\Aset\Http\Resources\RiwayatLokasiAsetResource;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatLokasiAset;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RiwayatLokasiAsetController extends Controller
{
    /**
     * Riwayat perpindahan satu aset, bagian dari kartu riwayat alatnya.
     *
     * Terpisah dari daftar aset karena pertanyaannya berbeda: yang ini menelusuri
     * satu alat sepanjang waktu, bukan seluruh alat pada satu saat.
     */
    public function ekspor(Request $request, Aset $aset, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('view', $aset);

        return $ekspor->unduh(
            // getQuery(): relasinya HasMany, sedangkan yang dibaca EksporDaftar
            // adalah Builder biasa.
            $aset->riwayatLokasi()
                ->with(['lokasiAsal', 'lokasiTujuan', 'dipindahkanOleh'])
                ->orderBy('DipindahkanPada')
                ->orderBy('Id')
                ->getQuery(),
            [
                KolomEkspor::tanggal('Dipindahkan', 'DipindahkanPada', 'Y-m-d H:i'),
                KolomEkspor::dari('Lokasi Asal', fn (RiwayatLokasiAset $r): string => BacaRelasi::teks(BacaRelasi::model($r, 'lokasiAsal'), 'Nama')),
                KolomEkspor::dari('Lokasi Tujuan', fn (RiwayatLokasiAset $r): string => BacaRelasi::teks(BacaRelasi::model($r, 'lokasiTujuan'), 'Nama')),
                KolomEkspor::atribut('Jenis Perpindahan', 'JenisPerpindahan'),
                KolomEkspor::atribut('Alasan', 'Alasan'),
                KolomEkspor::dari('Dipindahkan Oleh', fn (RiwayatLokasiAset $r): string => BacaRelasi::teks(BacaRelasi::model($r, 'dipindahkanOleh'), 'Nama')),
            ],
            'riwayat-lokasi-'.strtolower((string) $aset->KodeAset),
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Aset $aset): AnonymousResourceCollection
    {
        $this->authorize('view', $aset);

        $riwayat = $aset->riwayatLokasi()->with(['lokasiAsal', 'lokasiTujuan', 'dipindahkanOleh'])->get();

        return RiwayatLokasiAsetResource::collection($riwayat);
    }

    public function store(SimpanRiwayatLokasiAsetRequest $request, Aset $aset, PindahkanLokasiAset $aksi): RedirectResponse
    {
        $this->authorize('update', $aset);

        $data = $request->validated();
        $aksi->jalankan($aset, $data['LokasiTujuanId'] ?? null, $data['Alasan'] ?? null, $request->user('web')->Id);

        return back()->with('sukses', 'Lokasi aset berhasil dipindahkan.');
    }
}
