import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { RiwayatAktivitas } from '@/components/shared/RiwayatAktivitas';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type {
  EntriRiwayatTahap,
  EntriTimeline,
  ProspekDetail,
  TahapRingkas,
} from '@/features/Pemasaran/types';
import { rutePemasaran } from '@/features/Pemasaran/api';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  prospek: ProspekDetail;
  timeline: EntriTimeline[];
  riwayatTahap: EntriRiwayatTahap[];
  tahap: TahapRingkas[];
  jenisAktivitas: string[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

export default function PemasaranProspekShow({
  prospek,
  timeline,
  riwayatTahap,
  tahap,
  jenisAktivitas,
  wajib,
}: Props) {
  return (
    <KerangkaPlatform>
      <Head title={prospek.Nama} />

      <KepalaHalaman
        judul={prospek.Nama}
        deskripsi={prospek.Perusahaan ?? 'Tanpa perusahaan'}
        tanpaBreadcrumb
        lencana={
          <div className="flex flex-wrap gap-2">
            {prospek.Tahap ? <Badge variant="secondary">{prospek.Tahap}</Badge> : null}
            {prospek.Qualified ? <Badge>Qualified</Badge> : null}
          </div>
        }
        meta={
          <dl className="flex flex-wrap gap-x-6 gap-y-1 text-sm text-muted-foreground">
            <div className="flex gap-1">
              <dt>Skor</dt>
              <dd className="font-medium text-foreground tabular-nums">{prospek.Skor}</dd>
            </div>
            <div className="flex gap-1">
              <dt>Sumber</dt>
              <dd className="text-foreground">{prospek.Sumber}</dd>
            </div>
            {prospek.Kampanye ? (
              <div className="flex gap-1">
                <dt>Kampanye</dt>
                <dd className="text-foreground">{prospek.Kampanye}</dd>
              </div>
            ) : null}
          </dl>
        }
        className="mb-6"
      />

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="grid gap-6 lg:col-span-2">
          <KartuTahap prospek={prospek} tahap={tahap} wajib={wajib.tahap} />
          <KartuAktivitasBaru prospek={prospek} jenisAktivitas={jenisAktivitas} wajib={wajib.aktivitas} />

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Timeline</CardTitle>
            </CardHeader>
            <CardContent>
              <RiwayatAktivitas
                butir={timeline.map((satu, urutan) => ({
                  id: `${satu.Sumber}-${satu.Pada}-${urutan}`,
                  pelaku: satu.Sumber === 'Peristiwa' ? 'Sistem' : 'Tim',
                  ringkasan: satu.Judul,
                  rincian: satu.Isi,
                  waktu: satu.Pada,
                }))}
                pesanKosong="Belum ada aktivitas pada prospek ini."
              />
            </CardContent>
          </Card>
        </div>

        <div className="grid gap-6">
          <KartuKontak prospek={prospek} />
          <KartuRiwayatTahap riwayat={riwayatTahap} />
          <KartuSkor prospek={prospek} />
        </div>
      </div>
    </KerangkaPlatform>
  );
}

function KartuTahap({
  prospek,
  tahap,
  wajib,
}: {
  prospek: ProspekDetail;
  tahap: TahapRingkas[];
  wajib: AturanWajib;
}) {
  const [kode, setKode] = useState(prospek.KodeTahap ?? tahap[0]?.Kode ?? '');
  const [alasan, setAlasan] = useState('');

  const pindahkan = (e: FormEvent) => {
    e.preventDefault();
    router.post(
      rutePemasaran.prospekTahap(prospek.Id),
      { Kode: kode, Alasan: alasan || null },
      { preserveScroll: true, onSuccess: () => setAlasan('') },
    );
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Pindahkan Tahap</CardTitle>
      </CardHeader>
      <CardContent>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={pindahkan} className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_auto]">
            <div className="grid gap-2">
              <Label nama="tahap" htmlFor="tahap">
                Tahap
              </Label>
              <Select value={kode} onValueChange={setKode}>
                <SelectTrigger id="tahap">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {tahap.map((satu) => (
                    <SelectItem key={satu.Kode} value={satu.Kode}>
                      {satu.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid gap-2">
              <Label nama="alasan" htmlFor="alasan">
                Alasan
              </Label>
              <Input id="alasan" value={alasan} onChange={(e) => setAlasan(e.target.value)} />
            </div>
            <div className="flex items-end">
              <Button type="submit">Pindahkan</Button>
            </div>
          </form>
        </AturanWajibProvider>
      </CardContent>
    </Card>
  );
}

function KartuAktivitasBaru({
  prospek,
  jenisAktivitas,
  wajib,
}: {
  prospek: ProspekDetail;
  jenisAktivitas: string[];
  wajib: AturanWajib;
}) {
  const form = useForm({ Jenis: jenisAktivitas[0] ?? 'Catatan', Judul: '', Isi: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(rutePemasaran.prospekAktivitas(prospek.Id), {
      preserveScroll: true,
      onSuccess: () => form.reset('Judul', 'Isi'),
    });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Catat Aktivitas</CardTitle>
      </CardHeader>
      <CardContent>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="grid gap-3">
            <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
              <div className="grid gap-2">
                <Label nama="jenis" htmlFor="jenis">
                  Jenis
                </Label>
                <Select value={form.data.Jenis} onValueChange={(v) => form.setData('Jenis', v)}>
                  <SelectTrigger id="jenis">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {jenisAktivitas.map((satu) => (
                      <SelectItem key={satu} value={satu}>
                        {satu}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="grid gap-2">
                <Label nama="judul" htmlFor="judul">
                  Judul
                </Label>
                <Input
                  id="judul"
                  value={form.data.Judul}
                  onChange={(e) => form.setData('Judul', e.target.value)}
                  required
                />
                {form.errors.Judul ? <p className="text-sm text-destructive">{form.errors.Judul}</p> : null}
              </div>
            </div>
            <div className="grid gap-2">
              <Label nama="isi" htmlFor="isi">
                Catatan
              </Label>
              <Textarea
                id="isi"
                rows={3}
                value={form.data.Isi}
                onChange={(e) => form.setData('Isi', e.target.value)}
              />
            </div>
            <div>
              <Button type="submit" disabled={form.processing}>
                Simpan
              </Button>
            </div>
          </form>
        </AturanWajibProvider>
      </CardContent>
    </Card>
  );
}

function KartuKontak({ prospek }: { prospek: ProspekDetail }) {
  const baris: Array<[string, string | null]> = [
    ['Email', prospek.Email],
    ['Telepon', prospek.Telepon],
    ['WhatsApp', prospek.WhatsApp],
    ['Jabatan', prospek.Jabatan],
    ['Industri', prospek.Industri],
  ];

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Kontak</CardTitle>
      </CardHeader>
      <CardContent>
        <dl className="grid gap-2 text-sm">
          {baris.map(([label, nilai]) => (
            <div key={label} className="flex justify-between gap-3">
              <dt className="text-muted-foreground">{label}</dt>
              <dd className="text-right">{nilai ?? '—'}</dd>
            </div>
          ))}
        </dl>
      </CardContent>
    </Card>
  );
}

function KartuSkor({ prospek }: { prospek: ProspekDetail }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Rincian Skor</CardTitle>
      </CardHeader>
      <CardContent>
        {prospek.RincianSkor.length === 0 ? (
          <p className="text-sm text-muted-foreground">Skor belum pernah dihitung.</p>
        ) : (
          <dl className="grid gap-2 text-sm">
            {prospek.RincianSkor.map((satu) => (
              <div key={satu.Peristiwa} className="flex justify-between gap-3">
                <dt className="text-muted-foreground">{satu.Peristiwa}</dt>
                <dd className="tabular-nums">{satu.Bobot > 0 ? `+${satu.Bobot}` : satu.Bobot}</dd>
              </div>
            ))}
          </dl>
        )}
      </CardContent>
    </Card>
  );
}

/** Lama menetap per tahap -- pertanyaan pipeline yang tidak terjawab timeline. */
function KartuRiwayatTahap({ riwayat }: { riwayat: EntriRiwayatTahap[] }) {
  const berjalan = riwayat.find((satu) => satu.Berjalan);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Riwayat Tahap</CardTitle>
      </CardHeader>
      <CardContent>
        {riwayat.length === 0 ? (
          <p className="text-sm text-muted-foreground">Prospek ini belum pernah berpindah tahap.</p>
        ) : (
          <>
            {berjalan ? (
              <p className="mb-3 text-sm text-muted-foreground">
                Sudah {lamaHari(berjalan.LamaHari)} di tahap{' '}
                <span className="font-medium text-foreground">{berjalan.TahapSesudah ?? 'ini'}</span>.
              </p>
            ) : null}
            <ol className="grid gap-3 text-sm">
              {[...riwayat].reverse().map((satu) => (
                <li key={satu.Id} className="border-l-2 border-border pl-3">
                  <div className="flex flex-wrap items-baseline justify-between gap-2">
                    <span className="font-medium text-foreground">
                      {satu.TahapSebelum
                        ? `${satu.TahapSebelum} → ${satu.TahapSesudah ?? '—'}`
                        : `Masuk ${satu.TahapSesudah ?? '—'}`}
                    </span>
                    <span className="tabular-nums text-muted-foreground">{lamaHari(satu.LamaHari)}</span>
                  </div>
                  <div className="text-xs text-muted-foreground">
                    {tanggalWaktu(satu.BerpindahPada)}
                    {satu.Alasan ? ` · ${satu.Alasan}` : ''}
                  </div>
                </li>
              ))}
            </ol>
          </>
        )}
      </CardContent>
    </Card>
  );
}

function lamaHari(hari: number): string {
  return hari === 0 ? 'kurang dari sehari' : `${hari} hari`;
}

function tanggalWaktu(nilai: string): string {
  return new Date(nilai).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}
