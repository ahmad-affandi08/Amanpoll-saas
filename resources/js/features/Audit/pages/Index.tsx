import { FormEvent, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import type { Paginasi } from '@/types/global';
import type { CatatanAudit, FilterCatatanAudit } from '@/features/Audit/types';
import { ruteAudit } from '@/features/Audit/api';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { Combobox } from '@/components/ui/combobox';

interface Props {
  catatan: Paginasi<CatatanAudit>;
  filter: FilterCatatanAudit;
  jenisEntitasTersedia: string[];
}

const SEMUA = '__semua__';

export default function AuditIndex({ catatan, filter, jenisEntitasTersedia }: Props) {
  const [form, setForm] = useState<FilterCatatanAudit>(filter);

  const terapkanFilter = (e: FormEvent) => {
    e.preventDefault();
    router.get(ruteAudit.index, { ...form }, { preserveState: true, preserveScroll: true });
  };

  const resetFilter = () => {
    setForm({});
    router.get(ruteAudit.index, {}, { preserveState: true, preserveScroll: true });
  };

  return (
    <KerangkaAplikasi>
      <Head title="Log Audit" />
      <div className="space-y-5">
        <KepalaHalaman
          judul="Log Audit"
          deskripsi="Riwayat perubahan data lintas modul, tersaring per organisasi."
          aksi={<TombolEkspor url={ruteAudit.ekspor} filter={form as Record<string, string>} />}
        />

        <form onSubmit={terapkanFilter} className="flex flex-wrap items-end gap-3">
          <div className="w-full space-y-1.5 sm:w-48">
            <Label htmlFor="filter-audit-jenis-entitas">Jenis Entitas</Label>
            <Combobox
              id="filter-audit-jenis-entitas"
              nilai={form.jenisEntitas ?? SEMUA}
              onPilih={(v) => setForm((f) => ({ ...f, jenisEntitas: v === SEMUA ? undefined : v }))}
              opsi={[
                { nilai: SEMUA, label: 'Semua' },
                ...jenisEntitasTersedia.map((jenis) => ({ nilai: jenis, label: jenis })),
              ]}
              placeholder="Semua"
            />
          </div>
          <div className="w-full space-y-1.5 sm:w-44">
            <Label htmlFor="filter-audit-aksi">Aksi</Label>
            <Input
              id="filter-audit-aksi"
              value={form.aksi ?? ''}
              onChange={(e) => setForm((f) => ({ ...f, aksi: e.target.value || undefined }))}
              placeholder="mis. dibuat"
            />
          </div>
          <div className="w-full space-y-1.5 sm:w-64">
            <Label htmlFor="filter-audit-rentang">Rentang Tanggal</Label>
            <DateRangePicker
              id="filter-audit-rentang"
              dari={form.dariTanggal}
              sampai={form.sampaiTanggal}
              align="end"
              onChange={({ dari, sampai }) =>
                setForm((f) => ({
                  ...f,
                  dariTanggal: dari || undefined,
                  sampaiTanggal: sampai || undefined,
                }))
              }
            />
          </div>
          <div className="flex gap-2">
            <Button type="submit" variant="secondary">
              Terapkan
            </Button>
            <Button type="button" variant="ghost" onClick={resetFilter}>
              Reset
            </Button>
          </div>
        </form>

        <div className="rounded-md border border-border bg-card">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Waktu</TableHead>
                <TableHead>Pengguna</TableHead>
                <TableHead>Aksi</TableHead>
                <TableHead>Entitas</TableHead>
                <TableHead>Alamat IP</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {catatan.data.length === 0 && (
                <TableRow>
                  <TableCell colSpan={5} className="text-center text-muted-foreground">
                    Tidak ada catatan audit.
                  </TableCell>
                </TableRow>
              )}
              {catatan.data.map((c) => (
                <TableRow key={c.Id}>
                  <TableCell className="whitespace-nowrap">
                    {new Date(c.DibuatPada).toLocaleString('id-ID')}
                  </TableCell>
                  <TableCell>{c.NamaPengguna ?? '-'}</TableCell>
                  <TableCell>
                    <Badge variant="outline">{c.Aksi}</Badge>
                  </TableCell>
                  <TableCell>
                    {c.JenisEntitas}
                    {c.EntitasId ? ` #${c.EntitasId.slice(-8)}` : ''}
                  </TableCell>
                  <TableCell className="text-muted-foreground">{c.AlamatIp ?? '-'}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          <KontrolPaginasi
            meta={catatan.meta}
            onNavigasi={(halaman) => navigasiHalaman(halaman, form as Record<string, string>)}
          />
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
