import { FormEvent, useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { useIzin } from '@/hooks/use-izin';
import type { Pengguna, PeranRingkas } from '@/features/Pengguna/types';
import { rutePengguna } from '@/features/Pengguna/api';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari, opsiKosong, TANPA_PILIHAN } from '@/lib/pilihan';

interface Acuan {
  Id: string;
  Nama: string;
}

interface Props {
  pengguna: Paginasi<Pengguna>;
  filter: FilterDaftar;
  peranTersedia: PeranRingkas[];
  unitOrganisasi: Acuan[];
  lokasi: Acuan[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const kosong = {
  Nama: '',
  Email: '',
  KataSandi: '',
  Telepon: '',
  NomorPegawai: '',
  Jabatan: '',
  JenisPengguna: 'Internal' as const,
};

function DialogFormPengguna({ pengguna, wajib }: { pengguna: Pengguna | null; wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm(
    pengguna
      ? {
          Nama: pengguna.Nama,
          Email: pengguna.Email,
          KataSandi: '',
          Telepon: pengguna.Telepon ?? '',
          NomorPegawai: pengguna.NomorPegawai ?? '',
          Jabatan: pengguna.Jabatan ?? '',
          JenisPengguna: pengguna.JenisPengguna,
        }
      : kosong,
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    };
    if (pengguna) {
      form.put(rutePengguna.detail(pengguna.Id), opsi);
    } else {
      form.post(rutePengguna.index, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={pengguna ? 'outline' : 'default'} size={pengguna ? 'sm' : 'default'}>
          {pengguna ? 'Ubah' : 'Tambah Pengguna'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{pengguna ? 'Ubah Pengguna' : 'Tambah Pengguna'}</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label nama="Nama">Nama</Label>
                <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
                {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
              </div>
              <div className="space-y-2">
                <Label nama="Email">Email</Label>
                <Input
                  type="email"
                  value={form.data.Email}
                  onChange={(e) => form.setData('Email', e.target.value)}
                />
                {form.errors.Email && <p className="text-sm text-destructive">{form.errors.Email}</p>}
              </div>
            </div>
            <div className="space-y-2">
              <Label wajib={!pengguna}>{pengguna ? 'Kata Sandi Baru (opsional)' : 'Kata Sandi'}</Label>
              <Input
                type="password"
                value={form.data.KataSandi}
                onChange={(e) => form.setData('KataSandi', e.target.value)}
              />
              {form.errors.KataSandi && <p className="text-sm text-destructive">{form.errors.KataSandi}</p>}
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label nama="Telepon">Telepon</Label>
                <Input value={form.data.Telepon} onChange={(e) => form.setData('Telepon', e.target.value)} />
              </div>
              <div className="space-y-2">
                <Label nama="NomorPegawai">Nomor Pegawai</Label>
                <Input
                  value={form.data.NomorPegawai}
                  onChange={(e) => form.setData('NomorPegawai', e.target.value)}
                />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label nama="Jabatan">Jabatan</Label>
                <Input value={form.data.Jabatan} onChange={(e) => form.setData('Jabatan', e.target.value)} />
              </div>
              <div className="space-y-2">
                <Label nama="JenisPengguna">Jenis Pengguna</Label>
                <Select
                  value={form.data.JenisPengguna}
                  onValueChange={(v) => form.setData('JenisPengguna', v as 'Internal' | 'Eksternal')}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Internal">Internal</SelectItem>
                    <SelectItem value="Eksternal">Eksternal</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
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

function DialogKelolaPeran({
  pengguna,
  peranTersedia,
  unitOrganisasi,
  lokasi,
}: {
  pengguna: Pengguna;
  peranTersedia: PeranRingkas[];
  unitOrganisasi: Acuan[];
  lokasi: Acuan[];
}) {
  const [buka, setBuka] = useState(false);
  const [peranTerpilih, setPeranTerpilih] = useState('');
  const [unitTerpilih, setUnitTerpilih] = useState(TANPA_PILIHAN);
  const [lokasiTerpilih, setLokasiTerpilih] = useState(TANPA_PILIHAN);

  const tambahkan = () => {
    if (!peranTerpilih) return;
    router.post(
      rutePengguna.peran(pengguna.Id),
      {
        PeranId: peranTerpilih,
        UnitOrganisasiId: unitTerpilih === TANPA_PILIHAN ? null : unitTerpilih,
        LokasiId: lokasiTerpilih === TANPA_PILIHAN ? null : lokasiTerpilih,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          setPeranTerpilih('');
          setUnitTerpilih(TANPA_PILIHAN);
          setLokasiTerpilih(TANPA_PILIHAN);
        },
      },
    );
  };

  const namaUnit = (id: string | null) => unitOrganisasi.find((u) => u.Id === id)?.Nama;
  const namaLokasi = (id: string | null) => lokasi.find((l) => l.Id === id)?.Nama;

  const cabut = (penggunaPeranId: string) => {
    router.delete(rutePengguna.peranDetail(penggunaPeranId), { preserveScroll: true });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          Kelola Peran
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Peran untuk {pengguna.Nama}</DialogTitle>
        </DialogHeader>
        <div className="space-y-3">
          {pengguna.Peran.length === 0 && (
            <p className="text-sm text-muted-foreground">Belum ada peran ditetapkan.</p>
          )}
          {pengguna.Peran.map((p) => (
            <div
              key={p.Id}
              className="flex items-center justify-between rounded-md border border-border px-3 py-2"
            >
              <div className="min-w-0">
                <div className="text-sm">{p.NamaPeran}</div>
                <div className="text-xs text-muted-foreground">
                  {p.UnitOrganisasiId || p.LokasiId
                    ? `Terbatas pada ${[namaUnit(p.UnitOrganisasiId), namaLokasi(p.LokasiId)]
                        .filter(Boolean)
                        .join(' · ')}`
                    : 'Seluruh organisasi'}
                </div>
              </div>
              <Button variant="ghost" size="sm" onClick={() => cabut(p.Id)}>
                Cabut
              </Button>
            </div>
          ))}
          <div className="space-y-2 border-t border-border pt-3">
            <Combobox
              nilai={peranTerpilih}
              onPilih={setPeranTerpilih}
              opsi={opsiDari(peranTersedia, (p) => p.Nama)}
              placeholder="Pilih peran"
            />
            <div className="grid grid-cols-2 gap-2">
              <Combobox
                nilai={unitTerpilih}
                onPilih={setUnitTerpilih}
                opsi={[opsiKosong('Semua unit'), ...opsiDari(unitOrganisasi, (u) => u.Nama)]}
              />
              <Combobox
                nilai={lokasiTerpilih}
                onPilih={setLokasiTerpilih}
                opsi={[opsiKosong('Semua ruangan'), ...opsiDari(lokasi, (l) => l.Nama)]}
              />
            </div>
            <p className="text-sm text-muted-foreground">
              Membatasi unit atau ruangan membuat pengguna ini hanya melihat data di dalamnya, beserta
              sub-unit dan ruangan di bawahnya. Dibiarkan kosong berarti seluruh organisasi.
            </p>
            <Button onClick={tambahkan} className="w-full">
              Tetapkan
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

export default function PenggunaIndex({
  pengguna,
  peranTersedia,
  unitOrganisasi,
  lokasi,
  filter,
  wajib,
}: Props) {
  const { boleh } = useIzin();
  const bolehKelola = boleh('Pengguna.Kelola');

  const ubahStatus = (item: Pengguna) => {
    const statusBaru = item.Status === 'Aktif' ? 'Nonaktif' : 'Aktif';
    router.put(rutePengguna.status(item.Id), { Status: statusBaru }, { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<Pengguna>[]>(
    () => [
      {
        id: 'Nama',
        accessorFn: (row) => `${row.Nama} ${row.Email}`,
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        cell: ({ row }) => (
          <Link href={rutePengguna.detail(row.original.Id)} className="hover:underline">
            <div className="font-medium text-foreground">{row.original.Nama}</div>
            <div className="text-sm text-muted-foreground">{row.original.Email}</div>
          </Link>
        ),
        meta: { label: 'Nama' },
      },
      {
        accessorKey: 'Jabatan',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Jabatan" />,
        cell: ({ row }) => row.original.Jabatan ?? '—',
        meta: { label: 'Jabatan' },
      },
      {
        id: 'JenisPengguna',
        accessorKey: 'JenisPengguna',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Jenis" />,
        cell: ({ row }) => row.original.JenisPengguna,
        filterFn: (row, id, value: string[]) => value.includes(row.getValue(id)),
        meta: { label: 'Jenis' },
      },
      {
        id: 'Peran',
        header: 'Peran',
        accessorFn: (row) => row.Peran.map((p) => p.NamaPeran).join(', '),
        cell: ({ row }) => (
          <div className="flex flex-wrap gap-1">
            {row.original.Peran.map((p) => (
              <Badge key={p.Id} variant="secondary">
                {p.NamaPeran}
              </Badge>
            ))}
          </div>
        ),
        enableSorting: false,
        meta: { label: 'Peran' },
      },
      {
        accessorKey: 'Status',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <Badge variant={row.original.Status === 'Aktif' ? 'default' : 'outline'}>
            {row.original.Status}
          </Badge>
        ),
        filterFn: (row, id, value: string[]) => value.includes(row.getValue(id)),
        meta: { label: 'Status' },
      },
      ...(bolehKelola
        ? [
            {
              id: 'aksi',
              header: 'Aksi',
              cell: ({ row }: { row: { original: Pengguna } }) => (
                <div className="flex justify-end gap-2">
                  <DialogFormPengguna pengguna={row.original} wajib={wajib.pengguna} />
                  <DialogKelolaPeran
                    pengguna={row.original}
                    peranTersedia={peranTersedia}
                    unitOrganisasi={unitOrganisasi}
                    lokasi={lokasi}
                  />
                  <Button variant="ghost" size="sm" onClick={() => ubahStatus(row.original)}>
                    {row.original.Status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan'}
                  </Button>
                </div>
              ),
              enableSorting: false,
              enableHiding: false,
              meta: { label: 'Aksi' },
            } satisfies ColumnDef<Pengguna>,
          ]
        : []),
    ],
    [bolehKelola, peranTersedia, unitOrganisasi, lokasi, wajib],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Pengguna" />
      <KepalaHalaman
        judul="Pengguna"
        deskripsi="Kelola akun pengguna dan penetapan peran."
        aksi={<>{bolehKelola && <DialogFormPengguna pengguna={null} wajib={wajib.pengguna} />}</>}
        className="mb-6"
      />

      {pengguna.meta.total === 0 && !adaPenyaringAktif(filter) ? (
        <KeadaanKosong
          ilustrasi="/assets/3d/pengguna.webp"
          judul="Belum ada pengguna."
          deskripsi="Tambahkan pengguna pertama untuk memberi akses ke sistem."
        />
      ) : (
        <DataTable
          columns={columns}
          data={pengguna.data}
          server={{ meta: pengguna.meta, filter }}
          pencarianPlaceholder="Cari nama, email, jabatan..."
          facetedFilters={[
            {
              columnId: 'Status',
              title: 'Status',
              options: [
                { label: 'Aktif', value: 'Aktif' },
                { label: 'Nonaktif', value: 'Nonaktif' },
              ],
            },
            {
              columnId: 'JenisPengguna',
              title: 'Jenis',
              options: [
                { label: 'Internal', value: 'Internal' },
                { label: 'Eksternal', value: 'Eksternal' },
              ],
            },
          ]}
          pesanKosong="Tidak ada pengguna yang cocok."
          ilustrasiKosong="/assets/3d/pengguna.webp"
        />
      )}
    </KerangkaAplikasi>
  );
}
