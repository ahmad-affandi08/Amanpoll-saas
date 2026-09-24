/** Sisi terpanjang foto yang disimpan dan diunggah; cukup untuk bukti pekerjaan. */
const SISI_MAKS = 1600;

/**
 * Memperkecil foto kamera menjadi JPEG sebelum disimpan di perangkat atau diunggah.
 * Foto HP modern bisa belasan MB dan berformat yang tidak diterima server (HEIC);
 * hasilnya JPEG ±300 KB. Bila peramban tidak dapat membacanya, berkas asli dipakai.
 */
export async function perkecilFoto(berkas: File | Blob): Promise<Blob> {
  try {
    const gambar = await createImageBitmap(berkas);
    const skala = Math.min(1, SISI_MAKS / Math.max(gambar.width, gambar.height));
    const kanvas = document.createElement('canvas');
    kanvas.width = Math.round(gambar.width * skala);
    kanvas.height = Math.round(gambar.height * skala);
    const konteks = kanvas.getContext('2d');
    if (!konteks) return berkas;
    konteks.drawImage(gambar, 0, 0, kanvas.width, kanvas.height);
    gambar.close();

    return await new Promise<Blob>((selesai) =>
      kanvas.toBlob((hasil) => selesai(hasil ?? berkas), 'image/jpeg', 0.82),
    );
  } catch {
    return berkas;
  }
}
