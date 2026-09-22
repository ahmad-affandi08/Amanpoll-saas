import { FormEvent, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
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
import { Textarea } from '@/components/ui/textarea';
import type { RingkasanOtomasi } from '@/features/Pemasaran/types';
import { rutePemasaran } from '@/features/Pemasaran/api';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  otomasi: RingkasanOtomasi[];
  /** kode pemicu => peristiwa yang menyalakannya, atau BelumAdaSumber. */
  pilihan: { Pemicu: Record<string, string> };
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const AKAR = rutePemasaran.otomasi;
const BELUM_ADA_SUMBER = 'BelumAdaSumber';

export default function PemasaranOtomasiIndex({ otomasi, pilihan, wajib }: Props) {
  const belumBerlaku = otomasi.filter((satu) => satu.Aktif && !satu.PemicuBerlaku);

  return (
    <KerangkaPlatform>
      <Head title="Otomasi Pemasaran" />

      <KepalaHalaman
        judul="Otomasi Pemasaran"
        deskripsi="Pemicu, kondisi, jeda, dan aksi. Setiap versi dikunci saat diaktifkan."
        tanpaBreadcrumb
        aksi={<DialogOtomasi pilihan={pilihan} wajib={wajib.otomasi} />}
        className="mb-6"
      />

      {belumBerlaku.length > 0 ? (
        <div className="mb-4 rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
          {belumBerlaku.length} otomasi menyala tetapi pemicunya belum ada sumbernya, jadi tidak akan pernah
          berjalan: <span className="font-mono">{belumBerlaku.map((satu) => satu.Kode).join(', ')}</span>.
        </div>
      ) : null}

      {otomasi.length === 0 ? (
        <p className="text-sm text-muted-foreground">Belum ada otomasi.</p>
      ) : (
        <div className="grid gap-4 md:grid-cols-2">
          {otomasi.map((satu) => (
            <Card key={satu.Id}>
              <CardHeader className="flex-row items-start justify-between gap-2 space-y-0">
                <div className="min-w-0">
                  <CardTitle className="text-base">
                    <Link href={`${AKAR}/${satu.Kode}`} className="hover:underline">
                      {satu.Nama}
                    </Link>
                  </CardTitle>
                  <p className="font-mono text-xs text-muted-foreground">{satu.Kode}</p>
                </div>
                <div className="flex shrink-0 flex-wrap justify-end gap-1">
                  {satu.Aktif ? <Badge>Menyala</Badge> : <Badge variant="secondary">Mati</Badge>}
                  {satu.PemicuBerlaku ? null : <Badge variant="outline">Pemicu belum berlaku</Badge>}
                </div>
              </CardHeader>
              <CardContent className="space-y-2 text-sm">
                <p>
                  <span className="text-muted-foreground">Pemicu: </span>
                  <span className="font-mono">{satu.Pemicu}</span>
                </p>
                <p className="text-muted-foreground">
                  Versi aktif {satu.NomorVersiAktif ?? '—'} · {satu.JumlahBerjalan} berjalan
                  {satu.JumlahDlq > 0 ? (
                    <span className="text-destructive"> · {satu.JumlahDlq} di DLQ</span>
                  ) : null}
                </p>
                {satu.Keterangan ? <p className="text-muted-foreground">{satu.Keterangan}</p> : null}
                <div className="flex justify-end">
                  <Button asChild variant="outline" size="sm">
                    <Link href={`${AKAR}/${satu.Kode}`}>Buka</Link>
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

function DialogOtomasi({
  pilihan,
  wajib,
}: {
  pilihan: { Pemicu: Record<string, string> };
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const daftarPemicu = Object.entries(pilihan.Pemicu);

  const form = useForm({
    Kode: '',
    Nama: '',
    Keterangan: '',
    Pemicu: daftarPemicu[0]?.[0] ?? '',
  });

  const sumberTerpilih = pilihan.Pemicu[form.data.Pemicu];

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    form.post(AKAR, { onSuccess: () => setBuka(false) });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Tambah Otomasi</Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Tambah Otomasi</DialogTitle>
        </DialogHeader>

        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={kirim} className="grid gap-4">
            <BidangKode
              nilai={form.data.Kode}
              onUbah={(nilai) => form.setData('Kode', nilai)}
              galat={form.errors.Kode}
              contoh="sapa-prospek-baru"
            />

            <div className="grid gap-2">
              <Label nama="NamaOtomasi" htmlFor="NamaOtomasi">
                Nama
              </Label>
              <Input
                id="NamaOtomasi"
                value={form.data.Nama}
                onChange={(e) => form.setData('Nama', e.target.value)}
                required
              />
              {form.errors.Nama ? <p className="text-sm text-destructive">{form.errors.Nama}</p> : null}
            </div>

            <div className="grid gap-2">
              <Label nama="PemicuOtomasi" htmlFor="PemicuOtomasi">
                Pemicu
              </Label>
              <Select value={form.data.Pemicu} onValueChange={(v) => form.setData('Pemicu', v)}>
                <SelectTrigger id="PemicuOtomasi">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {daftarPemicu.map(([kode]) => (
                    <SelectItem key={kode} value={kode}>
                      {kode}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {sumberTerpilih === BELUM_ADA_SUMBER ? (
                <p className="text-sm text-muted-foreground">
                  Belum ada yang menghasilkan pemicu ini. Otomasinya tersimpan tetapi tidak akan berjalan
                  sampai sumbernya ada.
                </p>
              ) : null}
              {form.errors.Pemicu ? <p className="text-sm text-destructive">{form.errors.Pemicu}</p> : null}
            </div>

            <div className="grid gap-2">
              <Label nama="KeteranganOtomasi" htmlFor="KeteranganOtomasi">
                Keterangan
              </Label>
              <Textarea
                id="KeteranganOtomasi"
                rows={3}
                value={form.data.Keterangan}
                onChange={(e) => form.setData('Keterangan', e.target.value)}
              />
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
