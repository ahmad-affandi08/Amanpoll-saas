<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jawaban penerima atas satu penyerahan pekerjaan (PRD 8.22).
 *
 * `PenggunaId` terisi bila penerima mengonfirmasi dari akunnya (pelapor, pindai QR);
 * kosong untuk tanda tangan di HP teknisi. `DicatatOleh` adalah akun yang mengirim
 * permintaannya. `TandaTanganBerkasId` menunjuk berkas yang dicap saat itu.
 */
final class KonfirmasiPenerimaPerintahKerja extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KonfirmasiPenerimaPerintahKerja';

    public $timestamps = false;

    protected $attributes = ['Berlaku' => false];

    protected $fillable = [
        'OrganisasiId',
        'PerintahKerjaId',
        'Metode',
        'Hasil',
        'PenggunaId',
        'DicatatOleh',
        'NamaPenerima',
        'JabatanPenerima',
        'TandaTanganBerkasId',
        'Alasan',
        'Ulasan',
        'Penilaian',
        'Berlaku',
        'KunciPerangkat',
        'DikonfirmasiPada',
    ];

    protected function casts(): array
    {
        return [
            'Penilaian' => 'integer',
            'Berlaku' => 'boolean',
            'DikonfirmasiPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<PerintahKerja, $this> */
    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'PenggunaId', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DicatatOleh', 'Id');
    }

    /** @return BelongsTo<Berkas, $this> */
    public function tandaTangan(): BelongsTo
    {
        return $this->belongsTo(Berkas::class, 'TandaTanganBerkasId', 'Id');
    }
}
