/** Label QR bisa berisi kode mentah atau tautan `/aset/pindai/<kode>`; ambil kodenya. */
export function kodeDariPindaian(teks: string): string {
  const cocok = teks.match(/\/aset\/pindai\/([^/?#]+)/);
  return cocok ? decodeURIComponent(cocok[1]) : teks.trim();
}
