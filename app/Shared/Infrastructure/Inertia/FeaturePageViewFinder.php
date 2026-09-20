<?php

namespace App\Shared\Infrastructure\Inertia;

use Illuminate\View\FileViewFinder;

/**
 * Custom view finder yang me-resolve nama komponen Inertia ke struktur feature-based.
 *
 * Konvensi: `Inertia::render('Feature/Page')` → `resources/js/features/Feature/pages/Page.tsx`
 *
 * Contoh:
 * - `Audit/Index`     → `features/Audit/pages/Index.tsx`
 * - `Kalibrasi/Jenis/Index` → `features/Kalibrasi/pages/Jenis/Index.tsx`
 * - `Auth/Login`      → `features/Auth/pages/Login.tsx`
 */
class FeaturePageViewFinder extends FileViewFinder
{
    /**
     * Ubah nama komponen sebelum mencari file.
     *
     * `Feature/rest/of/path` → `Feature/pages/rest/of/path`
     *
     * @param  string  $name
     * @return string[]
     */
    protected function getPossibleViewFiles($name): array
    {
        $segments = explode('/', $name);

        if (count($segments) >= 2) {
            $feature = array_shift($segments);
            $name = $feature.'/pages/'.implode('/', $segments);
        }

        return parent::getPossibleViewFiles($name);
    }
}
