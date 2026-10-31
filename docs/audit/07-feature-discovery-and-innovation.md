# Phase 4 — Product Innovation & Feature Discovery

**Visi Transformasi:** Dari Sekadar Aplikasi Pencatatan Silsilah Menjadi **Modern Digital Family Heritage Platform**  
**Prinsip Desain:** Utamakan Nilai Guna, Hindari Kompleksitas yang Berlebihan, Jamin Keakuratan Sejarah Keluarga.

---

## 1. Digital Family Archive (Arsip & Kenangan Digital Keluarga)

### Inovasi 1.1: Lembar Riwayat Hidup & Cerita Leluhur (*Ancestor Life Stories*)
- **Masalah Pengguna:** Aplikasi saat ini hanya menyimpan data faktual ringkas (nama, tanggal, tempat lahir/wafat). Kisah perjuangan, nilai-nilai moral, dan riwayat hidup leluhur hilang seiring pergantian generasi.
- **Solusi Produk:** Tab khusus *"Kisah & Kenangan"* pada profil anggota untuk menyimpan narasi biografi, rekaman suara cerita keluarga (*voice notes*), dan kutipan pesan wasiat leluhur.
- **Kompleksitas:** Medium (M) | **Prioritas:** P2.

### Inovasi 1.2: Galeri Foto Lintas Masa (*Generational Timeline Gallery*)
- **Masalah Pengguna:** Foto profil hanya mendukung 1 foto per anggota. Perubahan rupa dari masa kanak-kanak, remaja, pernikahan, hingga usia senja tidak dapat didokumentasikan.
- **Solusi Produk:** Album foto berbasis linimasa tahun untuk setiap anggota, lengkap dengan takarir (*caption*) dan penandaan lokasi.
- **Kompleksitas:** Medium (M) | **Prioritas:** P2.

### Inovasi 1.3: Digital Memorial & Titik Makam (*Cemetery Geo-Tagging*)
- **Masalah Pengguna:** Generasi cucu/cicit sering tidak mengetahui lokasi makam leluhur saat tradisi ziarah keluarga atau mudik lebaran.
- **Solusi Produk:** Koordinat GPS makam yang terhubung langsung dengan Google Maps / Waze, disertai foto nisan dan panduan arah di area pemakaman.
- **Kompleksitas:** Low (S) | **Prioritas:** P2.

---

## 2. Family Intelligence (Kecerdasan & Eksplorasi Silsilah)

### Inovasi 2.1: Kinship Path Finder Interaktif ("Bagaimana Saya Terhubung dengan X?")
- **Masalah Pengguna:** Pada acara reuni keluarga besar dengan ratusan kerabat, orang sering bingung: *"Si Fulan ini sebenarnya hubungan kekerabatannya apa dengan saya dan lewat jalur siapa?"*
- **Solusi Produk:** Jalur visual langkah-demi-langkah dari User ke target kerabat (contoh: `Anda -> Ayah Anda (Ahmad) -> Kakek (Hasan) -> Paman (Budi) -> Sepupu (Fulan)`).
- **Kompleksitas:** Medium (M) | **Prioritas:** P1.

### Inovasi 2.2: Statistik & Demografi Warisan Keluarga (*Family Heritage Analytics*)
- **Masalah Pengguna:** Pengguna tidak memiliki gambaran makro mengenai dinamika keluarga besar mereka.
- **Solusi Produk:** Dashboard analitik silsilah:
  - Distribusi generasi (Generasi 1 sampai Generasi 7).
  - Usia harapan hidup rata-rata keluarga.
  - Bulan paling ramai kelahiran / pernikahan keluarga.
  - Peta persebaran domisili keturunan (Kota & Provinsi).
- **Kompleksitas:** Low-Medium (M) | **Prioritas:** P3.

---

## 3. Collaboration, Social & Preservation

### Inovasi 3.1: Antrean Usulan Kontribusi Keluarga (*Crowdsourced Contribution Queue*)
- **Masalah Pengguna:** Pemilik pohon ingin keluarga besar membantu melengkapi data, namun takut data pohon dirusak atau salah edit oleh kerabat yang kurang paham.
- **Solusi Produk:** Mode *"Kontributor dengan Persetujuan"* (Approval Workflow). Anggota keluarga yang diberi link kontribusi dapat mengisi data anak/cucu mereka, tetapi data masuk ke tab antrean `Pending Approval` sebelum digabungkan ke master tree oleh Admin pohon.
- **Kompleksitas:** Medium-High (L) | **Prioritas:** P1.

### Inovasi 3.2: Standar Interoperabilitas GEDCOM (Import & Export)
- **Masalah Pengguna:** Pengguna yang sudah pernah mencatat silsilah di software lain (seperti MyHeritage, Ancestry, Gramps) malas mengetik ulang ratusan nama satu per satu.
- **Solusi Produk:** Parser dan generator file standar industri `.ged` (GEDCOM 5.5.1 / 7.0).
- **Kompleksitas:** High (L) | **Prioritas:** P2.

### Inovasi 3.3: Cetak Poster Silsilah Format Besar (*High-Res Printable Poster*)
- **Masalah Pengguna:** Pengguna ingin memajang bagan silsilah berbingkai di dinding rumah atau membawa spanduk silsilah berukuran 2x1 meter pada acara reuni trah keluarga.
- **Solusi Produk:** Generator vector/PDF beresolusi tinggi (format A3, A2, A1, hingga A0) dengan ornamen kaligrafi/bingkai keluarga nusantara yang siap cetak.
- **Kompleksitas:** Medium (M) | **Prioritas:** P2.

---

## 4. Grounded AI Features (Kecerdasan Buatan Terkendali)

> [!IMPORTANT]
> **Prinsip Anti-Halusinasi:** AI dalam silsilah keluarga dilarang keras mengarang nasab, mereka-reka tanggal, atau menyimpulkan hubungan tanpa data primer dari manusia.

### Inovasi 4.1: Asisten Penulis Biografi Keluarga (*AI Family Biographer*)
- **Cara Kerja:** Mengambil data faktual yang sudah tercatat (tempat lahir, pasangan, tanggal nikah, daftar anak, profesi, riwayat pendidikan) dan menyusunnya menjadi draf artikel naratif yang hangat dan mengalir.
- **Guardrail:** Parameter prompt AI terkunci hanya menggunakan data yang ada di database (*grounded facts only*). Pengguna selalu diminta me-review draf sebelum disimpan.
- **Kompleksitas:** Medium (M) | **Prioritas:** P3.

### Inovasi 4.2: OCR Pembaca Dokumen Sejarah & Buku Nikah Lama
- **Cara Kerja:** Mengunggah foto Kartu Keluarga (KK), Akta Kelahiran lama, atau Buku Nikah kuno; Vision AI mengekstrak nama, tanggal lahir, dan nama orang tua untuk otomatis mengisi form anggota baru.
- **Guardrail:** Hasil ekstraksi ditampilkan berdampingan dengan gambar aslinya (*side-by-side verification*) agar pengguna memvalidasi sebelum disimpan.
- **Kompleksitas:** High (L) | **Prioritas:** P3.
