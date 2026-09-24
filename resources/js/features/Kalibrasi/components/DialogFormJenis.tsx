import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Plus, Pencil } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import type { JenisKalibrasi } from '@/features/Kalibrasi/types';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

function nilaiAwal(jenis: JenisKalibrasi | null) {
  return {
    Kode: jenis?.Kode ?? '',
    Nama: jenis?.Nama ?? '',
    Deskripsi: jenis?.Deskripsi ?? '',
    Aktif: jenis?.Aktif ?? true,
  };
}

export function DialogFormJenis({ jenis, wajib }: { jenis: JenisKalibrasi | null; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm(nilaiAwal(jenis));

  /** useForm mengunci nilai saat mount, jadi isinya disegarkan dari props tiap kali dibuka. */
  const ubahBuka = (terbuka: boolean) => {
    if (terbuka) {
      form.setData(nilaiAwal(jenis));
      form.clearErrors();
    }
    setBuka(terbuka);
  };

  const simpan = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    };

    if (jenis) {
      form.put(ruteKalibrasi.jenisDetail(jenis.Id), opsi);
    } else {
      form.post(ruteKalibrasi.jenis, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={ubahBuka}>
      <DialogTrigger asChild>
        {jenis ? (
          <Button
            variant="ghost"
            size="icon"
            className="sm:size-7 text-muted-foreground hover:text-foreground"
          >
            <Pencil className="size-3.5" />
          </Button>
        ) : (
          <Button size="sm">
            <Plus className="mr-1.5 size-4" />
            Tambah Jenis Kalibrasi
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{jenis ? 'Edit Jenis Kalibrasi' : 'Tambah Jenis Kalibrasi'}</DialogTitle>
          <DialogDescription>
            Tentukan kode unik, nama klasifikasi kalibrasi, dan metode atau unit pengukuran acuan.
          </DialogDescription>
        </DialogHeader>

        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={simpan} className="space-y-4">
            <BidangKode
              nilai={form.data.Kode}
              onUbah={(nilai) => form.setData('Kode', nilai)}
              galat={form.errors.Kode}
              label="Kode Jenis"
              contoh="mis. CAL-TEMP, CAL-PRESS"
            />

            <div className="space-y-1.5">
              <Label nama="Nama" htmlFor="Nama">
                Nama Jenis Kalibrasi *
              </Label>
              <Input
                id="Nama"
                placeholder="mis. Kalibrasi Suhu dan Thermocouple"
                value={form.data.Nama}
                onChange={(e) => form.setData('Nama', e.target.value)}
                required
              />
              {form.errors.Nama && <p className="text-xs text-destructive">{form.errors.Nama}</p>}
            </div>

            <div className="space-y-1.5">
              <Label nama="Deskripsi" htmlFor="Deskripsi">
                Deskripsi & Standar Acuan Metrologi
              </Label>
              <Textarea
                id="Deskripsi"
                placeholder="mis. Acuan SNI/ISO 17025. Satuan acuan Celsius (°C), rentang 0 - 500 °C."
                rows={3}
                value={form.data.Deskripsi}
                onChange={(e) => form.setData('Deskripsi', e.target.value)}
              />
            </div>

            <div className="flex items-center justify-between p-2.5 rounded-md border border-border">
              <div className="space-y-0.5">
                <Label nama="Aktif" htmlFor="Aktif">
                  Status Aktif
                </Label>
                <p className="text-xs text-grafit-500">
                  Jenis ini dapat dipilih saat membuat rencana kalibrasi baru.
                </p>
              </div>
              <Switch
                id="Aktif"
                checked={form.data.Aktif}
                onCheckedChange={(checked) => form.setData('Aktif', checked)}
              />
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setBuka(false)}>
                Batal
              </Button>
              <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Menyimpan...' : 'Simpan'}
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
