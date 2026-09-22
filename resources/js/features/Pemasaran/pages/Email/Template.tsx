import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
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
import { Textarea } from '@/components/ui/textarea';
import type { TemplateEmail } from '@/features/Pemasaran/types';
import { rutePemasaran } from '@/features/Pemasaran/api';
import { BidangKode } from '@/components/shared/BidangKode';

interface Props {
  template: TemplateEmail[];
  pilihan: { Jenis: string[]; Variabel: string[] };
}

const AKAR = rutePemasaran.emailTemplate;

export default function PemasaranEmailTemplate({ template, pilihan }: Props) {
  const konfirmasi = useKonfirmasi();

  const hapus = async (satu: TemplateEmail) => {
    const setuju = await konfirmasi({
      judul: 'Hapus template?',
      deskripsi: `Template ${satu.Kode} akan dibuang. Yang masih dipakai langkah sequence tidak dapat dihapus.`,
      ragam: 'bahaya',
    });

    if (setuju) {
      router.delete(`${AKAR}/${satu.Kode}`, { preserveScroll: true });
    }
  };

  return (
    <KerangkaPlatform>
      <Head title="Template Email" />

      <KepalaHalaman
        judul="Template Email"
        deskripsi="Naskah email pemasaran beserta variabelnya. Variabel yang salah ketik ditolak saat disimpan."
        tanpaBreadcrumb
        aksi={<DialogTemplate template={null} pilihan={pilihan} />}
        className="mb-6"
      />

      <div className="mb-6 rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
        Variabel yang tersedia:{' '}
        <span className="font-mono">{pilihan.Variabel.map((v) => `{{${v}}}`).join(', ')}</span>
      </div>

      {template.length === 0 ? (
        <p className="text-sm text-muted-foreground">Belum ada template email.</p>
      ) : (
        <div className="grid gap-4 md:grid-cols-2">
          {template.map((satu) => (
            <Card key={satu.Id}>
              <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                <div>
                  <CardTitle className="text-base">{satu.Nama}</CardTitle>
                  <p className="font-mono text-xs text-muted-foreground">{satu.Kode}</p>
                </div>
                <div className="flex shrink-0 gap-1">
                  <Badge variant="outline">{satu.Jenis}</Badge>
                  {satu.Aktif ? null : <Badge variant="secondary">Nonaktif</Badge>}
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <p className="text-sm">
                  <span className="text-muted-foreground">Subjek: </span>
                  {satu.Subjek}
                </p>
                <div className="flex justify-end gap-2">
                  <DialogTemplate template={satu} pilihan={pilihan} />
                  <Button variant="ghost" size="sm" onClick={() => hapus(satu)}>
                    Hapus
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </KerangkaPlatform>
  );
}

function DialogTemplate({
  template,
  pilihan,
}: {
  template: TemplateEmail | null;
  pilihan: { Jenis: string[]; Variabel: string[] };
}) {
  const [buka, setBuka] = useState(false);

  const form = useForm({
    Kode: template?.Kode ?? '',
    Nama: template?.Nama ?? '',
    Jenis: template?.Jenis ?? pilihan.Jenis[0] ?? '',
    Subjek: template?.Subjek ?? '',
    IsiHtml: template?.IsiHtml ?? '',
    IsiTeks: template?.IsiTeks ?? '',
    Aktif: template?.Aktif ?? true,
  });

  const kirim = (e: FormEvent) => {
    e.preventDefault();

    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!template) form.reset();
      },
    };

    if (template) {
      form.put(`${AKAR}/${template.Kode}`, opsi);
    } else {
      form.post(AKAR, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={template ? 'outline' : 'default'} size={template ? 'sm' : 'default'}>
          {template ? 'Ubah' : 'Tambah Template'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>{template ? 'Ubah Template' : 'Tambah Template'}</DialogTitle>
        </DialogHeader>

        <form onSubmit={kirim} className="grid gap-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <BidangKode
              nilai={form.data.Kode}
              onUbah={(nilai) => form.setData('Kode', nilai)}
              galat={form.errors.Kode}
              contoh="trial-hari-1"
            />

            <div className="grid gap-2">
              <Label htmlFor="Jenis">Jenis</Label>
              <Select value={form.data.Jenis} onValueChange={(v) => form.setData('Jenis', v)}>
                <SelectTrigger id="Jenis">
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
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Nama">Nama</Label>
            <Input
              id="Nama"
              value={form.data.Nama}
              onChange={(e) => form.setData('Nama', e.target.value)}
              required
            />
            {form.errors.Nama ? <p className="text-sm text-destructive">{form.errors.Nama}</p> : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="Subjek">Subjek</Label>
            <Input
              id="Subjek"
              value={form.data.Subjek}
              onChange={(e) => form.setData('Subjek', e.target.value)}
              required
            />
            {form.errors.Subjek ? <p className="text-sm text-destructive">{form.errors.Subjek}</p> : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="IsiHtml">Isi HTML</Label>
            <Textarea
              id="IsiHtml"
              rows={10}
              className="font-mono text-xs"
              value={form.data.IsiHtml}
              onChange={(e) => form.setData('IsiHtml', e.target.value)}
              required
            />
            {form.errors.IsiHtml ? <p className="text-sm text-destructive">{form.errors.IsiHtml}</p> : null}
          </div>

          <div className="grid gap-2">
            <Label htmlFor="IsiTeks">Isi Teks</Label>
            <Textarea
              id="IsiTeks"
              rows={5}
              className="font-mono text-xs"
              value={form.data.IsiTeks}
              onChange={(e) => form.setData('IsiTeks', e.target.value)}
            />
            <p className="text-sm text-muted-foreground">
              Versi tanpa format untuk pembaca yang menolak HTML. Kosongkan bila tidak perlu.
            </p>
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
