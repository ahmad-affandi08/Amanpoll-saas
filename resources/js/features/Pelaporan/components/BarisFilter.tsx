import { useState } from 'react';
import { router } from '@inertiajs/react';
import { CalendarRange, RotateCcw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { FilterMetrik, PilihanDimensi } from '@/features/Pelaporan/types';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';
import { DateRangePicker } from '@/components/ui/date-range-picker';

const PRESET = [
  { label: '7 hari', hari: 7 },
  { label: '30 hari', hari: 30 },
  { label: '90 hari', hari: 90 },
] as const;

function tanggalMundur(hari: number): { Dari: string; Sampai: string } {
  const sampai = new Date();
  const dari = new Date();
  dari.setDate(dari.getDate() - (hari - 1));

  return { Dari: dari.toISOString().slice(0, 10), Sampai: sampai.toISOString().slice(0, 10) };
}

/** Satu baris filter di atas seluruh isi yang dicakupnya (21.02). */
export function BarisFilter({
  filter,
  pilihanUnit,
  pilihanLokasi,
  url,
  paramTambahan = {},
}: {
  filter: FilterMetrik;
  pilihanUnit: PilihanDimensi[];
  pilihanLokasi: PilihanDimensi[];
  url: string;
  paramTambahan?: Record<string, string>;
}) {
  const [dari, setDari] = useState(filter.Dari);
  const [sampai, setSampai] = useState(filter.Sampai);

  const terapkan = (ubahan: Partial<FilterMetrik>) => {
    const berikutnya = {
      Dari: ubahan.Dari ?? dari,
      Sampai: ubahan.Sampai ?? sampai,
      UnitOrganisasiId: ubahan.UnitOrganisasiId ?? filter.UnitOrganisasiId,
      LokasiId: ubahan.LokasiId ?? filter.LokasiId,
    };

    setDari(berikutnya.Dari);
    setSampai(berikutnya.Sampai);

    router.get(url, { ...paramTambahan, ...berikutnya }, { preserveState: true, preserveScroll: true });
  };

  const pilihSatu = (nilai: string) => (nilai === 'semua' ? [] : [nilai]);

  return (
    <div className="flex flex-wrap items-end gap-3 rounded-[9px] border border-border bg-card p-3">
      <div className="flex items-end gap-1.5">
        {PRESET.map((preset) => (
          <Button
            key={preset.hari}
            type="button"
            variant="outline"
            size="sm"
            onClick={() => terapkan(tanggalMundur(preset.hari))}
          >
            {preset.label}
          </Button>
        ))}
      </div>

      <div className="space-y-1">
        <Label className="text-xs">Rentang tanggal</Label>
        <DateRangePicker
          dari={dari}
          sampai={sampai}
          onChange={(rentang) => terapkan({ Dari: rentang.dari ?? '', Sampai: rentang.sampai ?? '' })}
          className="w-[19rem]"
        />
      </div>

      <div className="space-y-1">
        <Label className="text-xs">Unit organisasi</Label>
        <Combobox
          nilai={filter.UnitOrganisasiId[0] ?? 'semua'}
          onPilih={(nilai) => terapkan({ UnitOrganisasiId: pilihSatu(nilai) })}
          opsi={[{ nilai: 'semua', label: 'Semua unit' }, ...opsiDari(pilihanUnit, (unit) => unit.Nama)]}
          placeholder="Semua unit"
          className="w-[11rem]"
        />
      </div>

      <div className="space-y-1">
        <Label className="text-xs">Lokasi</Label>
        <Combobox
          nilai={filter.LokasiId[0] ?? 'semua'}
          onPilih={(nilai) => terapkan({ LokasiId: pilihSatu(nilai) })}
          opsi={[
            { nilai: 'semua', label: 'Semua lokasi' },
            ...opsiDari(pilihanLokasi, (lokasi) => lokasi.Nama),
          ]}
          placeholder="Semua lokasi"
          className="w-[11rem]"
        />
      </div>

      <Button
        type="button"
        variant="ghost"
        size="sm"
        onClick={() => terapkan({ ...tanggalMundur(30), UnitOrganisasiId: [], LokasiId: [] })}
        className="ml-auto"
      >
        <RotateCcw className="size-4" />
        Atur ulang
      </Button>

      <p className="w-full text-xs text-muted-foreground">
        <CalendarRange className="mr-1 inline size-3.5 align-text-bottom" />
        Filter ini berlaku untuk seluruh angka di bawahnya.
      </p>
    </div>
  );
}
