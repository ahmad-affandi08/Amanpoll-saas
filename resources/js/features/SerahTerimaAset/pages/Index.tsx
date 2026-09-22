import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import type { Paginasi } from '@/types/global';
import type { SerahTerimaAset } from '@/features/SiklusAset/types';
import { VARIAN_BADGE_STATUS_SERAH_TERIMA } from '@/features/SiklusAset/status';
import { ruteSerahTerimaAset } from '@/features/SerahTerimaAset/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  serahTerima: Paginasi<SerahTerimaAset>;
  filter: { status?: string };
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const SEMUA = '__semua__';
const DAFTAR_STATUS = ['Diserahkan', 'Diterima'];

function DialogBuatSerahTerima({ wajib }: { wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Jenis: '', Catatan: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteSerahTerimaAset.index, form.data, { onSuccess: () => setBuka(false) });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Buat Dokumen Serah Terima</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Buat Dokumen Serah Terima</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="Jenis">Jenis</Label>
              <Input
                value={form.data.Jenis}
                onChange={(e) => form.setData('Jenis', e.target.value)}
                placeholder="Peminjaman, Pengembalian, dst."
              />
              {form.errors.Jenis && <p className="text-sm text-destructive">{form.errors.Jenis}</p>}
            </div>
            <p className="text-sm text-muted-foreground">
              Daftar aset dan kondisi dilengkapi setelah dokumen dibuat.
            </p>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Buat
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function SerahTerimaAsetIndex({ serahTerima, filter, wajib }: Props) {
  const [status, setStatus] = useState(filter.status ?? SEMUA);

  const terapkanFilter = (v: string) => {
    setStatus(v);
    router.get(ruteSerahTerimaAset.index, v === SEMUA ? {} : { status: v }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  return (
    <KerangkaAplikasi>
      <Head title="Serah Terima Aset" />
      <div className="space-y-4">
        <KepalaHalaman
          judul="Serah Terima Aset"
          deskripsi="Dokumentasi serah terima aset -- pihak asal, tujuan, dan kondisi."
          aksi={
            <>
              <DialogBuatSerahTerima wajib={wajib.serahTerima} />
            </>
          }
        />

        <div className="w-56 space-y-1.5">
          <Label>Status</Label>
          <Select value={status} onValueChange={terapkanFilter}>
            <SelectTrigger>
              <SelectValue placeholder="Semua" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={SEMUA}>Semua</SelectItem>
              {DAFTAR_STATUS.map((s) => (
                <SelectItem key={s} value={s}>
                  {s}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {serahTerima.data.length === 0 && (
          <div className="rounded-[9px] border border-border bg-card">
            <KeadaanKosong
              judul="Belum ada dokumen serah terima."
              deskripsi="Dokumen serah terima aset akan muncul di sini."
            />
          </div>
        )}

        {serahTerima.data.length > 0 && (
          <div className="rounded-[9px] border border-border bg-card">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nomor</TableHead>
                  <TableHead>Jenis</TableHead>
                  <TableHead>Pihak Menyerahkan</TableHead>
                  <TableHead>Pihak Menerima</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {serahTerima.data.map((s) => (
                  <TableRow
                    key={s.Id}
                    className="cursor-pointer"
                    onClick={() => router.visit(ruteSerahTerimaAset.detail(s.Id))}
                  >
                    <TableCell className="font-mono text-xs">{s.Nomor}</TableCell>
                    <TableCell>{s.Jenis}</TableCell>
                    <TableCell>{s.NamaPihakMenyerahkan ?? '—'}</TableCell>
                    <TableCell>{s.NamaPihakMenerima ?? '—'}</TableCell>
                    <TableCell>
                      <Badge variant={VARIAN_BADGE_STATUS_SERAH_TERIMA[s.Status]}>{s.Status}</Badge>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
            <KontrolPaginasi
              meta={serahTerima.meta}
              onNavigasi={(halaman) => navigasiHalaman(halaman, filter as Record<string, string>)}
            />
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
