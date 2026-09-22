<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Panduan pemakaian Amanpoll untuk pengguna.
 *
 * Isinya ada di sisi React; controller ini hanya memastikan slug yang diminta
 * memang dikenal, supaya alamat karangan berujung 404 alih-alih halaman kosong.
 */
final class DokumentasiController extends Controller
{
    /**
     * Urutannya sama dengan resources/js/features/Dokumentasi/daftar-halaman.ts,
     * dan kesamaannya dijaga oleh DokumentasiTest.
     *
     * @var list<string>
     */
    public const HALAMAN = [
        'pengenalan',
        'persiapan',
        'organisasi',
        'pengguna',
        'penomoran',
        'master-aset',
        'aset',
        'siklus-aset',
        'keluhan',
        'perintah-kerja',
        'preventif',
        'kalibrasi',
        'persediaan',
        'mutasi-stok',
        'pengadaan',
        'persetujuan',
        'lanjutan',
    ];

    public function __invoke(?string $halaman = null): Response
    {
        $halaman ??= self::HALAMAN[0];

        if (! in_array($halaman, self::HALAMAN, true)) {
            throw new NotFoundHttpException("Halaman dokumentasi '{$halaman}' tidak ada.");
        }

        return Inertia::render('Dokumentasi/Index', ['halaman' => $halaman]);
    }
}
