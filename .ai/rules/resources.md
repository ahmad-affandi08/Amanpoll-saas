---
paths:
  - 'resources/**'
---

# Resources

## Amanpoll tidak punya dark mode
PRD 14. Palet hanya terang. Jangan menulis kelas `dark:` apa pun di luar `resources/js/components/ui/` (vendor shadcn).

`resources/css/app.css` menimpa variannya ke selektor yang tidak pernah ada:
`@custom-variant dark (&:where([data-tema="gelap"] *));`
Tanpa penimpaan itu, satu kelas `dark:` yang terbawa akan benar-benar menyala di perangkat bertema gelap, di atas palet terang yang tidak punya pasangannya.

Dijaga `tests/Feature/Core/TanpaDarkModeTest.php`. Warna diambil dari token di `app.css` (`bg-permukaan-100`, `text-grafit-700`, `border-border`), bukan warna Tailwind mentah.
