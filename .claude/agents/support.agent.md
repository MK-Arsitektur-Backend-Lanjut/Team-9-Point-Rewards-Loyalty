---
name: support
description: Describe what this custom agent does and when to use it.
tools: Read, Grep, Glob, Bash # specify the tools this agent can use. If not set, all enabled tools are allowed.
---

<!-- Tip: Use /create-agent in chat to generate content with agent assistance -->

Aku sedang mengembangkan sistem backend berbasis Laravel menggunakan arsitektur Service + Repository Pattern.

Saat ini sudah tersedia Modul 1 (Activity Rules & Rewards) dengan fitur:

Fitur yang sudah ada:
Activity Rules (activity_code → point_value)
Endpoint: POST /api/activity/trigger → untuk menambahkan poin user
Poin user disimpan di tabel users
Log aktivitas disimpan di point_activity_logs
Reward catalog (points_required, stock, is_physical)
Endpoint pengurangan stok: POST /api/rewards/{id}/decrement-stock
Flow yang sudah berjalan:
Modul lain memanggil endpoint activity
Sistem membaca activity_rules
Sistem menambahkan poin ke user
Aktivitas dicatat di point_activity_logs

Sekarang aku ingin kamu membantu mengembangkan modul berikutnya yaitu: [Modul Membership
Tiering & Referral System] 

deskripsi untuk Modul Membership Tiering & Referral System atau modul 4

API Kalkulasi Tier otomatis, generator kode referral, dan logika pengali
poin (point multiplier) berdasarkan level user.

Yang aku butuhkan:
Buatkan flow sistem yang terintegrasi dengan Modul 1
Tentukan endpoint API yang perlu dibuat
Jelaskan business logic yang harus ada
Pastikan tidak overlap dengan Modul 1
Gunakan Laravel dengan pendekatan Service + Repository Pattern
Jelaskan validasi yang diperlukan
Constraint:
Jangan mengubah logic di Modul 1
Gunakan data dari Modul 1 (points, rewards, stock)
Fokus pada integrasi antar modul
Output yang diharapkan:
Flow sistem (dalam bentuk step by step)
Daftar endpoint API
Penjelasan business logic
Struktur folder (Controller, Service, Repository)
Catatan:
points_required = jumlah poin yang dibutuhkan untuk reward
Modul 1 tidak menangani proses redeem (itu bagian modul ini)