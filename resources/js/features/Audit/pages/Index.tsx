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
      <div className="space-y-4">
        <KepalaHalaman
          judul="Log Audit"
          deskripsi="Riwayat perubahan data lintas modul, tersaring per organisasi."
          aksi={<TombolEkspor url={ruteAudit.ekspor} filter={form as Record<string, string>} />}
        />

        <form
          onSubmit={terapkanFilter}
          className="grid grid-cols-1 gap-4 rounded-lg border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-4"
        >
          <div className="space-y-1.5">
            <Label>Jenis Entitas</Label>
            <Combobox
              nilai={form.jenisEntitas ?? SEMUA}
              onPilih={(v) => setForm((f) => ({ ...f, jenisEntitas: v === SEMUA ? undefined : v }))}
              opsi={[
                { nilai: SEMUA, label: 'Semua' },
                ...jenisEntitasTersedia.map((jenis) => ({ nilai: jenis, label: jenis })),
              ]}
              placeholder="Semua"
            />
          </div>
          <div className="space-y-1.5">
            <Label>Aksi</Label>
            <Input
              value={form.aksi ?? ''}
              onChange={(e) => setForm((f) => ({ ...f, aksi: e.target.value || undefined }))}
              placeholder="mis. dibuat"
            />
          </div>
          <div className="space-y-1.5">
            <Label>Rentang Tanggal</Label>
            <DateRangePicker
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
          <div className="flex items-end gap-2">
            <Button type="submit">Terapkan</Button>
            <Button type="button" variant="outline" onClick={resetFilter}>
              Reset
            </Button>
          </div>
        </form>

        <div className="rounded-lg border border-border bg-card">
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
