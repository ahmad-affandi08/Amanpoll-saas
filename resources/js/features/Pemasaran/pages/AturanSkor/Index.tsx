import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
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
import type { AturanSkor, PilihanAturanSkor } from '@/features/Pemasaran/types';
import { rutePemasaran } from '@/features/Pemasaran/api';

interface Props {
  aturan: AturanSkor[];
  pilihan: PilihanAturanSkor;
}

const AKAR = rutePemasaran.aturanSkor;

export default function PemasaranAturanSkorIndex({ aturan, pilihan }: Props) {
  const konfirmasi = useKonfirmasi();

  const hapus = async (satu: AturanSkor) => {
    const setuju = await konfirmasi({
      judul: 'Hapus aturan skor?',
      deskripsi: `Sinyal ${satu.Peristiwa} tidak lagi menyumbang apa pun pada perhitungan berikutnya.`,
      ragam: 'bahaya',
    });

    if (setuju) {
      router.delete(`${AKAR}/${satu.Id}`, { preserveScroll: true });
    }
  };

  const columns = useMemo<ColumnDef<AturanSkor>[]>(
    () => [
      {
        id: 'Peristiwa',
        accessorFn: (row) => row.Peristiwa,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Sinyal" />,
        cell: ({ row }) => (
          <div>
            <div className="font-medium text-foreground">{row.original.Peristiwa}</div>
            {row.original.Keterangan ? (
              <div className="text-xs text-muted-foreground">{row.original.Keterangan}</div>
            ) : null}
          </div>
        ),
        meta: { label: 'Sinyal', kartu: 'judul' },
      },
      {
        id: 'Bobot',
        accessorFn: (row) => row.Bobot,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Bobot" />,
        cell: ({ row }) => (
          <span
            className={`font-mono font-medium ${
              row.original.Bobot < 0 ? 'text-destructive' : 'text-foreground'
            }`}
          >
            {row.original.Bobot > 0 ? `+${row.original.Bobot}` : row.original.Bobot}
          </span>
        ),
        meta: { label: 'Bobot' },
      },
      {
        id: 'Asal',
        accessorFn: (row) => row.Asal ?? '',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Asal" />,
        cell: ({ row }) => (
          <div className="flex flex-wrap gap-1">
            <Badge variant="outline">{row.original.Asal ?? 'Tidak dikenal'}</Badge>
            {row.original.Aktif ? null : <Badge variant="secondary">Nonaktif</Badge>}
          </div>
        ),
        meta: { label: 'Asal' },
      },
      {
        id: 'Berlaku',
        accessorFn: (row) => (row.Berlaku ? 'ya' : 'tidak'),
        header: ({ column }) => <DataTableColumnHeader column={column} title="Berlaku" />,
        cell: ({ row }) =>
          row.original.Berlaku ? (
            <Badge>Berlaku</Badge>
          ) : (
            <Badge variant="outline" title="Belum ada yang menghasilkan sinyal ini.">
              Belum berlaku
            </Badge>
          ),
        meta: { label: 'Berlaku' },
      },
      {
        id: 'JumlahDipakai',
        accessorFn: (row) => row.JumlahDipakai,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Dipakai" />,
        cell: ({ row }) => <span className="font-mono text-xs">{row.original.JumlahDipakai}</span>,
        meta: { label: 'Dipakai' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="flex justify-end gap-2">
            <DialogAturan aturan={row.original} pilihan={pilihan} />
            <Button variant="ghost" size="sm" onClick={() => hapus(row.original)}>
              Hapus
            </Button>
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { label: 'Aksi', kartu: 'aksi' },
      },
    ],
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [pilihan],
  );

  const belumBerlaku = aturan.filter((satu) => satu.Aktif && !satu.Berlaku);

  return (
    <KerangkaPlatform>
      <Head title="Aturan Skor Prospek" />

      <KepalaHalaman
        judul="Aturan Skor Prospek"
        deskripsi="Bobot tiap sinyal terhadap skor prospek. Angkanya tidak pernah ditulis di kode program."
        tanpaBreadcrumb
        aksi={<DialogAturan aturan={null} pilihan={pilihan} />}
        className="mb-6"
      />

      {belumBerlaku.length > 0 ? (
        <div className="mb-4 rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
          {belumBerlaku.length} aturan aktif belum berlaku karena belum ada yang menghasilkan
          sinyalnya:{' '}
          <span className="font-mono">{belumBerlaku.map((satu) => satu.Peristiwa).join(', ')}</span>.
          Bobotnya tersimpan dan akan terpakai begitu sumbernya ada.
        </div>
      ) : null}

      <DataTable
        columns={columns}
        data={aturan}
        kartuDiPonsel
        pencarianPlaceholder="Cari sinyal..."
        pesanKosong="Belum ada aturan skor."
      />
    </KerangkaPlatform>
  );
}

function DialogAturan({ aturan, pilihan }: { aturan: AturanSkor | null; pilihan: PilihanAturanSkor }) {
  const [buka, setBuka] = useState(false);
  const daftarSinyal = Object.entries(pilihan.Peristiwa);

  const form = useForm({
    Peristiwa: aturan?.Peristiwa ?? (daftarSinyal[0]?.[0] ?? ''),
    Bobot: String(aturan?.Bobot ?? 0),
    Aktif: aturan?.Aktif ?? true,
    Keterangan: aturan?.Keterangan ?? '',
  });

  const asalTerpilih = pilihan.Peristiwa[form.data.Peristiwa];

  const kirim = (e: FormEvent) => {
    e.preventDefault();

    form.transform((data) => ({ ...data, Bobot: Number(data.Bobot) }));

    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!aturan) form.reset();
      },
    };

    if (aturan) {
      form.put(`${AKAR}/${aturan.Id}`, opsi);
    } else {
      form.post(AKAR, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={aturan ? 'outline' : 'default'} size={aturan ? 'sm' : 'default'}>
          {aturan ? 'Ubah' : 'Tambah Aturan'}
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{aturan ? 'Ubah Aturan Skor' : 'Tambah Aturan Skor'}</DialogTitle>
        </DialogHeader>

        <form onSubmit={kirim} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="Peristiwa">Sinyal</Label>
            <Select value={form.data.Peristiwa} onValueChange={(v) => form.setData('Peristiwa', v)}>
              <SelectTrigger id="Peristiwa">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {daftarSinyal.map(([kode, asal]) => (
                  <SelectItem key={kode} value={kode}>
                    {kode} · {asal}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {asalTerpilih === pilihan.AsalTertunda ? (
              <p className="text-sm text-muted-foreground">
                Belum ada yang menghasilkan sinyal ini. Bobotnya tersimpan tetapi belum menyumbang
                apa pun.
              </p>
            ) : null}
            {form.errors.Peristiwa ? (
              <p className="text-sm text-destructive">{form.errors.Peristiwa}</p>
            ) : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Bobot">Bobot</Label>
            <Input
              id="Bobot"
              type="number"
              value={form.data.Bobot}
              onChange={(e) => form.setData('Bobot', e.target.value)}
              required
            />
            <p className="text-sm text-muted-foreground">
              Boleh negatif, misalnya untuk sinyal yang menurunkan minat.
            </p>
            {form.errors.Bobot ? <p className="text-sm text-destructive">{form.errors.Bobot}</p> : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Keterangan">Keterangan</Label>
            <Input
              id="Keterangan"
              value={form.data.Keterangan}
              onChange={(e) => form.setData('Keterangan', e.target.value)}
            />
          </div>

          <label className="flex items-center gap-2 text-sm">
            <Checkbox
              checked={form.data.Aktif}
              onCheckedChange={(nilai) => form.setData('Aktif', nilai === true)}
            />
            Aktif
          </label>

          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
