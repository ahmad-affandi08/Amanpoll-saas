import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { DeretStatistik, KartuStatistik } from '@/components/shared/KartuStatistik';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import {
  Plus,
  Search,
  Filter,
  ArrowRight,
  AlertTriangle,
  CheckCircle2,
  XCircle,
  Calendar,
} from 'lucide-react';
import type { Inspeksi } from '@/features/PreventifInspeksi/types';
import type { Paginasi } from '@/types/global';
import { statusInspeksiBadge, hasilInspeksiBadge } from '@/features/PreventifInspeksi/status';
import { ruteInspeksi } from '@/features/Inspeksi/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';
import { tanggalHariIni, tanggalLokal } from '@/lib/waktu';

interface Props {
  inspeksi: Paginasi<Inspeksi>;
  templatInspeksi: { Id: string; Nama: string; Kode: string }[];
  aset: { Id: string; KodeAset: string; Nama: string; LokasiId?: string | null }[];
  inspektor: { Id: string; Nama: string }[];
  ringkasan: { Lolos: number; PerluPerhatian: number; Gagal: number };
  filter: { status?: string; hasil?: string; cari?: string | null };
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

/** Hanya penyaring yang benar-benar terisi yang ikut dibawa saat berpindah halaman. */
function filterAktif(filter: Props['filter']): Record<string, string> {
  return Object.fromEntries(
    Object.entries(filter).filter((pasangan): pasangan is [string, string] => Boolean(pasangan[1])),
  );
}

export default function InspeksiIndex({
  inspeksi,
  templatInspeksi,
  aset,
  inspektor,
  ringkasan,
  filter,
  wajib,
}: Props) {
  const [bukaDialog, setBukaDialog] = useState(false);
  const [pencarian, setPencarian] = useState(filter.cari ?? '');

  const form = useForm({
    TemplatInspeksiId: '',
    AsetId: '',
    DijadwalkanPada: tanggalHariIni(),
    DilaksanakanOleh: '',
  });

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteInspeksi.index, {
      onSuccess: () => {
        setBukaDialog(false);
        form.reset();
      },
    });
  };

  const terapkanFilter = (field: string, value: string) => {
    router.get(
      ruteInspeksi.index,
      {
        ...filter,
        [field]: value === '__all__' ? undefined : value,
      },
      { preserveState: true },
    );
  };

  // Pencarian dan hitungan kartu keduanya dijawab server; menyaring di sini hanya akan menyaring satu halaman.
  const daftarTersaring = inspeksi.data;
  const lolosCount = ringkasan.Lolos;
  const perhatianCount = ringkasan.PerluPerhatian;
  const gagalCount = ringkasan.Gagal;

  return (
    <KerangkaAplikasi>
      <Head title="Inspeksi Aset Berkala" />

      <div className="space-y-5">
        {/* Header */}
        <KepalaHalaman
          judul="Inspeksi Aset Berkala"
          deskripsi="Pemeriksaan fisik, pemantauan kondisi aset, dan pencatatan temuan operasional."
          aksi={
            <>
              <TombolEkspor url={ruteInspeksi.ekspor} filter={filter as Record<string, string>} />
              <Dialog open={bukaDialog} onOpenChange={setBukaDialog}>
                <DialogTrigger asChild>
                  <Button className="cursor-pointer">
                    <Plus className="h-4 w-4" />
                    Jadwalkan Inspeksi
                  </Button>
                </DialogTrigger>
                <DialogContent className="sm:max-w-md">
                  <AturanWajibProvider aturan={wajib.inspeksi}>
                    <form onSubmit={onSubmit}>
                      <DialogHeader>
                        <DialogTitle>Jadwalkan Inspeksi Aset</DialogTitle>
                      </DialogHeader>

                      <div className="grid gap-4 py-4">
                        <div className="space-y-1.5">
                          <Label nama="TemplatInspeksiId" htmlFor="TemplatInspeksiId" wajib>
                            Templat Inspeksi
                          </Label>
                          <Combobox
                            nilai={form.data.TemplatInspeksiId}
                            onPilih={(val) => form.setData('TemplatInspeksiId', val)}
                            opsi={opsiDari(templatInspeksi, (t) => `${t.Kode} - ${t.Nama}`)}
                            placeholder="Pilih Templat Inspeksi..."
                            className="cursor-pointer"
                          />
                        </div>

                        <div className="space-y-1.5">
                          <Label nama="AsetId" htmlFor="AsetId" wajib>
                            Unit Aset yang Diinspeksi
                          </Label>
                          <Combobox
                            nilai={form.data.AsetId}
                            onPilih={(val) => form.setData('AsetId', val)}
                            opsi={opsiDari(aset, (a) => `${a.KodeAset} - ${a.Nama}`)}
                            placeholder="Pilih Unit Aset..."
                            className="cursor-pointer"
                          />
                        </div>

                        <div className="space-y-1.5">
                          <Label nama="DijadwalkanPada" htmlFor="DijadwalkanPada" wajib>
                            Tanggal Jadwal Inspeksi
                          </Label>
                          <DatePicker
                            value={form.data.DijadwalkanPada}
                            onChange={(val) => form.setData('DijadwalkanPada', val)}
                            placeholder="Pilih tanggal jadwal..."
                          />
                          {form.errors.DijadwalkanPada && (
                            <p className="text-xs text-destructive">{form.errors.DijadwalkanPada}</p>
                          )}
                        </div>

                        <div className="space-y-1.5">
                          <Label nama="DilaksanakanOleh" htmlFor="DilaksanakanOleh">
                            Inspektor / Petugas (Opsional)
                          </Label>
                          <Combobox
                            nilai={form.data.DilaksanakanOleh || '__none__'}
                            onPilih={(val) => form.setData('DilaksanakanOleh', val === '__none__' ? '' : val)}
                            opsi={[
                              { nilai: '__none__', label: '-- Ditentukan Nanti --' },
                              ...opsiDari(inspektor, (p) => p.Nama),
                            ]}
                            placeholder="Pilih Petugas..."
                            className="cursor-pointer"
                          />
                        </div>
                      </div>

                      <DialogFooter>
                        <Button
                          type="button"
                          variant="outline"
                          className="cursor-pointer"
                          onClick={() => setBukaDialog(false)}
                        >
                          Batal
                        </Button>
                        <Button
                          type="submit"
                          className="cursor-pointer"
                          disabled={form.processing || !form.data.TemplatInspeksiId || !form.data.AsetId}
                        >
                          {form.processing ? 'Menjadwalkan...' : 'Jadwalkan'}
                        </Button>
                      </DialogFooter>
                    </form>
                  </AturanWajibProvider>
                </DialogContent>
              </Dialog>
            </>
          }
        />

        {/* Ringkasan Hasil Inspeksi */}
        <DeretStatistik kolom={4}>
          <KartuStatistik menyatu label="Total Jadwal" nilai={inspeksi.data.length} />
          <KartuStatistik menyatu label="Lolos Normal" nilai={lolosCount} ikon={CheckCircle2} />
          <KartuStatistik menyatu label="Perlu Perhatian" nilai={perhatianCount} ikon={AlertTriangle} />
          <KartuStatistik menyatu label="Gagal / Temuan" nilai={gagalCount} ikon={XCircle} />
        </DeretStatistik>

        {/* Filter & Search Bar */}
        <div className="flex flex-wrap items-center gap-2">
          <div className="relative w-full sm:w-64">
            <Search
              aria-hidden="true"
              className="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-grafit-500"
            />
            <Input
              type="text"
              aria-label="Cari inspeksi"
              placeholder="Cari nomor, aset, atau templat..."
              className="pl-8"
              value={pencarian}
              onChange={(e) => setPencarian(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  terapkanFilter('cari', pencarian === '' ? '__all__' : pencarian);
                }
              }}
            />
          </div>

          <div className="flex w-full items-center gap-2 sm:w-auto">
            <Select value={filter.status || '__all__'} onValueChange={(val) => terapkanFilter('status', val)}>
              <SelectTrigger aria-label="Status inspeksi" className="w-40 cursor-pointer">
                <SelectValue placeholder="Semua Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="__all__">Semua Status</SelectItem>
                <SelectItem value="Terjadwal">Terjadwal</SelectItem>
                <SelectItem value="SedangDikerjakan">Sedang Berjalan</SelectItem>
                <SelectItem value="Selesai">Selesai</SelectItem>
              </SelectContent>
            </Select>

            <Select value={filter.hasil || '__all__'} onValueChange={(val) => terapkanFilter('hasil', val)}>
              <SelectTrigger aria-label="Hasil inspeksi" className="w-40 cursor-pointer">
                <SelectValue placeholder="Semua Hasil" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="__all__">Semua Hasil</SelectItem>
                <SelectItem value="Lolos">Lolos</SelectItem>
                <SelectItem value="PerluPerhatian">Perlu Perhatian</SelectItem>
                <SelectItem value="Gagal">Gagal / Rusak</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        {/* Tabel Inspeksi */}
        {daftarTersaring.length === 0 ? (
          <KeadaanKosong
            ilustrasi="/assets/3d/persetujuan-kepatuhan.webp"
            judul="Belum Ada Catatan Inspeksi"
            deskripsi="Riwayat dan jadwal inspeksi kondisi aset operasional akan dicatat di sini."
          />
        ) : (
          <div className="overflow-hidden rounded-md border border-border bg-card">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="bg-permukaan-50 border-b border-garis-200 text-[12.5px] font-medium text-grafit-500">
                  <tr>
                    <th className="px-5 py-3">Nomor</th>
                    <th className="px-5 py-3">Aset</th>
                    <th className="px-5 py-3">Templat Inspeksi</th>
                    <th className="px-5 py-3">Jadwal / Pelaksanaan</th>
                    <th className="px-5 py-3">Status</th>
                    <th className="px-5 py-3">Hasil Evaluasi</th>
                    <th className="px-5 py-3 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-permukaan-100">
                  {daftarTersaring.map((item) => {
                    const statusBadge = statusInspeksiBadge[item.Status] ?? { label: item.Status, kelas: '' };
                    const hasilBadge = item.Hasil ? hasilInspeksiBadge[item.Hasil] : null;

                    return (
                      <tr key={item.Id} className="hover:bg-accent transition-colors">
                        <td className="px-5 py-4 font-mono font-medium text-xs text-grafit-950">
                          {item.Nomor}
                        </td>
                        <td className="px-5 py-4 text-xs font-medium text-grafit-950">
                          <div className="font-semibold text-grafit-950">{item.aset?.Nama}</div>
                          <span className="text-grafit-500 font-mono">
                            {item.aset?.KodeAset} • {item.aset?.lokasi?.Nama ?? '-'}
                          </span>
                        </td>
                        <td className="px-5 py-4 text-xs text-grafit-700">
                          {item.templat_inspeksi?.Nama ?? '-'}
                        </td>
                        <td className="px-5 py-4 text-xs text-grafit-700">
                          <div className="flex items-center gap-1.5">
                            <Calendar className="h-3.5 w-3.5 text-grafit-500" />
                            <span>{item.DijadwalkanPada ? tanggalLokal(item.DijadwalkanPada) : '-'}</span>
                          </div>
                        </td>
                        <td className="px-5 py-4">
                          <Badge variant="outline" className={`text-xs ${statusBadge.kelas}`}>
                            {statusBadge.label}
                          </Badge>
                        </td>
                        <td className="px-5 py-4">
                          {hasilBadge ? (
                            <Badge variant="outline" className={`text-xs ${hasilBadge.kelas}`}>
                              {hasilBadge.label}
                            </Badge>
                          ) : (
                            <span className="text-xs text-grafit-500">Belum Dievaluasi</span>
                          )}
                        </td>
                        <td className="px-5 py-4 text-right">
                          <Link
                            href={ruteInspeksi.detail(item.Id)}
                            className="inline-flex items-center gap-1 text-xs font-medium text-teknisi-600 hover:text-teknisi-700 cursor-pointer"
                          >
                            <span>Detail</span>
                            <ArrowRight className="h-3.5 w-3.5" />
                          </Link>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
            <KontrolPaginasi
              meta={inspeksi.meta}
              onNavigasi={(halaman) => navigasiHalaman(halaman, filterAktif(filter))}
            />
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
