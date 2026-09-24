import { useState } from 'react';
import { router } from '@inertiajs/react';
import { isAxiosError } from 'axios';
import { AlertTriangle, CheckCircle2, FileDown, FileUp, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PengunggahBerkas } from '@/components/shared/PengunggahBerkas';
import { DeretStatistik, KartuStatistik } from '@/components/shared/KartuStatistik';
import { http } from '@/lib/http';
import { ruteAset } from '@/features/Aset/api';
import type { GalatImporAset, HasilPratinjauImporAset } from '@/features/Aset/types';

type Tahap = 'pilih' | 'pratinjau' | 'selesai';

/** Di atas jumlah ini tombol unduh daftar galat ditawarkan; membacanya di layar sudah melelahkan. */
const GALAT_BANYAK = 10;

interface GalatPerBaris {
  baris: number;
  galat: GalatImporAset[];
}

function kelompokkanPerBaris(galat: GalatImporAset[]): GalatPerBaris[] {
  const peta = new Map<number, GalatImporAset[]>();

  galat.forEach((satu) => peta.set(satu.baris, [...(peta.get(satu.baris) ?? []), satu]));

  return Array.from(peta, ([baris, daftar]) => ({ baris, galat: daftar }));
}

/** Pesan galat berkas dari balasan 422 Laravel (`errors.Berkas`), atau pesan umum. */
function pesanGalatBerkas(galat: unknown): string[] {
  if (isAxiosError(galat)) {
    const data: unknown = galat.response?.data;

    if (data && typeof data === 'object' && 'errors' in data) {
      const berkas = (data as { errors?: Record<string, string[]> }).errors?.Berkas;
      if (berkas && berkas.length > 0) return berkas;
    }

    if (galat.response?.status === 403) return ['Anda tidak punya izin mendaftarkan aset.'];
  }

  return ['Berkas gagal diperiksa. Periksa koneksi Anda lalu coba lagi.'];
}

/** Balasan 422 konfirmasi yang membawa galat baris, bila ada. */
function hasilDariTolakan(galat: unknown): HasilPratinjauImporAset | null {
  if (!isAxiosError(galat) || galat.response?.status !== 422) return null;

  const data: unknown = galat.response.data;
  if (!data || typeof data !== 'object' || !('galat' in data)) return null;

  return { contoh: [], ...(data as Omit<HasilPratinjauImporAset, 'contoh'>) };
}

function formulir(berkas: File): FormData {
  const data = new FormData();
  data.append('Berkas', berkas);

  return data;
}

function Ringkasan({ hasil }: { hasil: HasilPratinjauImporAset }) {
  const bergalat = hasil.jumlahBaris - hasil.jumlahSah;

  return (
    <DeretStatistik kolom={3}>
      <KartuStatistik menyatu label="Baris dibaca" nilai={hasil.jumlahBaris} />
      <KartuStatistik menyatu label="Siap dibuat" nilai={hasil.jumlahSah} />
      <KartuStatistik
        menyatu
        label="Baris bergalat"
        nilai={
          <span className="inline-flex items-center gap-2">
            {bergalat}
            {bergalat > 0 && <span aria-hidden="true" className="size-2 rounded-full bg-bahaya-600" />}
          </span>
        }
      />
    </DeretStatistik>
  );
}

function DaftarGalat({ hasil }: { hasil: HasilPratinjauImporAset }) {
  const kelompok = kelompokkanPerBaris(hasil.galat);

  return (
    <div className="space-y-2">
      <p className="text-sm text-foreground">
        Perbaiki baris berikut di berkas Anda, simpan, lalu unggah ulang. Tidak ada aset yang dibuat selama
        masih ada galat.
      </p>
      {/* Layar sempit: dikelompokkan per baris. */}
      <ul className="max-h-80 space-y-2 overflow-y-auto pr-1 sm:hidden">
        {kelompok.map((satu) => (
          <li key={satu.baris} className="rounded-md border border-border bg-card px-3 py-2">
            <p className="text-sm font-medium text-foreground">Baris {satu.baris}</p>
            <ul className="mt-1 space-y-1">
              {satu.galat.map((g) => (
                <li key={`${g.kolom}-${g.pesan}`} className="text-sm">
                  <span className="font-medium text-foreground">{g.kolom}: </span>
                  <span className="text-destructive">{g.pesan}</span>
                  {g.nilai !== '' && (
                    <span className="block break-all text-xs text-muted-foreground">Isi sel: {g.nilai}</span>
                  )}
                </li>
              ))}
            </ul>
          </li>
        ))}
      </ul>
      <div className="hidden max-h-80 overflow-y-auto rounded-md border border-border sm:block">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-14">Baris</TableHead>
              <TableHead className="w-44">Kolom</TableHead>
              <TableHead>Masalah</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {kelompok.map((satu) =>
              satu.galat.map((g, i) => (
                <TableRow key={`${satu.baris}-${g.kolom}-${g.pesan}`}>
                  <TableCell className="align-top font-medium tabular-nums text-foreground">
                    {i === 0 ? satu.baris : ''}
                  </TableCell>
                  <TableCell className="whitespace-normal align-top text-foreground">{g.kolom}</TableCell>
                  <TableCell className="whitespace-normal align-top">
                    <span className="text-destructive">{g.pesan}</span>
                    {g.nilai !== '' && (
                      <span className="block break-all text-xs text-muted-foreground">
                        Isi sel: {g.nilai}
                      </span>
                    )}
                  </TableCell>
                </TableRow>
              )),
            )}
          </TableBody>
        </Table>
      </div>
      {hasil.jumlahGalat > hasil.galat.length && (
        <p className="text-xs text-muted-foreground">
          Menampilkan {hasil.galat.length} dari {hasil.jumlahGalat} galat. Unduh daftar galat untuk melihat
          seluruhnya.
        </p>
      )}
    </div>
  );
}

function ContohBaris({ hasil }: { hasil: HasilPratinjauImporAset }) {
  if (hasil.contoh.length === 0) return null;

  return (
    <div className="space-y-2">
      <p className="text-sm text-muted-foreground">
        Contoh {hasil.contoh.length} dari {hasil.jumlahSah} aset yang akan dibuat:
      </p>
      {/* Layar sempit: kartu, bukan tabel yang dipaksakan (DESIGN.md 20/33). */}
      <ul className="space-y-2 sm:hidden">
        {hasil.contoh.map((c) => (
          <li key={c.baris} className="rounded-md border border-border bg-card px-3 py-2 text-sm">
            <p className="font-medium text-foreground">{c.Nama}</p>
            <p className="font-mono text-xs text-muted-foreground">{c.KodeAset ?? 'Kode otomatis'}</p>
            <p className="text-muted-foreground">
              {c.Kategori ?? '—'} · {c.Lokasi ?? 'Lokasi belum diatur'}
              {c.UnitPengelola && ` · ${c.UnitPengelola}`}
            </p>
          </li>
        ))}
      </ul>
      <div className="hidden rounded-md border border-border sm:block">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-14">Baris</TableHead>
              <TableHead>Nama</TableHead>
              <TableHead>Kategori</TableHead>
              <TableHead>Lokasi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {hasil.contoh.map((c) => (
              <TableRow key={c.baris}>
                <TableCell className="tabular-nums text-muted-foreground">{c.baris}</TableCell>
                <TableCell className="whitespace-normal">
                  <div className="font-medium text-foreground">{c.Nama}</div>
                  <div className="font-mono text-xs text-muted-foreground">
                    {c.KodeAset ?? 'Kode otomatis'}
                  </div>
                </TableCell>
                <TableCell className="whitespace-normal">{c.Kategori ?? '—'}</TableCell>
                <TableCell className="whitespace-normal">
                  {c.Lokasi ?? '—'}
                  {c.UnitPengelola && (
                    <div className="text-xs text-muted-foreground">Dikelola {c.UnitPengelola}</div>
                  )}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </div>
  );
}

/**
 * Impor aset bertahap (PRD 8.4): unduh templat, pilih berkas, pratinjau, lalu konfirmasi.
 *
 * Berkasnya tetap di peramban dan dikirim ulang saat konfirmasi; server tidak
 * menyimpan apa pun di antara kedua langkah dan memeriksa ulang seluruhnya.
 */
export function DialogImporAset() {
  const [buka, setBuka] = useState(false);
  const [tahap, setTahap] = useState<Tahap>('pilih');
  const [berkas, setBerkas] = useState<File[]>([]);
  const [hasil, setHasil] = useState<HasilPratinjauImporAset | null>(null);
  const [galatBerkas, setGalatBerkas] = useState<string[]>([]);
  const [memproses, setMemproses] = useState<'periksa' | 'impor' | 'unduh' | null>(null);
  const [jumlahDibuat, setJumlahDibuat] = useState(0);

  const mulaiUlang = () => {
    setTahap('pilih');
    setBerkas([]);
    setHasil(null);
    setGalatBerkas([]);
  };

  const ubahBuka = (terbuka: boolean) => {
    if (memproses) return;
    if (terbuka) mulaiUlang();
    setBuka(terbuka);
  };

  const periksa = async () => {
    const satu = berkas[0];
    if (!satu) return;

    setMemproses('periksa');
    setGalatBerkas([]);
    try {
      const { data } = await http.post<HasilPratinjauImporAset>(ruteAset.imporPratinjau, formulir(satu));
      setHasil(data);
      setTahap('pratinjau');
    } catch (galat) {
      setGalatBerkas(pesanGalatBerkas(galat));
    } finally {
      setMemproses(null);
    }
  };

  const impor = async () => {
    const satu = berkas[0];
    if (!satu) return;

    setMemproses('impor');
    setGalatBerkas([]);
    try {
      const { data } = await http.post<{ jumlah: number }>(ruteAset.impor, formulir(satu));
      setJumlahDibuat(data.jumlah);
      setTahap('selesai');
      router.reload({ only: ['aset'] });
    } catch (galat) {
      // Data berubah sejak pratinjau (mis. kode baru saja dipakai): tampilkan hasil terbaru.
      const tolakan = hasilDariTolakan(galat);
      if (tolakan) {
        setHasil(tolakan);
      } else {
        setGalatBerkas(pesanGalatBerkas(galat));
      }
    } finally {
      setMemproses(null);
    }
  };

  const unduhGalat = async () => {
    const satu = berkas[0];
    if (!satu) return;

    setMemproses('unduh');
    try {
      const { data } = await http.post<Blob>(ruteAset.imporGalat, formulir(satu), { responseType: 'blob' });
      const tautan = document.createElement('a');
      tautan.href = URL.createObjectURL(data);
      tautan.download = 'galat-impor-aset.csv';
      tautan.click();
      URL.revokeObjectURL(tautan.href);
    } catch {
      setGalatBerkas(['Daftar galat gagal diunduh. Coba lagi sebentar lagi.']);
    } finally {
      setMemproses(null);
    }
  };

  const adaGalat = (hasil?.jumlahGalat ?? 0) > 0;

  return (
    <Dialog open={buka} onOpenChange={ubahBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          <FileUp className="size-4" />
          Impor
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Impor Aset</DialogTitle>
          <DialogDescription>
            {tahap === 'pilih' && 'Daftarkan banyak aset sekaligus dari berkas CSV atau Excel.'}
            {tahap === 'pratinjau' && hasil && `Hasil pemeriksaan ${hasil.namaBerkas}.`}
            {tahap === 'selesai' && 'Impor selesai.'}
          </DialogDescription>
        </DialogHeader>

        {tahap === 'pilih' && (
          <div className="space-y-4">
            <ol className="list-decimal space-y-1 pl-5 text-sm text-foreground">
              <li>
                Unduh templat, lalu isi satu aset per baris. Kategori, lokasi, dan unit diisi dengan kodenya.
              </li>
              <li>Pilih berkas yang sudah diisi (paling banyak 1.000 baris, 5 MB).</li>
              <li>Periksa hasil pratinjau, lalu konfirmasi impor.</li>
            </ol>
            <div className="flex flex-wrap gap-2">
              <Button variant="outline" size="sm" asChild>
                <a href={ruteAset.imporTemplat('xlsx')}>
                  <FileDown className="size-4" />
                  Templat Excel (XLSX)
                </a>
              </Button>
              <Button variant="outline" size="sm" asChild>
                <a href={ruteAset.imporTemplat('csv')}>
                  <FileDown className="size-4" />
                  Templat CSV
                </a>
              </Button>
            </div>
            <PengunggahBerkas
              berkas={berkas}
              onUbah={(daftar) => {
                setBerkas(daftar);
                setGalatBerkas([]);
              }}
              terima=".csv,.xlsx"
              maksMb={5}
              disabled={memproses !== null}
              petunjuk="Seret berkas CSV atau XLSX ke sini, atau pilih dari perangkat Anda."
            />
          </div>
        )}

        {tahap === 'pratinjau' && hasil && (
          <div className="space-y-4">
            <Ringkasan hasil={hasil} />
            {adaGalat ? (
              <DaftarGalat hasil={hasil} />
            ) : (
              <div className="flex items-start gap-2 rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground">
                <CheckCircle2 aria-hidden="true" className="mt-0.5 size-4 shrink-0 text-primary" />
                Semua baris sah. Periksa contoh di bawah, lalu lanjutkan impor.
              </div>
            )}
            {!adaGalat && <ContohBaris hasil={hasil} />}
          </div>
        )}

        {tahap === 'selesai' && (
          <div className="flex flex-col items-center gap-3 py-6 text-center">
            <CheckCircle2 aria-hidden="true" className="size-10 text-primary" />
            <p className="text-base font-medium text-foreground">{jumlahDibuat} aset berhasil diimpor.</p>
            <p className="max-w-prose text-sm text-muted-foreground">
              Kode QR dan riwayat lokasi awalnya sudah terbentuk. Aset baru tampil paling atas di daftar.
            </p>
          </div>
        )}

        {galatBerkas.length > 0 && (
          <div role="alert" className="flex gap-2 rounded-md border border-destructive/40 px-3 py-2 text-sm">
            <AlertTriangle aria-hidden="true" className="mt-0.5 size-4 shrink-0 text-destructive" />
            <ul className="space-y-1 text-destructive">
              {galatBerkas.map((pesan) => (
                <li key={pesan}>{pesan}</li>
              ))}
            </ul>
          </div>
        )}

        <DialogFooter className="gap-2">
          {tahap === 'pilih' && (
            <Button onClick={periksa} disabled={berkas.length === 0 || memproses !== null}>
              {memproses === 'periksa' && <Loader2 className="size-4 animate-spin" />}
              {memproses === 'periksa' ? 'Memeriksa...' : 'Periksa Berkas'}
            </Button>
          )}
          {tahap === 'pratinjau' && hasil && (
            <>
              {hasil.jumlahGalat > GALAT_BANYAK && (
                <Button variant="outline" onClick={unduhGalat} disabled={memproses !== null}>
                  {memproses === 'unduh' ? (
                    <Loader2 className="size-4 animate-spin" />
                  ) : (
                    <FileDown className="size-4" />
                  )}
                  Unduh Daftar Galat
                </Button>
              )}
              <Button variant="outline" onClick={mulaiUlang} disabled={memproses !== null}>
                Pilih Berkas Lain
              </Button>
              {!adaGalat && (
                <Button onClick={impor} disabled={memproses !== null || hasil.jumlahSah === 0}>
                  {memproses === 'impor' && <Loader2 className="size-4 animate-spin" />}
                  {memproses === 'impor' ? 'Mengimpor...' : `Impor ${hasil.jumlahSah} Aset`}
                </Button>
              )}
            </>
          )}
          {tahap === 'selesai' && (
            <Button
              onClick={() => {
                setBuka(false);
                router.visit(ruteAset.index);
              }}
            >
              Lihat Daftar Aset
            </Button>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
