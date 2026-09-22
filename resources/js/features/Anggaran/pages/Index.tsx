import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Search, WalletCards } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Paginasi } from '@/types/global';
import type { Anggaran, StatusAnggaran } from '@/features/Anggaran/types';
import { formatUang } from '@/lib/uang';
import { ruteAnggaran } from '@/features/Anggaran/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TANPA_PILIHAN, opsiDari, opsiKosong } from '@/lib/pilihan';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';

interface Ringkas {
  Id: string;
  Nama: string;
}

interface Props {
  anggaran: Paginasi<Anggaran>;
  unitOrganisasi: Ringkas[];
  filter: { cari?: string; tahun?: number; status?: StatusAnggaran };
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const SEMUA = '__semua__';
const VARIAN_STATUS = {
  Draft: 'netral',
  MenungguPersetujuan: 'perhatian',
  Aktif: 'sukses',
  Ditolak: 'bahaya',
  Ditutup: 'netral',
} as const;

function DialogBuatAnggaran({ unitOrganisasi, wajib }: { unitOrganisasi: Ringkas[]; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    UnitOrganisasiId: TANPA_PILIHAN,
    Kode: '',
    Nama: '',
    Tahun: new Date().getFullYear().toString(),
    MataUang: 'IDR',
    Jumlah: '',
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      UnitOrganisasiId: data.UnitOrganisasiId === TANPA_PILIHAN ? null : data.UnitOrganisasiId,
    }));
    form.post(ruteAnggaran.index, { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="min-h-11 sm:min-h-9">
          <Plus /> Buat Anggaran
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Buat Anggaran</DialogTitle>
          <DialogDescription>
            Siapkan periode dan pagu. Pos anggaran ditambahkan setelah draft dibuat.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <BidangKode
                nilai={form.data.Kode}
                onUbah={(nilai) => form.setData('Kode', nilai)}
                galat={form.errors.Kode}
              />
              <div className="space-y-1.5">
                <Label nama="Tahun" htmlFor="tahun-anggaran">
                  Periode Tahun
                </Label>
                <Input
                  id="tahun-anggaran"
                  type="number"
                  min={2000}
                  max={2100}
                  value={form.data.Tahun}
                  onChange={(event) => form.setData('Tahun', event.target.value)}
                />
                {form.errors.Tahun && <p className="text-sm text-destructive">{form.errors.Tahun}</p>}
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="Nama" htmlFor="nama-anggaran">
                Nama
              </Label>
              <Input
                id="nama-anggaran"
                value={form.data.Nama}
                onChange={(event) => form.setData('Nama', event.target.value)}
              />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
            <div className="space-y-1.5">
              <Label nama="UnitOrganisasiId">Scope Unit</Label>
              <Combobox
                nilai={form.data.UnitOrganisasiId}
                onPilih={(value) => form.setData('UnitOrganisasiId', value)}
                opsi={[opsiKosong('Seluruh organisasi'), ...opsiDari(unitOrganisasi, (unit) => unit.Nama)]}
              />
            </div>
            <div className="grid gap-4 sm:grid-cols-3">
              <div className="space-y-1.5 sm:col-span-2">
                <Label nama="Jumlah" htmlFor="jumlah-anggaran">
                  Total Anggaran
                </Label>
                <Input
                  id="jumlah-anggaran"
                  type="number"
                  min="0.01"
                  step="0.01"
                  value={form.data.Jumlah}
                  onChange={(event) => form.setData('Jumlah', event.target.value)}
                />
                {form.errors.Jumlah && <p className="text-sm text-destructive">{form.errors.Jumlah}</p>}
              </div>
              <div className="space-y-1.5">
                <Label nama="MataUang" htmlFor="mata-uang">
                  Mata Uang
                </Label>
                <Input
                  id="mata-uang"
                  maxLength={3}
                  value={form.data.MataUang}
                  onChange={(event) => form.setData('MataUang', event.target.value.toUpperCase())}
                />
              </div>
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Buat Draft
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function AnggaranIndex({ anggaran, unitOrganisasi, filter, wajib }: Props) {
  const [cari, setCari] = useState(filter.cari ?? '');
  const [status, setStatus] = useState(filter.status ?? SEMUA);

  function terapkanFilter(event: FormEvent): void {
    event.preventDefault();
    router.get(
      ruteAnggaran.index,
      { cari: cari || undefined, status: status === SEMUA ? undefined : status },
      { preserveState: true },
    );
  }

  return (
    <KerangkaAplikasi>
      <Head title="Anggaran" />
      <div className="space-y-6">
        <KepalaHalaman
          judul="Anggaran"
          deskripsi="Kelola pagu, pos, komitmen, realisasi, dan saldo yang dapat direkonsiliasi."
          aksi={
            <>
              <DialogBuatAnggaran unitOrganisasi={unitOrganisasi} wajib={wajib.anggaran} />
            </>
          }
        />

        <form
          onSubmit={terapkanFilter}
          className="flex flex-col gap-2 rounded-[9px] border border-border bg-card p-3 sm:flex-row"
        >
          <div className="relative flex-1">
            <Search className="absolute left-3 top-2.5 size-4 text-muted-foreground" />
            <Input
              aria-label="Cari anggaran"
              className="pl-9"
              placeholder="Cari kode atau nama..."
              value={cari}
              onChange={(event) => setCari(event.target.value)}
            />
          </div>
          <Combobox
            nilai={status}
            onPilih={setStatus}
            opsi={[
              { nilai: SEMUA, label: 'Semua status' },
              ...Object.keys(VARIAN_STATUS).map((nilai) => ({
                nilai: nilai,
                label: nilai === 'MenungguPersetujuan' ? 'Menunggu Persetujuan' : nilai,
              })),
            ]}
            className="sm:w-52"
          />
          <Button type="submit" variant="outline">
            Terapkan
          </Button>
        </form>

        {anggaran.data.length === 0 ? (
          <KeadaanKosong
            ilustrasi="/assets/3d/dashboard-analitik.webp"
            judul="Belum ada anggaran."
            deskripsi="Buat anggaran periode pertama untuk mulai mengalokasikan pos."
          />
        ) : (
          <div className="overflow-hidden rounded-[9px] border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Anggaran</th>
                    <th className="px-4 py-3">Periode / Scope</th>
                    <th className="px-4 py-3 text-right">Total</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {anggaran.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-muted/30">
                      <td className="px-4 py-3">
                        <Link
                          className="font-medium text-foreground hover:text-primary"
                          href={ruteAnggaran.detail(item.Id)}
                        >
                          {item.Nama}
                        </Link>
                        <div className="font-mono text-xs text-muted-foreground">
                          {item.Kode} · {item.JumlahPos ?? 0} pos
                        </div>
                      </td>
                      <td className="px-4 py-3 text-muted-foreground">
                        {item.Tahun}
                        <div className="text-xs">{item.NamaUnitOrganisasi ?? 'Seluruh organisasi'}</div>
                      </td>
                      <td className="px-4 py-3 text-right font-mono font-semibold">
                        {formatUang(item.Jumlah, item.MataUang)}
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={VARIAN_STATUS[item.Status]}>
                          {item.Status === 'MenungguPersetujuan' ? 'Menunggu Persetujuan' : item.Status}
                        </Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="divide-y divide-border md:hidden">
              {anggaran.data.map((item) => (
                <Link
                  key={item.Id}
                  href={ruteAnggaran.detail(item.Id)}
                  className="flex min-h-24 items-center gap-3 p-4"
                >
                  <WalletCards className="size-5 shrink-0 text-primary" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-medium">{item.Nama}</p>
                    <p className="font-mono text-xs text-muted-foreground">
                      {item.Kode} · {item.Tahun}
                    </p>
                    <p className="mt-1 text-sm font-semibold">{formatUang(item.Jumlah, item.MataUang)}</p>
                  </div>
                  <Badge variant={VARIAN_STATUS[item.Status]}>
                    {item.Status === 'MenungguPersetujuan' ? 'Menunggu' : item.Status}
                  </Badge>
                </Link>
              ))}
            </div>
            <KontrolPaginasi
              meta={anggaran.meta}
              onNavigasi={(page) => navigasiHalaman(page, { cari, status: status === SEMUA ? '' : status })}
            />
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
