import { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
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
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import type { FieldFormulir, Formulir, PilihanFormulir } from '@/features/Pemasaran/types';
import { rutePemasaran } from '@/features/Pemasaran/api';

interface Props {
  formulir: Formulir[];
  pilihan: PilihanFormulir;
}

const AKAR = rutePemasaran.formulir;

const FIELD_BARU: FieldFormulir = {
  Kode: '',
  Label: '',
  Jenis: 'Teks',
  Wajib: false,
  Pilihan: [],
  Placeholder: null,
  Bantuan: null,
};

export default function PemasaranFormulirIndex({ formulir, pilihan }: Props) {
  return (
    <KerangkaPlatform>
      <Head title="Formulir Pemasaran" />

      <KepalaHalaman
        judul="Formulir Pemasaran"
        deskripsi="Formulir yang dipasang di halaman publik. Setiap pengiriman menjadi prospek beserta UTM-nya."
        tanpaBreadcrumb
        aksi={<DialogFormulir formulir={null} pilihan={pilihan} />}
        className="mb-6"
      />

      {formulir.length === 0 ? (
        <KeadaanKosong
          judul="Belum ada formulir"
          deskripsi="Buat formulir lebih dulu, lalu pasang sebagai blok pada halaman pemasaran."
        />
      ) : (
        <div className="grid gap-4 lg:grid-cols-2">
          {formulir.map((satu) => (
            <Card key={satu.Id}>
              <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0">
                <div className="grid gap-1">
                  <CardTitle className="text-base">{satu.Nama}</CardTitle>
                  <span className="font-mono text-xs text-muted-foreground">{satu.Kode}</span>
                </div>
                <div className="flex gap-1">
                  {satu.Aktif ? <Badge>Aktif</Badge> : <Badge variant="outline">Nonaktif</Badge>}
                </div>
              </CardHeader>
              <CardContent className="grid gap-4">
                <dl className="grid grid-cols-2 gap-2 text-sm">
                  <div>
                    <dt className="text-muted-foreground">Sumber</dt>
                    <dd>{satu.Sumber}</dd>
                  </div>
                  <div>
                    <dt className="text-muted-foreground">Pengiriman</dt>
                    <dd className="font-mono">{satu.JumlahPengiriman}</dd>
                  </div>
                  <div>
                    <dt className="text-muted-foreground">Field</dt>
                    <dd className="font-mono">{satu.Field.length}</dd>
                  </div>
                  <div>
                    <dt className="text-muted-foreground">Persetujuan</dt>
                    <dd>{satu.WajibPersetujuan ? 'Wajib' : 'Opsional'}</dd>
                  </div>
                </dl>

                <div className="flex flex-wrap gap-2">
                  <Button variant="outline" size="sm" asChild>
                    <Link href={`${AKAR}/${satu.Kode}`}>Pengiriman</Link>
                  </Button>
                  <DialogFormulir formulir={satu} pilihan={pilihan} />
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </KerangkaPlatform>
  );
}

function DialogFormulir({ formulir, pilihan }: { formulir: Formulir | null; pilihan: PilihanFormulir }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: formulir?.Kode ?? '',
    Nama: formulir?.Nama ?? '',
    PesanSukses: formulir?.PesanSukses ?? '',
    UrlRedirect: formulir?.UrlRedirect ?? '',
    Sumber: formulir?.Sumber ?? pilihan.Sumber[0],
    KampanyeId: formulir?.KampanyeId ?? '',
    Tag: (formulir?.Tag ?? []).join(', '),
    PemicuOtomasi: formulir?.PemicuOtomasi ?? '',
    UrlWebhook: formulir?.UrlWebhook ?? '',
    WajibPersetujuan: formulir?.WajibPersetujuan ?? true,
    CaptchaAktif: formulir?.CaptchaAktif ?? false,
    Aktif: formulir?.Aktif ?? true,
  });

  /* Daftar field dipegang terpisah dari form, sama seperti daftar blok di editor halaman. */
  const [field, setField] = useState<FieldFormulir[]>(
    () => formulir?.Field ?? [{ ...FIELD_BARU, Kode: 'Nama', Label: 'Nama', Wajib: true }],
  );

  const kirim = (e: FormEvent) => {
    e.preventDefault();

    form.transform((data) => ({
      ...data,
      Tag: data.Tag.split(',')
        .map((satu) => satu.trim())
        .filter((satu) => satu !== ''),
      Field: field,
    }));

    const opsi = {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        if (!formulir) form.reset();
      },
    };

    if (formulir) {
      form.put(`${AKAR}/${formulir.Kode}`, opsi);
    } else {
      form.post(AKAR, opsi);
    }
  };

  const ubahField = (urutan: number, ubahan: Partial<FieldFormulir>) => {
    setField((sebelum) => sebelum.map((satu, ke) => (ke === urutan ? { ...satu, ...ubahan } : satu)));
  };

  const galatField = (form.errors as Record<string, string | undefined>).Field;

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={formulir ? 'outline' : 'default'} size={formulir ? 'sm' : 'default'}>
          {formulir ? 'Ubah' : 'Tambah Formulir'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>{formulir ? 'Ubah Formulir' : 'Tambah Formulir'}</DialogTitle>
        </DialogHeader>

        <form onSubmit={kirim} className="grid gap-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
              <Label htmlFor="Kode">Kode</Label>
              <Input
                id="Kode"
                value={form.data.Kode}
                onChange={(e) => form.setData('Kode', e.target.value)}
                placeholder="demo-manufaktur"
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

            <div className="grid gap-2">
              <Label htmlFor="Sumber">Sumber prospek</Label>
              <Select value={form.data.Sumber} onValueChange={(v) => form.setData('Sumber', v)}>
                <SelectTrigger id="Sumber">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {pilihan.Sumber.map((satu) => (
                    <SelectItem key={satu} value={satu}>
                      {satu}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="grid gap-2">
              <Label htmlFor="Tag">Tag (dipisah koma)</Label>
              <Input
                id="Tag"
                value={form.data.Tag}
                onChange={(e) => form.setData('Tag', e.target.value)}
              />
            </div>

            <div className="grid gap-2 sm:col-span-2">
              <Label htmlFor="PesanSukses">Pesan sukses</Label>
              <Textarea
                id="PesanSukses"
                rows={2}
                value={form.data.PesanSukses}
                onChange={(e) => form.setData('PesanSukses', e.target.value)}
              />
            </div>

            <div className="grid gap-2">
              <Label htmlFor="UrlRedirect">Redirect setelah kirim</Label>
              <Input
                id="UrlRedirect"
                value={form.data.UrlRedirect}
                onChange={(e) => form.setData('UrlRedirect', e.target.value)}
              />
              {form.errors.UrlRedirect ? (
                <p className="text-sm text-destructive">{form.errors.UrlRedirect}</p>
              ) : null}
            </div>

            <div className="grid gap-2">
              <Label htmlFor="UrlWebhook">Webhook</Label>
              <Input
                id="UrlWebhook"
                value={form.data.UrlWebhook}
                onChange={(e) => form.setData('UrlWebhook', e.target.value)}
              />
            </div>

            <div className="grid gap-2 sm:col-span-2">
              <Label htmlFor="PemicuOtomasi">Pemicu otomasi</Label>
              <Input
                id="PemicuOtomasi"
                value={form.data.PemicuOtomasi}
                onChange={(e) => form.setData('PemicuOtomasi', e.target.value)}
              />
              <p className="text-sm text-muted-foreground">
                Disimpan sebagai kode. Mesin otomasinya menyusul pada fase berikutnya.
              </p>
            </div>
          </div>

          <div className="flex flex-wrap gap-6">
            <label className="flex items-center gap-2 text-sm">
              <Checkbox
                checked={form.data.WajibPersetujuan}
                onCheckedChange={(nilai) => form.setData('WajibPersetujuan', nilai === true)}
              />
              Wajib persetujuan
            </label>
            <label className="flex items-center gap-2 text-sm">
              <Checkbox
                checked={form.data.CaptchaAktif}
                onCheckedChange={(nilai) => form.setData('CaptchaAktif', nilai === true)}
              />
              CAPTCHA
            </label>
            <label className="flex items-center gap-2 text-sm">
              <Checkbox
                checked={form.data.Aktif}
                onCheckedChange={(nilai) => form.setData('Aktif', nilai === true)}
              />
              Aktif
            </label>
          </div>

          <Separator />

          <div className="flex items-center justify-between">
            <h3 className="text-sm font-medium">Field</h3>
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => setField((sebelum) => [...sebelum, { ...FIELD_BARU }])}
            >
              Tambah Field
            </Button>
          </div>

          {field.map((satu, urutan) => (
            <div key={urutan} className="grid gap-3 rounded-lg border p-3">
              <div className="grid gap-3 sm:grid-cols-3">
                <div className="grid gap-2">
                  <Label>Kode</Label>
                  <Input value={satu.Kode} onChange={(e) => ubahField(urutan, { Kode: e.target.value })} />
                </div>
                <div className="grid gap-2">
                  <Label>Label</Label>
                  <Input
                    value={satu.Label}
                    onChange={(e) => ubahField(urutan, { Label: e.target.value })}
                  />
                </div>
                <div className="grid gap-2">
                  <Label>Jenis</Label>
                  <Select value={satu.Jenis} onValueChange={(v) => ubahField(urutan, { Jenis: v })}>
                    <SelectTrigger>
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

              <div className="grid gap-3 sm:grid-cols-2">
                <div className="grid gap-2">
                  <Label>Pilihan (dipisah koma)</Label>
                  <Input
                    value={satu.Pilihan.join(', ')}
                    onChange={(e) =>
                      ubahField(urutan, {
                        Pilihan: e.target.value
                          .split(',')
                          .map((bagian) => bagian.trim())
                          .filter((bagian) => bagian !== ''),
                      })
                    }
                  />
                </div>
                <div className="flex items-end justify-between gap-4">
                  <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                      checked={satu.Wajib}
                      onCheckedChange={(nilai) => ubahField(urutan, { Wajib: nilai === true })}
                    />
                    Wajib
                  </label>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => setField((sebelum) => sebelum.filter((_, ke) => ke !== urutan))}
                  >
                    Hapus
                  </Button>
                </div>
              </div>
            </div>
          ))}

          {galatField ? <p className="text-sm text-destructive">{galatField}</p> : null}

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
