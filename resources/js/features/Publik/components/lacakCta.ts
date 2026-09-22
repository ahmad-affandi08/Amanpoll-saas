/**
 * Mencatat satu klik CTA sebelum pengunjung berpindah. Pencatatannya tidak
 * pernah boleh menahan navigasi, jadi kegagalannya diabaikan (MARKETING.md 23).
 */
export function lacakCta(label: string | null, tujuan: string | null, sumber?: string): void {
  // Token dikirim di badan permintaan supaya perlindungan CSRF tetap berlaku.
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

  void fetch('/cta', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ _token: token, Label: label, Tujuan: tujuan, Sumber: sumber ?? null }),
    // Permintaan tetap berangkat walau halamannya sudah berpindah.
    keepalive: true,
  }).catch(() => undefined);
}
