import { FormEvent, useEffect, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { http } from '@/lib/http';
import type { Aset, MeterAset, PembacaanMeterAset } from '@/features/Aset/types';
import { ruteAset } from '@/features/Aset/api';
import { DialogTambahMeter } from '@/features/Aset/components/DialogTambahMeter';

function KartuMeter({ meter, onUbah }: { meter: MeterAset; onUbah: () => void }) {
  const [data, setData] = useState<PembacaanMeterAset[]>([]);
  const [buka, setBuka] = useState(false);
  const form = useForm({ Nilai: '', DibacaPada: '' });

  const muat = () => {
    http.get(ruteAset.meterPembacaan(meter.Id)).then((res) => setData(res.data));
  };

  useEffect(muat, [meter.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteAset.meterPembacaan(meter.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
        muat();
        onUbah();
      },
    });
  };

  return (
    <div className="rounded-md border border-border p-3">
      <div className="flex items-center justify-between">
        <div>
          <span className="font-medium text-foreground">{meter.Nama}</span>{' '}
          <Badge variant="secondary">{meter.Jenis}</Badge>
        </div>
        <Dialog open={buka} onOpenChange={setBuka}>
          <DialogTrigger asChild>
            <Button variant="outline" size="sm">
              Catat Pembacaan
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Catat Pembacaan -- {meter.Nama}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
              <Input
                placeholder={`Nilai (${meter.Satuan})`}
                type="number"
                value={form.data.Nilai}
                onChange={(e) => form.setData('Nilai', e.target.value)}
              />
              <Input
                type="datetime-local"
                value={form.data.DibacaPada}
                onChange={(e) => form.setData('DibacaPada', e.target.value)}
              />
              {form.errors.Nilai && <p className="text-sm text-destructive">{form.errors.Nilai}</p>}
              <DialogFooter>
                <Button type="submit" disabled={form.processing}>
                  Simpan
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>
      <div className="mt-1 text-sm text-muted-foreground">
        Nilai terakhir: {meter.NilaiTerakhir ?? meter.NilaiAwal} {meter.Satuan}
      </div>
      <div className="mt-2 space-y-1">
        {data.slice(0, 5).map((p) => (
          <div key={p.Id} className="text-xs text-muted-foreground">
            {new Date(p.DibacaPada).toLocaleString('id-ID')}:{' '}
            <span className="font-medium text-foreground">
              {p.Nilai} {meter.Satuan}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}

export function TabMeter({ aset }: { aset: Aset }) {
  const [data, setData] = useState<MeterAset[]>([]);
  const [memuat, setMemuat] = useState(true);

  const muat = () => {
    setMemuat(true);
    http
      .get(ruteAset.meter(aset.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [aset.Id]);

  return (
    <div className="space-y-4">
      <div className="flex justify-end">
        <DialogTambahMeter aset={aset} onSukses={muat} />
      </div>
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && data.length === 0 && <p className="text-sm text-muted-foreground">Belum ada meter.</p>}
      <div className="space-y-3">
        {data.map((m) => (
          <KartuMeter key={m.Id} meter={m} onUbah={muat} />
        ))}
      </div>
    </div>
  );
}
