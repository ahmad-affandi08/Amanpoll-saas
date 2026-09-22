import { FormEvent, useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { rutePemasaran } from '@/features/Pemasaran/api';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { formatAngka } from '@/lib/angka';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Kampanye {
  Id: string;
  Kode: string;
  Nama: string;
  Objective: string;
  Status: string;
  MulaiPada: string | null;
  SelesaiPada: string | null;
  Channel: string[];
  JumlahKunjungan: number;
  TotalBiaya: number;
  Budget: number | null;
  Audience: string | null;
  Offer: string | null;
  HalamanId: string | null;
  FormulirId: string | null;
  UtmSource: string | null;
  UtmMedium: string | null;
  UtmTerm: string | null;
  UtmContent: string | null;
  Catatan: string | null;
}

interface Pilihan {
  Status: string[];
  Objective: string[];
  Channel: string[];
  Halaman: Record<string, string>;
  Formulir: Record<string, string>;
}

interface Props {
  kampanye: Paginasi<Kampanye>;
  pilihan: Pilihan;
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogFormKampanye({
  kampanye,
  pilihan,
  wajib,
}: {
  kampanye: Kampanye | null;
  pilihan: Pilihan;
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: kampanye?.Kode ?? '',
    Nama: kampanye?.Nama ?? '',
    Objective: kampanye?.Objective ?? pilihan.Objective[0],
    Status: kampanye?.Status ?? 'Draf',
    MulaiPada: kampanye?.MulaiPada ?? '',
    SelesaiPada: kampanye?.SelesaiPada ?? '',
    Channel: kampanye?.Channel ?? [],
    Budget: kampanye?.Budget === null || kampanye?.Budget === undefined ? '' : String(kampanye.Budget),
    Audience: kampanye?.Audience ?? '',
    Offer: kampanye?.Offer ?? '',
    HalamanId: kampanye?.HalamanId ?? '',
    FormulirId: kampanye?.FormulirId ?? '',
    UtmSource: kampanye?.UtmSource ?? '',
    UtmMedium: kampanye?.UtmMedium ?? '',
    UtmTerm: kampanye?.UtmTerm ?? '',
    UtmContent: kampanye?.UtmContent ?? '',
    Catatan: kampanye?.Catatan ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!kampanye) form.reset();
      },
    };

    if (kampanye) {
      router.put(rutePemasaran.kampanyeDetail(kampanye.Id), form.data, opsi);
    } else {
      router.post(rutePemasaran.kampanye, form.data, opsi);
    }
  };

  const ubahChannel = (channel: string, dipilih: boolean) => {
    form.setData(
      'Channel',
      dipilih ? [...form.data.Channel, channel] : form.data.Channel.filter((satu) => satu !== channel),
    );
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={kampanye ? 'outline' : 'default'} size={kampanye ? 'sm' : 'default'}>
          {kampanye ? 'Ubah' : 'Tambah Kampanye'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{kampanye ? 'Ubah Kampanye' : 'Tambah Kampanye'}</DialogTitle>
        </DialogHeader>

        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="grid gap-4">
            <BidangKode
              nilai={form.data.Kode}
              onUbah={(nilai) => form.setData('Kode', nilai)}
              galat={form.errors.Kode}
            />

            <div className="grid gap-2">
              <Label nama="Nama" htmlFor="Nama">
                Nama
              </Label>
              <Input
                id="Nama"
                value={form.data.Nama}
                onChange={(e) => form.setData('Nama', e.target.value)}
                required
              />
              {form.errors.Nama ? <p className="text-sm text-destructive">{form.errors.Nama}</p> : null}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="grid gap-2">
                <Label nama="Objective" htmlFor="Objective">
                  Objective
                </Label>
                <Select value={form.data.Objective} onValueChange={(v) => form.setData('Objective', v)}>
                  <SelectTrigger id="Objective">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {pilihan.Objective.map((satu) => (
                      <SelectItem key={satu} value={satu}>
                        {satu}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="grid gap-2">
                <Label nama="Status" htmlFor="Status">
                  Status
                </Label>
                {kampanye ? (
                  <Select value={form.data.Status} onValueChange={(v) => form.setData('Status', v)}>
                    <SelectTrigger id="Status">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {pilihan.Status.map((satu) => (
                        <SelectItem key={satu} value={satu}>
                          {satu}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                ) : (
                  <Input id="Status" value="Draf" readOnly className="bg-muted" />
                )}
                <p className="text-sm text-muted-foreground">
                  Kampanye lahir sebagai draf, lalu berpindah menurut peta transisinya.
                </p>
                {form.errors.Status ? <p className="text-sm text-destructive">{form.errors.Status}</p> : null}
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="grid gap-2">
                <Label nama="MulaiPada" htmlFor="MulaiPada">
                  Mulai
                </Label>
                <Input
                  id="MulaiPada"
                  type="date"
                  value={form.data.MulaiPada}
                  onChange={(e) => form.setData('MulaiPada', e.target.value)}
                />
              </div>
              <div className="grid gap-2">
                <Label nama="SelesaiPada" htmlFor="SelesaiPada">
                  Selesai
                </Label>
                <Input
                  id="SelesaiPada"
                  type="date"
                  value={form.data.SelesaiPada}
                  onChange={(e) => form.setData('SelesaiPada', e.target.value)}
                />
                {form.errors.SelesaiPada ? (
                  <p className="text-sm text-destructive">{form.errors.SelesaiPada}</p>
                ) : null}
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="grid gap-2">
                <Label nama="Budget" htmlFor="Budget">
                  Budget
                </Label>
                <Input
                  id="Budget"
                  type="number"
                  min="0"
                  value={form.data.Budget}
                  onChange={(e) => form.setData('Budget', e.target.value)}
                />
                <p className="text-sm text-muted-foreground">
                  Rencana belanja. Realisasinya dicatat per hari di halaman detail.
                </p>
              </div>
              <div className="grid gap-2">
                <Label nama="Offer" htmlFor="Offer">
                  Offer
                </Label>
                <Input
                  id="Offer"
                  value={form.data.Offer}
                  onChange={(e) => form.setData('Offer', e.target.value)}
                />
              </div>
            </div>

            <div className="grid gap-2">
              <Label nama="Audience" htmlFor="Audience">
                Audience
              </Label>
              <Input
                id="Audience"
                value={form.data.Audience}
                onChange={(e) => form.setData('Audience', e.target.value)}
              />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="grid gap-2">
                <Label nama="HalamanId" htmlFor="HalamanId">
                  Landing page
                </Label>
                <Select
                  value={form.data.HalamanId === '' ? 'kosong' : form.data.HalamanId}
                  onValueChange={(v) => form.setData('HalamanId', v === 'kosong' ? '' : v)}
                >
                  <SelectTrigger id="HalamanId">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="kosong">Belum ditentukan</SelectItem>
                    {Object.entries(pilihan.Halaman).map(([id, slug]) => (
                      <SelectItem key={id} value={id}>
                        {slug}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="grid gap-2">
                <Label nama="FormulirId" htmlFor="FormulirId">
                  Formulir
                </Label>
                <Select
                  value={form.data.FormulirId === '' ? 'kosong' : form.data.FormulirId}
                  onValueChange={(v) => form.setData('FormulirId', v === 'kosong' ? '' : v)}
                >
                  <SelectTrigger id="FormulirId">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="kosong">Belum ditentukan</SelectItem>
                    {Object.entries(pilihan.Formulir).map(([id, kode]) => (
                      <SelectItem key={id} value={id}>
                        {kode}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            <fieldset className="grid gap-2">
              <legend className="text-sm font-medium">Tag UTM</legend>
              <p className="text-sm text-muted-foreground">
                <code className="font-mono">utm_campaign</code> selalu memakai kode di atas.
              </p>
              <div className="grid gap-3 sm:grid-cols-2">
                {(
                  [
                    ['UtmSource', 'utm_source'],
                    ['UtmMedium', 'utm_medium'],
                    ['UtmTerm', 'utm_term'],
                    ['UtmContent', 'utm_content'],
                  ] as const
                ).map(([kunci, label]) => (
                  <div key={kunci} className="grid gap-2">
                    <Label htmlFor={kunci}>{label}</Label>
                    <Input
                      id={kunci}
                      value={form.data[kunci]}
                      onChange={(e) => form.setData(kunci, e.target.value)}
                    />
                  </div>
                ))}
              </div>
            </fieldset>

            <fieldset className="grid gap-2">
              <legend className="text-sm font-medium">Channel</legend>
              <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                {pilihan.Channel.map((satu) => (
                  <label key={satu} className="flex items-center gap-2 text-sm">
                    <Checkbox
                      checked={form.data.Channel.includes(satu)}
                      onCheckedChange={(nilai) => ubahChannel(satu, nilai === true)}
                    />
                    {satu}
                  </label>
                ))}
              </div>
            </fieldset>

            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function PemasaranKampanye({ kampanye, pilihan, filter, wajib }: Props) {
  const columns = useMemo<ColumnDef<Kampanye>[]>(
    () => [
      {
        id: 'Nama',
        accessorFn: (row) => `${row.Nama} ${row.Kode}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kampanye" />,
        cell: ({ row }) => (
          <div>
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            <div className="font-mono text-xs text-muted-foreground">{row.original.Kode}</div>
          </div>
        ),
        meta: { label: 'Kampanye', kartu: 'judul' },
      },
      {
        id: 'Status',
        accessorFn: (row) => row.Status,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => <Badge variant="secondary">{row.original.Status}</Badge>,
        meta: { label: 'Status' },
      },
      {
        id: 'Objective',
        accessorFn: (row) => row.Objective,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Objective" />,
        meta: { label: 'Objective' },
      },
      {
        id: 'Channel',
        header: 'Channel',
        cell: ({ row }) =>
          row.original.Channel.length === 0 ? (
            '—'
          ) : (
            <div className="flex flex-wrap gap-1">
              {row.original.Channel.map((satu) => (
                <Badge key={satu} variant="outline">
                  {satu}
                </Badge>
              ))}
            </div>
          ),
        enableSorting: false,
        meta: { label: 'Channel' },
      },
      {
        id: 'JumlahKunjungan',
        accessorFn: (row) => row.JumlahKunjungan,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kunjungan" />,
        // Agregat yang dihitung untuk halaman ini saja, jadi tidak dapat diurutkan lintas halaman.
        enableSorting: false,
        meta: { label: 'Kunjungan' },
      },
      {
        id: 'TotalBiaya',
        accessorFn: (row) => row.TotalBiaya,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Biaya" />,
        cell: ({ row }) => <span className="font-mono">{formatAngka(row.original.TotalBiaya)}</span>,
        enableSorting: false,
        meta: { label: 'Biaya' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <Button variant="ghost" size="sm" asChild>
              <Link href={rutePemasaran.kampanyeDetail(row.original.Id)}>Detail</Link>
            </Button>
            <DialogFormKampanye kampanye={row.original} pilihan={pilihan} wajib={wajib.kampanye} />
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { label: 'Aksi', kartu: 'aksi' },
      },
    ],
    [pilihan, wajib],
  );

  return (
    <KerangkaPlatform>
      <Head title="Kampanye" />

      <KepalaHalaman
        judul="Kampanye"
        deskripsi="Kode kampanye menjadi utm_campaign pada tautan iklan, sehingga kunjungannya tertaut otomatis."
        tanpaBreadcrumb
        aksi={<DialogFormKampanye kampanye={null} pilihan={pilihan} wajib={wajib.kampanye} />}
        className="mb-6"
      />

      <DataTable
        columns={columns}
        data={kampanye.data}
        server={{ meta: kampanye.meta, filter }}
        facetedFilters={[
          {
            columnId: 'Status',
            title: 'Status',
            options: pilihan.Status.map((satu) => ({ label: satu, value: satu })),
          },
          {
            columnId: 'Objective',
            title: 'Objective',
            options: pilihan.Objective.map((satu) => ({ label: satu, value: satu })),
          },
        ]}
        kartuDiPonsel
        pencarianPlaceholder="Cari nama atau kode kampanye..."
        pesanKosong={adaPenyaringAktif(filter) ? 'Tidak ada kampanye yang cocok.' : 'Belum ada kampanye.'}
      />
    </KerangkaPlatform>
  );
}
