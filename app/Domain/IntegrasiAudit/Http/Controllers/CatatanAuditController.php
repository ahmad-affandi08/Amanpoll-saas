<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Http\Controllers;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Domain\IntegrasiAudit\Http\Resources\CatatanAuditResource;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CatatanAuditController extends Controller
{
    public function __construct(private readonly KalenderOrganisasi $kalender) {}

    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<CatatanAudit>
     */
    private function kueriTersaring(array $filter): Builder
    {
        return CatatanAudit::query()
            ->with('pengguna')
            ->when($filter['jenisEntitas'] ?? null, fn ($q, $v) => $q->where('JenisEntitas', $v))
            ->when($filter['entitasId'] ?? null, fn ($q, $v) => $q->where('EntitasId', $v))
            ->when($filter['penggunaId'] ?? null, fn ($q, $v) => $q->where('PenggunaId', $v))
            ->when($filter['aksi'] ?? null, fn ($q, $v) => $q->where('Aksi', 'like', "%{$v}%"))
            ->when($filter['dariTanggal'] ?? null, fn ($q, $v) => $q->where('DibuatPada', '>=', $this->kalender->awalHari((string) $v)))
            ->when($filter['sampaiTanggal'] ?? null, fn ($q, $v) => $q->where('DibuatPada', '<', $this->kalender->awalHariBerikutnya((string) $v)))
            ->orderByDesc('DibuatPada')
            ->orderBy('Id');
    }

    /**
     * Jejak audit dalam rentang yang dipilih.
     *
     * Isi DataSebelum/DataSesudah sengaja tidak ikut: keduanya berisi cuplikan
     * baris apa adanya, termasuk kolom yang tidak pernah tampil di layar, dan
     * berkas yang beredar di luar aplikasi bukan tempatnya.
     */
    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', CatatanAudit::class);

        $filter = $request->validate([
            'jenisEntitas' => ['nullable', 'string'],
            'entitasId' => ['nullable', 'string'],
            'penggunaId' => ['nullable', 'string'],
            'aksi' => ['nullable', 'string'],
            'dariTanggal' => ['nullable', 'date'],
            'sampaiTanggal' => ['nullable', 'date'],
        ]);

        return $ekspor->unduh(
            $this->kueriTersaring($filter),
            [
                KolomEkspor::tanggal('Waktu', 'DibuatPada', 'Y-m-d H:i:s'),
                KolomEkspor::atribut('Aksi', 'Aksi'),
                KolomEkspor::atribut('Jenis Entitas', 'JenisEntitas'),
                KolomEkspor::atribut('Entitas', 'EntitasId'),
                KolomEkspor::dari('Pengguna', fn (CatatanAudit $c): string => BacaRelasi::teks(BacaRelasi::model($c, 'pengguna'), 'Nama')),
                KolomEkspor::atribut('Alamat IP', 'AlamatIp'),
            ],
            'jejak-audit',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CatatanAudit::class);

        $filter = $request->validate([
            'jenisEntitas' => ['nullable', 'string'],
            'entitasId' => ['nullable', 'string'],
            'penggunaId' => ['nullable', 'string'],
            'aksi' => ['nullable', 'string'],
            'dariTanggal' => ['nullable', 'date'],
            'sampaiTanggal' => ['nullable', 'date'],
        ]);

        $catatan = $this->kueriTersaring($filter)->paginate(25)->withQueryString();

        return Inertia::render('Audit/Index', [
            'catatan' => CatatanAuditResource::collection($catatan),
            'filter' => $filter,
            'jenisEntitasTersedia' => CatatanAudit::query()->distinct()->orderBy('JenisEntitas')->pluck('JenisEntitas'),
        ]);
    }
}
