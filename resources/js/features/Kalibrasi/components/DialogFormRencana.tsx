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
import { TANPA_PILIHAN, opsiDari, opsiUnitPengelola } from '@/lib/pilihan';
import type { UnitPengelolaRingkas } from '@/features/UnitOrganisasi/types';
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
        UnitPengelolaId: rencana.UnitPengelolaId ?? TANPA_PILIHAN,
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
        UnitPengelolaId: TANPA_PILIHAN,
      };
}

export function DialogFormRencana({
  rencana,
  aset,
  jenisKalibrasi,
  penyedia,
  wajib,
  pilihanUnitPengelola = [],
  unitPengelolaDipakai = false,
}: {
  rencana: RencanaKalibrasi | null;
  aset: { Id: string; KodeAset: string; Nama: string; UnitPengelolaId?: string | null }[];
  jenisKalibrasi: { Id: string; Kode: string; Nama: string }[];
  penyedia: { Id: string; Kode: string; Nama: string }[];
  wajib: AturanWajib;
  /** Unit pengelola aktif (PRD 8.21); isiannya hanya tampil bila organisasi memakai fitur ini. */
  pilihanUnitPengelola?: UnitPengelolaRingkas[];
  unitPengelolaDipakai?: boolean;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm(nilaiAwal(rencana));
  /** Selama isian unit pengelola belum disentuh, memilih aset mengisikan unit pengelola asetnya. */
  const [unitDisentuh, setUnitDisentuh] = useState(false);

  /** Nilai tersimpan yang unitnya kini nonaktif tetap muncul di pilihan, supaya tidak tampil kosong. */
  const opsiUnit =
    rencana?.unit_pengelola && !pilihanUnitPengelola.some((unit) => unit.Id === rencana.unit_pengelola?.Id)
      ? [...pilihanUnitPengelola, rencana.unit_pengelola]
      : pilihanUnitPengelola;

  const pilihAset = (asetId: string) => {
    const unitAset = aset.find((a) => a.Id === asetId)?.UnitPengelolaId;

    form.setData({
      ...form.data,
      AsetId: asetId,
      UnitPengelolaId: !unitDisentuh && !rencana && unitAset ? unitAset : form.data.UnitPengelolaId,
    });
  };

  /** useForm mengunci nilai saat mount, jadi isinya disegarkan dari props tiap kali dibuka. */
  const ubahBuka = (terbuka: boolean) => {
    if (terbuka) {
      form.setData(nilaiAwal(rencana));
      form.clearErrors();
      setUnitDisentuh(false);
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

    form.transform((data) => ({
      ...data,
      UnitPengelolaId: data.UnitPengelolaId === TANPA_PILIHAN ? null : data.UnitPengelolaId,
    }));

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
              <Label nama="AsetId" htmlFor="AsetId" wajib>
                Pilih Aset / Instrumen
              </Label>
              <Combobox
                nilai={form.data.AsetId}
                onPilih={pilihAset}
                opsi={opsiDari(aset, (a) => `${a.KodeAset} - ${a.Nama}`)}
                placeholder="Pilih Aset"
              />
              {form.errors.AsetId && <p className="text-xs text-destructive">{form.errors.AsetId}</p>}
            </div>

            {unitPengelolaDipakai && (
              <div className="space-y-1.5">
                <Label nama="UnitPengelolaId" htmlFor="UnitPengelolaId">
                  Unit Pengelola
                </Label>
                <Combobox
                  nilai={form.data.UnitPengelolaId}
                  onPilih={(val) => {
                    setUnitDisentuh(true);
                    form.setData('UnitPengelolaId', val);
                  }}
                  opsi={opsiUnitPengelola(opsiUnit)}
                  placeholder="Pilih unit pengelola"
                />
                {!rencana && !unitDisentuh && (
                  <p className="text-[11px] text-grafit-500">Terisi dari unit pengelola aset yang dipilih.</p>
                )}
                {form.errors.UnitPengelolaId && (
                  <p className="text-xs text-destructive">{form.errors.UnitPengelolaId}</p>
                )}
              </div>
            )}

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
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div className="space-y-1.5">
                <Label nama="IntervalHari" htmlFor="IntervalHari" wajib>
                  Interval (Hari)
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
                />
              </div>

              <div className="space-y-1.5">
                <Label nama="TanggalMulai" htmlFor="TanggalMulai" wajib>
                  Tanggal Mulai
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
                  required
                />
              </div>

              <div className="space-y-1.5">
                <Label nama="TanggalBerikutnya" htmlFor="TanggalBerikutnya" wajib>
                  Jatuh Tempo Berikutnya
                </Label>
                <DatePicker
                  value={form.data.TanggalBerikutnya}
                  onChange={(nilai) => form.setData('TanggalBerikutnya', nilai)}
                  id="TanggalBerikutnya"
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
              />
              <p className="text-[11px] text-grafit-500">
                Sistem akan memicu status "Segera Jatuh Tempo" dan mengirim notifikasi saat waktu tersisa
                mencapai nilai ini.
              </p>
            </div>

            <div className="flex items-center justify-between p-2.5 rounded-md border border-border">
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
