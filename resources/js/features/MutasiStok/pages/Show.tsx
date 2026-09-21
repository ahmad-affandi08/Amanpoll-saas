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
import type { MutasiStok } from '@/features/Persediaan/types';
import { VARIAN_BADGE_STATUS_MUTASI_STOK } from '@/features/Persediaan/status';
import { ruteMutasiStok } from '@/features/MutasiStok/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { PageHeader } from '@/components/shared/PageHeader';

interface SukuCadangRingkas {
  Id: string;
  Nama: string;
  Kode: string;
}

interface Props {
  mutasiStok: MutasiStok;
  sukuCadang: SukuCadangRingkas[];
}

function DialogTambahDetail({
  mutasiStok,
  sukuCadang,
}: {
  mutasiStok: MutasiStok;
  sukuCadang: SukuCadangRingkas[];
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ SukuCadangId: '', Jumlah: '', HargaSatuan: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteMutasiStok.detail2(mutasiStok.Id), form.data, {
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
          Tambah Baris
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Tambah Baris Detail</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Suku Cadang</Label>
            <Select value={form.data.SukuCadangId} onValueChange={(v) => form.setData('SukuCadangId', v)}>
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Pilih suku cadang" />
              </SelectTrigger>
              <SelectContent>
                {sukuCadang.map((s) => (
                  <SelectItem key={s.Id} value={s.Id}>
                    {s.Nama} ({s.Kode})
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <Label>Jumlah {mutasiStok.Jenis === 'Adjustment' && '(boleh negatif)'}</Label>
              <Input
                type="number"
                value={form.data.Jumlah}
                onChange={(e) => form.setData('Jumlah', e.target.value)}
              />
              {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
            </div>
            <div className="space-y-1.5">
              <Label>Harga Satuan</Label>
              <Input
                type="number"
                min={0}
                value={form.data.HargaSatuan}
                onChange={(e) => form.setData('HargaSatuan', e.target.value)}
              />
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing || !form.data.SukuCadangId}>
              Tambah
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function MutasiStokShow({ mutasiStok, sukuCadang }: Props) {
  const konfirmasi = useKonfirmasi();
  const hapusDetail = async (detailId: string) => {
    if (
      !(await konfirmasi({
        judul: 'Hapus baris ini dari mutasi?',
        deskripsi: 'Saldo stok belum berubah selama mutasi masih berstatus draft.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(`/detail-mutasi-stok/${detailId}`, { preserveScroll: true });
  };

  const posting = async () => {
    if (
      !(await konfirmasi({
        judul: 'Posting mutasi ini?',
        deskripsi: 'Saldo stok akan berubah secara permanen.',
        ragam: 'perhatian',
      }))
    )
      return;
    router.post(ruteMutasiStok.posting(mutasiStok.Id), {}, { preserveScroll: true });
  };

  const batalkan = async () => {
    if (
      !(await konfirmasi({
        judul: 'Batalkan draft mutasi ini?',
        deskripsi: 'Draft tidak dapat diposting lagi dan saldo stok tidak berubah.',
        ragam: 'bahaya',
        ilustrasi: '/assets/3d/peringatan.webp',
      }))
    )
      return;
    router.post(ruteMutasiStok.batalkan(mutasiStok.Id), {}, { preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title={mutasiStok.Nomor} />
      <div className="space-y-6">
        <PageHeader
          judul={mutasiStok.Jenis}
          labelBreadcrumb={mutasiStok.Nomor}
          lencana={
            <Badge variant={VARIAN_BADGE_STATUS_MUTASI_STOK[mutasiStok.Status]}>{mutasiStok.Status}</Badge>
          }
          deskripsi={
            <>
              <span className="font-mono">{mutasiStok.Nomor}</span> · {mutasiStok.NamaGudangAsal ?? '—'}{' '}
              {(mutasiStok.NamaGudangAsal || mutasiStok.NamaGudangTujuan) && '→'}{' '}
              {mutasiStok.NamaGudangTujuan ?? '—'}
            </>
          }
          meta={mutasiStok.Catatan ? <span className="max-w-xl">{mutasiStok.Catatan}</span> : undefined}
          aksi={
            <>
              {mutasiStok.Status === 'Draft' && (
                <Button size="sm" onClick={posting}>
                  Posting
                </Button>
              )}
              {mutasiStok.Status === 'Draft' && (
                <Button size="sm" variant="outline" onClick={batalkan}>
                  Batalkan
                </Button>
              )}
            </>
          }
        />

        <div className="rounded-[9px] border border-border bg-card p-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-foreground">Detail Baris</h2>
            {mutasiStok.Status === 'Draft' && (
              <DialogTambahDetail mutasiStok={mutasiStok} sukuCadang={sukuCadang} />
            )}
          </div>
          {mutasiStok.DetailMutasiStok.length === 0 ? (
            <EmptyState
              judul="Belum ada baris detail."
              deskripsi="Tambahkan suku cadang yang terlibat dalam mutasi ini."
            />
          ) : (
            <div className="space-y-2">
              {mutasiStok.DetailMutasiStok.map((d) => (
                <div
                  key={d.Id}
                  className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
                >
                  <div>
                    <span className="font-medium text-foreground">{d.NamaSukuCadang}</span>
                    <span className="ml-2 font-mono text-xs text-muted-foreground">{d.KodeSukuCadang}</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="font-semibold text-foreground">
                      {d.Jumlah} {d.SatuanDasar}
                    </span>
                    {mutasiStok.Status === 'Draft' && (
                      <Button variant="ghost" size="sm" onClick={() => hapusDetail(d.Id)}>
                        Hapus
                      </Button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
