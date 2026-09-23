import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Plus, Pencil } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';
import { DatePicker } from '@/components/ui/date-picker';
import { tambahHari, tanggalHariIni } from '@/lib/waktu';

const TANPA_JENIS = '__none__';
const INTERNAL = '__internal__';

function hitungTanggalBerikutnya(tanggalMulai: string, intervalHari: number): string {
  if (!tanggalMulai || isNaN(intervalHari)) {
    return '';
  }

  return tambahHari(tanggalMulai, Number(intervalHari));
}

function nilaiAwal(rencana: RencanaKalibrasi | null) {
  const hariIni = tanggalHariIni();

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
  wajib,
}: {
  rencana: RencanaKalibrasi | null;
  aset: { Id: string; KodeAset: string; Nama: string }[];
  jenisKalibrasi: { Id: string; Kode: string; Nama: string }[];
  penyedia: { Id: string; Kode: string; Nama: string }[];
  wajib: AturanWajib;
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

        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={simpan} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="AsetId" htmlFor="AsetId">
                Pilih Aset / Instrumen *
              </Label>
              <Combobox
                nilai={form.data.AsetId}
                onPilih={(val) => form.setData('AsetId', val)}
                opsi={opsiDari(aset, (a) => `${a.KodeAset} - ${a.Nama}`)}
                placeholder="Pilih Aset"
                className="h-9 text-xs"
              />
              {form.errors.AsetId && <p className="text-xs text-destructive">{form.errors.AsetId}</p>}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label nama="JenisKalibrasiId" htmlFor="JenisKalibrasiId">
                  Jenis Kalibrasi
                </Label>
                <Combobox
                  nilai={form.data.JenisKalibrasiId || TANPA_JENIS}
                  onPilih={(val) => form.setData('JenisKalibrasiId', val === TANPA_JENIS ? '' : val)}
                  opsi={[
                    { nilai: TANPA_JENIS, label: 'Tanpa Spesifikasi Jenis' },
                    ...opsiDari(jenisKalibrasi, (jk) => jk.Nama),
                  ]}
                  placeholder="Pilih Jenis (Opsional)"
                  className="h-9 text-xs"
                />
              </div>

              <div className="space-y-1.5">
                <Label nama="PenyediaId" htmlFor="PenyediaId">
                  Penyedia / Laboratorium Rekanan
                </Label>
                <Combobox
                  nilai={form.data.PenyediaId || INTERNAL}
                  onPilih={(val) => form.setData('PenyediaId', val === INTERNAL ? '' : val)}
                  opsi={[
                    { nilai: INTERNAL, label: 'Internal Perusahaan' },
                    ...opsiDari(penyedia, (p) => p.Nama),
                  ]}
                  placeholder="Internal / Rekanan"
                  className="h-9 text-xs"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div className="space-y-1.5">
                <Label nama="IntervalHari" htmlFor="IntervalHari">
                  Interval (Hari) *
                </Label>
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
                <Label nama="TanggalMulai" htmlFor="TanggalMulai">
                  Tanggal Mulai *
                </Label>
                <DatePicker
                  value={form.data.TanggalMulai}
                  onChange={(nilai) => {
                    const tanggalMulai = nilai;
                    form.setData({
                      ...form.data,
                      TanggalMulai: tanggalMulai,
                      TanggalBerikutnya: hitungTanggalBerikutnya(tanggalMulai, form.data.IntervalHari),
                    });
                  }}
                  id="TanggalMulai"
                  className="h-9 text-xs"
                  required
                />
              </div>

              <div className="space-y-1.5">
                <Label nama="TanggalBerikutnya" htmlFor="TanggalBerikutnya">
                  Jatuh Tempo Berikutnya *
                </Label>
                <DatePicker
                  value={form.data.TanggalBerikutnya}
                  onChange={(nilai) => form.setData('TanggalBerikutnya', nilai)}
                  id="TanggalBerikutnya"
                  className="h-9 text-xs"
                  required
                />
              </div>
            </div>

            <div className="space-y-1.5">
              <Label nama="PeringatanHariSebelum" htmlFor="PeringatanHariSebelum">
                Jendela Pengingat Peringatan (Hari Sebelum)
              </Label>
              <Input
                id="PeringatanHariSebelum"
                type="number"
                min={1}
                placeholder="30"
                value={form.data.PeringatanHariSebelum}
                onChange={(e) => form.setData('PeringatanHariSebelum', Number(e.target.value))}
                className="h-9 text-xs"
              />
              <p className="text-[11px] text-grafit-500">
                Sistem akan memicu status "Segera Jatuh Tempo" dan mengirim notifikasi saat waktu tersisa
                mencapai nilai ini.
              </p>
            </div>

            <div className="flex items-center justify-between p-2.5 rounded-lg border border-border">
              <div className="space-y-0.5">
                <Label nama="AktifRencana" htmlFor="AktifRencana">
                  Status Aktif
                </Label>
                <p className="text-xs text-grafit-500">
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
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
