import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { FilterMetrik, PilihanDimensi } from '@/features/Pelaporan/types';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari, opsiUnitPengelola, TANPA_PILIHAN } from '@/lib/pilihan';
import type { UnitPengelolaRingkas } from '@/features/UnitOrganisasi/types';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import { tambahHari, tanggalHariIni } from '@/lib/waktu';
import { cn } from '@/lib/utils';

const PRESET = [
  { label: '7 hari', hari: 7 },
  { label: '30 hari', hari: 30 },
  { label: '90 hari', hari: 90 },
] as const;

function tanggalMundur(hari: number): { Dari: string; Sampai: string } {
  const sampai = tanggalHariIni();

  return { Dari: tambahHari(sampai, -(hari - 1)), Sampai: sampai };
}

/** Satu baris filter di atas seluruh isi yang dicakupnya (21.02). */
export function BarisFilter({
  filter,
  pilihanUnit,
  pilihanLokasi,
  pilihanUnitPengelola = [],
  url,
  paramTambahan = {},
}: {
  filter: FilterMetrik;
  pilihanUnit: PilihanDimensi[];
  pilihanLokasi: PilihanDimensi[];
  /** Kosong bila organisasi tidak memakai unit pengelola; pemilihnya lalu tidak tampil. */
  pilihanUnitPengelola?: UnitPengelolaRingkas[];
  url: string;
  paramTambahan?: Record<string, string>;
}) {
  const [dari, setDari] = useState(filter.Dari);
  const [sampai, setSampai] = useState(filter.Sampai);

  // Rentang dari server bisa berganti tanpa lewat baris ini (mis. membuka laporan tersimpan
  // yang memulihkan filternya) sementara state halaman dipertahankan.
  useEffect(() => {
    setDari(filter.Dari);
    setSampai(filter.Sampai);
  }, [filter.Dari, filter.Sampai]);

  const terapkan = (ubahan: Partial<FilterMetrik>) => {
    const berikutnya = {
      Dari: ubahan.Dari ?? dari,
      Sampai: ubahan.Sampai ?? sampai,
      UnitOrganisasiId: ubahan.UnitOrganisasiId ?? filter.UnitOrganisasiId,
      LokasiId: ubahan.LokasiId ?? filter.LokasiId,
      UnitPengelolaId: ubahan.UnitPengelolaId ?? filter.UnitPengelolaId ?? [],
    };

    setDari(berikutnya.Dari);
    setSampai(berikutnya.Sampai);

    router.get(url, { ...paramTambahan, ...berikutnya }, { preserveState: true, preserveScroll: true });
  };

  const pilihSatu = (nilai: string) => (nilai === 'semua' ? [] : [nilai]);

  const presetAktif = PRESET.find((preset) => {
    const rentang = tanggalMundur(preset.hari);

    return rentang.Dari === dari && rentang.Sampai === sampai;
  })?.hari;

  return (
    <div className="flex flex-wrap items-center gap-2 border-b border-border pb-5">
      <div
        role="group"
        aria-label="Rentang cepat"
        className="inline-flex overflow-hidden rounded-sm border border-input"
      >
        {PRESET.map((preset) => (
          <button
            key={preset.hari}
            type="button"
            aria-pressed={presetAktif === preset.hari}
            onClick={() => terapkan(tanggalMundur(preset.hari))}
            className={cn(
              'min-h-11 border-r border-input px-3 text-[13px] text-grafit-700 last:border-r-0 hover:bg-permukaan-50 hover:text-foreground focus-visible:relative focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:h-8 sm:min-h-0',
              presetAktif === preset.hari &&
                'bg-permukaan-100 font-medium text-foreground hover:bg-permukaan-100',
            )}
          >
            {preset.label}
          </button>
        ))}
      </div>

      <Label htmlFor="filter-rentang" className="sr-only">
        Rentang tanggal
      </Label>
      <DateRangePicker
        id="filter-rentang"
        dari={dari}
        sampai={sampai}
        onChange={(rentang) => terapkan({ Dari: rentang.dari ?? '', Sampai: rentang.sampai ?? '' })}
        className="w-[16.5rem]"
      />

      <Label htmlFor="filter-unit" className="sr-only">
        Unit organisasi
      </Label>
      <Combobox
        id="filter-unit"
        nilai={filter.UnitOrganisasiId[0] ?? 'semua'}
        onPilih={(nilai) => terapkan({ UnitOrganisasiId: pilihSatu(nilai) })}
        opsi={[{ nilai: 'semua', label: 'Semua unit' }, ...opsiDari(pilihanUnit, (unit) => unit.Nama)]}
        placeholder="Semua unit"
        className="w-[9rem]"
      />

      <Label htmlFor="filter-lokasi" className="sr-only">
        Lokasi
      </Label>
      <Combobox
        id="filter-lokasi"
        nilai={filter.LokasiId[0] ?? 'semua'}
        onPilih={(nilai) => terapkan({ LokasiId: pilihSatu(nilai) })}
        opsi={[
          { nilai: 'semua', label: 'Semua lokasi' },
          ...opsiDari(pilihanLokasi, (lokasi) => lokasi.Nama),
        ]}
        placeholder="Semua lokasi"
        className="w-[9rem]"
      />

      {pilihanUnitPengelola.length > 0 && (
        <>
          <Label htmlFor="filter-unit-pengelola" className="sr-only">
            Unit pengelola
          </Label>
          <Combobox
            id="filter-unit-pengelola"
            nilai={filter.UnitPengelolaId?.[0] ?? TANPA_PILIHAN}
            onPilih={(nilai) => terapkan({ UnitPengelolaId: nilai === TANPA_PILIHAN ? [] : [nilai] })}
            opsi={opsiUnitPengelola(pilihanUnitPengelola, 'Semua unit pengelola')}
            placeholder="Semua unit pengelola"
            className="w-[12.5rem]"
          />
        </>
      )}

      <Button
        type="button"
        variant="ghost"
        size="sm"
        title="Filter di baris ini berlaku untuk seluruh angka di bawahnya."
        onClick={() =>
          terapkan({ ...tanggalMundur(30), UnitOrganisasiId: [], LokasiId: [], UnitPengelolaId: [] })
        }
        className="ml-auto"
      >
        <RotateCcw className="size-3.5" />
        Atur ulang
      </Button>
    </div>
  );
}
