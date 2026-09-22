import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { FileBadge } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import type { PelaksanaanKalibrasi } from '@/features/Kalibrasi/types';
import { ruteKalibrasi } from '@/features/Kalibrasi/api';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { DatePicker } from '@/components/ui/date-picker';

function kondisi(pelaksanaan: PelaksanaanKalibrasi, kunci: string, bawaan: string): string {
  const nilai = pelaksanaan.KondisiLingkungan?.[kunci];

  return typeof nilai === 'string' && nilai !== '' ? nilai : bawaan;
}

function nilaiAwal(pelaksanaan: PelaksanaanKalibrasi) {
  return {
    Hasil: pelaksanaan.Hasil === 'Terjadwal' ? 'Lolos' : pelaksanaan.Hasil,
    NomorSertifikat: pelaksanaan.NomorSertifikat ?? '',
    TanggalKalibrasi: pelaksanaan.TanggalKalibrasi,
    TanggalBerlakuSampai: pelaksanaan.TanggalBerlakuSampai ?? '',
    Laboratorium: pelaksanaan.Laboratorium ?? '',
    Catatan: pelaksanaan.Catatan ?? '',
    KondisiLingkungan: {
      Suhu: kondisi(pelaksanaan, 'Suhu', '20 °C'),
      Kelembapan: kondisi(pelaksanaan, 'Kelembapan', '55% RH'),
    },
  };
}

export function DialogFinalisasiKalibrasi({
  pelaksanaan,
  wajib,
}: {
  pelaksanaan: PelaksanaanKalibrasi;
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm(nilaiAwal(pelaksanaan));

  /** useForm mengunci nilai saat mount, jadi isinya disegarkan dari props tiap kali dibuka. */
  const ubahBuka = (terbuka: boolean) => {
    if (terbuka) {
      form.setData(nilaiAwal(pelaksanaan));
      form.clearErrors();
    }
    setBuka(terbuka);
  };

  const simpan = (e: FormEvent) => {
    e.preventDefault();
    form.post(ruteKalibrasi.pelaksanaanFinalisasi(pelaksanaan.Id), {
      onSuccess: () => setBuka(false),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={ubahBuka}>
      <DialogTrigger asChild>
        <Button size="sm">
          <FileBadge className="mr-1.5 size-4" />
          Finalisasi & Sahkan Sertifikat
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-foreground">
            <FileBadge className="size-5 text-teknisi-700" />
            Finalisasi Kalibrasi & Otorisasi Sertifikat
          </DialogTitle>
          <DialogDescription>
            Pengesahan hasil pengujian. Jika kalibrasi lolos dan terhubung ke rencana kalibrasi, siklus
            tanggal kalibrasi berikutnya akan otomatis dimajukan.
          </DialogDescription>
        </DialogHeader>

        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={simpan} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="HasilFinal" htmlFor="HasilFinal">
                Hasil Kesimpulan Kalibrasi *
              </Label>
              <Select value={form.data.Hasil} onValueChange={(val) => form.setData('Hasil', val)}>
                <SelectTrigger className="h-9 text-xs">
                  <SelectValue placeholder="Pilih Hasil" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Lolos">Lolos (Memenuhi seluruh toleransi)</SelectItem>
                  <SelectItem value="LolosDenganCatatan">Lolos dengan Catatan Deviasi</SelectItem>
                  <SelectItem value="Gagal">Gagal (Tidak memenuhi standar)</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-1.5">
              <Label nama="NomorSertifikat" htmlFor="NomorSertifikat">
                Nomor Sertifikat Resmi *
              </Label>
              <Input
                id="NomorSertifikat"
                placeholder="mis. CERT-CAL/2026/09/0081"
                value={form.data.NomorSertifikat}
                onChange={(e) => form.setData('NomorSertifikat', e.target.value)}
                required
                className="h-9 text-xs font-mono"
              />
              {form.errors.NomorSertifikat && (
                <p className="text-xs text-bahaya-600">{form.errors.NomorSertifikat}</p>
              )}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label nama="TglKalibrasi" htmlFor="TglKalibrasi">
                  Tanggal Pengujian *
                </Label>
                <DatePicker
                  value={form.data.TanggalKalibrasi}
                  onChange={(nilai) => form.setData('TanggalKalibrasi', nilai)}
                  id="TglKalibrasi"
                  className="h-9 text-xs font-mono"
                  required
                />
              </div>

              <div className="space-y-1.5">
                <Label nama="TglBerlaku" htmlFor="TglBerlaku">
                  Berlaku Sampai (Jatuh Tempo)
                </Label>
                <DatePicker
                  value={form.data.TanggalBerlakuSampai}
                  onChange={(nilai) => form.setData('TanggalBerlakuSampai', nilai)}
                  id="TglBerlaku"
                  className="h-9 text-xs font-mono"
                />
                <p className="text-[10px] text-muted-foreground">
                  Kosongkan jika ingin dihitung otomatis dari interval rencana kalibrasi.
                </p>
              </div>
            </div>

            <div className="space-y-1.5">
              <Label nama="LaboratoriumUji" htmlFor="LaboratoriumUji">
                Nama Laboratorium Penguji
              </Label>
              <Input
                id="LaboratoriumUji"
                placeholder="mis. Balai Kalibrasi Standar Industri"
                value={form.data.Laboratorium}
                onChange={(e) => form.setData('Laboratorium', e.target.value)}
                className="h-9 text-xs"
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label nama="KondisiSuhu" htmlFor="KondisiSuhu">
                  Suhu Lingkungan Ruang
                </Label>
                <Input
                  id="KondisiSuhu"
                  placeholder="20 ± 2 °C"
                  value={form.data.KondisiLingkungan.Suhu}
                  onChange={(e) =>
                    form.setData('KondisiLingkungan', {
                      ...form.data.KondisiLingkungan,
                      Suhu: e.target.value,
                    })
                  }
                  className="h-9 text-xs"
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="KondisiKelembapan" htmlFor="KondisiKelembapan">
                  Kelembapan Udara
                </Label>
                <Input
                  id="KondisiKelembapan"
                  placeholder="55 ± 5 % RH"
                  value={form.data.KondisiLingkungan.Kelembapan}
                  onChange={(e) =>
                    form.setData('KondisiLingkungan', {
                      ...form.data.KondisiLingkungan,
                      Kelembapan: e.target.value,
                    })
                  }
                  className="h-9 text-xs"
                />
              </div>
            </div>

            <div className="space-y-1.5">
              <Label nama="CatatanFinal" htmlFor="CatatanFinal">
                Catatan Verifikasi & Kesimpulan
              </Label>
              <Textarea
                id="CatatanFinal"
                placeholder="mis. Alat telah dikalibrasi sesuai metode perbandingan standar dan memenuhi spesifikasi kelas akurasi."
                rows={2}
                value={form.data.Catatan}
                onChange={(e) => form.setData('Catatan', e.target.value)}
              />
            </div>

            <DialogFooter className="pt-2">
              <Button type="button" variant="outline" onClick={() => setBuka(false)}>
                Batal
              </Button>
              <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Menyahkan...' : 'Sahkan Sertifikat'}
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}
