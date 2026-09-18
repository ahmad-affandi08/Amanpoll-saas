<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class StatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'aplikasi' => config('app.name'),
            'status' => 'ok',
            'versi' => '1.0.0',
            'waktu' => now()->toIso8601String(),
        ]);
    }
}
