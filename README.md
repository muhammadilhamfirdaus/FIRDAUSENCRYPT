# 🔐 FirdausEncrypt: Aplikasi Enkripsi File Multi-Lapis

**FirdausEncrypt** adalah aplikasi web berbasis **Laravel** untuk *enkripsi* dan *dekripsi* file dengan keamanan tingkat tinggi.  
Aplikasi ini menerapkan **enkripsi berlapis (multi-layer encryption)** yang menggabungkan **AES (simetris)** dan **RSA (asimetris)** untuk memastikan keamanan maksimum.  
Selain itu, aplikasi menyediakan opsi **key escrow (backup kunci)** oleh admin.

UI dirancang dengan gaya **Web3 / Glassmorphism Modern** menggunakan **Tailwind CSS**.

---

## 🚀 Fitur Utama

### 🧩 Enkripsi Berbasis Passphrase
- File dienkripsi menggunakan **passphrase** yang dibuat oleh pengguna.
- Passphrase **tidak pernah disimpan** di server (zero-knowledge principle).

### 🧱 Enkripsi Multi-Lapis
Terdiri dari 3 lapisan keamanan berurutan:
1. **XOR Obfuscation** — Menyamarkan data asli.
2. **AES-256-GCM Encryption** — Enkripsi inti dengan autentikasi digital.
3. **RSA-4096 Encryption (Key Escrow)** — Backup kunci aman untuk admin.

### 🧠 Zero-Knowledge Architecture
- Server **tidak dapat membaca** file pengguna.
- Dekripsi hanya mungkin dilakukan oleh pengguna dengan passphrase yang benar.

### 📦 File .enc Mandiri
- File terenkripsi (`.enc`) mengandung:
  - Header metadata (IV, Salt, Tag, Mask, dll)
  - Payload terenkripsi
- File dapat didekripsi di mana saja menggunakan aplikasi ini.

### 🔑 Key Escrow (Admin Backup)
- Kunci AES pengguna **dibungkus (wrapped)** dengan kunci publik RSA server.
- Admin (dengan kunci privat RSA) dapat membantu memulihkan file jika pengguna lupa passphrase.

---

## 🛠️ Arsitektur Keamanan

### 🔒 **Proses Enkripsi (Upload)**

1. **Persiapan Kunci**
   - Kombinasi *passphrase* pengguna + *salt acak* → diproses dengan **PBKDF2 (500.000 iterasi)** untuk menghasilkan **kunci AES-256**.

2. **Lapisan 1: XOR (Obfuscation)**
   - File di-XOR dengan *mask* (64 byte acak).
   - Hasil: *Data tersamarkan.*

3. **Lapisan 2: AES-256-GCM**
   - *Data tersamarkan* dienkripsi dengan kunci AES dan IV acak.
   - Mode GCM menyediakan **autentikasi digital (Tag)**.
   - Hasil: *Payload terenkripsi + Tag.*

4. **Lapisan 3: RSA-4096 (Key Escrow)**
   - Kunci AES terenkripsi menggunakan **kunci publik RSA** server.
   - Hasil: *Wrapped key (kunci AES terenkripsi).*

5. **Finalisasi File .enc**
   - Metadata seperti `iv`, `salt`, `tag`, `mask`, `wrapped_key`, `original_filename` disimpan dalam header JSON.
   - File akhir berbentuk:
     ```
     [Header JSON] + ::METADATA_SEPARATOR:: + [Payload Terenkripsi]
     ```

---

### 🔑 **Proses Dekripsi (Download)**

1. **Pemisahan File**
   - File `.enc` dibaca dan dipisah menjadi **Header JSON** dan **Payload terenkripsi**.

2. **Pembuatan Ulang Kunci**
   - *Passphrase* + *Salt* (dari header) diproses ulang melalui PBKDF2 → menghasilkan **Kunci AES (Versi Pengguna).**

3. **Pembukaan Brankas (AES-GCM)**
   - Sistem mencoba mendekripsi payload dengan Kunci AES, IV, dan Tag.
   - Jika salah passphrase atau file rusak → proses **gagal**.

4. **Pembukaan Samaran (XOR)**
   - Jika dekripsi berhasil → hasil di-XOR dengan *mask* untuk mengembalikan **file asli.**

---

## ⚙️ Instalasi Lokal

### 1️⃣ Clone Repositori
```bash
git clone [URL-REPO-ANDA]
cd FirdausEncrypt
