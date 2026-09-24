import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FileSignature, Plus, Search } from 'lucide-react';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { DeretStatistik, KartuStatistik } from '@/components/shared/KartuStatistik';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Paginasi } from '@/types/global';
import type { JenisKontrak, Kontrak, RingkasanKontrak, StatusKontrak } from '@/features/Kontrak/types';
import { ruteKontrak } from '@/features/Kontrak/api';
import { formatUang } from '@/lib/uang';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TANPA_PILIHAN, opsiDari, opsiKosong } from '@/lib/pilihan';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
import { tanggalHariIni } from '@/lib/waktu';
import { InputUang } from '@/components/shared/InputUang';

interface PenyediaRingkas {
  Id: string;
  Kode: string;
  Nama: string;
}
interface TingkatLayananRingkas {
  Id: string;
  Nama: string;
}
interface Props {
  kontrak: Paginasi<Kontrak>;
  penyedia: PenyediaRingkas[];
  tingkatLayanan: TingkatLayananRingkas[];
  ringkasan: RingkasanKontrak;
  filter: { cari?: string; status?: StatusKontrak; penyedia?: string };
}

const SEMUA = '__semua__';
const STATUS: StatusKontrak[] = ['Aktif', 'Berakhir', 'Dibatalkan'];
const JENIS: JenisKontrak[] = ['Pemeliharaan', 'Layanan', 'Sewa', 'Pembelian', 'Lainnya'];
const VARIAN_STATUS = { Aktif: 'sukses', Berakhir: 'netral', Dibatalkan: 'bahaya' } as const;

/** Menerjemahkan sisa hari menjadi label masa berlaku yang bisa dibaca cepat. */
function labelSisa(kontrak: Kontrak): { teks: string; varian: 'sukses' | 'perhatian' | 'bahaya' | 'netral' } {
  if (kontrak.Status !== 'Aktif') return { teks: '—', varian: 'netral' };
  if (kontrak.SisaHari < 0) return { teks: `Lewat ${Math.abs(kontrak.SisaHari)} hari`, varian: 'bahaya' };
  if (kontrak.SisaHari <= kontrak.PeringatanHariSebelum)
    return { teks: `${kontrak.SisaHari} hari lagi`, varian: 'perhatian' };
  return { teks: `${kontrak.SisaHari} hari lagi`, varian: 'sukses' };
}

function DialogBuatKontrak({ penyedia, tingkatLayanan }: Pick<Props, 'penyedia' | 'tingkatLayanan'>) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Nomor: '',
    Nama: '',
    Jenis: 'Pemeliharaan',
    PenyediaId: TANPA_PILIHAN,
    MulaiPada: tanggalHariIni(),
    BerakhirPada: '',
    Nilai: '',
    MataUang: 'IDR',
    TingkatLayananId: TANPA_PILIHAN,
    PeringatanHariSebelum: '30',
    Catatan: '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      PenyediaId: data.PenyediaId === TANPA_PILIHAN ? null : data.PenyediaId,
      TingkatLayananId: data.TingkatLayananId === TANPA_PILIHAN ? null : data.TingkatLayananId,
      Nilai: data.Nilai || null,
      Catatan: data.Catatan || null,
    }));
    form.post(ruteKontrak.index, { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="min-h-11 sm:min-h-9">
          <Plus /> Buat Kontrak
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Kontrak Baru</DialogTitle>
          <DialogDescription>
            Periode kontrak membatasi periode cakupan aset yang bisa dilampirkan nanti.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-[12rem_1fr]">
            <div className="space-y-1.5">
              <Label nama="Nomor" htmlFor="Nomor">
                Nomor
              </Label>
              <Input
                id="Nomor"
                value={form.data.Nomor}
                onChange={(event) => form.setData('Nomor', event.target.value)}
              />
              {form.errors.Nomor && <p className="text-sm text-destructive">{form.errors.Nomor}</p>}
            </div>
            <div className="space-y-1.5">
              <Label nama="Nama" htmlFor="Nama">
                Nama Kontrak
              </Label>
              <Input
                id="Nama"
                value={form.data.Nama}
                onChange={(event) => form.setData('Nama', event.target.value)}
              />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label nama="Jenis">Jenis</Label>
              <Select value={form.data.Jenis} onValueChange={(value) => form.setData('Jenis', value)}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {JENIS.map((item) => (
                    <SelectItem key={item} value={item}>
                      {item}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label nama="PenyediaId">Penyedia</Label>
              <Combobox
                nilai={form.data.PenyediaId}
                onPilih={(value) => form.setData('PenyediaId', value)}
                opsi={[
                  opsiKosong('Tanpa penyedia'),
                  ...opsiDari(penyedia, (item) => `${item.Kode} — ${item.Nama}`),
                ]}
              />
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label nama="MulaiPada" htmlFor="MulaiPada">
                Mulai
              </Label>
              <DatePicker
                value={form.data.MulaiPada}
                onChange={(nilai) => form.setData('MulaiPada', nilai)}
                id="MulaiPada"
              />
              {form.errors.MulaiPada && <p className="text-sm text-destructive">{form.errors.MulaiPada}</p>}
            </div>
            <div className="space-y-1.5">
              <Label nama="BerakhirPada" htmlFor="BerakhirPada">
                Berakhir
              </Label>
              <DatePicker
                value={form.data.BerakhirPada}
                onChange={(nilai) => form.setData('BerakhirPada', nilai)}
                id="BerakhirPada"
              />
              {form.errors.BerakhirPada && (
                <p className="text-sm text-destructive">{form.errors.BerakhirPada}</p>
              )}
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-[1fr_7rem_9rem]">
            <div className="space-y-1.5">
              <Label nama="Nilai" htmlFor="Nilai">
                Nilai Kontrak
              </Label>
              <InputUang
                id="Nilai"
                value={form.data.Nilai}
                onChange={(nilai) => form.setData('Nilai', nilai)}
                mataUang={form.data.MataUang || 'IDR'}
              />
              {form.errors.Nilai && <p className="text-sm text-destructive">{form.errors.Nilai}</p>}
            </div>
            <div className="space-y-1.5">
              <Label nama="MataUang" htmlFor="MataUang">
                Mata Uang
              </Label>
              <Input
                id="MataUang"
                maxLength={3}
                value={form.data.MataUang}
                onChange={(event) => form.setData('MataUang', event.target.value.toUpperCase())}
              />
            </div>
            <div className="space-y-1.5">
              <Label nama="PeringatanHariSebelum" htmlFor="PeringatanHariSebelum">
                Ingatkan (hari)
              </Label>
              <Input
                id="PeringatanHariSebelum"
                type="number"
                min="0"
                max="365"
                value={form.data.PeringatanHariSebelum}
                onChange={(event) => form.setData('PeringatanHariSebelum', event.target.value)}
              />
            </div>
          </div>

          <div className="space-y-1.5">
            <Label nama="TingkatLayananId">Tingkat Layanan</Label>
            <Combobox
              nilai={form.data.TingkatLayananId}
              onPilih={(value) => form.setData('TingkatLayananId', value)}
              opsi={[opsiKosong('Tanpa SLA khusus'), ...opsiDari(tingkatLayanan, (item) => item.Nama)]}
            />
          </div>

          <div className="space-y-1.5">
            <Label nama="Catatan" htmlFor="Catatan">
              Catatan
            </Label>
            <Textarea
              id="Catatan"
              rows={2}
              value={form.data.Catatan}
              onChange={(event) => form.setData('Catatan', event.target.value)}
            />
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan Kontrak
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function KontrakIndex({ kontrak, penyedia, tingkatLayanan, ringkasan, filter }: Props) {
  const [cari, setCari] = useState(filter.cari ?? '');
  const [status, setStatus] = useState<string>(filter.status ?? SEMUA);
  const [penyediaFilter, setPenyediaFilter] = useState<string>(filter.penyedia ?? SEMUA);

  function terapkanFilter(event: FormEvent): void {
    event.preventDefault();
    router.get(
      ruteKontrak.index,
      {
        cari,
        status: status === SEMUA ? '' : status,
        penyedia: penyediaFilter === SEMUA ? '' : penyediaFilter,
      },
      { preserveState: true, replace: true },
    );
  }

  return (
    <KerangkaAplikasi>
      <Head title="Kontrak" />
      <div className="space-y-5">
        <KepalaHalaman
          judul="Kontrak"
          deskripsi="Kontrak penyedia, aset yang tercakup, layanan, dan pengingat masa berlaku."
          aksi={
            <>
              <TombolEkspor url={ruteKontrak.ekspor} filter={filter as Record<string, string>} />
              <DialogBuatKontrak penyedia={penyedia} tingkatLayanan={tingkatLayanan} />
            </>
          }
        />

        <DeretStatistik kolom={4}>
          <KartuStatistik menyatu label="Kontrak aktif" nilai={ringkasan.aktif} />
          <KartuStatistik menyatu label="Akan berakhir" nilai={ringkasan.akanBerakhir} />
          <KartuStatistik menyatu label="Kedaluwarsa" nilai={ringkasan.kedaluwarsa} />
          <KartuStatistik menyatu label="Tanpa penyedia" nilai={ringkasan.tanpaPenyedia} />
        </DeretStatistik>

        <form onSubmit={terapkanFilter} className="flex flex-wrap items-center gap-2">
          <div className="relative w-full sm:w-64">
            <Search
              aria-hidden="true"
              className="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-grafit-500"
            />
            <Input
              aria-label="Cari nomor atau nama kontrak"
              placeholder="Cari nomor atau nama kontrak"
              className="pl-8"
              value={cari}
              onChange={(event) => setCari(event.target.value)}
            />
          </div>
          <Select value={status} onValueChange={setStatus}>
            <SelectTrigger className="w-full sm:w-40" aria-label="Saring status">
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
          <Label htmlFor="filter-kontrak-penyedia" className="sr-only">
            Penyedia
          </Label>
          <Combobox
            id="filter-kontrak-penyedia"
            nilai={penyediaFilter}
            onPilih={setPenyediaFilter}
            opsi={[{ nilai: SEMUA, label: 'Semua penyedia' }, ...opsiDari(penyedia, (item) => item.Nama)]}
            className="w-full sm:w-48"
          />
          <Button type="submit" variant="secondary">
            Terapkan
          </Button>
        </form>

        {kontrak.data.length === 0 ? (
          <KeadaanKosong
            ilustrasi="/assets/3d/penyedia-kontrak.webp"
            judul="Belum ada kontrak."
            deskripsi="Buat kontrak untuk menghubungkan penyedia dengan aset dan tingkat layanannya."
          />
        ) : (
          <div className="overflow-hidden rounded-md border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-permukaan-50 text-left text-[12.5px] text-grafit-500 [&_th]:font-medium">
                  <tr>
                    <th className="px-4 py-3">Kontrak</th>
                    <th className="px-4 py-3">Penyedia</th>
                    <th className="px-4 py-3">Periode</th>
                    <th className="px-4 py-3">Masa berlaku</th>
                    <th className="px-4 py-3 text-right">Nilai</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {kontrak.data.map((item) => {
                    const sisa = labelSisa(item);
                    return (
                      <tr key={item.Id} className="hover:bg-accent">
                        <td className="px-4 py-3">
                          <Link className="font-medium hover:text-primary" href={ruteKontrak.detail(item.Id)}>
                            {item.Nama}
                          </Link>
                          <p className="font-mono text-xs text-muted-foreground">
                            {item.Nomor} · {item.Jenis}
                          </p>
                        </td>
                        <td className="px-4 py-3">
                          {item.NamaPenyedia ?? 'Tanpa penyedia'}
                          <p className="text-xs text-muted-foreground">
                            {item.NamaTingkatLayanan ?? 'Tanpa SLA'} · {item.JumlahAset ?? 0} aset
                          </p>
                        </td>
                        <td className="px-4 py-3 text-xs">
                          {item.MulaiPada}
                          <span className="text-muted-foreground"> s.d. </span>
                          {item.BerakhirPada}
                        </td>
                        <td className="px-4 py-3">
                          <Badge variant={sisa.varian}>{sisa.teks}</Badge>
                        </td>
                        <td className="px-4 py-3 text-right font-mono">
                          {item.Nilai ? formatUang(item.Nilai, item.MataUang) : '—'}
                        </td>
                        <td className="px-4 py-3">
                          <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
            <div className="divide-y divide-border md:hidden">
              {kontrak.data.map((item) => {
                const sisa = labelSisa(item);
                return (
                  <Link
                    key={item.Id}
                    href={ruteKontrak.detail(item.Id)}
                    className="flex min-h-24 items-center gap-3 p-4 transition-colors hover:bg-accent focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
                  >
                    <FileSignature aria-hidden="true" className="size-4 shrink-0 text-grafit-500" />
                    <div className="min-w-0 flex-1">
                      <p className="line-clamp-2 font-medium">{item.Nama}</p>
                      <p className="truncate font-mono text-xs text-muted-foreground">{item.Nomor}</p>
                      <p className="mt-1 text-xs text-muted-foreground">
                        {item.NamaPenyedia ?? 'Tanpa penyedia'} · {sisa.teks}
                      </p>
                    </div>
                    <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                  </Link>
                );
              })}
            </div>
            <KontrolPaginasi
              meta={kontrak.meta}
              onNavigasi={(halaman) =>
                navigasiHalaman(halaman, {
                  cari,
                  status: status === SEMUA ? '' : status,
                  penyedia: penyediaFilter === SEMUA ? '' : penyediaFilter,
                })
              }
            />
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
