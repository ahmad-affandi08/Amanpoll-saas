<?php

declare(strict_types=1);

namespace App\Core\Kesehatan;

use RuntimeException;

/**
 * Dilempar saat pemeriksaan kesehatan menemukan ketergantungan yang mati.
 *
 * Sengaja bukan turunan PengecualianDomain: ini kegagalan infrastruktur, bukan
 * aturan bisnis, dan tidak boleh dirender sebagai halaman kesalahan Inertia.
 */
final class KesehatanSistemTerganggu extends RuntimeException {}
