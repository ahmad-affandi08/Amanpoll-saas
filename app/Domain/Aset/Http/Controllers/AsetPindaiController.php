<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Http\RedirectResponse;

final class AsetPindaiController extends Controller
{
    /**
     * Scan resolver: menerima kode dari QR/barcode/NFC fisik yang ditempel
     * ke aset lalu mengarahkan ke halaman detailnya. Satu titik masuk untuk
     * ketiga jenis identifier supaya perangkat pemindai apa pun (kamera QR,
     * pembaca barcode, pembaca NFC) bisa memakai URL yang sama.
     */
    public function tampilkan(string $kode): RedirectResponse
    {
        $aset = Aset::query()
            ->where('KodeQr', $kode)
            ->orWhere('KodeBatang', $kode)
            ->orWhere('NfcUid', $kode)
            ->first();

        if (! $aset) {
            throw new DataTidakDitemukan("Aset dengan kode '{$kode}' tidak ditemukan.");
        }

        $this->authorize('view', $aset);

        return redirect("/aset/{$aset->Id}");
    }
}
