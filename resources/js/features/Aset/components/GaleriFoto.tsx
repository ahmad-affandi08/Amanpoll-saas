import { useRef, useState, type ChangeEvent } from 'react';
import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, ImagePlus, Star, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { FotoAtauIkon3D } from '@/components/shared/FotoAtauIkon3D';
import { Ikon3D } from '@/components/shared/Ikon3D';
import { ikonKategori } from '@/components/shared/ikon-kategori';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { pampatkanGambar } from '@/lib/pemampat-gambar';
import { ruteAset } from '@/features/Aset/api';
import type { Aset, FotoAset } from '@/features/Aset/types';

const TERIMA_FOTO = 'image/jpeg,image/png,image/webp';

/** Ikon 3D cadangan aset: dari kategorinya, lalu dari namanya ("Genset Gedung B"). */
function ikonAset(aset: Pick<Aset, 'NamaKategoriAset' | 'Nama'>) {
  const dariKategori = ikonKategori(aset.NamaKategoriAset);
  return dariKategori.ikon !== 'toolbox' ? dariKategori : ikonKategori(aset.Nama);
}

interface PropsGaleri {
  aset: Aset;
  foto: FotoAset[];
  maks: number;
  bolehTambah: boolean;
  bolehKelola: boolean;
}

/** Unggah beberapa foto sekaligus; tiap gambar dikecilkan dulu di peramban (PRD 11.1). */
function useUnggahFoto(aset: Aset, sisa: number, maks: number) {
  const masukan = useRef<HTMLInputElement | null>(null);
  const [mengunggah, setMengunggah] = useState(false);
  const [galat, setGalat] = useState<string | null>(null);

  const pilih = async (event: ChangeEvent<HTMLInputElement>) => {
    const daftar = Array.from(event.target.files ?? []);
    event.target.value = '';
    if (daftar.length === 0) return;
    if (daftar.length > sisa) {
      setGalat(
        sisa === 0
          ? `Galeri sudah berisi ${maks} foto. Hapus foto lama lebih dulu.`
          : `Tersisa tempat untuk ${sisa} foto lagi (paling banyak ${maks}).`,
      );
      return;
    }

    setGalat(null);
    setMengunggah(true);
    const kecil = await Promise.all(daftar.map((satu) => pampatkanGambar(satu)));
    router.post(
      ruteAset.foto(aset.Id),
      { Foto: kecil },
      {
        forceFormData: true,
        preserveScroll: true,
        onError: (errors) => setGalat(Object.values(errors)[0] ?? 'Foto belum terunggah. Coba lagi.'),
        onFinish: () => setMengunggah(false),
      },
    );
  };

  const input = (
    <input
      ref={masukan}
      type="file"
      accept={TERIMA_FOTO}
      multiple
      className="hidden"
      onChange={(event) => void pilih(event)}
    />
  );

  return { input, buka: () => masukan.current?.click(), mengunggah, galat };
}

/** Foto utama besar di kepala halaman detail aset, dengan cadangan ikon 3D kategori. */
export function KartuFotoUtama({
  aset,
  jumlahFoto,
  maks,
  onLihatGaleri,
}: {
  aset: Aset;
  jumlahFoto: number;
  maks: number;
  onLihatGaleri: () => void;
}) {
  const ikon = ikonAset(aset);

  return (
    <div className="flex flex-col gap-3 rounded-md border border-border bg-card p-3">
      <button
        type="button"
        onClick={onLihatGaleri}
        className="block overflow-hidden rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        aria-label={jumlahFoto > 0 ? `Buka galeri foto ${aset.Nama}` : `Tambah foto ${aset.Nama}`}
      >
        <FotoAtauIkon3D
          url={aset.FotoUtamaThumbnailUrl}
          ikon={ikon.ikon}
          ukuranIkon={104}
          alt={`Foto utama ${aset.Nama}`}
          segera
          className="aspect-[16/10] w-full bg-permukaan-100 md:aspect-[4/3]"
        />
      </button>
      <div className="flex items-center justify-between gap-2 px-1">
        <p className="text-sm text-muted-foreground">
          {jumlahFoto > 0 ? `${jumlahFoto} dari ${maks} foto` : 'Belum ada foto'}
        </p>
        <Button variant="ghost" size="sm" onClick={onLihatGaleri}>
          {jumlahFoto > 0 ? 'Lihat galeri' : 'Buka galeri'}
        </Button>
      </div>
    </div>
  );
}

/** Tab galeri foto aset (PRD 8.4 "Foto Aset"): lihat besar, unggah banyak, jadikan utama, hapus. */
export function TabFoto({ aset, foto, maks, bolehTambah, bolehKelola }: PropsGaleri) {
  const konfirmasi = useKonfirmasi();
  const [terbuka, setTerbuka] = useState<number | null>(null);
  const [memproses, setMemproses] = useState<string | null>(null);
  const sisa = Math.max(0, maks - foto.length);
  const unggah = useUnggahFoto(aset, sisa, maks);
  const ikon = ikonAset(aset);
  const aktif = terbuka === null ? null : (foto[terbuka] ?? null);

  const jadikanUtama = (satu: FotoAset) => {
    setMemproses(satu.BerkasId);
    router.put(
      ruteAset.fotoUtama(aset.Id, satu.BerkasId),
      {},
      {
        preserveScroll: true,
        onFinish: () => setMemproses(null),
      },
    );
  };

  const hapus = async (satu: FotoAset) => {
    const setuju = await konfirmasi({
      judul: `Hapus foto "${satu.NamaAsli}"?`,
      deskripsi: satu.Utama
        ? 'Foto ini foto utama. Foto berikutnya di galeri akan menjadi foto utama.'
        : 'Foto dihapus dari galeri aset dan tidak dapat dipulihkan.',
      ragam: 'bahaya',
      labelAksi: 'Hapus foto',
    });
    if (!setuju) return;
    setMemproses(satu.BerkasId);
    router.delete(ruteAset.fotoDetail(aset.Id, satu.BerkasId), {
      preserveScroll: true,
      onSuccess: () => setTerbuka(null),
      onFinish: () => setMemproses(null),
    });
  };

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p className="text-sm font-medium text-foreground">Galeri foto</p>
          <p className="text-sm text-muted-foreground">
            {foto.length} dari {maks} foto. Foto utama tampil di daftar aset dan Mode Lapangan.
          </p>
        </div>
        {bolehTambah && (
          <Button onClick={unggah.buka} disabled={unggah.mengunggah || sisa === 0}>
            <ImagePlus className="size-4" />
            {unggah.mengunggah ? 'Mengunggah…' : 'Tambah foto'}
          </Button>
        )}
        {unggah.input}
      </div>

      {unggah.galat && (
        <p role="alert" className="text-sm text-destructive">
          {unggah.galat}
        </p>
      )}

      {foto.length === 0 ? (
        <div className="flex flex-col items-center gap-3 rounded-md border border-dashed border-border bg-card px-6 py-10 text-center">
          <span className="flex size-32 items-center justify-center rounded-full bg-permukaan-100">
            <Ikon3D nama={ikon.ikon} ukuran={80} />
          </span>
          <p className="font-medium text-foreground">Belum ada foto aset</p>
          <p className="max-w-sm text-sm text-muted-foreground">
            {bolehTambah
              ? `Tambahkan foto agar aset mudah dikenali di lapangan. Paling banyak ${maks} foto; foto pertama menjadi foto utama.`
              : 'Aset ini belum punya foto. Ikon kategorinya dipakai sebagai gantinya.'}
          </p>
        </div>
      ) : (
        <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
          {foto.map((satu, indeks) => (
            <li key={satu.BerkasId} className="overflow-hidden rounded-md border border-border bg-card">
              <button
                type="button"
                onClick={() => setTerbuka(indeks)}
                className="relative block w-full focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                aria-label={`Lihat besar foto ${indeks + 1}${satu.Utama ? ' (foto utama)' : ''}`}
              >
                <FotoAtauIkon3D
                  url={satu.UrlThumbnail}
                  ikon={ikon.ikon}
                  ukuranIkon={48}
                  alt=""
                  className="aspect-square w-full bg-permukaan-100"
                />
                {satu.Utama && <Badge className="absolute top-2 left-2">Utama</Badge>}
              </button>
              {bolehKelola && (
                <div className="flex items-center justify-between gap-1 px-2 py-1.5">
                  <Button
                    variant="ghost"
                    size="sm"
                    className="h-8 px-2"
                    disabled={satu.Utama || memproses === satu.BerkasId}
                    onClick={() => jadikanUtama(satu)}
                    aria-label={satu.Utama ? 'Sudah foto utama' : `Jadikan foto ${indeks + 1} foto utama`}
                  >
                    <Star className="size-4" />
                    <span className="hidden sm:inline">{satu.Utama ? 'Utama' : 'Jadikan utama'}</span>
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    className="h-8 px-2 text-destructive hover:text-destructive"
                    disabled={memproses === satu.BerkasId}
                    onClick={() => void hapus(satu)}
                    aria-label={`Hapus foto ${indeks + 1}`}
                  >
                    <Trash2 className="size-4" />
                  </Button>
                </div>
              )}
            </li>
          ))}
        </ul>
      )}

      <Dialog open={aktif !== null} onOpenChange={(buka) => !buka && setTerbuka(null)}>
        <DialogContent className="max-w-[calc(100%-1rem)] gap-3 p-3 sm:max-w-4xl sm:p-4">
          {aktif && terbuka !== null && (
            <>
              <DialogTitle className="pr-8 text-base">
                Foto {terbuka + 1} dari {foto.length}
                {aktif.Utama && (
                  <Badge variant="secondary" className="ms-2 align-middle">
                    Foto utama
                  </Badge>
                )}
              </DialogTitle>
              <DialogDescription>
                Diunggah {new Date(aktif.DibuatPada).toLocaleString('id-ID')}
              </DialogDescription>
              <div className="relative flex items-center justify-center rounded-sm bg-grafit-950">
                <img
                  src={aktif.UrlUnduh}
                  alt={`Foto ${terbuka + 1} ${aset.Nama}`}
                  className="max-h-[70vh] w-auto max-w-full object-contain"
                />
                {foto.length > 1 && (
                  <>
                    <Button
                      variant="secondary"
                      size="icon"
                      className="absolute top-1/2 left-2 -translate-y-1/2 rounded-full"
                      onClick={() => setTerbuka((terbuka - 1 + foto.length) % foto.length)}
                      aria-label="Foto sebelumnya"
                    >
                      <ChevronLeft className="size-5" />
                    </Button>
                    <Button
                      variant="secondary"
                      size="icon"
                      className="absolute top-1/2 right-2 -translate-y-1/2 rounded-full"
                      onClick={() => setTerbuka((terbuka + 1) % foto.length)}
                      aria-label="Foto berikutnya"
                    >
                      <ChevronRight className="size-5" />
                    </Button>
                  </>
                )}
              </div>
              {bolehKelola && (
                <div className="flex flex-wrap justify-end gap-2">
                  <Button
                    variant="outline"
                    disabled={aktif.Utama || memproses === aktif.BerkasId}
                    onClick={() => jadikanUtama(aktif)}
                  >
                    <Star className="size-4" />
                    {aktif.Utama ? 'Sudah foto utama' : 'Jadikan foto utama'}
                  </Button>
                  <Button
                    variant="destructive"
                    disabled={memproses === aktif.BerkasId}
                    onClick={() => void hapus(aktif)}
                  >
                    <Trash2 className="size-4" />
                    Hapus
                  </Button>
                </div>
              )}
            </>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
