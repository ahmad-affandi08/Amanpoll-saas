import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { KonfirmasiPenerimaPerintahKerja, PerintahKerja } from '@/features/PerintahKerja/types';

function formatTanggal(nilai: string): string {
  return new Date(nilai).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}

/**
 * Kartu "Konfirmasi penerima" di detail perintah kerja (PRD 8.22): cara, penerima, jabatan,
 * waktu, penilaian atau alasan, dan tanda tangan yang dicap. Jawaban siklus sebelumnya
 * (dibuka kembali, atau "masih bermasalah") tetap tampil sebagai riwayat.
 */
export function KartuKonfirmasiPenerima({
  perintahKerja,
  konfirmasi,
  wajib,
}: {
  perintahKerja: PerintahKerja;
  konfirmasi: KonfirmasiPenerimaPerintahKerja[];
  wajib: boolean;
}) {
  const berlaku = konfirmasi.find((satu) => satu.Berlaku);
  const riwayat = konfirmasi.filter((satu) => satu !== berlaku);

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between gap-2">
        <CardTitle>Konfirmasi Penerima</CardTitle>
        {berlaku ? (
          <Badge variant="sukses">Diterima</Badge>
        ) : perintahKerja.MenungguKonfirmasiPenerima ? (
          <Badge variant="perhatian">Menunggu konfirmasi penerima</Badge>
        ) : (
          <Badge variant="outline">{wajib ? 'Wajib' : 'Opsional'}</Badge>
        )}
      </CardHeader>
      <CardContent className="space-y-4 text-sm">
        {berlaku ? (
          <RincianKonfirmasi konfirmasi={berlaku} />
        ) : (
          <p className="text-xs text-muted-foreground">
            {perintahKerja.MenungguKonfirmasiPenerima
              ? 'Teknisi sudah menyerahkan pekerjaan. Penerima bisa mengonfirmasi lewat aplikasi pelapor, pindai QR dari HP teknisi, atau tanda tangan di HP teknisi.'
              : 'Belum ada konfirmasi penerima untuk penyelesaian ini.'}
            {wajib && ' Organisasimu mewajibkan konfirmasi sebelum perintah kerja diverifikasi.'}
          </p>
        )}

        {riwayat.length > 0 && (
          <div className="border-t border-border pt-3">
            <div className="mb-2 text-xs font-medium text-grafit-500">Jawaban sebelumnya</div>
            <ul className="space-y-2">
              {riwayat.map((satu) => (
                <li key={satu.Id} className="text-xs">
                  <span className="font-medium text-foreground">{satu.NamaPenerima}</span>
                  <span className="text-muted-foreground">
                    {' '}
                    · {satu.Hasil === 'MasihBermasalah'
                      ? 'Masih bermasalah'
                      : 'Diterima (siklus lama)'} · {formatTanggal(satu.DikonfirmasiPada)}
                  </span>
                  {satu.Alasan && <p className="mt-0.5 text-muted-foreground">{satu.Alasan}</p>}
                </li>
              ))}
            </ul>
          </div>
        )}
      </CardContent>
    </Card>
  );
}

function RincianKonfirmasi({ konfirmasi }: { konfirmasi: KonfirmasiPenerimaPerintahKerja }) {
  return (
    <>
      <dl className="grid grid-cols-2 gap-3">
        <div>
          <dt className="text-xs text-muted-foreground">Penerima</dt>
          <dd className="font-medium">{konfirmasi.NamaPenerima}</dd>
          {konfirmasi.JabatanPenerima && (
            <dd className="text-xs text-muted-foreground">{konfirmasi.JabatanPenerima}</dd>
          )}
        </div>
        <div>
          <dt className="text-xs text-muted-foreground">Cara</dt>
          <dd className="font-medium">{konfirmasi.LabelMetode}</dd>
        </div>
        <div>
          <dt className="text-xs text-muted-foreground">Waktu</dt>
          <dd className="font-medium">{formatTanggal(konfirmasi.DikonfirmasiPada)}</dd>
        </div>
        {konfirmasi.Penilaian !== null && (
          <div>
            <dt className="text-xs text-muted-foreground">Penilaian</dt>
            <dd className="font-medium">{konfirmasi.Penilaian} dari 5</dd>
          </div>
        )}
      </dl>
      {konfirmasi.Ulasan && <p className="text-muted-foreground">“{konfirmasi.Ulasan}”</p>}
      {konfirmasi.UrlTandaTangan && (
        <div>
          <div className="mb-1 text-xs text-muted-foreground">Tanda tangan</div>
          <img
            src={konfirmasi.UrlTandaTangan}
            alt={`Tanda tangan ${konfirmasi.NamaPenerima}`}
            className="h-24 w-full rounded-md border border-border bg-muted/30 object-contain p-2"
          />
        </div>
      )}
    </>
  );
}
