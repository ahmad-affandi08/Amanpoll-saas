import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { PageHeader } from '@/components/shared/PageHeader';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { BarisSupresi, PermintaanData, RiwayatKonsen } from '@/features/Pemasaran/types';

interface Props {
  cari: string;
  supresi: BarisSupresi[];
  riwayat: RiwayatKonsen[];
  permintaan: PermintaanData[];
  pilihan: { Alasan: string[]; Jenis: string[] };
}

const AKAR = '/admin-platform/pemasaran/email/konsen';

const waktu = (nilai: string | null) => (nilai ? new Date(nilai).toLocaleString('id-ID') : '—');

export default function Konsen({ cari, supresi, riwayat, permintaan, pilihan }: Props) {
  const [kataKunci, setKataKunci] = useState(cari);

  const telusuri = (e: FormEvent) => {
    e.preventDefault();
    router.get(AKAR, { cari: kataKunci }, { preserveState: true, preserveScroll: true });
  };

  return (
    <KerangkaPlatform>
      <Head title="Consent dan Supresi" />

      <PageHeader
        judul="Consent dan Supresi"
        deskripsi="Siapa boleh dikirimi pesan pemasaran, sejak kapan, dan atas dasar apa."
        tanpaBreadcrumb
        className="mb-6"
      />

      <form onSubmit={telusuri} className="mb-6 flex gap-2">
        <Input
          value={kataKunci}
          onChange={(e) => setKataKunci(e.target.value)}
          placeholder="Cari alamat email..."
          className="max-w-sm"
        />
        <Button type="submit" variant="outline">
          Cari
        </Button>
      </form>

      <Tabs defaultValue="supresi">
        <TabsList>
          <TabsTrigger value="supresi">Daftar Supresi</TabsTrigger>
          <TabsTrigger value="riwayat">Riwayat Consent</TabsTrigger>
          <TabsTrigger value="permintaan">Permintaan Data</TabsTrigger>
        </TabsList>

        <TabsContent value="supresi" className="mt-4 space-y-4">
          <div className="flex justify-end">
            <DialogSupresi pilihan={pilihan} />
          </div>
          <DaftarSupresiTabel supresi={supresi} />
        </TabsContent>

        <TabsContent value="riwayat" className="mt-4">
          <RiwayatKonsenTabel riwayat={riwayat} cari={cari} />
        </TabsContent>

        <TabsContent value="permintaan" className="mt-4 space-y-4">
          <div className="flex justify-end">
            <DialogPermintaan pilihan={pilihan} />
          </div>
          <PermintaanTabel permintaan={permintaan} />
        </TabsContent>
      </Tabs>
    </KerangkaPlatform>
  );
}

function DaftarSupresiTabel({ supresi }: { supresi: BarisSupresi[] }) {
  if (supresi.length === 0) {
    return <p className="text-sm text-muted-foreground">Tidak ada alamat yang disupresi.</p>;
  }

  return (
    <div className="space-y-2">
      {supresi.map((satu) => (
        <Card key={satu.Id}>
          <CardContent className="flex flex-wrap items-center justify-between gap-2 p-4">
            <div className="min-w-0">
              <p className="truncate font-mono text-sm">
                {satu.Email ?? <span className="italic text-muted-foreground">alamat sudah dihapus</span>}
              </p>
              {satu.Catatan ? <p className="text-xs text-muted-foreground">{satu.Catatan}</p> : null}
            </div>
            <div className="flex shrink-0 items-center gap-2">
              <Badge variant="outline">{satu.Alasan}</Badge>
              <span className="text-xs text-muted-foreground">{waktu(satu.DitambahkanPada)}</span>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

function RiwayatKonsenTabel({ riwayat, cari }: { riwayat: RiwayatKonsen[]; cari: string }) {
  if (cari === '') {
    return (
      <p className="text-sm text-muted-foreground">
        Cari satu alamat untuk membaca riwayat persetujuannya.
      </p>
    );
  }

  if (riwayat.length === 0) {
    return <p className="text-sm text-muted-foreground">Tidak ada catatan consent untuk alamat itu.</p>;
  }

  return (
    <div className="space-y-2">
      {riwayat.map((satu) => (
        <Card key={satu.Id}>
          <CardContent className="flex flex-wrap items-center justify-between gap-2 p-4">
            <div className="min-w-0">
              <p className="truncate font-mono text-sm">{satu.Email}</p>
              <p className="text-xs text-muted-foreground">
                {satu.Sumber} · kebijakan {satu.VersiKebijakan}
              </p>
            </div>
            <div className="flex shrink-0 items-center gap-2">
              {satu.Diberikan ? <Badge>Disetujui</Badge> : <Badge variant="destructive">Dicabut</Badge>}
              <span className="text-xs text-muted-foreground">{waktu(satu.DicatatPada)}</span>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

function PermintaanTabel({ permintaan }: { permintaan: PermintaanData[] }) {
  const konfirmasi = useKonfirmasi();

  const proses = async (satu: PermintaanData) => {
    const setuju = await konfirmasi({
      judul: `Proses permintaan ${satu.Jenis.toLowerCase()}?`,
      deskripsi:
        satu.Jenis === 'Penghapusan'
          ? 'Data prospek ini dibuang dan alamatnya dilepas dari seluruh catatan. Hanya sidik alamatnya yang tersisa di daftar supresi. Tidak dapat dibatalkan.'
          : 'Identitas prospek ini dilepas, tetapi barisnya tetap ada agar corong dan attribution tetap terbaca. Tidak dapat dibatalkan.',
      ragam: 'bahaya',
    });

    if (setuju) {
      router.post(`${AKAR}/permintaan/${satu.Id}/proses`, {}, { preserveScroll: true });
    }
  };

  if (permintaan.length === 0) {
    return <p className="text-sm text-muted-foreground">Belum ada permintaan data.</p>;
  }

  return (
    <div className="space-y-2">
      {permintaan.map((satu) => (
        <Card key={satu.Id}>
          <CardContent className="flex flex-wrap items-center justify-between gap-2 p-4">
            <div className="min-w-0">
              <p className="truncate font-mono text-sm">
                {satu.Email ?? <span className="italic text-muted-foreground">alamat sudah dihapus</span>}
              </p>
              <p className="text-xs text-muted-foreground">
                Diminta {waktu(satu.DimintaPada)}
                {satu.Catatan ? ` · ${satu.Catatan}` : ''}
              </p>
            </div>
            <div className="flex shrink-0 items-center gap-2">
              <Badge variant="outline">{satu.Jenis}</Badge>
              {satu.DiprosesPada ? (
                <span className="text-xs text-muted-foreground">Diproses {waktu(satu.DiprosesPada)}</span>
              ) : (
                <Button variant="outline" size="sm" onClick={() => proses(satu)}>
                  Proses
                </Button>
              )}
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

function DialogSupresi({ pilihan }: { pilihan: { Alasan: string[] } }) {
  const [buka, setBuka] = useState(false);

  const form = useForm({
    Email: '',
    Alasan: pilihan.Alasan[0] ?? '',
    Catatan: '',
  });

  const kirim = (e: FormEvent) => {
    e.preventDefault();

    form.post(`${AKAR}/supresi`, {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Supresi Alamat</Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Supresi Alamat</DialogTitle>
        </DialogHeader>

        <form onSubmit={kirim} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="EmailSupresi">Email</Label>
            <Input
              id="EmailSupresi"
              type="email"
              value={form.data.Email}
              onChange={(e) => form.setData('Email', e.target.value)}
              required
            />
            {form.errors.Email ? <p className="text-sm text-destructive">{form.errors.Email}</p> : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="AlasanSupresi">Alasan</Label>
            <Select value={form.data.Alasan} onValueChange={(v) => form.setData('Alasan', v)}>
              <SelectTrigger id="AlasanSupresi">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Alasan.map((alasan) => (
                  <SelectItem key={alasan} value={alasan}>
                    {alasan}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="CatatanSupresi">Catatan</Label>
            <Input
              id="CatatanSupresi"
              value={form.data.Catatan}
              onChange={(e) => form.setData('Catatan', e.target.value)}
            />
          </div>

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

function DialogPermintaan({ pilihan }: { pilihan: { Jenis: string[] } }) {
  const [buka, setBuka] = useState(false);

  const form = useForm({
    Email: '',
    Jenis: pilihan.Jenis[0] ?? '',
    Catatan: '',
  });

  const kirim = (e: FormEvent) => {
    e.preventDefault();

    form.post(`${AKAR}/permintaan`, {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Catat Permintaan</Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Catat Permintaan Data</DialogTitle>
        </DialogHeader>

        <form onSubmit={kirim} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="EmailPermintaan">Email</Label>
            <Input
              id="EmailPermintaan"
              type="email"
              value={form.data.Email}
              onChange={(e) => form.setData('Email', e.target.value)}
              required
            />
            {form.errors.Email ? <p className="text-sm text-destructive">{form.errors.Email}</p> : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="JenisPermintaan">Jenis</Label>
            <Select value={form.data.Jenis} onValueChange={(v) => form.setData('Jenis', v)}>
              <SelectTrigger id="JenisPermintaan">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Jenis.map((jenis) => (
                  <SelectItem key={jenis} value={jenis}>
                    {jenis}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="CatatanPermintaan">Catatan</Label>
            <Input
              id="CatatanPermintaan"
              value={form.data.Catatan}
              onChange={(e) => form.setData('Catatan', e.target.value)}
            />
          </div>

          <p className="text-sm text-muted-foreground">
            Alamatnya langsung disupresi begitu permintaan dicatat, sebelum siapa pun sempat
            memprosesnya.
          </p>

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
