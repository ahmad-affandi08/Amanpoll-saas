import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { HUE_UTAMA } from '@/components/grafik/palet';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { rutePemasaran } from '@/features/Pemasaran/api';

interface Template {
  Id: string;
  Kode: string;
  Nama: string;
  Bahasa: string;
  Kategori: string;
  IsiTeks: string;
  StatusPersetujuan: string;
  AlasanPenolakan: string | null;
  IdTemplatePenyedia: string | null;
  DiperiksaPada: string | null;
  Aktif: boolean;
  SiapKirim: boolean;
  TujuanStatus: string[];
}

interface Menu {
  Id: string;
  Kunci: string;
  Urutan: number;
  Label: string;
  Balasan: string;
  Aktif: boolean;
}

interface Props {
  template: Template[];
  menu: Menu[];
  pratinjauMenu: string;
  kataBerhenti: string[];
  ringkasanKiriman: Record<string, number>;
  variabel: string[];
  pilihan: { Status: string[] };
}

const AKAR = rutePemasaran.whatsapp;

export default function PemasaranWhatsApp({
  template,
  menu,
  pratinjauMenu,
  kataBerhenti,
  ringkasanKiriman,
  variabel,
  pilihan,
}: Props) {
  return (
    <KerangkaPlatform>
      <Head title="WhatsApp" />

      <KepalaHalaman
        judul="WhatsApp"
        deskripsi="Template menunggu persetujuan penyedia sebelum boleh berangkat, dan setiap nomor dapat berhenti kapan saja."
        tanpaBreadcrumb
        aksi={<DialogFormTemplate template={null} variabel={variabel} />}
        className="mb-6"
      />

      <RingkasanKiriman ringkasan={ringkasanKiriman} kataBerhenti={kataBerhenti} />

      <Tabs defaultValue="template" className="mt-6">
        <TabsList>
          <TabsTrigger value="template">Template</TabsTrigger>
          <TabsTrigger value="menu">Menu Percakapan</TabsTrigger>
        </TabsList>

        <TabsContent value="template" className="mt-4 space-y-4">
          {template.length === 0 ? (
            <p className="text-sm text-muted-foreground">Belum ada template WhatsApp.</p>
          ) : (
            template.map((satu) => (
              <KartuTemplate key={satu.Id} template={satu} variabel={variabel} pilihan={pilihan} />
            ))
          )}
        </TabsContent>

        <TabsContent value="menu" className="mt-4">
          <KonsolMenu menu={menu} pratinjau={pratinjauMenu} />
        </TabsContent>
      </Tabs>
    </KerangkaPlatform>
  );
}

function RingkasanKiriman({
  ringkasan,
  kataBerhenti,
}: {
  ringkasan: Record<string, number>;
  kataBerhenti: string[];
}) {
  const baris = Object.entries(ringkasan);
  const puncak = Math.max(...baris.map(([, nilai]) => nilai), 1);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Kiriman WhatsApp</CardTitle>
      </CardHeader>
      <CardContent className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-3">
          {baris.map(([status, jumlah]) => (
            <div key={status}>
              <div className="flex items-baseline justify-between gap-2 text-sm">
                <span className="text-foreground">{status}</span>
                <span className="font-mono text-foreground">{jumlah.toLocaleString('id-ID')}</span>
              </div>
              <div className="mt-1 h-2 rounded-sm bg-muted" role="img" aria-label={`${status}: ${jumlah}`}>
                <div
                  className="h-2 rounded-sm"
                  style={{
                    width: `${Math.max((jumlah / puncak) * 100, jumlah > 0 ? 2 : 0)}%`,
                    backgroundColor: HUE_UTAMA,
                  }}
                />
              </div>
            </div>
          ))}
        </div>

        <div className="text-sm text-muted-foreground">
          <p>
            Nomor berhenti dengan membalas{' '}
            {kataBerhenti.map((satu) => (
              <Badge key={satu} variant="outline" className="mx-0.5 font-mono">
                {satu}
              </Badge>
            ))}
            . Setelah itu nomornya masuk daftar supresi dan tidak pernah dikirimi lagi, walau
            otomasi menjadwalkannya.
          </p>
          <p className="mt-2">
            Opt-in dibaca dari buku consent yang sama dengan email; berhenti dari WhatsApp tidak
            mencabut consent emailnya.
          </p>
        </div>
      </CardContent>
    </Card>
  );
}

function KartuTemplate({
  template,
  variabel,
  pilihan,
}: {
  template: Template;
  variabel: string[];
  pilihan: { Status: string[] };
}) {
  return (
    <Card>
      <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <CardTitle className="text-base">{template.Nama}</CardTitle>
          <p className="font-mono text-xs text-muted-foreground">
            {template.Kode} · {template.Bahasa} · {template.Kategori}
          </p>
        </div>
        <div className="flex shrink-0 flex-wrap items-center gap-2">
          <Badge variant={template.SiapKirim ? 'default' : 'secondary'}>
            {template.StatusPersetujuan}
          </Badge>
          <DialogFormTemplate template={template} variabel={variabel} />
          <Button
            variant="outline"
            size="sm"
            onClick={() => router.post(`${AKAR}/template/${template.Id}/ajukan`, {}, { preserveScroll: true })}
          >
            Ajukan
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => router.post(`${AKAR}/template/${template.Id}/periksa`, {}, { preserveScroll: true })}
          >
            Periksa
          </Button>
          <DialogKeputusan template={template} pilihan={pilihan} />
        </div>
      </CardHeader>

      <CardContent className="space-y-2 text-sm">
        <pre className="whitespace-pre-wrap rounded-md bg-muted p-3 font-sans text-foreground">
          {template.IsiTeks}
        </pre>
        {template.AlasanPenolakan ? (
          <p className="text-destructive">{template.AlasanPenolakan}</p>
        ) : null}
        <p className="text-xs text-muted-foreground">
          {template.SiapKirim
            ? 'Siap dikirim.'
            : 'Belum dapat dikirim sampai penyedia menyetujuinya dan templatenya aktif.'}
          {template.IdTemplatePenyedia ? ` Id penyedia: ${template.IdTemplatePenyedia}.` : ''}
        </p>
      </CardContent>
    </Card>
  );
}

function DialogFormTemplate({ template, variabel }: { template: Template | null; variabel: string[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: template?.Kode ?? '',
    Nama: template?.Nama ?? '',
    Bahasa: template?.Bahasa ?? 'id',
    Kategori: template?.Kategori ?? 'Marketing',
    IsiTeks: template?.IsiTeks ?? '',
    Aktif: template?.Aktif ?? true,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!template) form.reset();
      },
    };

    if (template) {
      router.put(`${AKAR}/template/${template.Id}`, form.data, opsi);
    } else {
      router.post(`${AKAR}/template`, form.data, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={template ? 'outline' : 'default'} size={template ? 'sm' : 'default'}>
          {template ? 'Ubah' : 'Tambah Template'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{template ? 'Ubah Template' : 'Tambah Template'}</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="Kode">Kode</Label>
              <Input
                id="Kode"
                value={form.data.Kode}
                onChange={(e) => form.setData('Kode', e.target.value)}
                required
              />
              {form.errors.Kode ? <p className="text-sm text-destructive">{form.errors.Kode}</p> : null}
            </div>
            <div className="grid gap-2">
              <Label htmlFor="Nama">Nama</Label>
              <Input
                id="Nama"
                value={form.data.Nama}
                onChange={(e) => form.setData('Nama', e.target.value)}
                required
              />
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="Bahasa">Bahasa</Label>
              <Input
                id="Bahasa"
                value={form.data.Bahasa}
                onChange={(e) => form.setData('Bahasa', e.target.value)}
                required
              />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="Kategori">Kategori</Label>
              <Input
                id="Kategori"
                value={form.data.Kategori}
                onChange={(e) => form.setData('Kategori', e.target.value)}
                required
              />
            </div>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="IsiTeks">Naskah</Label>
            <Textarea
              id="IsiTeks"
              rows={6}
              value={form.data.IsiTeks}
              onChange={(e) => form.setData('IsiTeks', e.target.value)}
              required
            />
            <p className="text-sm text-muted-foreground">
              Variabel yang dikenal: {variabel.map((satu) => `{{${satu}}}`).join(', ')}.
            </p>
            {form.errors.IsiTeks ? (
              <p className="text-sm text-destructive">{form.errors.IsiTeks}</p>
            ) : null}
            <p className="text-sm text-muted-foreground">
              Mengubah naskah template yang sudah disetujui mengembalikannya ke draf: yang disetujui
              penyedia adalah naskah lamanya.
            </p>
          </div>

          <label className="flex items-center gap-2 text-sm">
            <Checkbox
              checked={form.data.Aktif}
              onCheckedChange={(nilai) => form.setData('Aktif', nilai === true)}
            />
            Template aktif
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

/** Jalur manual untuk penyedia tanpa API: keputusannya tetap milik penyedia, operator hanya menyalin. */
function DialogKeputusan({ template, pilihan }: { template: Template; pilihan: { Status: string[] } }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Status: template.TujuanStatus[0] ?? template.StatusPersetujuan,
    IdTemplatePenyedia: template.IdTemplatePenyedia ?? '',
    Alasan: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(`${AKAR}/template/${template.Id}/keputusan`, form.data, {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  if (template.TujuanStatus.length === 0) {
    return null;
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="ghost" size="sm">
          Catat keputusan
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Catat Keputusan Penyedia</DialogTitle>
        </DialogHeader>

        <form onSubmit={submit} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="Status">Status</Label>
            <Select value={form.data.Status} onValueChange={(v) => form.setData('Status', v)}>
              <SelectTrigger id="Status">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Status.filter((satu) => template.TujuanStatus.includes(satu)).map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <p className="text-sm text-muted-foreground">
              Hanya status yang sah dari {template.StatusPersetujuan} yang tersedia di sini.
            </p>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="IdTemplatePenyedia">Id template penyedia</Label>
            <Input
              id="IdTemplatePenyedia"
              value={form.data.IdTemplatePenyedia}
              onChange={(e) => form.setData('IdTemplatePenyedia', e.target.value)}
            />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Alasan">Alasan</Label>
            <Textarea
              id="Alasan"
              rows={3}
              value={form.data.Alasan}
              onChange={(e) => form.setData('Alasan', e.target.value)}
            />
          </div>

          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Catat
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function KonsolMenu({ menu, pratinjau }: { menu: Menu[]; pratinjau: string }) {
  const [baris, setBaris] = useState<Menu[]>(menu);

  const ubah = (indeks: number, kunci: keyof Menu, nilai: string | boolean) =>
    setBaris((lama) => lama.map((satu, ke) => (ke === indeks ? { ...satu, [kunci]: nilai } : satu)));

  const tambah = () =>
    setBaris((lama) => [
      ...lama,
      {
        Id: `baru-${lama.length}`,
        Kunci: String(lama.length + 1),
        Urutan: lama.length,
        Label: '',
        Balasan: '',
        Aktif: true,
      },
    ]);

  const simpan = (e: FormEvent) => {
    e.preventDefault();
    router.put(
      `${AKAR}/menu`,
      {
        Menu: baris.map((satu) => ({
          Kunci: satu.Kunci,
          Label: satu.Label,
          Balasan: satu.Balasan,
          Aktif: satu.Aktif,
        })),
      },
      { preserveScroll: true },
    );
  };

  return (
    <div className="grid gap-6 lg:grid-cols-3">
      <form onSubmit={simpan} className="space-y-4 lg:col-span-2">
        {baris.map((satu, indeks) => (
          <Card key={satu.Id}>
            <CardContent className="grid gap-3 p-4 sm:grid-cols-[6rem_1fr]">
              <div className="grid gap-2">
                <Label htmlFor={`Kunci-${indeks}`}>Balasan</Label>
                <Input
                  id={`Kunci-${indeks}`}
                  value={satu.Kunci}
                  onChange={(e) => ubah(indeks, 'Kunci', e.target.value)}
                  required
                />
              </div>
              <div className="grid gap-2">
                <Label htmlFor={`Label-${indeks}`}>Label menu</Label>
                <Input
                  id={`Label-${indeks}`}
                  value={satu.Label}
                  onChange={(e) => ubah(indeks, 'Label', e.target.value)}
                  required
                />
              </div>
              <div className="sm:col-span-2 grid gap-2">
                <Label htmlFor={`Balasan-${indeks}`}>Jawaban yang dikirim</Label>
                <Textarea
                  id={`Balasan-${indeks}`}
                  rows={3}
                  value={satu.Balasan}
                  onChange={(e) => ubah(indeks, 'Balasan', e.target.value)}
                  required
                />
              </div>
              <div className="sm:col-span-2 flex items-center justify-between">
                <label className="flex items-center gap-2 text-sm">
                  <Checkbox
                    checked={satu.Aktif}
                    onCheckedChange={(nilai) => ubah(indeks, 'Aktif', nilai === true)}
                  />
                  Aktif
                </label>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={() => setBaris((lama) => lama.filter((_, ke) => ke !== indeks))}
                >
                  Hapus
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}

        <div className="flex gap-2">
          <Button type="button" variant="outline" onClick={tambah}>
            Tambah butir
          </Button>
          <Button type="submit">Simpan menu</Button>
        </div>
      </form>

      <Card className="h-fit">
        <CardHeader>
          <CardTitle className="text-base">Pratinjau</CardTitle>
        </CardHeader>
        <CardContent>
          <pre className="whitespace-pre-wrap rounded-md bg-muted p-3 font-sans text-sm text-foreground">
            {pratinjau}
          </pre>
          <p className="mt-2 text-xs text-muted-foreground">
            Sapaan dan balasan untuk pilihan tak dikenal diatur di halaman Pengaturan pemasaran.
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
