# Project Log — Sistem Pengurusan Komisyen Affiliate TikTok

Fail ini adalah log rekod berterusan untuk projek ni: semua perubahan (features, bugfix, migration),
pengiraan/formula komisyen yang digunakan, keputusan teknikal, dan isu/TODO yang masih outstanding.

Tujuan: supaya ada satu tempat rujukan sejarah projek tanpa perlu korek git log / chat lama.
Dokumentasi rasmi sistem (cara guna, install, rules) tetap di [README.md](README.md).
Testing scenarios tetap di [TESTING_NOTES.md](TESTING_NOTES.md).

> Cara update: setiap kali ada perubahan (code, schema, business rule, keputusan), tambah entri baru
> di bahagian **Log Perubahan** (paling atas = paling baru), dan kemaskini **Pengiraan Komisyen Semasa**
> jika rate/rule berubah.

---

## Status Semasa (2026-09-29)

- **Stack**: Laravel / PHP, MySQL, Blade, Tailwind CSS.
- **Scope teras**: affiliate hierarchy (upline/downline), TikTok account management, CSV order import,
  monthly commission calculation, commission report (Admin) & dashboard (Affiliate).
- **Belum diimplement** (out of scope tugasan asal): advanced recruitment flow, payout processing,
  full analytics dashboard.

---

## Pengiraan Komisyen Semasa (rujukan cepat)

Base pengiraan: `estimated_commission_base` (dari CSV column `Est. Commission Base`).
Order eligible: `order_status = Settled` sahaja.

| Type | Rate | Syarat |
|---|---:|---|
| Personal Commission | 10% | Seller affiliate, semua order settled sendiri |
| Manager Bonus | +1% | Seller ada ≥1 direct downline DAN tiada upline (tambahan atas jualan sendiri; manager yang ada upline = RM0) |
| Overriding L1 | 1% | Direct upline seller — terpakai sama ada seller ada downline atau tidak |
| L1 Split (bila threshold dicapai) | 70% seller / 30% upline | Lihat syarat threshold di bawah |
| Overriding L2 | 0.3% | Second upline |
| Overriding L3 | 0.2% | Third upline |

**L1 Overriding Split (70/30)** — trigger bila SEMUA syarat berikut dipenuhi serentak:
- Seller ada direct downline, DAN
- Jualan personal seller ≥ RM20,000 (bulan semasa), DAN
- Jualan personal seller + jualan direct downline ≥ RM100,000 (bulan semasa)

Bila trigger: 70% daripada Overriding L1 kekal kepada seller (`l1_split_seller`), 30% ke upline
(`l1_split_upline`). Jika tak capai threshold, Overriding L1 normal (100% ke upline, `l1_overriding`).

**Nota teknikal penting**: status "ada direct downline" dikira **dinamik** dari `affiliates.upline_id`
setiap kali commission run, BUKAN dari flag `is_manager` pada table `affiliates` (column tu wujud dalam
schema tapi dah tak dipakai oleh `CommissionCalculatorService` sebab tak auto-update bila hierarchy
berubah). Jangan guna `is_manager` untuk logic komisyen baru.

Monthly commission run guna `Time Commission Paid` sebagai tarikh utama, fallback ke `Time Created`
jika kosong.

### Contoh pengiraan (baseline test case)

Ali jual RM5,000 (settled), Ali tiada direct downline, Abu = direct upline Ali:

| Affiliate | Type | Amount |
|---|---|---:|
| Ali | Personal Commission 10% | RM500 |
| Abu | Overriding L1 1% | RM50 |
| **Total** | | **RM550** |

---

## Log Perubahan

### 2026-09-29
- Buat `PROJECT_LOG.md` untuk mula rekod berpusat semua perubahan/pengiraan/keputusan projek akan datang.

### 2026-09-28
- Migration `2026_09_28_090000_add_current_password_to_users_table.php`: tambah column
  `current_password` (text, nullable, encrypted via `APP_KEY`) pada table `users`.
  - **Sebab**: Admin minta boleh lihat password semasa affiliate (walaupun affiliate dah tukar sendiri)
    untuk tujuan support.
  - **Nota keselamatan**: column ni encrypted & reversible — **bukan** plaintext, dan **bukan**
    pengganti `password` (bcrypt one-way hash) yang digunakan untuk authentication sebenar. Trade-off
    keselamatan ni accepted secara eksplisit oleh admin/stakeholder.
  - README dikemaskini (11:26) selari dengan state semasa sistem.
- `Affiliate Login Access Report.pdf` ditambah dalam root project (16:59) — rujukan/laporan akses login
  affiliate.

### 2026-09-27 (baseline / initial commit state)
- Struktur projek asal: Laravel app dengan modules — Auth & role-based access, Admin Affiliate
  Management, TikTok Account Management, CSV Upload & Order Parsing, Commission Calculation Engine,
  Admin Commission Report, Affiliate Dashboard.
- Commission engine schema (`2026_05_10_..._create_affiliate_commission_tables.php`) dan siri migration
  sokongan: commission rate settings, password reset audit, excel import fields, affiliate referrals,
  affiliate applications, TikTok account requests, badges, order imports, `is_manager` flag (legacy,
  tak dipakai — lihat nota di atas), TikTok Shop tokens.
- `TESTING_NOTES.md` — semua test case baseline (login admin, affiliate management, TikTok account,
  CSV import, commission calculation, dashboard Ali/Abu) — status: **Pass**.
- `GAMIFICATION_PROPOSAL.md` wujud sebagai cadangan (belum diimplement dalam core system).

---

## Isu / TODO Terbuka

- [ ] Tiada item terbuka direkod setakat ni — tambah di sini bila jumpa bug/keputusan yang perlu buat.

---

## Cara Guna Fail Ni Akan Datang

1. Setiap kali ada perubahan code/schema/business rule → tambah entri baru di atas **Log Perubahan**
   (letak entri terbaru di paling atas bahagian tarikh yang sama, atau tarikh baru di paling atas sekali).
2. Kalau rate/formula komisyen berubah → update bahagian **Pengiraan Komisyen Semasa** supaya sentiasa
   reflect rule yang betul-betul jalan dalam code (`CommissionCalculatorService`), bukan rule lama.
3. Kalau ada keputusan teknikal penting (contoh: kenapa guna encrypted column bukan plaintext) → catat
   sebab dia, bukan setakat apa yang berubah.
