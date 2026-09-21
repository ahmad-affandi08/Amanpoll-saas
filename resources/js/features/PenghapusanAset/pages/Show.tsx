import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { EmptyState } from '@/components/shared/EmptyState';
import { formatUang } from '@/lib/uang';
import type { PengajuanPenghapusanAset } from '@/features/SiklusAset/types';
import { VARIAN_BADGE_STATUS_PENGHAPUSAN } from '@/features/SiklusAset/status';
import type { Aset } from '@/features/Aset/types';
import { rutePenghapusanAset } from '@/features/PenghapusanAset/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';

interface Props {
  pengajuan: PengajuanPenghapusanAset;
  aset: Aset[];
}

function DialogTambahAset({ pengajuan, aset }: { pengajuan: PengajuanPenghapusanAset; aset: Aset[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ AsetId: '', NilaiBukuSaatPenghapusan: '', HasilPelepasan: '' });

  const asetTersedia = aset.filter((a) => !pengajuan.DetailPenghapusanAset.some((d) => d.AsetId === a.Id));

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(rutePenghapusanAset.detail2(pengajuan.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant="outline">
          Tambah Aset
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Tambah Aset ke Pengajuan</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Aset</Label>
            <Select value={form.data.AsetId} onValueChange={(v) => form.setData('AsetId', v)}>
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Pilih aset" />
              </SelectTrigger>
              <SelectContent>
                {asetTersedia.map((a) => (
                  <SelectItem key={a.Id} value={a.Id}>
                    {a.Nama} ({a.KodeAset})
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <Label>Nilai Buku Saat Ini</Label>
              <Input
                type="number"
                min={0}
                value={form.data.NilaiBukuSaatPenghapusan}
                onChange={(e) => form.setData('NilaiBukuSaatPenghapusan', e.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label>Estimasi Hasil Pelepasan</Label>
              <Input
                type="number"
                min={0}
                value={form.data.HasilPelepasan}
                onChange={(e) => form.setData('HasilPelepasan', e.target.value)}
              />
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing || !form.data.AsetId}>
              Tambah
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function PenghapusanAsetShow({ pengajuan, aset }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapusDetail = async (detailId: string) => {
    if (
      !(await konfirmasi({
        judul: 'Hapus aset ini dari pengajuan?',
        deskripsi: 'Aset dikeluarkan dari pengajuan; status asetnya tidak berubah.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(rutePenghapusanAset.detailDetail(detailId), { preserveScroll: true });
  };

  const submit = () => router.post(rutePenghapusanAset.submit(pengajuan.Id), {}, { preserveScroll: true });
  const batalkan = async () => {
    if (
      !(await konfirmasi({
        judul: 'Batalkan pengajuan penghapusan ini?',
        deskripsi: 'Pengajuan tidak dapat dilanjutkan dan aset tetap aktif.',
        ragam: 'bahaya',
        ilustrasi: '/assets/3d/peringatan.webp',
      }))
    )
      return;
    router.post(rutePenghapusanAset.batalkan(pengajuan.Id), {}, { preserveScroll: true });
  };
  const eksekusi = async () => {
    if (
      !(await konfirmasi({
        judul: 'Eksekusi penghapusan?',
        deskripsi: 'Aset akan diarsipkan dan tidak dapat dikembalikan lewat halaman ini.',
        ragam: 'bahaya',
        ilustrasi: '/assets/3d/peringatan.webp',
      }))
    )
      return;
    router.post(rutePenghapusanAset.eksekusi(pengajuan.Id), {}, { preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title={pengajuan.Nomor} />
      <div className="space-y-6">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p className="font-mono text-sm text-muted-foreground">{pengajuan.Nomor}</p>
            <h1 className="text-2xl font-semibold tracking-tight text-foreground">
              {pengajuan.MetodePenghapusan ?? 'Penghapusan Aset'}
            </h1>
            <p className="max-w-xl text-sm text-muted-foreground">{pengajuan.Alasan}</p>
          </div>
          <div className="flex items-center gap-2">
            <Badge variant={VARIAN_BADGE_STATUS_PENGHAPUSAN[pengajuan.Status]}>{pengajuan.Status}</Badge>
            {pengajuan.Status === 'Draft' && (
              <Button size="sm" onClick={submit}>
                Submit
              </Button>
            )}
            {(pengajuan.Status === 'Draft' || pengajuan.Status === 'Menunggu') && (
              <Button size="sm" variant="outline" onClick={batalkan}>
                Batalkan
              </Button>
            )}
            {pengajuan.Status === 'Disetujui' && (
              <Button size="sm" variant="destructive" onClick={eksekusi}>
                Eksekusi
              </Button>
            )}
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <div className="rounded-[9px] border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">Diajukan Oleh</p>
            <p className="text-sm font-medium text-foreground">{pengajuan.NamaDiajukanOleh ?? '—'}</p>
          </div>
          <div className="rounded-[9px] border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">Diselesaikan Pada</p>
            <p className="text-sm font-medium text-foreground">
              {pengajuan.DiselesaikanPada
                ? new Date(pengajuan.DiselesaikanPada).toLocaleString('id-ID')
                : '—'}
            </p>
          </div>
        </div>

        <div className="rounded-[9px] border border-border bg-card p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-foreground">Daftar Aset</h2>
            {pengajuan.Status === 'Draft' && <DialogTambahAset pengajuan={pengajuan} aset={aset} />}
          </div>
          {pengajuan.DetailPenghapusanAset.length === 0 && (
            <EmptyState
              judul="Belum ada aset ditambahkan."
              deskripsi="Tambahkan aset yang akan dihapuskan."
            />
          )}
          <div className="space-y-2">
            {pengajuan.DetailPenghapusanAset.map((d) => (
              <div
                key={d.Id}
                className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
              >
                <div>
                  <span className="font-medium text-foreground">{d.NamaAset ?? '—'}</span>
                  <span className="ml-2 font-mono text-xs text-muted-foreground">{d.KodeAset}</span>
                  {d.NilaiBukuSaatPenghapusan && (
                    <span className="ml-2 text-xs text-muted-foreground">
                      Nilai buku: {formatUang(d.NilaiBukuSaatPenghapusan)}
                    </span>
                  )}
                </div>
                <div className="flex items-center gap-2">
                  <Badge
                    variant={
                      d.Status === 'Selesai' ? 'sukses' : d.Status === 'Dibatalkan' ? 'netral' : 'perhatian'
                    }
                  >
                    {d.Status}
                  </Badge>
                  {pengajuan.Status === 'Draft' && (
                    <Button variant="ghost" size="sm" onClick={() => hapusDetail(d.Id)}>
                      Hapus
                    </Button>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
