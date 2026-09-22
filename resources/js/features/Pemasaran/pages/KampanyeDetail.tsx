import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { HUE_UTAMA } from '@/components/grafik/palet';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

interface Kampanye {
  Id: string;
  Kode: string;
  Nama: string;
  Objective: string;
  Status: string;
  Budget: number | null;
  Audience: string | null;
  Offer: string | null;
  HalamanId: string | null;
  FormulirId: string | null;
  UtmSource: string | null;
  UtmMedium: string | null;
  UtmTerm: string | null;
  UtmContent: string | null;
  MulaiPada: string | null;
  SelesaiPada: string | null;
  Catatan: string | null;
  Channel: string[];
  TujuanStatus: string[];
}

interface Biaya {
  Id: string;
  Channel: string;
  Tanggal: string;
  Jumlah: number;
  Catatan: string | null;
}

interface Target {
  Id: string;
  Metrik: string;
  Nilai: number;
  SatuanUang: boolean;
  Realisasi: number;
}

interface Konten {
  Id: string;
  Jenis: string;
  Judul: string;
  Tautan: string | null;
  Catatan: string | null;
  Urutan: number;
}

interface Props {
  kampanye: Kampanye;
  biaya: Biaya[];
  target: Target[];
  konten: Konten[];
  pilihan: {
    Status: string[];
    Objective: string[];
    Channel: string[];
    Metrik: string[];
    JenisKonten: string[];
    Halaman: Record<string, string>;
    Formulir: Record<string, string>;
  };
}

const rupiah = (nilai: number) =>
  new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(nilai);

export default function PemasaranKampanyeDetail({ kampanye, biaya, target, konten, pilihan }: Props) {
  const akar = `/admin-platform/pemasaran/kampanye/${kampanye.Id}`;
  const totalBiaya = biaya.reduce((jumlah, satu) => jumlah + satu.Jumlah, 0);

  return (
    <KerangkaPlatform>
      <Head title={`Kampanye ${kampanye.Kode}`} />

      <KepalaHalaman
        judul={kampanye.Nama}
        deskripsi={`Kode ${kampanye.Kode} dipakai sebagai utm_campaign pada tautan iklannya.`}
        tanpaBreadcrumb
        aksi={
          <Button variant="outline" asChild>
            <Link href="/admin-platform/pemasaran/kampanye">Kembali ke daftar</Link>
          </Button>
        }
        className="mb-6"
      />

      <RingkasanKampanye kampanye={kampanye} pilihan={pilihan} totalBiaya={totalBiaya} />

      <Tabs defaultValue="biaya" className="mt-6">
        <TabsList>
          <TabsTrigger value="biaya">Biaya</TabsTrigger>
          <TabsTrigger value="target">Target</TabsTrigger>
          <TabsTrigger value="konten">Konten</TabsTrigger>
        </TabsList>

        <TabsContent value="biaya" className="mt-4">
          <KonsolBiaya akar={akar} biaya={biaya} channel={kampanye.Channel} total={totalBiaya} />
        </TabsContent>

        <TabsContent value="target" className="mt-4">
          <KonsolTarget akar={akar} target={target} metrik={pilihan.Metrik} />
        </TabsContent>

        <TabsContent value="konten" className="mt-4">
          <KonsolKonten akar={akar} konten={konten} jenis={pilihan.JenisKonten} />
        </TabsContent>
      </Tabs>
    </KerangkaPlatform>
  );
}

function RingkasanKampanye({
  kampanye,
  pilihan,
  totalBiaya,
}: {
  kampanye: Kampanye;
  pilihan: Props['pilihan'];
  totalBiaya: number;
}) {
  const utm = [
    ['utm_campaign', kampanye.Kode],
    ['utm_source', kampanye.UtmSource],
    ['utm_medium', kampanye.UtmMedium],
    ['utm_term', kampanye.UtmTerm],
    ['utm_content', kampanye.UtmContent],
  ].filter(([, isi]) => isi);

  return (
    <div className="grid gap-4 lg:grid-cols-3">
      <Card className="lg:col-span-2">
        <CardHeader>
          <CardTitle className="text-base">Rencana</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-3 text-sm sm:grid-cols-2">
          <Butir label="Status" isi={<Badge variant="secondary">{kampanye.Status}</Badge>} />
          <Butir label="Objective" isi={kampanye.Objective} />
          <Butir
            label="Budget"
            isi={kampanye.Budget === null ? 'Belum ditetapkan' : rupiah(kampanye.Budget)}
          />
          <Butir label="Sudah dibelanjakan" isi={rupiah(totalBiaya)} />
          <Butir label="Mulai" isi={kampanye.MulaiPada ?? '—'} />
          <Butir label="Selesai" isi={kampanye.SelesaiPada ?? '—'} />
          <Butir label="Audience" isi={kampanye.Audience ?? '—'} />
          <Butir label="Offer" isi={kampanye.Offer ?? '—'} />
          <Butir label="Landing page" isi={pilihan.Halaman[kampanye.HalamanId ?? ''] ?? '—'} />
          <Butir label="Formulir" isi={pilihan.Formulir[kampanye.FormulirId ?? ''] ?? '—'} />
          <Butir
            label="Channel"
            isi={
              kampanye.Channel.length === 0 ? (
                '—'
              ) : (
                <span className="flex flex-wrap gap-1">
                  {kampanye.Channel.map((satu) => (
                    <Badge key={satu} variant="outline">
                      {satu}
                    </Badge>
                  ))}
                </span>
              )
            }
          />
          <Butir
            label="Status berikutnya yang sah"
            isi={kampanye.TujuanStatus.join(', ') || 'Tidak ada'}
          />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Tag UTM</CardTitle>
        </CardHeader>
        <CardContent className="space-y-2 text-sm">
          {utm.map(([kunci, isi]) => (
            <div key={kunci} className="flex justify-between gap-2">
              <span className="font-mono text-xs text-muted-foreground">{kunci}</span>
              <span className="font-mono text-foreground">{isi}</span>
            </div>
          ))}
          {kampanye.Catatan ? (
            <p className="border-t pt-2 text-muted-foreground">{kampanye.Catatan}</p>
          ) : null}
        </CardContent>
      </Card>
    </div>
  );
}

function Butir({ label, isi }: { label: string; isi: React.ReactNode }) {
  return (
    <div>
      <p className="text-xs text-muted-foreground">{label}</p>
      <div className="mt-0.5 text-foreground">{isi}</div>
    </div>
  );
}

/** Biaya dicatat per channel per hari, sehingga CAC terbaca pada rentang tanggal mana pun. */
function KonsolBiaya({
  akar,
  biaya,
  channel,
  total,
}: {
  akar: string;
  biaya: Biaya[];
  channel: string[];
  total: number;
}) {
  const form = useForm({
    Channel: channel[0] ?? '',
    Tanggal: '',
    Jumlah: '',
    Catatan: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(`${akar}/biaya`, form.data, {
      preserveScroll: true,
      onSuccess: () => form.setData('Jumlah', ''),
    });
  };

  if (channel.length === 0) {
    return (
      <p className="text-sm text-muted-foreground">
        Kampanye ini belum punya channel. Tambahkan channelnya lebih dulu agar biayanya punya tempat.
      </p>
    );
  }

  return (
    <div className="space-y-4">
      <form onSubmit={submit} className="flex flex-wrap items-end gap-3 rounded-lg border p-4">
        <div className="grid gap-1.5">
          <Label htmlFor="Channel">Channel</Label>
          <Select value={form.data.Channel} onValueChange={(v) => form.setData('Channel', v)}>
            <SelectTrigger id="Channel" className="w-44">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {channel.map((satu) => (
                <SelectItem key={satu} value={satu}>
                  {satu}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div className="grid gap-1.5">
          <Label htmlFor="Tanggal">Tanggal</Label>
          <Input
            id="Tanggal"
            type="date"
            className="w-40"
            value={form.data.Tanggal}
            onChange={(e) => form.setData('Tanggal', e.target.value)}
            required
          />
        </div>

        <div className="grid gap-1.5">
          <Label htmlFor="Jumlah">Jumlah</Label>
          <Input
            id="Jumlah"
            type="number"
            min="0"
            step="1"
            className="w-40"
            value={form.data.Jumlah}
            onChange={(e) => form.setData('Jumlah', e.target.value)}
            required
          />
        </div>

        <div className="grid gap-1.5">
          <Label htmlFor="Catatan">Catatan</Label>
          <Input
            id="Catatan"
            className="w-56"
            value={form.data.Catatan}
            onChange={(e) => form.setData('Catatan', e.target.value)}
          />
        </div>

        <Button type="submit" disabled={form.processing}>
          Catat biaya
        </Button>
      </form>

      {form.errors.Channel ? <p className="text-sm text-destructive">{form.errors.Channel}</p> : null}
      {form.errors.Jumlah ? <p className="text-sm text-destructive">{form.errors.Jumlah}</p> : null}

      <p className="text-sm text-muted-foreground">
        Satu channel pada satu tanggal hanya punya satu angka; mengirim ulang berarti mengoreksinya.
        Total belanja tercatat: <span className="font-mono text-foreground">{rupiah(total)}</span>.
      </p>

      {biaya.length === 0 ? (
        <p className="text-sm text-muted-foreground">Belum ada biaya tercatat.</p>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Tanggal</TableHead>
              <TableHead>Channel</TableHead>
              <TableHead className="text-right">Jumlah</TableHead>
              <TableHead>Catatan</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {biaya.map((satu) => (
              <TableRow key={satu.Id}>
                <TableCell className="font-mono text-xs">{satu.Tanggal}</TableCell>
                <TableCell>{satu.Channel}</TableCell>
                <TableCell className="text-right font-mono">{rupiah(satu.Jumlah)}</TableCell>
                <TableCell className="text-muted-foreground">{satu.Catatan ?? '—'}</TableCell>
                <TableCell className="text-right">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() =>
                      router.delete(`${akar}/biaya/${satu.Id}`, { preserveScroll: true })
                    }
                  >
                    Hapus
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
    </div>
  );
}

/** Target dikirim utuh: metrik yang dikosongkan berarti dicabut. */
function KonsolTarget({ akar, target, metrik }: { akar: string; target: Target[]; metrik: string[] }) {
  const [nilai, setNilai] = useState<Record<string, string>>(
    Object.fromEntries(target.map((satu) => [satu.Metrik, String(satu.Nilai)])),
  );

  const realisasi = Object.fromEntries(target.map((satu) => [satu.Metrik, satu.Realisasi]));

  const simpan = (e: FormEvent) => {
    e.preventDefault();

    const Target = Object.entries(nilai)
      .filter(([, isi]) => isi !== '')
      .map(([Metrik, isi]) => ({ Metrik, Nilai: Number(isi) }));

    router.put(`${akar}/target`, { Target }, { preserveScroll: true });
  };

  return (
    <form onSubmit={simpan} className="space-y-4">
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {metrik.map((satu) => {
          const dicapai = realisasi[satu] ?? 0;
          const sasaran = Number(nilai[satu] ?? 0);
          const persen = sasaran > 0 ? Math.min((dicapai / sasaran) * 100, 100) : 0;

          return (
            <div key={satu} className="rounded-lg border p-3">
              <Label htmlFor={`target-${satu}`}>{satu}</Label>
              <Input
                id={`target-${satu}`}
                type="number"
                min="0"
                className="mt-1.5"
                value={nilai[satu] ?? ''}
                onChange={(e) => setNilai((lama) => ({ ...lama, [satu]: e.target.value }))}
                placeholder="Tanpa target"
              />
              <div className="mt-2 h-2 rounded-sm bg-muted">
                <div
                  className="h-2 rounded-sm"
                  style={{ width: `${persen}%`, backgroundColor: HUE_UTAMA }}
                />
              </div>
              <p className="mt-1 text-xs text-muted-foreground">
                Realisasi {dicapai.toLocaleString('id-ID')}
                {sasaran > 0 ? ` dari ${sasaran.toLocaleString('id-ID')}` : ' (belum ada target)'}
              </p>
            </div>
          );
        })}
      </div>

      <p className="text-sm text-muted-foreground">
        Realisasi dibaca dari metrik harian yang dihitung pekerjaan
        <span className="font-mono"> pemasaran:hitung-metrik</span>, bukan dari tabel mentah.
      </p>

      <Button type="submit">Simpan target</Button>
    </form>
  );
}

function KonsolKonten({ akar, konten, jenis }: { akar: string; konten: Konten[]; jenis: string[] }) {
  const form = useForm({
    Jenis: jenis[0] ?? '',
    Judul: '',
    Tautan: '',
    Catatan: '',
    Urutan: '0',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(`${akar}/konten`, form.data, {
      preserveScroll: true,
      onSuccess: () => form.reset('Judul', 'Tautan', 'Catatan'),
    });
  };

  return (
    <div className="space-y-4">
      <form onSubmit={submit} className="flex flex-wrap items-end gap-3 rounded-lg border p-4">
        <div className="grid gap-1.5">
          <Label htmlFor="Jenis">Jenis</Label>
          <Select value={form.data.Jenis} onValueChange={(v) => form.setData('Jenis', v)}>
            <SelectTrigger id="Jenis" className="w-40">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {jenis.map((satu) => (
                <SelectItem key={satu} value={satu}>
                  {satu}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <div className="grid gap-1.5">
          <Label htmlFor="Judul">Judul</Label>
          <Input
            id="Judul"
            className="w-64"
            value={form.data.Judul}
            onChange={(e) => form.setData('Judul', e.target.value)}
            required
          />
        </div>

        <div className="grid gap-1.5">
          <Label htmlFor="Tautan">Tautan</Label>
          <Input
            id="Tautan"
            type="url"
            className="w-64"
            value={form.data.Tautan}
            onChange={(e) => form.setData('Tautan', e.target.value)}
          />
        </div>

        <Button type="submit" disabled={form.processing}>
          Tambah konten
        </Button>
      </form>

      {form.errors.Tautan ? <p className="text-sm text-destructive">{form.errors.Tautan}</p> : null}

      {konten.length === 0 ? (
        <p className="text-sm text-muted-foreground">Belum ada konten yang ditautkan.</p>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Jenis</TableHead>
              <TableHead>Judul</TableHead>
              <TableHead>Tautan</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {konten.map((satu) => (
              <TableRow key={satu.Id}>
                <TableCell>
                  <Badge variant="outline">{satu.Jenis}</Badge>
                </TableCell>
                <TableCell className="text-foreground">{satu.Judul}</TableCell>
                <TableCell className="break-all font-mono text-xs text-muted-foreground">
                  {satu.Tautan ?? '—'}
                </TableCell>
                <TableCell className="text-right">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() =>
                      router.delete(`${akar}/konten/${satu.Id}`, { preserveScroll: true })
                    }
                  >
                    Hapus
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
    </div>
  );
}
