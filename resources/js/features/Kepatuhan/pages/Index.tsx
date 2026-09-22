import { type FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ClipboardCheck, Search, Trash2 } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import type { Paginasi } from '@/types/global';
import type {
  KepatuhanAset,
  RingkasanKepatuhan,
  StandarKepatuhan,
  StatusKepatuhan,
} from '@/features/Kepatuhan/types';
import { ruteKepatuhan } from '@/features/Kepatuhan/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DialogBuatStandar } from '@/features/Kepatuhan/components/DialogBuatStandar';
import { DialogTugaskan } from '@/features/Kepatuhan/components/DialogTugaskan';
import { DialogPemeriksaan } from '@/features/Kepatuhan/components/DialogPemeriksaan';
import type { AsetRingkas } from '@/features/Kepatuhan/types';
import type { AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  kewajiban: Paginasi<KepatuhanAset>;
  standar: StandarKepatuhan[];
  aset: AsetRingkas[];
  ringkasan: RingkasanKepatuhan;
  filter: { cari?: string; status?: StatusKepatuhan };
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const SEMUA = '__semua__';
const STATUS: StatusKepatuhan[] = ['BelumDiperiksa', 'Patuh', 'TidakPatuh', 'Kedaluwarsa'];
const VARIAN_STATUS = {
  BelumDiperiksa: 'netral',
  Patuh: 'sukses',
  TidakPatuh: 'bahaya',
  Kedaluwarsa: 'perhatian',
} as const;

export default function KepatuhanIndex({ kewajiban, standar, aset, ringkasan, filter, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const [cari, setCari] = useState(filter.cari ?? '');
  const [status, setStatus] = useState<string>(filter.status ?? SEMUA);

  function terapkanFilter(event: FormEvent): void {
    event.preventDefault();
    router.get(
      ruteKepatuhan.index,
      { cari, status: status === SEMUA ? '' : status },
      { preserveState: true, replace: true },
    );
  }

  async function lepaskan(item: KepatuhanAset): Promise<void> {
    const lanjut = await konfirmasi({
      judul: `Lepas persyaratan "${item.KodePersyaratan}" dari aset ${item.KodeAset}?`,
      deskripsi: 'Riwayat pemeriksaan pada kewajiban ini ikut terhapus.',
      ragam: 'bahaya',
    });
    if (lanjut) router.delete(ruteKepatuhan.kewajibanDetail(item.Id), { preserveScroll: true });
  }

  return (
    <KerangkaAplikasi>
      <Head title="Kepatuhan" />
      <div className="space-y-6">
        <KepalaHalaman
          judul="Kepatuhan"
          deskripsi="Standar yang berlaku bagi organisasi, persyaratannya, dan status kepatuhan tiap aset."
          aksi={
            <>
              <div className="flex flex-wrap gap-2">
                <DialogTugaskan standar={standar} aset={aset} wajib={wajib.tugaskan} />
                <DialogBuatStandar wajib={wajib.standar} />
              </div>
            </>
          }
        />

        <div className="grid gap-3 sm:grid-cols-4">
          {[
            { label: 'Kepatuhan', nilai: `${ringkasan.persentaseKepatuhan}%`, kelas: 'text-foreground' },
            { label: 'Belum diperiksa', nilai: ringkasan.belumDiperiksa, kelas: 'text-muted-foreground' },
            { label: 'Tidak patuh', nilai: ringkasan.tidakPatuh, kelas: 'text-bahaya-600' },
            { label: 'Kedaluwarsa', nilai: ringkasan.kedaluwarsa, kelas: 'text-safety-600' },
          ].map((kartu) => (
            <div key={kartu.label} className="rounded-[9px] border border-border bg-card p-4">
              <p className="text-xs text-muted-foreground">{kartu.label}</p>
              <p className={`mt-1 text-2xl font-semibold ${kartu.kelas}`}>{kartu.nilai}</p>
            </div>
          ))}
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Standar Terdaftar</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {standar.length === 0 ? (
              <KeadaanKosong
                judul="Belum ada standar kepatuhan."
                deskripsi="Daftarkan standar yang berlaku bagi organisasi Anda, lalu rinci persyaratannya."
              />
            ) : (
              <div className="grid gap-2 sm:grid-cols-2">
                {standar.map((item) => (
                  <Link
                    key={item.Id}
                    href={ruteKepatuhan.standarDetail(item.Id)}
                    className="flex items-center justify-between rounded-[9px] border border-border p-3 transition hover:border-primary/40"
                  >
                    <div className="min-w-0">
                      <p className="truncate font-medium">{item.Nama}</p>
                      <p className="font-mono text-xs text-muted-foreground">
                        {item.Kode}
                        {item.VersiStandar ? ` · v${item.VersiStandar}` : ''} · {item.JumlahPersyaratan ?? 0}{' '}
                        persyaratan
                      </p>
                    </div>
                    <Badge variant={item.Aktif ? 'sukses' : 'netral'}>
                      {item.Aktif ? 'Aktif' : 'Nonaktif'}
                    </Badge>
                  </Link>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <form onSubmit={terapkanFilter} className="grid gap-3 sm:grid-cols-[1fr_13rem_auto]">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              aria-label="Cari kode atau nama aset"
              placeholder="Cari kode atau nama aset"
              className="pl-9"
              value={cari}
              onChange={(event) => setCari(event.target.value)}
            />
          </div>
          <Select value={status} onValueChange={setStatus}>
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={SEMUA}>Semua status</SelectItem>
              {STATUS.map((item) => (
                <SelectItem key={item} value={item}>
                  {item}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Button type="submit" variant="outline">
            Terapkan
          </Button>
        </form>

        {kewajiban.data.length === 0 ? (
          <KeadaanKosong
            ilustrasi="/assets/3d/persetujuan-kepatuhan.webp"
            judul="Belum ada kewajiban kepatuhan."
            deskripsi="Tugaskan standar ke aset agar status kepatuhannya dapat dipantau."
          />
        ) : (
          <div className="overflow-hidden rounded-[9px] border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Aset</th>
                    <th className="px-4 py-3">Persyaratan</th>
                    <th className="px-4 py-3">Diperiksa</th>
                    <th className="px-4 py-3">Berlaku sampai</th>
                    <th className="px-4 py-3">Status</th>
                    <th className="px-4 py-3" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {kewajiban.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-muted/30">
                      <td className="px-4 py-3">
                        {item.NamaAset}
                        <p className="font-mono text-xs text-muted-foreground">{item.KodeAset}</p>
                      </td>
                      <td className="px-4 py-3">
                        {item.NamaPersyaratan}
                        <p className="font-mono text-xs text-muted-foreground">{item.KodePersyaratan}</p>
                      </td>
                      <td className="px-4 py-3 text-xs">
                        {item.TanggalPemeriksaan ?? '—'}
                        {item.NamaPemeriksa && (
                          <p className="text-muted-foreground">oleh {item.NamaPemeriksa}</p>
                        )}
                      </td>
                      <td className="px-4 py-3 text-xs">
                        {item.BerlakuSampai ?? 'Tanpa batas'}
                        {item.SisaHari !== null && item.SisaHari < 0 && (
                          <p className="text-bahaya-600">Lewat {Math.abs(item.SisaHari)} hari</p>
                        )}
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center justify-end gap-2">
                          <DialogPemeriksaan kewajiban={item} wajib={wajib.pemeriksaan} />
                          <Button
                            size="icon"
                            variant="ghost"
                            aria-label={`Lepas ${item.KodePersyaratan}`}
                            onClick={() => lepaskan(item)}
                          >
                            <Trash2 />
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="divide-y divide-border md:hidden">
              {kewajiban.data.map((item) => (
                <div key={item.Id} className="space-y-2 p-4">
                  <div className="flex items-start gap-3">
                    <ClipboardCheck className="size-5 shrink-0 text-primary" />
                    <div className="min-w-0 flex-1">
                      <p className="truncate font-medium">{item.NamaPersyaratan}</p>
                      <p className="truncate font-mono text-xs text-muted-foreground">
                        {item.KodeAset} · {item.KodePersyaratan}
                      </p>
                      <p className="mt-1 text-xs text-muted-foreground">
                        Berlaku sampai {item.BerlakuSampai ?? 'tanpa batas'}
                      </p>
                    </div>
                    <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                  </div>
                  <div className="flex gap-2">
                    <DialogPemeriksaan kewajiban={item} wajib={wajib.pemeriksaan} />
                    <Button
                      size="icon"
                      variant="ghost"
                      aria-label={`Lepas ${item.KodePersyaratan}`}
                      onClick={() => lepaskan(item)}
                    >
                      <Trash2 />
                    </Button>
                  </div>
                </div>
              ))}
            </div>
            <KontrolPaginasi
              meta={kewajiban.meta}
              onNavigasi={(halaman) =>
                navigasiHalaman(halaman, { cari, status: status === SEMUA ? '' : status })
              }
            />
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
