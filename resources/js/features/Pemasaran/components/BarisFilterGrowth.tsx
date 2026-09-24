import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
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
    <form onSubmit={terapkan} className="flex flex-wrap items-center gap-2">
      <Label htmlFor="filter-growth-rentang" className="sr-only">
        Rentang tanggal
      </Label>
      <DateRangePicker
        id="filter-growth-rentang"
        dari={nilai.dari ?? ''}
        sampai={nilai.sampai ?? ''}
        onChange={(rentang) => ubahRentang(rentang)}
        className="w-64"
      />

      {daftar.map(([kunci, label, opsi]) => (
        <div key={kunci} className="contents">
          <Label htmlFor={`filter-growth-${kunci}`} className="sr-only">
            {label}
          </Label>
          <Combobox
            id={`filter-growth-${kunci}`}
            nilai={nilai[kunci] ?? 'semua'}
            onPilih={(v) => ubah(kunci, v === 'semua' ? '' : v)}
            opsi={[
              { nilai: 'semua', label: `Semua ${label.toLowerCase()}` },
              ...opsi.map((satu) => ({ nilai: satu, label: satu })),
            ]}
            placeholder={`Semua ${label.toLowerCase()}`}
            className="w-40"
          />
        </div>
      ))}

      <div className="relative w-full sm:w-64">
        <Label htmlFor="filter-growth-landing" className="sr-only">
          Landing page
        </Label>
        <Search
          aria-hidden="true"
          className="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-grafit-500"
        />
        <Input
          id="filter-growth-landing"
          value={nilai.landing ?? ''}
          onChange={(e) => ubah('landing', e.target.value)}
          placeholder="Cari jalur landing page..."
          className="pl-8"
        />
      </div>

      <Button type="submit" variant="secondary">
        Terapkan
      </Button>
      <Button type="button" variant="ghost" onClick={() => router.get(rutePemasaran.growth)}>
        Reset
      </Button>
    </form>
  );
}
