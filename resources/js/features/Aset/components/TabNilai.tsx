import { FormEvent, useEffect, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { DatePicker } from '@/components/ui/date-picker';
import { http } from '@/lib/http';
import { formatUang } from '@/lib/uang';
import type { Aset, NilaiAset } from '@/features/Aset/types';
import { ruteAset } from '@/features/Aset/api';
import { InputUang } from '@/components/shared/InputUang';

export function TabNilai({ aset }: { aset: Aset }) {
  const [data, setData] = useState<NilaiAset[]>([]);
  const [memuat, setMemuat] = useState(true);
  const [errorPratinjau, setErrorPratinjau] = useState<string | null>(null);
  const form = useForm({
    TanggalNilai: '',
    NilaiBuku: '',
    AkumulasiPenyusutan: '',
    BebanPenyusutanPeriode: '',
  });

  const muat = () => {
    setMemuat(true);
    http
      .get(ruteAset.nilai(aset.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [aset.Id]);

  const hitungPratinjau = () => {
    if (!form.data.TanggalNilai) return;
    setErrorPratinjau(null);
    http
      .get(ruteAset.nilaiPratinjau(aset.Id), { params: { tanggal: form.data.TanggalNilai } })
      .then((res) => {
        form.setData({
          ...form.data,
          NilaiBuku: String(res.data.NilaiBuku),
          AkumulasiPenyusutan: String(res.data.AkumulasiPenyusutan),
          BebanPenyusutanPeriode: String(res.data.BebanPenyusutanPeriode),
        });
      })
      .catch((err) => {
        setErrorPratinjau(err.response?.data?.pesan ?? 'Gagal menghitung penyusutan otomatis.');
      });
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteAset.nilai(aset.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        muat();
      },
    });
  };

  return (
    <div className="space-y-4">
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && data.length === 0 && (
        <p className="text-sm text-muted-foreground">Belum ada catatan nilai.</p>
      )}
      <div className="space-y-2">
        {data.map((n) => (
          <div
            key={n.Id}
            className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
          >
            <span className="text-foreground">{n.TanggalNilai}</span>
            <span className="font-medium text-foreground">{formatUang(n.NilaiBuku, aset.MataUang)}</span>
          </div>
        ))}
      </div>
      <form onSubmit={submit} className="space-y-2 border-t border-border pt-4">
        <div className="flex items-end gap-2">
          <div className="space-y-1 flex-1">
            <Label className="text-xs">Tanggal Nilai</Label>
            <DatePicker
              value={form.data.TanggalNilai}
              onChange={(val) => form.setData('TanggalNilai', val)}
              placeholder="Pilih tanggal nilai"
            />
          </div>
          <Button type="button" variant="outline" onClick={hitungPratinjau}>
            Hitung Otomatis (Garis Lurus)
          </Button>
        </div>
        {errorPratinjau && <p className="text-sm text-destructive">{errorPratinjau}</p>}
        <div className="grid grid-cols-3 gap-2">
          <InputUang
            placeholder="Nilai Buku"
            value={form.data.NilaiBuku}
            onChange={(nilai) => form.setData('NilaiBuku', nilai)}
            mataUang={aset.MataUang || 'IDR'}
          />
          <InputUang
            placeholder="Akumulasi Penyusutan"
            value={form.data.AkumulasiPenyusutan}
            onChange={(nilai) => form.setData('AkumulasiPenyusutan', nilai)}
            mataUang={aset.MataUang || 'IDR'}
          />
          <InputUang
            placeholder="Beban Periode"
            value={form.data.BebanPenyusutanPeriode}
            onChange={(nilai) => form.setData('BebanPenyusutanPeriode', nilai)}
            mataUang={aset.MataUang || 'IDR'}
          />
        </div>
        <Button type="submit" disabled={form.processing}>
          Simpan Nilai
        </Button>
      </form>
    </div>
  );
}
