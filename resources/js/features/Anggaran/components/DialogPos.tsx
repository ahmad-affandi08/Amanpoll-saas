import { type FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
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
import type { Anggaran, PosAnggaran } from '@/features/Anggaran/types';
import { ruteAnggaran } from '@/features/Anggaran/api';
import { TANPA_PILIHAN } from '@/lib/pilihan';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { InputUang } from '@/components/shared/InputUang';

export function DialogPos({
  anggaran,
  pos,
  semuaPos,
  wajib,
}: {
  anggaran: Anggaran;
  pos?: PosAnggaran;
  semuaPos: PosAnggaran[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    IndukId: pos?.IndukId ?? TANPA_PILIHAN,
    Kode: pos?.Kode ?? '',
    Nama: pos?.Nama ?? '',
    Jumlah: pos?.Jumlah ?? '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({ ...data, IndukId: data.IndukId === TANPA_PILIHAN ? null : data.IndukId }));
    const opsi = { preserveScroll: true, onSuccess: () => setBuka(false) };
    if (pos) form.put(ruteAnggaran.posDetail(pos.Id), opsi);
    else form.post(ruteAnggaran.pos(anggaran.Id), opsi);
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        {pos ? (
          <Button variant="ghost" size="sm" aria-label={`Ubah ${pos.Nama}`}>
            <Pencil />
          </Button>
        ) : (
          <Button size="sm">
            <Plus /> Tambah Pos
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{pos ? 'Ubah Pos Anggaran' : 'Tambah Pos Anggaran'}</DialogTitle>
          <DialogDescription>
            Nilai anak tidak boleh melebihi pos induk; pos utama tidak boleh melampaui total anggaran.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="IndukId">Pos Induk</Label>
              <Select value={form.data.IndukId} onValueChange={(value) => form.setData('IndukId', value)}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA_PILIHAN}>Pos utama</SelectItem>
                  {semuaPos
                    .filter((item) => item.Id !== pos?.Id)
                    .map((item) => (
                      <SelectItem key={item.Id} value={item.Id}>
                        {item.Kode} — {item.Nama}
                      </SelectItem>
                    ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <BidangKode
                nilai={form.data.Kode}
                onUbah={(nilai) => form.setData('Kode', nilai)}
                galat={form.errors.Kode}
              />
              <div className="space-y-1.5">
                <Label nama="Jumlah" htmlFor="nilai-pos">
                  Nilai
                </Label>
                <InputUang
                  id="nilai-pos"
                  value={form.data.Jumlah}
                  onChange={(nilai) => form.setData('Jumlah', nilai)}
                  mataUang={anggaran.MataUang}
                />
                {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="Nama" htmlFor="nama-pos">
                Nama
              </Label>
              <Input
                id="nama-pos"
                value={form.data.Nama}
                onChange={(event) => form.setData('Nama', event.target.value)}
              />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                {pos ? 'Simpan Perubahan' : 'Tambah Pos'}
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
