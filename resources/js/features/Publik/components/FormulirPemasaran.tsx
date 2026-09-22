import { FormEvent } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { FieldPublik, FormulirPublik, KonfigurasiCaptcha } from '../types';
import { rutePublik } from '@/features/Publik/api';

/** Nama field perangkap. */
const FIELD_HONEYPOT = 'situs_perusahaan';

interface Props {
  formulir: FormulirPublik;
  judul?: string;
  deskripsi?: string;
}

type NilaiField = string | boolean | string[];

function nilaiAwal(field: FieldPublik): NilaiField {
  if (field.Jenis === 'KotakCentang' || field.Jenis === 'Persetujuan') {
    return false;
  }

  return field.Jenis === 'PilihanGanda' ? [] : '';
}

/** Formulir pemasaran yang bentuknya datang dari server (MARKETING.md 10). */
export function FormulirPemasaran({ formulir, judul, deskripsi }: Props) {
  const { props } = usePage<{ unduhan?: string | null }>();
  const unduhan = props.unduhan ?? null;

  const awal: Record<string, NilaiField> = { [FIELD_HONEYPOT]: '' };
  formulir.Field.forEach((field) => {
    awal[field.Kode] = nilaiAwal(field);
  });

  const form = useForm(awal);

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    form.post(rutePublik.formulir(formulir.Kode), {
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  };

  const galat = (kode: string) => (form.errors as Record<string, string | undefined>)[kode];

  return (
    <form onSubmit={kirim} className="grid gap-4">
      {judul ? <h2 className="text-xl font-semibold tracking-tight">{judul}</h2> : null}
      {deskripsi ? <p className="text-sm text-muted-foreground">{deskripsi}</p> : null}

      {/* Perangkap bot. */}
      <div className="absolute left-[-9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
        <label htmlFor={FIELD_HONEYPOT}>Situs perusahaan</label>
        <input
          id={FIELD_HONEYPOT}
          name={FIELD_HONEYPOT}
          type="text"
          tabIndex={-1}
          autoComplete="off"
          value={String(form.data[FIELD_HONEYPOT] ?? '')}
          onChange={(e) => form.setData(FIELD_HONEYPOT, e.target.value)}
        />
      </div>

      {formulir.Field.map((field) => (
        <div key={field.Kode} className="grid gap-2">
          {field.Jenis === 'Persetujuan' || field.Jenis === 'KotakCentang' ? (
            <label className="flex items-start gap-2 text-sm">
              <Checkbox
                checked={form.data[field.Kode] === true}
                onCheckedChange={(nilai) => form.setData(field.Kode, nilai === true)}
              />
              <span>
                {field.Label}
                {field.Wajib ? <span className="text-destructive"> *</span> : null}
              </span>
            </label>
          ) : (
            <>
              <Label htmlFor={field.Kode}>
                {field.Label}
                {field.Wajib ? <span className="text-destructive"> *</span> : null}
              </Label>
              <IsianField
                field={field}
                nilai={form.data[field.Kode]}
                ubah={(nilai) => form.setData(field.Kode, nilai)}
              />
            </>
          )}

          {field.Bantuan ? <p className="text-sm text-muted-foreground">{field.Bantuan}</p> : null}
          {galat(field.Kode) ? <p className="text-sm text-destructive">{galat(field.Kode)}</p> : null}
        </div>
      ))}

      {formulir.Captcha ? <Captcha konfigurasi={formulir.Captcha} /> : null}

      <div>
        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'Mengirim...' : 'Kirim'}
        </Button>
      </div>

      {/* Tautan unduhan baru ada setelah formulirnya benar-benar terkirim. */}
      {unduhan ? (
        <div className="rounded-lg border p-4">
          <p className="text-sm">Terima kasih. Berkas Anda siap diunduh.</p>
          <Button asChild variant="outline" size="sm" className="mt-3">
            <a href={unduhan}>Unduh berkas</a>
          </Button>
        </div>
      ) : null}
    </form>
  );
}

/** Widget CAPTCHA. */
function Captcha({ konfigurasi }: { konfigurasi: KonfigurasiCaptcha }) {
  if (konfigurasi.KunciSitus === null || konfigurasi.Skrip === null) {
    return (
      <p className="text-sm text-destructive">
        Formulir ini memerlukan verifikasi anti-spam yang belum dikonfigurasi.
      </p>
    );
  }

  return (
    <>
      <Head>
        <script src={konfigurasi.Skrip} async defer />
      </Head>
      <div
        className="cf-turnstile"
        data-sitekey={konfigurasi.KunciSitus}
        data-response-field-name={konfigurasi.NamaField}
      />
    </>
  );
}

function IsianField({
  field,
  nilai,
  ubah,
}: {
  field: FieldPublik;
  nilai: NilaiField;
  ubah: (nilai: NilaiField) => void;
}) {
  const teks = typeof nilai === 'string' ? nilai : '';

  switch (field.Jenis) {
    case 'AreaTeks':
      return (
        <Textarea
          id={field.Kode}
          rows={4}
          placeholder={field.Placeholder ?? undefined}
          value={teks}
          onChange={(e) => ubah(e.target.value)}
        />
      );

    case 'Pilihan':
      return (
        <Select value={teks} onValueChange={ubah}>
          <SelectTrigger id={field.Kode}>
            <SelectValue placeholder={field.Placeholder ?? 'Pilih salah satu'} />
          </SelectTrigger>
          <SelectContent>
            {field.Pilihan.map((satu) => (
              <SelectItem key={satu} value={satu}>
                {satu}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      );

    case 'Radio':
      return (
        <RadioGroup value={teks} onValueChange={ubah}>
          {field.Pilihan.map((satu) => (
            <label key={satu} className="flex items-center gap-2 text-sm">
              <RadioGroupItem value={satu} />
              {satu}
            </label>
          ))}
        </RadioGroup>
      );

    case 'PilihanGanda': {
      const terpilih = Array.isArray(nilai) ? nilai : [];

      return (
        <div className="grid gap-2 sm:grid-cols-2">
          {field.Pilihan.map((satu) => (
            <label key={satu} className="flex items-center gap-2 text-sm">
              <Checkbox
                checked={terpilih.includes(satu)}
                onCheckedChange={(dipilih) =>
                  ubah(dipilih === true ? [...terpilih, satu] : terpilih.filter((x) => x !== satu))
                }
              />
              {satu}
            </label>
          ))}
        </div>
      );
    }

    default:
      return (
        <Input
          id={field.Kode}
          type={tipeInput(field.Jenis)}
          placeholder={field.Placeholder ?? undefined}
          value={teks}
          onChange={(e) => ubah(e.target.value)}
        />
      );
  }
}

function tipeInput(jenis: string): string {
  switch (jenis) {
    case 'Email':
      return 'email';
    case 'Telepon':
      return 'tel';
    case 'Angka':
      return 'number';
    default:
      return 'text';
  }
}
