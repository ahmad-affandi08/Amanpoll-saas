import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
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
import { Textarea } from '@/components/ui/textarea';
import { EmptyState } from '@/components/shared/EmptyState';
import type {
  JenisPerintahKerja,
  PerintahKerja,
  PrioritasPerintahKerja,
  StatusPerintahKerja,
} from '@/features/PerintahKerja';
import {
  VARIAN_PRIORITAS_PERINTAH_KERJA,
  VARIAN_STATUS_PERINTAH_KERJA,
} from '@/features/PerintahKerja/status';

interface KeluhanRingkas {
  Id: string;
  Nomor: string;
  Judul: string;
  Prioritas: PrioritasPerintahKerja;
  LokasiId: string | null;
  AsetId: string | null;
}

interface AsetRingkas {
  Id: string;
  KodeAset: string;
  Nama: string;
  LokasiId: string | null;
}

interface LokasiRingkas {
  Id: string;
  Nama: string;
}

interface Props {
  perintahKerja: PerintahKerja[];
  keluhan: KeluhanRingkas[];
  aset: AsetRingkas[];
  lokasi: LokasiRingkas[];
  filter: { status?: string; prioritas?: string };
  dapatMengelola: boolean;
}

const TANPA = '__tanpa__';

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

const DAFTAR_PRIORITAS: PrioritasPerintahKerja[] = ['Rendah', 'Normal', 'Tinggi', 'Kritis'];

const DAFTAR_JENIS: JenisPerintahKerja[] = [
  'Korektif',
  'Preventif',
  'Inspeksi',
  'Kalibrasi',
  'Umum',
  'Vendor',
];

function DialogBuatPerintahKerja({
  keluhan,
  aset,
  lokasi,
}: Pick<Props, 'keluhan' | 'aset' | 'lokasi'>) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    KeluhanId: TANPA,
    Jenis: 'Korektif' as JenisPerintahKerja,
    Judul: '',
    Deskripsi: '',
    Prioritas: 'Normal' as PrioritasPerintahKerja,
    LokasiId: '',
    AsetIds: [] as string[],
    MembutuhkanWaktuHenti: false,
    MembutuhkanPersetujuan: false,
    DijadwalkanMulaiPada: '',
    DijadwalkanSelesaiPada: '',
  });

  const tanganiPilihKeluhan = (keluhanId: string) => {
    if (keluhanId === TANPA) {
      form.setData({
        ...form.data,
        KeluhanId: TANPA,
      });
      return;
    }

    const dipilih = keluhan.find((k) => k.Id === keluhanId);
    if (!dipilih) return;

    form.setData({
      ...form.data,
      KeluhanId: keluhanId,
      Judul: form.data.Judul || `Tindak Lanjut: ${dipilih.Judul}`,
      Prioritas: dipilih.Prioritas,
      LokasiId: dipilih.LokasiId ?? form.data.LokasiId,
      AsetIds: dipilih.AsetId ? [dipilih.AsetId] : form.data.AsetIds,
    });
  };

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      KeluhanId: data.KeluhanId === TANPA ? null : data.KeluhanId,
      LokasiId: data.LokasiId ? data.LokasiId : null,
      DijadwalkanMulaiPada: data.DijadwalkanMulaiPada || null,
      DijadwalkanSelesaiPada: data.DijadwalkanSelesaiPada || null,
    }));
    form.post('/pemeliharaan/perintah-kerja', {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  const toggleAset = (idAset: string) => {
    const ada = form.data.AsetIds.includes(idAset);
    if (ada) {
      form.setData(
        'AsetIds',
        form.data.AsetIds.filter((id) => id !== idAset)
      );
    } else {
      form.setData('AsetIds', [...form.data.AsetIds, idAset]);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="cursor-pointer">Buat Perintah Kerja</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Buat Perintah Kerja Baru</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Terkait Keluhan (Opsional)</Label>
              <Select value={form.data.KeluhanId} onValueChange={tanganiPilihKeluhan}>
                <SelectTrigger className="w-full cursor-pointer">
                  <SelectValue placeholder="Pilih keluhan" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA} className="cursor-pointer">
                    Tanpa keluhan (Pekerjaan Mandiri)
                  </SelectItem>
                  {keluhan.map((k) => (
                    <SelectItem key={k.Id} value={k.Id} className="cursor-pointer">
                      {k.Nomor} · {k.Judul}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errors.KeluhanId && (
                <p className="text-sm text-destructive">{form.errors.KeluhanId}</p>
              )}
            </div>

            <div className="space-y-1.5">
              <Label>Jenis Pekerjaan</Label>
              <Select
                value={form.data.Jenis}
                onValueChange={(val) => form.setData('Jenis', val as JenisPerintahKerja)}
              >
                <SelectTrigger className="w-full cursor-pointer">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {DAFTAR_JENIS.map((jenis) => (
                    <SelectItem key={jenis} value={jenis} className="cursor-pointer">
                      {jenis}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errors.Jenis && <p className="text-sm text-destructive">{form.errors.Jenis}</p>}
            </div>
          </div>

          <div className="space-y-1.5">
            <Label>Judul Pekerjaan</Label>
            <Input
              value={form.data.Judul}
              onChange={(e) => form.setData('Judul', e.target.value)}
              placeholder="Contoh: Perbaikan Pompa Utama Chiller 2"
            />
            {form.errors.Judul && <p className="text-sm text-destructive">{form.errors.Judul}</p>}
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Prioritas</Label>
              <Select
                value={form.data.Prioritas}
                onValueChange={(val) => form.setData('Prioritas', val as PrioritasPerintahKerja)}
              >
                <SelectTrigger className="w-full cursor-pointer">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {DAFTAR_PRIORITAS.map((p) => (
                    <SelectItem key={p} value={p} className="cursor-pointer">
                      {p}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errors.Prioritas && (
                <p className="text-sm text-destructive">{form.errors.Prioritas}</p>
              )}
            </div>

            <div className="space-y-1.5">
              <Label>Lokasi</Label>
              <Select
                value={form.data.LokasiId}
                onValueChange={(val) => form.setData('LokasiId', val)}
              >
                <SelectTrigger className="w-full cursor-pointer">
                  <SelectValue placeholder="Pilih lokasi kerja" />
                </SelectTrigger>
                <SelectContent>
                  {lokasi.map((l) => (
                    <SelectItem key={l.Id} value={l.Id} className="cursor-pointer">
                      {l.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {form.errors.LokasiId && (
                <p className="text-sm text-destructive">{form.errors.LokasiId}</p>
              )}
            </div>
          </div>

          <div className="space-y-1.5">
            <Label>Aset yang Ditangani (Pilih satu atau lebih)</Label>
            <div className="max-h-36 overflow-y-auto rounded-md border border-input p-2 space-y-1">
              {aset.map((a) => {
                const dipilih = form.data.AsetIds.includes(a.Id);
                return (
                  <label
                    key={a.Id}
                    className="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-muted cursor-pointer"
                  >
                    <input
                      type="checkbox"
                      checked={dipilih}
                      onChange={() => toggleAset(a.Id)}
                      className="cursor-pointer rounded border-gray-300 text-teknisi-700 focus:ring-teknisi-600"
                    />
                    <span className="font-mono text-xs text-muted-foreground">{a.KodeAset}</span>
                    <span>{a.Nama}</span>
                  </label>
                );
              })}
            </div>
            {form.errors.AsetIds && <p className="text-sm text-destructive">{form.errors.AsetIds}</p>}
          </div>

          <div className="space-y-1.5">
            <Label>Deskripsi / Instruksi Pekerjaan</Label>
            <Textarea
              rows={3}
              value={form.data.Deskripsi}
              onChange={(e) => form.setData('Deskripsi', e.target.value)}
              placeholder="Jelaskan detail perbaikan, gejala, atau langkah awal yang diharapkan..."
            />
            {form.errors.Deskripsi && (
              <p className="text-sm text-destructive">{form.errors.Deskripsi}</p>
            )}
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Jadwal Mulai</Label>
              <Input
                type="datetime-local"
                value={form.data.DijadwalkanMulaiPada}
                onChange={(e) => form.setData('DijadwalkanMulaiPada', e.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label>Jadwal Selesai</Label>
              <Input
                type="datetime-local"
                value={form.data.DijadwalkanSelesaiPada}
                onChange={(e) => form.setData('DijadwalkanSelesaiPada', e.target.value)}
              />
            </div>
          </div>

          <div className="flex flex-wrap gap-6 pt-2">
            <label className="flex items-center gap-2 cursor-pointer text-sm font-medium">
              <input
                type="checkbox"
                checked={form.data.MembutuhkanWaktuHenti}
                onChange={(e) => form.setData('MembutuhkanWaktuHenti', e.target.checked)}
                className="cursor-pointer rounded border-gray-300 text-teknisi-700 focus:ring-teknisi-600"
              />
              Membutuhkan Downtime Mesin / Aset
            </label>
            <label className="flex items-center gap-2 cursor-pointer text-sm font-medium">
              <input
                type="checkbox"
                checked={form.data.MembutuhkanPersetujuan}
                onChange={(e) => form.setData('MembutuhkanPersetujuan', e.target.checked)}
                className="cursor-pointer rounded border-gray-300 text-teknisi-700 focus:ring-teknisi-600"
              />
              Perlu Verifikasi / Persetujuan Hasil
            </label>
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing} className="cursor-pointer">
              Simpan Perintah Kerja
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function formatTanggal(nilai: string | null): string {
  return nilai
    ? new Date(nilai).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
    : '—';
}

function formatRupiah(nilai: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(nilai);
}

export default function PerintahKerjaIndex({
  perintahKerja,
  keluhan,
  aset,
  lokasi,
  filter,
  dapatMengelola,
}: Props) {
  const filterData = (kunci: 'status' | 'prioritas', nilai: string) => {
    router.get(
      '/pemeliharaan/perintah-kerja',
      { ...filter, [kunci]: nilai === TANPA ? undefined : nilai },
      { preserveState: true, replace: true }
    );
  };

  return (
    <AppLayout>
      <Head title="Perintah Kerja" />
      <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Perintah Kerja</h1>
          <p className="text-sm text-muted-foreground">
            {dapatMengelola
              ? 'Tugaskan teknisi, pantau jam kerja, catat downtime aset, dan kontrol biaya perbaikan.'
              : 'Daftar penugasan perintah kerja dan pencatatan operasional Anda.'}
          </p>
        </div>
        {dapatMengelola && (
          <DialogBuatPerintahKerja keluhan={keluhan} aset={aset} lokasi={lokasi} />
        )}
      </div>

      <div className="mb-4 grid gap-2 sm:grid-cols-2 lg:max-w-xl">
        <Select
          value={filter.status ?? TANPA}
          onValueChange={(val) => filterData('status', val)}
        >
          <SelectTrigger className="w-full cursor-pointer">
            <SelectValue placeholder="Semua status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={TANPA} className="cursor-pointer">
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
          value={filter.prioritas ?? TANPA}
          onValueChange={(val) => filterData('prioritas', val)}
        >
          <SelectTrigger className="w-full cursor-pointer">
            <SelectValue placeholder="Semua prioritas" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value={TANPA} className="cursor-pointer">
              Semua prioritas
            </SelectItem>
            {DAFTAR_PRIORITAS.map((p) => (
              <SelectItem key={p} value={p} className="cursor-pointer">
                {p}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {perintahKerja.length === 0 ? (
        <EmptyState
          judul="Belum ada perintah kerja"
          deskripsi="Perintah kerja perbaikan atau pemeliharaan aset akan tercatat di sini."
        />
      ) : (
        <div className="overflow-x-auto rounded-lg border border-border bg-card">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-border bg-muted/40 text-xs font-medium text-muted-foreground">
              <tr>
                <th className="px-4 py-3">Nomor</th>
                <th className="px-4 py-3">Judul & Jenis</th>
                <th className="px-4 py-3">Aset & Lokasi</th>
                <th className="px-4 py-3">Prioritas</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Teknisi</th>
                <th className="px-4 py-3">Waktu / Henti</th>
                <th className="px-4 py-3">Total Biaya</th>
                <th className="px-4 py-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {perintahKerja.map((item) => {
                const asetUtama = item.Aset?.find((a) => a.Utama) ?? item.Aset?.[0];
                return (
                  <tr key={item.Id} className="hover:bg-muted/30 transition-colors">
                    <td className="px-4 py-3 font-mono text-xs font-medium">
                      <Link
                        href={`/pemeliharaan/perintah-kerja/${item.Id}`}
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
                    <td className="px-4 py-3">
                      <Badge variant={VARIAN_PRIORITAS_PERINTAH_KERJA[item.Prioritas]}>
                        {item.Prioritas}
                      </Badge>
                    </td>
                    <td className="px-4 py-3">
                      <Badge variant={VARIAN_STATUS_PERINTAH_KERJA[item.Status]}>
                        {item.Status}
                      </Badge>
                    </td>
                    <td className="px-4 py-3">
                      {item.Penugasan && item.Penugasan.length > 0 ? (
                        <div className="text-xs">
                          <span className="font-medium">
                            {item.Penugasan[0].NamaPengguna ?? 'Teknisi'}
                          </span>
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
                      <div className="text-muted-foreground">
                        Henti: {item.TotalDowntimeMenit ?? 0}m
                      </div>
                    </td>
                    <td className="px-4 py-3 text-xs font-medium">
                      {formatRupiah(item.TotalBiaya ?? 0)}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <Button asChild size="sm" variant="outline" className="cursor-pointer">
                        <Link href={`/pemeliharaan/perintah-kerja/${item.Id}`}>Buka</Link>
                      </Button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </AppLayout>
  );
}
