import { FormEvent, useEffect, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { http } from '@/lib/http';
import type { Aset, RiwayatLokasiAset } from '@/features/Aset/types';
import type { Lokasi } from '@/features/Lokasi/types';
import { ruteAset } from '@/features/Aset/api';
import { TANPA_PILIHAN } from '@/lib/pilihan';

export function TabLokasi({ aset, lokasi }: { aset: Aset; lokasi: Lokasi[] }) {
  const [data, setData] = useState<RiwayatLokasiAset[]>([]);
  const [memuat, setMemuat] = useState(true);
  const form = useForm({ LokasiTujuanId: TANPA_PILIHAN, Alasan: '' });

  const muat = () => {
    setMemuat(true);
    http
      .get(ruteAset.riwayatLokasi(aset.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [aset.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      LokasiTujuanId: form.data.LokasiTujuanId === TANPA_PILIHAN ? null : form.data.LokasiTujuanId,
      Alasan: form.data.Alasan || null,
    };
    router.post(ruteAset.riwayatLokasi(aset.Id), payload, { preserveScroll: true, onSuccess: muat });
  };

  return (
    <div className="space-y-4">
      <div className="rounded-md border border-border bg-muted/30 px-3 py-2 text-sm">
        Lokasi saat ini:{' '}
        <span className="font-semibold text-foreground">{aset.NamaLokasi ?? 'Belum ditentukan'}</span>
      </div>
      <form onSubmit={submit} className="flex flex-wrap items-end gap-2 border-b border-border pb-4">
        <div className="space-y-1">
          <Label className="text-xs">Pindahkan ke</Label>
          <Select value={form.data.LokasiTujuanId} onValueChange={(v) => form.setData('LokasiTujuanId', v)}>
            <SelectTrigger className="w-56">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={TANPA_PILIHAN}>Tidak ada (kosongkan lokasi)</SelectItem>
              {lokasi.map((l) => (
                <SelectItem key={l.Id} value={l.Id}>
                  {l.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <Input
          placeholder="Alasan (opsional)"
          value={form.data.Alasan}
          onChange={(e) => form.setData('Alasan', e.target.value)}
          className="w-56"
        />
        <Button type="submit" disabled={form.processing}>
          Pindahkan
        </Button>
      </form>
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && data.length === 0 && (
        <p className="text-sm text-muted-foreground">Belum ada riwayat lokasi.</p>
      )}
      <div className="space-y-2">
        {data.map((r) => (
          <div key={r.Id} className="rounded-md border border-border px-3 py-2 text-sm">
            <div className="font-medium text-foreground">
              {r.NamaLokasiAsal ?? '—'} → {r.NamaLokasiTujuan ?? '—'}
            </div>
            <div className="text-xs text-muted-foreground">
              {r.JenisPerpindahan} · {new Date(r.DipindahkanPada).toLocaleString('id-ID')} ·{' '}
              {r.NamaDipindahkanOleh ?? '—'}
            </div>
            {r.Alasan && <div className="text-xs text-muted-foreground">"{r.Alasan}"</div>}
          </div>
        ))}
      </div>
    </div>
  );
}
