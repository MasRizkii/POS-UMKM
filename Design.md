---
name: Web POS Mobile-First UMKM
description: Sistem point-of-sale berbasis web yang dioptimalkan sepenuhnya untuk smartphone, terinspirasi oleh UI food delivery modern.
colors:
  primary: "#EF5A35"
  primary-subtle: "#FFF1EE"
  background: "#F8F9FA"
  surface: "#FFFFFF"
  text-primary: "#1F2937"
  text-secondary: "#6B7280"
  border: "#E5E7EB"
  success: "#10B981"
  error: "#EF4444"
typography:
  fontFamily: "'Inter', sans-serif"
  h1:
    fontSize: "1.5rem"
    fontWeight: 600
    lineHeight: 1.2
  h2:
    fontSize: "1.25rem"
    fontWeight: 600
    lineHeight: 1.3
  body-md:
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
  label-sm:
    fontSize: "0.75rem"
    fontWeight: 500
rounded:
  sm: "4px"
  md: "8px"
  lg: "16px"
  full: "9999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "24px"
  2xl: "32px"
components:
  card:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.lg}"
    padding: "{spacing.md}"
    boxShadow: "0 4px 6px -1px rgba(0, 0, 0, 0.05)"
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.surface}"
    rounded: "{rounded.md}"
    padding: "10px 16px"
  bottom-nav:
    backgroundColor: "{colors.surface}"
    borderTop: "1px solid {colors.border}"
    height: "64px"
---

## Overview
Desain POS yang difokuskan pada kecepatan dan kenyamanan penggunaan di smartphone oleh kasir F&B. Memadukan estetika *appetizing* (warna oranye vibrant) dengan struktur tata letak *mobile-first* yang ketat (berdasarkan PRD v0.3).

## Colors
Warna oranye (`#EF5A35`) bertindak sebagai panduan visual utama yang mengarahkan mata kasir ke aksi terpenting: Harga dan Tombol Checkout. Latar belakang *off-white* memisahkan kartu produk secara natural tanpa bayangan yang berat.

## Typography
Menggunakan Inter atau Nunito untuk memberikan kesan modern namun tetap *approachable*. Label harga dibuat menonjol (ukuran lebih besar dan *bold*) karena merupakan informasi transaksional paling krusial.

## Spacing & Layout
Grid 8px mendominasi. Layout dikunci untuk ukuran *smartphone* (360px - 430px) tanpa *horizontal scroll* pada kontainer utama. Kategori menggunakan *horizontal scroll* (chips) untuk menghemat ruang vertikal.

## Elevation & Depth
Menggunakan *subtle shadow* hanya pada *Product Cards* dan komponen yang mengambang di atas konten utama (*Sticky Cart Button* dan *Bottom Navigation*).

## Rules to Never Break
- Layout HARUS *mobile-first*, jangan gunakan tata letak *sidebar* desktop atau layar 3 panel.
- Keranjang (Cart) disembunyikan dalam wujud tombol *sticky* di atas *bottom navigation*, bukan memenuhi sisi kanan layar.
- Area sentuh (Touch target) minimal 44x44px.