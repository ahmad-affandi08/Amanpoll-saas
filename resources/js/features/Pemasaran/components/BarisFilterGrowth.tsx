import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type { FilterGrowth, PilihanGrowth } from '@/features/Pemasaran/types';
import { Combobox } from '@/components/ui/combobox';
import { DateRangePicker } from '@/components/ui/date-range-picker';

export function BarisFilterGrowth({ filter, pilihan }: { filter: FilterGrowth; pilihan: PilihanGrowth }) {
  const [nilai, setNilai] = useState<FilterGrowth>(filter);

  const ubah = (kunci: string, isi: string) =>
    setNilai((lama) => ({ ...lama, [kunci]: isi === '' ? null : isi }));

  const ubahRentang = (rentang: { dari?: string; sampai?: string }) =>
    setNilai((lama) => ({
      ...lama,
      dari: rentang.dari || null,
      sampai: rentang.sampai || null,
    }));

  const terapkan = (e: FormEvent) => {
    e.preventDefault();

    const bersih = Object.fromEntries(Object.entries(nilai).filter(([, isi]) => isi !== null && isi !== ''));

    router.get(rutePemasaran.growth, bersih, { preserveState: true, preserveScroll: true });
  };

  const daftar: Array<[string, string, string[]]> = [
    ['channel', 'Channel', pilihan.Channel],
    ['kampanye', 'Campaign', pilihan.Kampanye],
    ['industri', 'Industri', pilihan.Industri],
    ['perangkat', 'Device', pilihan.Perangkat],
    ['paket', 'Paket', pilihan.Paket],
    ['referral', 'Referral', pilihan.Referral],
  ];

  return (
    <form onSubmit={terapkan} className="flex flex-wrap items-end gap-3 rounded-lg border p-4">
      <div className="grid gap-1.5">
        <Label>Rentang tanggal</Label>
        <DateRangePicker
          dari={nilai.dari ?? ''}
          sampai={nilai.sampai ?? ''}
          onChange={(rentang) => ubahRentang(rentang)}
          className="w-64"
        />
      </div>

      {daftar.map(([kunci, label, opsi]) => (
        <div key={kunci} className="grid gap-1.5">
          <Label htmlFor={kunci}>{label}</Label>
          <Combobox
            nilai={nilai[kunci] ?? 'semua'}
            onPilih={(v) => ubah(kunci, v === 'semua' ? '' : v)}
            opsi={[{ nilai: 'semua', label: 'Semua' }, ...opsi.map((satu) => ({ nilai: satu, label: satu }))]}
            placeholder="Semua"
            className="w-40"
          />
        </div>
      ))}

      <div className="grid gap-1.5">
        <Label htmlFor="landing">Landing page</Label>
        <Input
          id="landing"
          value={nilai.landing ?? ''}
          onChange={(e) => ubah('landing', e.target.value)}
          placeholder="Cari jalur..."
          className="w-48"
        />
      </div>

      <Button type="submit">Terapkan</Button>
      <Button type="button" variant="ghost" onClick={() => router.get(rutePemasaran.growth)}>
        Reset
      </Button>
    </form>
  );
}
