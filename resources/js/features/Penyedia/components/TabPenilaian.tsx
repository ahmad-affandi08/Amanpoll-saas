import { FormEvent, useEffect, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { DatePicker } from '@/components/ui/date-picker';
import { http } from '@/lib/http';
import type { Penyedia, PenilaianPenyedia, RekapPenilaianPenyedia } from '@/features/Penyedia/types';
import { rutePenyedia } from '@/features/Penyedia/api';

export function TabPenilaian({ penyedia }: { penyedia: Penyedia }) {
  const [histori, setHistori] = useState<PenilaianPenyedia[]>([]);
  const [rekap, setRekap] = useState<RekapPenilaianPenyedia | null>(null);
  const [memuat, setMemuat] = useState(true);
  const form = useForm({
    PeriodeMulai: '',
    PeriodeSelesai: '',
    SkorKualitas: '',
    SkorKetepatanWaktu: '',
    SkorHarga: '',
    SkorLayanan: '',
    Catatan: '',
  });

  const muat = () => {
    setMemuat(true);
    http
      .get(rutePenyedia.penilaian(penyedia.Id))
      .then((res) => {
        setHistori(res.data.histori);
        setRekap(res.data.rekap);
      })
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [penyedia.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(rutePenyedia.penilaian(penyedia.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        muat();
      },
    });
  };

  return (
    <div className="space-y-4">
      {rekap && rekap.JumlahPenilaian > 0 && (
        <div className="rounded-md border border-border bg-muted/30 px-3 py-2 text-sm">
          Skor total rata-rata:{' '}
          <span className="font-semibold text-foreground">{rekap.SkorTotalRataRata}</span> dari{' '}
          {rekap.JumlahPenilaian} penilaian.
        </div>
      )}
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && histori.length === 0 && (
        <p className="text-sm text-muted-foreground">Belum ada penilaian.</p>
      )}
      <div className="space-y-2">
        {histori.map((p) => (
          <div key={p.Id} className="rounded-md border border-border px-3 py-2 text-sm">
            <div className="flex items-center justify-between">
              <span className="font-medium text-foreground">
                {p.PeriodeMulai} s/d {p.PeriodeSelesai}
              </span>
              {p.SkorTotal && <Badge variant="default">Total {p.SkorTotal}</Badge>}
            </div>
            <div className="mt-1 text-xs text-muted-foreground">
              Kualitas {p.SkorKualitas ?? '-'} · Ketepatan {p.SkorKetepatanWaktu ?? '-'} · Harga{' '}
              {p.SkorHarga ?? '-'} · Layanan {p.SkorLayanan ?? '-'}
            </div>
            {p.Catatan && <div className="mt-1 text-xs text-muted-foreground">"{p.Catatan}"</div>}
            {p.NamaPenilai && (
              <div className="mt-1 text-xs text-muted-foreground">Dinilai oleh {p.NamaPenilai}</div>
            )}
          </div>
        ))}
      </div>
      <form onSubmit={submit} className="space-y-2 border-t border-border pt-4">
        <div className="grid grid-cols-2 gap-2">
          <div className="space-y-1">
            <Label className="text-xs">Periode Mulai</Label>
            <DatePicker
              value={form.data.PeriodeMulai}
              onChange={(val) => form.setData('PeriodeMulai', val)}
            />
          </div>
          <div className="space-y-1">
            <Label className="text-xs">Periode Selesai</Label>
            <DatePicker
              value={form.data.PeriodeSelesai}
              onChange={(val) => form.setData('PeriodeSelesai', val)}
            />
          </div>
        </div>
        <div className="grid grid-cols-4 gap-2">
          <Input
            placeholder="Kualitas"
            type="number"
            min={0}
            max={100}
            value={form.data.SkorKualitas}
            onChange={(e) => form.setData('SkorKualitas', e.target.value)}
          />
          <Input
            placeholder="Ketepatan"
            type="number"
            min={0}
            max={100}
            value={form.data.SkorKetepatanWaktu}
            onChange={(e) => form.setData('SkorKetepatanWaktu', e.target.value)}
          />
          <Input
            placeholder="Harga"
            type="number"
            min={0}
            max={100}
            value={form.data.SkorHarga}
            onChange={(e) => form.setData('SkorHarga', e.target.value)}
          />
          <Input
            placeholder="Layanan"
            type="number"
            min={0}
            max={100}
            value={form.data.SkorLayanan}
            onChange={(e) => form.setData('SkorLayanan', e.target.value)}
          />
        </div>
        <Textarea
          placeholder="Catatan (opsional)"
          value={form.data.Catatan}
          onChange={(e) => form.setData('Catatan', e.target.value)}
          rows={2}
        />
        <Button type="submit" disabled={form.processing}>
          Simpan Penilaian
        </Button>
      </form>
    </div>
  );
}
