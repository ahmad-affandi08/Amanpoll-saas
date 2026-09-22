import { FormEvent, useEffect, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { http } from '@/lib/http';
import type { Aset, RiwayatPenanggungJawabAset } from '@/features/Aset/types';
import type { UnitOrganisasi } from '@/features/UnitOrganisasi/types';
import { ruteAset } from '@/features/Aset/api';
import { TANPA_PILIHAN } from '@/lib/pilihan';

export function TabPenanggungJawab({
  aset,
  unitOrganisasi,
}: {
  aset: Aset;
  unitOrganisasi: UnitOrganisasi[];
}) {
  const [data, setData] = useState<RiwayatPenanggungJawabAset[]>([]);
  const [memuat, setMemuat] = useState(true);
  const form = useForm({ Jenis: 'unit' as 'unit', UnitOrganisasiId: TANPA_PILIHAN, Catatan: '' });

  const muat = () => {
    setMemuat(true);
    http
      .get(ruteAset.penanggungJawab(aset.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [aset.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (form.data.UnitOrganisasiId === TANPA_PILIHAN) return;
    router.post(
      ruteAset.penanggungJawab(aset.Id),
      {
        UnitOrganisasiId: form.data.UnitOrganisasiId,
        Catatan: form.data.Catatan || null,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          form.reset();
          muat();
        },
      },
    );
  };

  const aktif = data.find((r) => r.SelesaiPada === null);

  return (
    <div className="space-y-4">
      <div className="rounded-md border border-border bg-muted/30 px-3 py-2 text-sm">
        Penanggung jawab saat ini:{' '}
        <span className="font-semibold text-foreground">
          {aktif ? (aktif.NamaPengguna ?? aktif.NamaUnitOrganisasi ?? '—') : 'Belum ditetapkan'}
        </span>
      </div>
      <form onSubmit={submit} className="flex flex-wrap items-end gap-2 border-b border-border pb-4">
        <div className="space-y-1">
          <Label className="text-xs">Unit Penanggung Jawab</Label>
          <Select
            value={form.data.UnitOrganisasiId}
            onValueChange={(v) => form.setData('UnitOrganisasiId', v)}
          >
            <SelectTrigger className="w-56">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={TANPA_PILIHAN}>Pilih unit</SelectItem>
              {unitOrganisasi.map((u) => (
                <SelectItem key={u.Id} value={u.Id}>
                  {u.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <Input
          placeholder="Catatan (opsional)"
          value={form.data.Catatan}
          onChange={(e) => form.setData('Catatan', e.target.value)}
          className="w-56"
        />
        <Button type="submit" disabled={form.processing}>
          Tetapkan
        </Button>
      </form>
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && data.length === 0 && (
        <p className="text-sm text-muted-foreground">Belum ada riwayat penanggung jawab.</p>
      )}
      <div className="space-y-2">
        {data.map((r) => (
          <div key={r.Id} className="rounded-md border border-border px-3 py-2 text-sm">
            <div className="flex items-center justify-between">
              <span className="font-medium text-foreground">
                {r.NamaPengguna ?? r.NamaUnitOrganisasi ?? '—'}
              </span>
              {r.SelesaiPada === null && <Badge variant="sukses">Aktif</Badge>}
            </div>
            <div className="text-xs text-muted-foreground">
              {new Date(r.MulaiPada).toLocaleString('id-ID')}{' '}
              {r.SelesaiPada && `-- ${new Date(r.SelesaiPada).toLocaleString('id-ID')}`}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
