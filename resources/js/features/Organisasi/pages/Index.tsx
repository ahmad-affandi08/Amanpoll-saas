import { ChangeEvent, FormEvent, useRef } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { Organisasi } from '@/features/Organisasi/types';
import { ruteOrganisasi } from '@/features/Organisasi/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  organisasi: Organisasi;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const ZONA_WAKTU = ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'];

function FormLogo({ organisasi }: { organisasi: Organisasi }) {
  const inputRef = useRef<HTMLInputElement>(null);

  const pilihBerkas = (e: ChangeEvent<HTMLInputElement>) => {
    const berkas = e.target.files?.[0];
    if (!berkas) return;
    router.post(
      ruteOrganisasi.logo,
      { Logo: berkas },
      {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
          if (inputRef.current) inputRef.current.value = '';
        },
      },
    );
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Logo</CardTitle>
      </CardHeader>
      <CardContent className="flex items-center gap-4">
        {organisasi.LogoUrl ? (
          <img
            src={organisasi.LogoUrl}
            alt="Logo organisasi"
            className="h-16 w-16 rounded-md border border-border object-contain"
          />
        ) : (
          <div className="flex h-16 w-16 items-center justify-center rounded-md border border-dashed border-border text-xs text-muted-foreground">
            Tidak ada
          </div>
        )}
        <div>
          <Button variant="outline" size="sm" onClick={() => inputRef.current?.click()}>
            Ganti Logo
          </Button>
          <input ref={inputRef} type="file" accept="image/*" className="hidden" onChange={pilihBerkas} />
        </div>
      </CardContent>
    </Card>
  );
}

export default function OrganisasiIndex({ organisasi, wajib }: Props) {
  const form = useForm({
    Nama: organisasi.Nama,
    NamaLegal: organisasi.NamaLegal ?? '',
    JenisUsaha: organisasi.JenisUsaha ?? '',
    NomorIdentitasPajak: organisasi.NomorIdentitasPajak ?? '',
    Email: organisasi.Email ?? '',
    Telepon: organisasi.Telepon ?? '',
    Alamat: organisasi.Alamat ?? '',
    Negara: organisasi.Negara ?? '',
    Provinsi: organisasi.Provinsi ?? '',
    Kota: organisasi.Kota ?? '',
    ZonaWaktu: organisasi.ZonaWaktu,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(ruteOrganisasi.index);
  };

  return (
    <KerangkaAplikasi>
      <Head title="Organisasi" />
      <KepalaHalaman
        judul="Organisasi"
        deskripsi={
          <>
            Kode: <span className="font-mono">{organisasi.Kode}</span>{' '}
            <Badge variant="outline" className="ml-2">
              {organisasi.Status}
            </Badge>
          </>
        }
      />

      <div className="grid gap-4 md:grid-cols-3">
        <div className="md:col-span-1">
          <FormLogo organisasi={organisasi} />
        </div>
        <div className="md:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle>Profil Organisasi</CardTitle>
            </CardHeader>
            <CardContent>
              <AturanWajibProvider aturan={wajib.organisasi}>
                <form onSubmit={submit} className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label nama="Nama">Nama</Label>
                      <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
                      {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
                    </div>
                    <div className="space-y-2">
                      <Label nama="NamaLegal">Nama Legal</Label>
                      <Input
                        value={form.data.NamaLegal}
                        onChange={(e) => form.setData('NamaLegal', e.target.value)}
                      />
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label nama="JenisUsaha">Jenis Usaha</Label>
                      <Input
                        value={form.data.JenisUsaha}
                        onChange={(e) => form.setData('JenisUsaha', e.target.value)}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label nama="NomorIdentitasPajak">NPWP</Label>
                      <Input
                        value={form.data.NomorIdentitasPajak}
                        onChange={(e) => form.setData('NomorIdentitasPajak', e.target.value)}
                      />
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label nama="Email">Email</Label>
                      <Input
                        type="email"
                        value={form.data.Email}
                        onChange={(e) => form.setData('Email', e.target.value)}
                      />
                      {form.errors.Email && <p className="text-sm text-destructive">{form.errors.Email}</p>}
                    </div>
                    <div className="space-y-2">
                      <Label nama="Telepon">Telepon</Label>
                      <Input
                        value={form.data.Telepon}
                        onChange={(e) => form.setData('Telepon', e.target.value)}
                      />
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label nama="Alamat">Alamat</Label>
                    <Textarea
                      value={form.data.Alamat}
                      onChange={(e) => form.setData('Alamat', e.target.value)}
                    />
                  </div>
                  <div className="grid gap-4 sm:grid-cols-3">
                    <div className="space-y-2">
                      <Label nama="Negara">Negara</Label>
                      <Input
                        value={form.data.Negara}
                        onChange={(e) => form.setData('Negara', e.target.value)}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label nama="Provinsi">Provinsi</Label>
                      <Input
                        value={form.data.Provinsi}
                        onChange={(e) => form.setData('Provinsi', e.target.value)}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label nama="Kota">Kota</Label>
                      <Input value={form.data.Kota} onChange={(e) => form.setData('Kota', e.target.value)} />
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label nama="ZonaWaktu">Zona Waktu</Label>
                    <select
                      className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                      value={form.data.ZonaWaktu}
                      onChange={(e) => form.setData('ZonaWaktu', e.target.value)}
                    >
                      {ZONA_WAKTU.map((zona) => (
                        <option key={zona} value={zona}>
                          {zona}
                        </option>
                      ))}
                    </select>
                    {form.errors.ZonaWaktu && (
                      <p className="text-sm text-destructive">{form.errors.ZonaWaktu}</p>
                    )}
                  </div>
                  <Button type="submit" disabled={form.processing}>
                    Simpan Perubahan
                  </Button>
                </form>
              </AturanWajibProvider>
            </CardContent>
          </Card>
        </div>
      </div>
    </KerangkaAplikasi>
  );
}
