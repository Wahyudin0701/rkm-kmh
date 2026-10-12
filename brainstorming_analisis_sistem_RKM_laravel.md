# Brainstorming dan Analisis Sistem RKM untuk Migrasi ke Laravel

## 1. Konteks Proyek

Saya mendapatkan tugas untuk melakukan revisi dan pengembangan ulang terhadap sistem bernama **RKM**, yang akan dimigrasikan ke framework Laravel.

Sebelum memulai migrasi, saya ingin membedah sistem yang sudah ada untuk memahami struktur aplikasi, fungsi setiap modul, relasi antar-data, serta kesesuaiannya dengan kebutuhan bisnis yang baru.

Saya akan memberikan foto, file, dan/atau source code dari sistem lama sebagai bahan analisis.

Fokus utama tahap ini adalah mengevaluasi sistem lama dan menentukan komponen yang perlu dipertahankan, diperbaiki, disederhanakan, atau dihapus. Jangan langsung mengimplementasikan kode Laravel sebelum analisis dan rancangan sistem disepakati.

## 2. Gambaran Umum Sistem RKM

Sistem RKM dirancang untuk mendukung pengelolaan data kendaraan dan pencatatan aktivitas kendaraan perusahaan.

Komponen utama yang menjadi acuan awal adalah:

1. **Master Data HSE**: Mengelola data karyawan yang memiliki SIMPER.
2. **Master Data QMA**: Mengelola data kendaraan ringan perusahaan.
3. **Form Aktivitas Kendaraan**: Digunakan pengguna untuk mencatat aktivitas kendaraan melalui pemindaian QR Code.
4. **Dashboard dan Log Aktivitas QMA**: Digunakan QMA untuk memantau catatan aktivitas kendaraan.
5. **Modul Private Guard (PG)**: Digunakan petugas keamanan untuk memvalidasi aktivitas kendaraan.

Struktur tersebut merupakan gambaran kebutuhan awal dan perlu dicocokkan dengan implementasi sistem lama.

## 3. Master Data HSE

HSE bertanggung jawab mengelola data karyawan yang memiliki SIMPER (Surat Izin Mengemudi Perusahaan).

Atribut data karyawan meliputi:

- NIK
- Nama
- Unit Kerja:
  - Pembangkit
  - Bendungan
  - Teknik
  - Umum
- Divisi:
  - Audit
  - Bendungan (Operasional)
  - Bendungan (Operator)
  - Civil & Inviro
  - Civil Enviro
  - Enviro
  - GA
  - Geomonitoring
  - HR
  - INFORMATION & TECHNOLOGY
  - Inspeksi & Monitoring
  - Jaringan 20 Kv & Genset
  - K3
  - Keuangan
  - Logistik
  - Manajemen
  - Mechanical
  - Paramedis
  - Pembangkit (Engineering)
  - Pembangkit (Operasional)
  - Pembangkit (Pemeliharaan)
  - Perencanaan Teknik
  - PG
  - POSO
  - Public Relation & CSR
  - Quality Management Asset (QMA)
  - Regulating Weir
  - Sipil
  - Sipil & Enviro
  - Sipil & Lingkungan
  - SUBCON
  - Survey
- Posisi/Jabatan
- Operator/Driver
- Masa Berlaku SIMPER

Data karyawan ini menjadi referensi untuk mengidentifikasi pemakai kendaraan ketika aktivitas kendaraan dicatat.

Analisis juga harus mempertimbangkan bagaimana sistem menangani SIMPER yang kedaluwarsa atau data karyawan yang sudah tidak aktif, tanpa menetapkan aturan baru sebelum kebutuhan bisnisnya dikonfirmasi.

## 4. Master Data QMA

QMA (Quality Management Asset) merupakan divisi yang bertanggung jawab atas pengelolaan aset kendaraan dalam sistem RKM.

Pada pengembangan sistem ini, **QMA hanya mengelola kategori Kendaraan Ringan (Light Vehicle/LV)**. Kategori Kendaraan Berat (Unit Support) tidak digunakan dalam modul QMA dan tidak perlu dimasukkan ke dalam rancangan maupun migrasi modul ini.

Setiap kendaraan ringan memiliki atribut:

- LVCode
- LVBrandType
- Ownership
- Divisi
- Status
- Nopol
- HM/KM

Master data kendaraan ini menjadi referensi utama untuk proses pencatatan aktivitas kendaraan melalui QR Code dan penyajian data pada Dashboard serta Log Aktivitas QMA.

Dalam analisis sistem lama, identifikasi seluruh tabel, fitur, relasi, dan kode yang berkaitan dengan pengelolaan kendaraan berat. Tentukan apakah komponen tersebut dapat dikeluarkan dari ruang lingkup QMA tanpa mengganggu fungsi lain yang masih dibutuhkan. Jangan menghapus komponen dari sistem lama secara otomatis sebelum dependensinya diperiksa. Fokuskan rancangan sistem baru pada pengelolaan Kendaraan Ringan (LV).

## 5. Alur Pencatatan Aktivitas Kendaraan

Alur bisnis awal yang menjadi acuan adalah:

1. Pengguna membawa kendaraan yang telah terdaftar di sistem.
2. Pengguna memindai QR Code identitas kendaraan yang ditempelkan pada kendaraan.
3. Sistem mengidentifikasi kendaraan dan menyediakan form pencatatan aktivitas.
4. Pengguna mengisi data yang diperlukan, termasuk nama pemakai, gate, jenis aktivitas In/Out, HM/KM, dan rencana kerja.
5. Sistem menyimpan catatan aktivitas kendaraan.
6. Catatan tersebut ditampilkan pada log aktivitas di dashboard QMA.
7. Aktivitas kemudian masuk ke proses validasi oleh modul PG.

Detail validasi oleh PG, hubungan antara petugas dan gate, serta alur persetujuan atau penolakan akan dibahas pada tahap berikutnya.

## 6. Form Input Aktivitas Kendaraan

Form ini diakses melalui pemindaian QR Code identitas kendaraan.

Data yang diinputkan pengguna meliputi:

### A. Identitas Pemakai

Nama pemakai unit diambil dari master data HSE. Hubungan data harus menggunakan identitas karyawan yang konsisten, bukan hanya mengandalkan teks nama.

### B. Gate/Gerbang

Pilihan gate yang tersedia:

- Adit 1
- Adit 2
- Adit 3
- Bedeng 6
- Beringin Tunggal
- CCR DAM
- Crest DAM
- Dermaga Jakon
- Gudang Induk
- Lainnya
- Mess Kumun
- PH - Office
- PH - Utama
- Regulating Weir
- Vertical Shaft
- VS - TPST

### C. Jenis Aktivitas

- In
- Out

### D. HM/KM

Nilai Hour Meter atau Kilometer kendaraan pada saat aktivitas dicatat.

### E. Rencana Kerja

Keterangan mengenai rencana atau tujuan penggunaan kendaraan.

Identitas kendaraan sebaiknya diperoleh dari QR Code yang dipindai. Analisis perlu menentukan bagaimana data kendaraan tersebut dihubungkan dengan master data QMA dan bagaimana sistem mencegah pencatatan untuk kendaraan yang tidak terdaftar.

## 7. Dashboard dan Log Aktivitas QMA

Berdasarkan tampilan tabel yang diberikan, **Log Aktivitas QMA memiliki 10 kolom utama** dengan susunan sebagai berikut:

| No. | Kolom | Fungsi awal |
|---|---|---|
| 1 | Tanggal | Menampilkan tanggal aktivitas kendaraan. |
| 2 | Kode LV | Menampilkan kode identitas kendaraan ringan. |
| 3 | Unit | Menampilkan unit atau informasi unit kendaraan sesuai definisi pada sistem lama. |
| 4 | Penanggung Jawab | Menampilkan pihak yang bertanggung jawab atas kendaraan atau aktivitas. |
| 5 | Driver | Menampilkan nama pengemudi kendaraan. |
| 6 | Gate | Menampilkan lokasi gerbang tempat aktivitas dicatat. |
| 7 | Akses | Menampilkan informasi akses kendaraan sesuai definisi bisnis sistem lama. |
| 8 | KM Sebelumnya | Menampilkan nilai kilometer sebelumnya sebagai referensi perjalanan atau pencatatan terdahulu. |
| 9 | KM Berjalan | Menampilkan nilai kilometer pada aktivitas saat ini, sesuai definisi sistem lama. |
| 10 | Aktifitas | Menampilkan informasi aktivitas kendaraan. |

**Catatan:** Fungsi di atas merupakan interpretasi awal berdasarkan nama kolom pada gambar. Definisi pasti untuk kolom Unit, Penanggung Jawab, Akses, KM Sebelumnya, KM Berjalan, dan Aktifitas harus diverifikasi melalui sistem lama, source code, atau penjelasan proses bisnis.

### A. Hubungan Log dengan Master Data

Analisis harus menentukan asal data untuk setiap kolom.

- **Kode LV**: Seharusnya mengacu pada identitas kendaraan dalam master data QMA.
- **Unit**: Perlu dipastikan apakah merujuk pada kategori kendaraan, unit kerja, atau atribut lain.
- **Penanggung Jawab**: Perlu dipastikan apakah merupakan pemilik aset, penanggung jawab kendaraan, atau penanggung jawab aktivitas.
- **Driver**: Perlu dipetakan ke data karyawan HSE atau sumber data pengemudi yang digunakan sistem lama.
- **Gate**: Mengacu pada data atau daftar lokasi gerbang.
- **Akses**: Perlu dikonfirmasi apakah menunjukkan status atau jenis akses tertentu.
- **KM Sebelumnya**: Perlu ditentukan sumber nilai sebelumnya dan aturan pengambilannya.
- **KM Berjalan**: Perlu dipastikan apakah berasal dari input pengguna, hasil perhitungan, atau data lain.
- **Aktifitas**: Perlu dipastikan apakah menunjukkan In/Out, rencana kerja, status validasi, atau gabungan informasi aktivitas lainnya.
- **Tanggal**: Perlu dipastikan apakah menampilkan tanggal pencatatan, tanggal aktivitas, atau tanggal data divalidasi.

Jangan langsung membuat kolom baru atau mengubah arti kolom yang sudah ada sebelum fungsi bisnisnya dipastikan.

### B. Perilaku Tabel

Pada gambar, beberapa judul kolom menampilkan ikon pengurutan. Karena itu, periksa apakah tabel mendukung:

- Pengurutan data berdasarkan kolom.
- Pengurutan berdasarkan tanggal atau urutan aktivitas.
- Pencarian dan filter data, jika tersedia atau dibutuhkan.
- Penampilan data aktivitas terbaru dan riwayat aktivitas.
- Pembatasan akses data berdasarkan peran pengguna, jika diperlukan.

Periksa pula apakah data ditampilkan melalui tabel statis, pemrosesan backend, atau mekanisme lain. Jangan mengasumsikan bahwa semua fungsi tersebut sudah tersedia hanya berdasarkan tampilan visual.

### C. Konsistensi Data Kilometer

Salah satu fokus analisis adalah hubungan antara KM Sebelumnya dan KM Berjalan.

Pastikan apakah:

1. KM Sebelumnya diambil dari aktivitas kendaraan terakhir.
2. KM Berjalan merupakan nilai odometer saat aktivitas dicatat.
3. Selisih keduanya digunakan untuk menghitung jarak perjalanan.
4. Data kilometer disimpan pada setiap transaksi atau diperbarui langsung pada master kendaraan.
5. Sistem juga mendukung kendaraan yang menggunakan Hour Meter (HM), bukan odometer kilometer.

Jangan menetapkan rumus atau aturan validasi final sebelum mekanisme yang digunakan sistem lama dan kebutuhan bisnis dikonfirmasi.

## 8. Analisis Sistem Lama

Setelah menerima foto, file, dan source code, lakukan analisis dengan tahapan berikut.

### A. Pemetaan Modul dan Fitur

Identifikasi seluruh modul, halaman, fitur, tabel basis data, relasi, dan alur yang ada.

Kelompokkan komponen menjadi:

- **Dipertahankan**: Masih relevan dengan kebutuhan RKM.
- **Diperbaiki**: Fungsinya masih dibutuhkan tetapi implementasinya perlu diperbarui.
- **Disederhanakan**: Fitur atau struktur yang berlebihan dan dapat diringkas.
- **Dihapus**: Tidak memiliki relevansi dengan kebutuhan baru dan tidak menjadi dependensi fitur lain.

Setiap rekomendasi harus disertai alasan yang jelas.

### B. Pemetaan Struktur Data

Periksa:

- Data master karyawan HSE.
- Data master kendaraan ringan QMA.
- Data aktivitas kendaraan.
- Data referensi gate.
- Data kilometer atau hour meter.
- Relasi antara kendaraan, karyawan, dan aktivitas.
- Sumber data setiap kolom pada Log Aktivitas QMA.
- Data yang duplikat, tidak terpakai, atau masih dibutuhkan modul lain.

Bedakan antara master data, data transaksi, data referensi, dan data hasil perhitungan.

### C. Analisis Kesesuaian dengan Laravel

Setelah struktur sistem lama dipahami, berikan rancangan konseptual yang mencakup:

- Pembagian modul aplikasi.
- Entitas dan relasi basis data.
- Pemisahan master data dan transaksi aktivitas.
- Sumber data yang digunakan untuk mengisi setiap kolom log.
- Alur penyimpanan dan pengambilan data aktivitas.
- Kebutuhan refactoring dan migrasi dari sistem lama.

Pada tahap ini, jangan membuat migration, model, controller, atau kode implementasi Laravel sebelum desain disepakati.

## 9. Batasan Pembahasan Tahap Pertama

Jangan mendalami mekanisme validasi Private Guard (PG), alur persetujuan aktivitas, maupun hubungan operasional petugas dengan gate.

Untuk saat ini, fokuskan analisis pada:

1. Struktur dan fungsi sistem lama.
2. Master data HSE dan QMA.
3. Proses pencatatan aktivitas kendaraan.
4. Struktur serta sumber data Log Aktivitas QMA.
5. Relasi data dan kesesuaian sistem untuk dimigrasikan ke Laravel.
6. Identifikasi komponen yang perlu dipertahankan, diperbaiki, disederhanakan, atau dihapus.

## 10. Hasil Analisis yang Diharapkan

Susun hasil analisis secara bertahap dengan keluaran berikut:

1. **Ringkasan sistem lama**: Modul, fitur, dan struktur yang ditemukan.
2. **Pemetaan fitur**: Fungsi, relevansi, dependensi, dan rekomendasi tindakan.
3. **Pemetaan Log Aktivitas QMA**: Kesepuluh kolom, definisi bisnis, sumber data, relasi, serta status verifikasi.
4. **Pemetaan struktur data**: Entitas, atribut, relasi, dan kebutuhan perubahan.
5. **Analisis kesenjangan**: Perbedaan antara sistem lama dan kebutuhan RKM.
6. **Rancangan konseptual sistem baru**: Pembagian modul dan relasi data untuk Laravel.
7. **Daftar pertanyaan terbuka**: Informasi yang belum cukup untuk menetapkan desain final.

Jika informasi belum tersedia, nyatakan sebagai belum diketahui. Jangan mengarang fungsi kolom, relasi, atau aturan bisnis.

## 11. Cara Kerja Diskusi

Lakukan brainstorming secara bertahap dan kolaboratif. Periksa materi yang saya berikan, jelaskan temuan berdasarkan bukti yang tersedia, kemudian berikan rekomendasi dan alasan sebelum berpindah ke tahap berikutnya.

Jangan langsung menyimpulkan seluruh arsitektur atau membuat kode. Setiap keputusan mengenai struktur data dan penghapusan fitur harus mempertimbangkan dampaknya terhadap keseluruhan sistem.

**Tugas pertama:** Analisis sistem lama berdasarkan materi yang saya unggah. Petakan komponen yang relevan dengan kebutuhan RKM, lalu telusuri struktur Log Aktivitas QMA beserta sumber data dan relasinya sebelum kita membahas modul Private Guard (PG).
