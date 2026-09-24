import { Head, Link, router } from '@inertiajs/react';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import type {
  AsetRingkas,
  KeluhanRingkas,
  LokasiRingkas,
  PerintahKerja,
  StatusPerintahKerja,
} from '@/features/PerintahKerja/types';
import {
  DAFTAR_PRIORITAS,
  VARIAN_PRIORITAS_PERINTAH_KERJA,
  VARIAN_STATUS_PERINTAH_KERJA,
} from '@/features/PerintahKerja/status';
import { rutePerintahKerja } from '@/features/PerintahKerja/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import type { Paginasi } from '@/types/global';
import { TANPA_PILIHAN } from '@/lib/pilihan';
import { DialogBuatPerintahKerja } from '@/features/PerintahKerja/components/DialogBuatPerintahKerja';
import type { AturanWajib } from '@/lib/aturan-wajib';
import type { UnitPengelolaRingkas } from '@/features/UnitOrganisasi/types';

interface Props {
  perintahKerja: Paginasi<PerintahKerja>;
  keluhan: KeluhanRingkas[];
  aset: AsetRingkas[];
  lokasi: LokasiRingkas[];
  filter: { status?: string; prioritas?: string; unitPengelola?: string };
  dapatMengelola: boolean;
  /** Organisasi memakai unit pengelola (PRD 8.21); bila tidak, halaman tampil seperti sebelumnya. */
  unitPengelolaDipakai: boolean;
  /** Unit pengelola aktif untuk formulir buat. */
  pilihanUnitPengelola: UnitPengelolaRingkas[];
  /** Termasuk unit nonaktif: tiket lama bisa saja milik unit yang kini nonaktif. */
  saringanUnitPengelola: UnitPengelolaRingkas[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const DAFTAR_STATUS: StatusPerintahKerja[] = [
  'Draf',
  'Terjadwal',
  'Ditugaskan',
  'Diterima',
  'Dikerjakan',
  'MenungguSukuCadang',
  'MenungguPenyedia',
  'Dijeda',
  'MenungguVerifikasi',
  'Selesai',
  'Ditutup',
  'Dibatalkan',
];

function formatTanggal(nilai: string | null): string {
  return nilai ? new Date(nilai).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
}

function formatRupiah(nilai: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(nilai);
}

/** Hanya penyaring yang benar-benar terisi yang ikut dibawa saat berpindah halaman. */
function filterAktif(filter: Props['filter']): Record<string, string> {
  return Object.fromEntries(
    Object.entries(filter).filter((pasangan): pasangan is [string, string] => Boolean(pasangan[1])),
  );
}

export default function PerintahKerjaIndex({
  perintahKerja,
  keluhan,
  aset,
  lokasi,
  filter,
  dapatMengelola,
  wajib,
  unitPengelolaDipakai,
  pilihanUnitPengelola,
  saringanUnitPengelola,
}: Props) {
  const filterData = (kunci: 'status' | 'prioritas' | 'unitPengelola', nilai: string) => {
    router.get(
      rutePerintahKerja.index,
      { ...filter, [kunci]: nilai === TANPA_PILIHAN ? undefined : nilai },
      { preserveState: true, replace: true },
    );
  };

  return (
    <KerangkaAplikasi>
      <Head title="Perintah Kerja" />
      <KepalaHalaman
        judul="Perintah Kerja"
        deskripsi={
          dapatMengelola
            ? 'Tugaskan teknisi, pantau jam kerja, catat downtime aset, dan kontrol biaya perbaikan.'
            : 'Daftar penugasan perintah kerja dan pencatatan operasional Anda.'
        }
        aksi={
          <>
            <TombolEkspor url={rutePerintahKerja.ekspor} filter={filter as Record<string, string>} />
            {dapatMengelola && (
              <DialogBuatPerintahKerja
                keluhan={keluhan}
                aset={aset}
                lokasi={lokasi}
                wajib={wajib.perintahKerja}
                pilihanUnitPengelola={pilihanUnitPengelola}
                unitPengelolaDipakai={unitPengelolaDipakai}
              />
            )}
          </>
        }
        className="mb-5"
      />

      <div className="mb-4 flex flex-wrap items-center gap-2">
        <Select value={filter.status ?? TANPA_PILIHAN} onValueChange={(val) => filterData('status', val)}>
          <SelectTrigger className="w-full cursor-pointer sm:w-44" aria-label="Saring status">
            <SelectValue placeholder="Semua status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={TANPA_PILIHAN} className="cursor-pointer">
              Semua status
            </SelectItem>
            {DAFTAR_STATUS.map((s) => (
              <SelectItem key={s} value={s} className="cursor-pointer">
                {s}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        <Select
          value={filter.prioritas ?? TANPA_PILIHAN}
          onValueChange={(val) => filterData('prioritas', val)}
        >
          <SelectTrigger className="w-full cursor-pointer sm:w-44" aria-label="Saring prioritas">
            <SelectValue placeholder="Semua prioritas" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={TANPA_PILIHAN} className="cursor-pointer">
              Semua prioritas
            </SelectItem>
            {DAFTAR_PRIORITAS.map((p) => (
              <SelectItem key={p} value={p} className="cursor-pointer">
                {p}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        {unitPengelolaDipakai && (
          <Select
            value={filter.unitPengelola ?? TANPA_PILIHAN}
            onValueChange={(val) => filterData('unitPengelola', val)}
          >
            <SelectTrigger className="w-full cursor-pointer sm:w-48" aria-label="Saring unit pengelola">
              <SelectValue placeholder="Semua unit pengelola" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={TANPA_PILIHAN} className="cursor-pointer">
                Semua unit pengelola
              </SelectItem>
              {saringanUnitPengelola.map((unit) => (
                <SelectItem key={unit.Id} value={unit.Id} className="cursor-pointer">
                  {unit.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        )}
      </div>

      {perintahKerja.data.length === 0 ? (
        <KeadaanKosong
          judul="Belum ada perintah kerja"
          deskripsi="Perintah kerja perbaikan atau pemeliharaan aset akan tercatat di sini."
        />
      ) : (
        <div className="rounded-md border border-border bg-card">
          {/* Di ponsel daftar ini menjadi kartu (DESIGN.md 9.3). */}
          <ul className="divide-y divide-border sm:hidden">
            {perintahKerja.data.map((item) => {
              const asetUtama = item.Aset?.find((a) => a.Utama) ?? item.Aset?.[0];

              return (
                <li key={item.Id} className="space-y-2 p-4">
                  <div className="flex flex-wrap items-center gap-2">
                    <Link
                      href={rutePerintahKerja.detail(item.Id)}
                      className="font-mono text-xs font-medium text-teknisi-700 hover:underline"
                    >
                      {item.Nomor}
                    </Link>
                    <Badge variant={VARIAN_PRIORITAS_PERINTAH_KERJA[item.Prioritas]}>{item.Prioritas}</Badge>
                    <Badge variant={VARIAN_STATUS_PERINTAH_KERJA[item.Status]}>{item.Status}</Badge>
                    {item.MenungguKonfirmasiPenerima && (
                      <Badge variant="perhatian">Menunggu konfirmasi penerima</Badge>
                    )}
                  </div>

                  <p className="text-sm font-medium text-foreground">{item.Judul}</p>

                  <dl className="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-xs">
                    <dt className="text-muted-foreground">Aset</dt>
                    <dd className="min-w-0 text-foreground">
                      {asetUtama ? `${asetUtama.KodeAset} · ${asetUtama.Nama}` : '—'}
                    </dd>
                    <dt className="text-muted-foreground">Lokasi</dt>
                    <dd className="min-w-0 text-foreground">{item.NamaLokasi ?? '—'}</dd>
                    {unitPengelolaDipakai && (
                      <>
                        <dt className="text-muted-foreground">Pengelola</dt>
                        <dd className="min-w-0 text-foreground">{item.UnitPengelola?.Nama ?? '—'}</dd>
                      </>
                    )}
                    <dt className="text-muted-foreground">Teknisi</dt>
                    <dd className="min-w-0 text-foreground">
                      {item.Penugasan && item.Penugasan.length > 0
                        ? (item.Penugasan[0].NamaPengguna ?? 'Teknisi')
                        : 'Belum ditugaskan'}
                    </dd>
                    <dt className="text-muted-foreground">Waktu</dt>
                    <dd className="min-w-0 text-foreground">
                      Kerja {item.TotalWaktuKerjaMenit ?? 0}m · Henti {item.TotalDowntimeMenit ?? 0}m
                    </dd>
                  </dl>

                  <Button asChild size="sm" variant="outline" className="w-full cursor-pointer">
                    <Link href={rutePerintahKerja.detail(item.Id)}>Buka perintah kerja</Link>
                  </Button>
                </li>
              );
            })}
          </ul>

          <div className="hidden overflow-x-auto sm:block">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-border bg-permukaan-50 text-[12.5px] text-grafit-500 [&_th]:font-medium">
                <tr>
                  <th className="px-4 py-3">Nomor</th>
                  <th className="px-4 py-3">Judul & Jenis</th>
                  <th className="px-4 py-3">Aset & Lokasi</th>
                  {unitPengelolaDipakai && <th className="px-4 py-3">Unit Pengelola</th>}
                  <th className="px-4 py-3">Prioritas</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Teknisi</th>
                  <th className="px-4 py-3">Waktu / Henti</th>
                  <th className="px-4 py-3">Total Biaya</th>
                  <th className="px-4 py-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {perintahKerja.data.map((item) => {
                  const asetUtama = item.Aset?.find((a) => a.Utama) ?? item.Aset?.[0];
                  return (
                    <tr key={item.Id} className="hover:bg-accent transition-colors">
                      <td className="px-4 py-3 font-mono text-xs font-medium">
                        <Link
                          href={rutePerintahKerja.detail(item.Id)}
                          className="text-teknisi-700 hover:underline cursor-pointer"
                        >
                          {item.Nomor}
                        </Link>
                      </td>
                      <td className="px-4 py-3">
                        <div className="font-medium text-foreground">{item.Judul}</div>
                        <div className="text-xs text-muted-foreground">
                          {item.Jenis}
                          {item.NomorKeluhan && (
                            <span className="ml-1 text-primary">· Keluhan {item.NomorKeluhan}</span>
                          )}
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <div className="font-medium">
                          {asetUtama ? `${asetUtama.KodeAset} · ${asetUtama.Nama}` : '—'}
                        </div>
                        <div className="text-xs text-muted-foreground">{item.NamaLokasi ?? '—'}</div>
                      </td>
                      {unitPengelolaDipakai && (
                        <td className="px-4 py-3 text-xs">
                          {item.UnitPengelola ? (
                            <span className="font-medium">{item.UnitPengelola.Nama}</span>
                          ) : (
                            <span className="text-muted-foreground">—</span>
                          )}
                        </td>
                      )}
                      <td className="px-4 py-3">
                        <Badge variant={VARIAN_PRIORITAS_PERINTAH_KERJA[item.Prioritas]}>
                          {item.Prioritas}
                        </Badge>
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={VARIAN_STATUS_PERINTAH_KERJA[item.Status]}>{item.Status}</Badge>
                        {item.MenungguKonfirmasiPenerima && (
                          <div className="mt-1 text-xs text-safety-700">Menunggu konfirmasi penerima</div>
                        )}
                      </td>
                      <td className="px-4 py-3">
                        {item.Penugasan && item.Penugasan.length > 0 ? (
                          <div className="text-xs">
                            <span className="font-medium">{item.Penugasan[0].NamaPengguna ?? 'Teknisi'}</span>
                            {item.Penugasan.length > 1 && (
                              <span className="text-muted-foreground ml-1">
                                (+{item.Penugasan.length - 1})
                              </span>
                            )}
                          </div>
                        ) : (
                          <span className="text-xs text-muted-foreground">Belum ditugaskan</span>
                        )}
                      </td>
                      <td className="px-4 py-3 text-xs">
                        <div>Kerja: {item.TotalWaktuKerjaMenit ?? 0}m</div>
                        <div className="text-muted-foreground">Henti: {item.TotalDowntimeMenit ?? 0}m</div>
                      </td>
                      <td className="px-4 py-3 text-xs font-medium">{formatRupiah(item.TotalBiaya ?? 0)}</td>
                      <td className="px-4 py-3 text-right">
                        <Button asChild size="sm" variant="outline" className="cursor-pointer">
                          <Link href={rutePerintahKerja.detail(item.Id)}>Buka</Link>
                        </Button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
          <KontrolPaginasi
            meta={perintahKerja.meta}
            onNavigasi={(halaman) => navigasiHalaman(halaman, filterAktif(filter))}
          />
        </div>
      )}
    </KerangkaAplikasi>
  );
}
