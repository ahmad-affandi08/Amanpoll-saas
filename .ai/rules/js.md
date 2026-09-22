---
paths:
  - 'resources/js/**'
---

# Js

## Jebakan useForm Inertia yang tidak terlihat TypeScript
Dua hal ini lolos `tsc` dan baru ketahuan sebagai bug perilaku.

**1. `setData(objek)` mengganti seluruh data, bukan menggabung.** Tipenya `Partial<TForm>` sehingga objek separuh diterima diam-diam. Untuk pembaruan sebagian, sebar dulu:
```ts
form.setData({ ...form.data, Nama: nilai });
```

**2. `useForm` mengunci nilai awal saat mount.** Dialog yang dirender sekali lalu dipakai berulang (dialog bersama, bukan per baris) akan menampilkan nilai baris sebelumnya. Segarkan saat dibuka:
```ts
const ubahBuka = (terbuka: boolean) => {
  if (terbuka) { form.setData(nilaiAwal(item)); form.clearErrors(); }
  setBuka(terbuka);
};
```

Catatan: `noUnusedLocals` mati di tsconfig, jadi impor yatim tidak tertangkap gerbang mana pun. Periksa sendiri setelah memindahkan komponen.
