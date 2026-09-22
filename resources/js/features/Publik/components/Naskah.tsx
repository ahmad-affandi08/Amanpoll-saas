interface Props {
  isi: string;
}

type Blok =
  | { jenis: 'judul'; tingkat: 2 | 3 | 4; teks: string }
  | { jenis: 'kutipan'; teks: string }
  | { jenis: 'kode'; teks: string }
  | { jenis: 'daftar'; butir: string[] }
  | { jenis: 'paragraf'; teks: string };

/** Sengaja hanya lima bentuk; sisanya tampil apa adanya sebagai paragraf. */
function susun(isi: string): Blok[] {
  const baris = isi.replace(/\r\n/g, '\n').split('\n');
  const blok: Blok[] = [];
  let paragraf: string[] = [];
  let daftar: string[] = [];
  let kode: string[] | null = null;

  const tutupParagraf = () => {
    if (paragraf.length > 0) {
      blok.push({ jenis: 'paragraf', teks: paragraf.join(' ') });
      paragraf = [];
    }
  };

  const tutupDaftar = () => {
    if (daftar.length > 0) {
      blok.push({ jenis: 'daftar', butir: daftar });
      daftar = [];
    }
  };

  for (const satu of baris) {
    if (satu.trimEnd().startsWith('```')) {
      if (kode === null) {
        tutupParagraf();
        tutupDaftar();
        kode = [];
      } else {
        blok.push({ jenis: 'kode', teks: kode.join('\n') });
        kode = null;
      }
      continue;
    }

    if (kode !== null) {
      kode.push(satu);
      continue;
    }

    if (satu.trim() === '') {
      tutupParagraf();
      tutupDaftar();
      continue;
    }

    const judul = /^(#{2,4})\s+(.*)$/.exec(satu);

    if (judul) {
      tutupParagraf();
      tutupDaftar();
      blok.push({ jenis: 'judul', tingkat: judul[1].length as 2 | 3 | 4, teks: judul[2] });
      continue;
    }

    const butir = /^\s*[-*]\s+(.*)$/.exec(satu);

    if (butir) {
      tutupParagraf();
      daftar.push(butir[1]);
      continue;
    }

    const kutipan = /^>\s?(.*)$/.exec(satu);

    if (kutipan) {
      tutupParagraf();
      tutupDaftar();
      blok.push({ jenis: 'kutipan', teks: kutipan[1] });
      continue;
    }

    tutupDaftar();
    paragraf.push(satu.trim());
  }

  tutupParagraf();
  tutupDaftar();

  if (kode !== null && kode.length > 0) {
    blok.push({ jenis: 'kode', teks: kode.join('\n') });
  }

  return blok;
}

/** Naskah konten CMS; React merender teksnya sebagai node, bukan sebagai HTML. */
export function Naskah({ isi }: Props) {
  return (
    <div className="space-y-4">
      {susun(isi).map((blok, urutan) => {
        const kunci = `${blok.jenis}-${urutan}`;

        if (blok.jenis === 'judul') {
          const kelas =
            blok.tingkat === 2 ? 'text-2xl font-semibold' : blok.tingkat === 3 ? 'text-xl font-semibold' : 'text-lg font-semibold';

          return (
            <p key={kunci} className={`${kelas} mt-8 first:mt-0`} role="heading" aria-level={blok.tingkat}>
              {blok.teks}
            </p>
          );
        }

        if (blok.jenis === 'daftar') {
          return (
            <ul key={kunci} className="list-disc space-y-1 pl-6">
              {blok.butir.map((butir, ke) => (
                <li key={`${kunci}-${ke}`}>{butir}</li>
              ))}
            </ul>
          );
        }

        if (blok.jenis === 'kutipan') {
          return (
            <blockquote key={kunci} className="border-l-4 pl-4 text-muted-foreground italic">
              {blok.teks}
            </blockquote>
          );
        }

        if (blok.jenis === 'kode') {
          return (
            <pre key={kunci} className="overflow-x-auto rounded-md bg-muted p-4 text-sm">
              <code>{blok.teks}</code>
            </pre>
          );
        }

        return (
          <p key={kunci} className="leading-7">
            {blok.teks}
          </p>
        );
      })}
    </div>
  );
}
