import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Plus, Pencil } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
import type { RencanaKalibrasi } from '@/features/Kalibrasi/types';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';

const TANPA_JENIS = '__none__';
const INTERNAL = '__internal__';

function hitungTanggalBerikutnya(tanggalMulai: string, intervalHari: number): string {
  if (!tanggalMulai || isNaN(intervalHari)) {
    return '';
  }

  const tanggal = new Date(tanggalMulai);
  tanggal.setDate(tanggal.getDate() + Number(intervalHari));

  return tanggal.toISOString().split('T')[0];
}

function nilaiAwal(rencana: RencanaKalibrasi | null) {
  const hariIni = new Date().toISOString().split('T')[0];

  return rencana
    ? {
        AsetId: rencana.AsetId,
        JenisKalibrasiId: rencana.JenisKalibrasiId ?? '',
        PenyediaId: rencana.PenyediaId ?? '',
        IntervalHari: rencana.IntervalHari,
        TanggalMulai: rencana.TanggalMulai,
        TanggalBerikutnya: rencana.TanggalBerikutnya,
        PeringatanHariSebelum: rencana.PeringatanHariSebelum,
        Aktif: rencana.Aktif,
      }
    : {
        AsetId: '',
        JenisKalibrasiId: '',
        PenyediaId: '',
        IntervalHari: 365,
        TanggalMulai: hariIni,
        TanggalBerikutnya: hitungTanggalBerikutnya(hariIni, 365),
        PeringatanHariSebelum: 30,
        Aktif: true,
      };
}

export function DialogFormRencana({
  rencana,
  aset,
  jenisKalibrasi,
  penyedia,
}: {
  rencana: RencanaKalibrasi | null;
  aset: { Id: string; KodeAset: string; Nama: string }[];
  jenisKalibrasi: { Id: string; Kode: string; Nama: string }[];
  penyedia: { Id: string; Kode: string; Nama: string }[];
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm(nilaiAwal(rencana));

  /** useForm mengunci nilai saat mount, jadi isinya disegarkan dari props tiap kali dibuka. */
  const ubahBuka = (terbuka: boolean) => {
    if (terbuka) {
      form.setData(nilaiAwal(rencana));
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

    if (rencana) {
      form.put(ruteKalibrasi.rencanaDetail(rencana.Id), opsi);
    } else {
      form.post(ruteKalibrasi.rencana, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={ubahBuka}>
      <DialogTrigger asChild>
        {rencana ? (
          <Button variant="ghost" size="icon" className="h-7 w-7 text-muted-foreground hover:text-foreground">
            <Pencil className="size-3.5" />
          </Button>
        ) : (
          <Button size="sm">
            <Plus className="mr-1.5 size-4" />
            Buat Rencana Kalibrasi
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{rencana ? 'Edit Rencana Kalibrasi' : 'Buat Rencana Kalibrasi Baru'}</DialogTitle>
          <DialogDescription>
            Tentukan aset, siklus periode kalibrasi ulang, dan jendela peringatan jatuh tempo.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={simpan} className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="AsetId">Pilih Aset / Instrumen *</Label>
            <Select value={form.data.AsetId} onValueChange={(val) => form.setData('AsetId', val)} required>
              <SelectTrigger className="h-9 text-xs">
                <SelectValue placeholder="Pilih Aset" />
              </SelectTrigger>
              <SelectContent className="max-h-56">
                {aset.map((a) => (
                  <SelectItem key={a.Id} value={a.Id}>
                    {a.KodeAset} - {a.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.AsetId && <p className="text-xs text-rose-600">{form.errors.AsetId}</p>}
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label htmlFor="JenisKalibrasiId">Jenis Kalibrasi</Label>
              <Select
                value={form.data.JenisKalibrasiId || TANPA_JENIS}
                onValueChange={(val) => form.setData('JenisKalibrasiId', val === TANPA_JENIS ? '' : val)}
              >
                <SelectTrigger className="h-9 text-xs">
                  <SelectValue placeholder="Pilih Jenis (Opsional)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA_JENIS}>Tanpa Spesifikasi Jenis</SelectItem>
                  {jenisKalibrasi.map((jk) => (
                    <SelectItem key={jk.Id} value={jk.Id}>
                      {jk.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="PenyediaId">Penyedia / Laboratorium Rekanan</Label>
              <Select
                value={form.data.PenyediaId || INTERNAL}
                onValueChange={(val) => form.setData('PenyediaId', val === INTERNAL ? '' : val)}
              >
                <SelectTrigger className="h-9 text-xs">
                  <SelectValue placeholder="Internal / Rekanan" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={INTERNAL}>Internal Perusahaan</SelectItem>
                  {penyedia.map((p) => (
                    <SelectItem key={p.Id} value={p.Id}>
                      {p.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div className="space-y-1.5">
              <Label htmlFor="IntervalHari">Interval (Hari) *</Label>
              <Input
                id="IntervalHari"
                type="number"
                min={1}
                placeholder="365"
                value={form.data.IntervalHari}
                onChange={(e) => {
                  const interval = Number(e.target.value);
                  form.setData({
                    ...form.data,
                    IntervalHari: interval,
                    TanggalBerikutnya: hitungTanggalBerikutnya(form.data.TanggalMulai, interval),
                  });
                }}
                required
                className="h-9 text-xs"
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="TanggalMulai">Tanggal Mulai *</Label>
              <Input
                id="TanggalMulai"
                type="date"
                value={form.data.TanggalMulai}
                onChange={(e) => {
                  const tanggalMulai = e.target.value;
                  form.setData({
                    ...form.data,
                    TanggalMulai: tanggalMulai,
                    TanggalBerikutnya: hitungTanggalBerikutnya(tanggalMulai, form.data.IntervalHari),
                  });
                }}
                required
                className="h-9 text-xs"
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="TanggalBerikutnya">Jatuh Tempo Berikutnya *</Label>
              <Input
                id="TanggalBerikutnya"
                type="date"
                value={form.data.TanggalBerikutnya}
                onChange={(e) => form.setData('TanggalBerikutnya', e.target.value)}
                required
                className="h-9 text-xs"
              />
            </div>
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="PeringatanHariSebelum">Jendela Pengingat Peringatan (Hari Sebelum)</Label>
            <Input
              id="PeringatanHariSebelum"
              type="number"
              min={1}
              placeholder="30"
              value={form.data.PeringatanHariSebelum}
              onChange={(e) => form.setData('PeringatanHariSebelum', Number(e.target.value))}
              className="h-9 text-xs"
            />
            <p className="text-[11px] text-zinc-500">
              Sistem akan memicu status "Segera Jatuh Tempo" dan mengirim notifikasi saat waktu tersisa
              mencapai nilai ini.
            </p>
          </div>

          <div className="flex items-center justify-between p-2.5 rounded-lg border border-border">
            <div className="space-y-0.5">
              <Label htmlFor="AktifRencana">Status Aktif</Label>
              <p className="text-xs text-zinc-500">
                Rencana aktif diperhitungkan dalam kepatuhan dan notifikasi.
              </p>
            </div>
            <Switch
              id="AktifRencana"
              checked={form.data.Aktif}
              onCheckedChange={(checked) => form.setData('Aktif', checked)}
            />
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => setBuka(false)}>
              Batal
            </Button>
            <Button type="submit" disabled={form.processing}>
              {form.processing ? 'Menyimpan...' : 'Simpan Rencana'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
