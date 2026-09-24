import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import type { FormDataConvertible } from '@inertiajs/core';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { rutePemasaran } from '@/features/Pemasaran/api';
import { Combobox } from '@/components/ui/combobox';

interface Domain {
  publik: string | null;
  dashboard: string;
  partner: string | null;
  kanonik: string | null;
  situsPublikAktif: boolean;
}

interface Fitur {
  Kode: string;
  Nama: string;
  Keterangan: string;
  Aktif: boolean;
}

interface Konfigurasi {
  Kunci: string;
  Keterangan: string;
  Nilai: unknown;
  Bawaan: unknown;
}

interface PilihanKonfigurasi {
  Nilai: string;
  Label: string;
  Keterangan: string;
}

interface Props {
  domain: Domain;
  fitur: Fitur[];
  konfigurasi: Konfigurasi[];
  pilihanKonfigurasi: Record<string, PilihanKonfigurasi[]>;
}

export default function PemasaranPengaturan({ domain, fitur, konfigurasi, pilihanKonfigurasi }: Props) {
  return (
    <KerangkaPlatform>
      <Head title="Pengaturan Growth & Marketing" />

      <KepalaHalaman
        judul="Pengaturan"
        deskripsi="Host, modul, dan setelan domain Pemasaran."
        tanpaBreadcrumb
      />

      <div className="mt-5 grid gap-5">
        <KartuDomain domain={domain} />
        <KartuFitur fitur={fitur} />
        <KartuKonfigurasi konfigurasi={konfigurasi} pilihan={pilihanKonfigurasi} />
      </div>
    </KerangkaPlatform>
  );
}

/** Host hanya ditampilkan, tidak pernah diubah dari sini: sumbernya environment (MARKETING.md 30). */
function KartuDomain({ domain }: { domain: Domain }) {
  const baris: Array<[string, string | null]> = [
    ['Host publik', domain.publik],
    ['Bentuk kanonik', domain.kanonik],
    ['Host dashboard', domain.dashboard],
    ['Host partner', domain.partner],
  ];

  return (
    <Card>
      <CardHeader>
        <CardTitle>Domain</CardTitle>
      </CardHeader>
      <CardContent className="grid gap-3 text-sm">
        <p className="text-muted-foreground">
          Nilai berasal dari environment dan hanya dapat dibaca di sini. Ubah lewat konfigurasi server, bukan
          lewat form.
        </p>
        <dl className="grid gap-2 sm:grid-cols-2">
          {baris.map(([label, nilai]) => (
            <div
              key={label}
              className="flex items-center justify-between gap-3 rounded-sm bg-muted/50 px-3 py-2"
            >
              <dt className="text-muted-foreground">{label}</dt>
              <dd className="font-mono text-xs">{nilai ?? 'belum diatur'}</dd>
            </div>
          ))}
        </dl>
        {!domain.situsPublikAktif ? (
          <p className="text-muted-foreground">
            Situs publik belum aktif. Isi host publik di environment untuk menyalakannya.
          </p>
        ) : null}
      </CardContent>
    </Card>
  );
}

function KartuFitur({ fitur }: { fitur: Fitur[] }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Modul</CardTitle>
      </CardHeader>
      <CardContent className="grid gap-3">
        <p className="text-sm text-muted-foreground">
          Modul yang dimatikan menutup rutenya, bukan sekadar menyembunyikan menunya.
        </p>
        {fitur.map((satu) => (
          <div
            key={satu.Kode}
            className="flex flex-wrap items-center justify-between gap-3 rounded-[6px] border border-border px-3 py-3"
          >
            <div className="min-w-0">
              <p className="text-sm font-medium">{satu.Nama}</p>
              <p className="text-sm text-muted-foreground">{satu.Keterangan}</p>
              <Badge variant="outline" className="mt-1 font-mono text-xs">
                {satu.Kode}
              </Badge>
            </div>
            <Switch
              checked={satu.Aktif}
              aria-label={`Aktifkan ${satu.Nama}`}
              onCheckedChange={(aktif) =>
                router.post(
                  rutePemasaran.pengaturanFitur,
                  { Kode: satu.Kode, Aktif: aktif },
                  { preserveScroll: true },
                )
              }
            />
          </div>
        ))}
      </CardContent>
    </Card>
  );
}

function KartuKonfigurasi({
  konfigurasi,
  pilihan,
}: {
  konfigurasi: Konfigurasi[];
  pilihan: Record<string, PilihanKonfigurasi[]>;
}) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Setelan</CardTitle>
      </CardHeader>
      <CardContent className="grid gap-4">
        {konfigurasi.map((satu) => (
          <BarisKonfigurasi key={satu.Kunci} konfigurasi={satu} pilihan={pilihan[satu.Kunci]} />
        ))}
      </CardContent>
    </Card>
  );
}

function BarisKonfigurasi({
  konfigurasi,
  pilihan,
}: {
  konfigurasi: Konfigurasi;
  pilihan?: PilihanKonfigurasi[];
}) {
  const majemuk = typeof konfigurasi.Nilai === 'object' && konfigurasi.Nilai !== null;
  const awal = majemuk ? JSON.stringify(konfigurasi.Nilai, null, 2) : String(konfigurasi.Nilai ?? '');
  const [nilai, setNilai] = useState(awal);
  const [galat, setGalat] = useState<string | null>(null);

  const simpan = () => {
    let muatan: FormDataConvertible = nilai;

    if (majemuk) {
      try {
        muatan = JSON.parse(nilai) as FormDataConvertible;
      } catch {
        setGalat('Format JSON belum benar.');

        return;
      }
    } else if (typeof konfigurasi.Bawaan === 'number') {
      muatan = Number(nilai);
    }

    setGalat(null);
    router.post(
      rutePemasaran.pengaturanKonfigurasi,
      { Kunci: konfigurasi.Kunci, Nilai: muatan },
      { preserveScroll: true },
    );
  };

  const idKolom = `konfigurasi-${konfigurasi.Kunci}`;

  return (
    <div className="grid content-start gap-2">
      <Label htmlFor={idKolom} className="font-mono text-xs">
        {konfigurasi.Kunci}
      </Label>
      <p className="text-sm text-muted-foreground">{konfigurasi.Keterangan}</p>
      {/* Setelan berdaftar tertutup tidak pernah ditawarkan sebagai kotak teks bebas. */}
      {pilihan ? (
        <>
          <Combobox
            nilai={nilai}
            onPilih={setNilai}
            opsi={pilihan.map((satu) => ({ nilai: satu.Nilai, label: satu.Label }))}
          />
          <p className="text-xs text-muted-foreground">
            {pilihan.find((satu) => satu.Nilai === nilai)?.Keterangan ?? ''}
          </p>
        </>
      ) : majemuk ? (
        <Textarea id={idKolom} rows={10} value={nilai} onChange={(e) => setNilai(e.target.value)} />
      ) : (
        <Input id={idKolom} value={nilai} onChange={(e) => setNilai(e.target.value)} />
      )}
      {galat ? <p className="text-sm text-destructive">{galat}</p> : null}
      <div>
        <Button size="sm" variant="outline" onClick={simpan}>
          Simpan
        </Button>
      </div>
    </div>
  );
}
