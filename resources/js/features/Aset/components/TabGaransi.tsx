import { FormEvent, useEffect, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { DatePicker } from '@/components/ui/date-picker';
import { http } from '@/lib/http';
import type { Aset, GaransiAset } from '@/features/Aset/types';
import type { Penyedia } from '@/features/Penyedia/types';
import { ruteAset } from '@/features/Aset/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { TANPA_PILIHAN, opsiDari, opsiKosong } from '@/lib/pilihan';
import { Combobox } from '@/components/ui/combobox';

export function TabGaransi({ aset, penyedia }: { aset: Aset; penyedia: Penyedia[] }) {
  const konfirmasi = useKonfirmasi();
  const [data, setData] = useState<GaransiAset[]>([]);
  const [memuat, setMemuat] = useState(true);
  const form = useForm({
    PenyediaId: TANPA_PILIHAN,
    NomorGaransi: '',
    MulaiPada: '',
    BerakhirPada: '',
    Status: 'Aktif' as const,
  });

  const muat = () => {
    setMemuat(true);
    http
      .get(ruteAset.garansi(aset.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [aset.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      PenyediaId: form.data.PenyediaId === TANPA_PILIHAN ? null : form.data.PenyediaId,
    };
    router.post(ruteAset.garansi(aset.Id), payload, {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        muat();
      },
    });
  };

  const hapus = async (garansi: GaransiAset) => {
    if (
      !(await konfirmasi({
        judul: 'Hapus garansi ini?',
        deskripsi: 'Pengingat masa garansi untuk aset ini ikut berhenti.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteAset.garansiDetail(garansi.Id), { preserveScroll: true, onSuccess: muat });
  };

  return (
    <div className="space-y-4">
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && data.length === 0 && <p className="text-sm text-muted-foreground">Belum ada garansi.</p>}
      <div className="space-y-2">
        {data.map((g) => (
          <div key={g.Id} className="rounded-md border border-border px-3 py-2 text-sm">
            <div className="flex items-center justify-between">
              <span className="font-medium text-foreground">
                {g.NamaPenyedia ?? g.NomorGaransi ?? 'Garansi'}
              </span>
              <div className="flex gap-1">
                {g.AkanBerakhir && <Badge variant="perhatian">Akan berakhir {g.SisaHari} hari</Badge>}
                {g.SudahBerakhir && <Badge variant="bahaya">Sudah berakhir</Badge>}
                <Button variant="ghost" size="sm" onClick={() => hapus(g)}>
                  Hapus
                </Button>
              </div>
            </div>
            <div className="text-xs text-muted-foreground">
              {g.MulaiPada} s/d {g.BerakhirPada}
            </div>
          </div>
        ))}
      </div>
      <form onSubmit={submit} className="space-y-2 border-t border-border pt-4">
        <div className="grid gap-2 sm:grid-cols-2">
          <Combobox
            nilai={form.data.PenyediaId}
            onPilih={(v) => form.setData('PenyediaId', v)}
            opsi={[opsiKosong('Tanpa penyedia'), ...opsiDari(penyedia, (p) => p.Nama)]}
          />
          <Input
            placeholder="Nomor Garansi"
            value={form.data.NomorGaransi}
            onChange={(e) => form.setData('NomorGaransi', e.target.value)}
          />
        </div>
        <div className="grid gap-2 sm:grid-cols-2">
          <div className="space-y-1">
            <Label className="text-xs">Mulai</Label>
            <DatePicker
              value={form.data.MulaiPada}
              onChange={(val) => form.setData('MulaiPada', val)}
              placeholder="Pilih tanggal mulai"
            />
          </div>
          <div className="space-y-1">
            <Label className="text-xs">Berakhir</Label>
            <DatePicker
              value={form.data.BerakhirPada}
              onChange={(val) => form.setData('BerakhirPada', val)}
              placeholder="Pilih tanggal berakhir"
            />
          </div>
        </div>
        <Button type="submit" disabled={form.processing}>
          Tambah Garansi
        </Button>
      </form>
    </div>
  );
}
