# Panduan Frontend: Integrasi Fitur Feedback (Sentiment Analysis)

Dokumen ini berisi panduan teknis bagi tim Frontend untuk mengimplementasikan antarmuka pengiriman *Feedback Layanan* dan *Dashboard Admin Sentiment*.

## 1. Halaman User: Form Kirim Feedback

### Endpoint
User mengirimkan *feedback* ke endpoint backend Laravel:

```http
POST /feedback
```

### Payload Request (JSON)
Anda bisa mengirimkan satu atau beberapa feedback sekaligus dalam format array `feedbacks`. Kategori yang diperbolehkan adalah: `booking`, `payment`, `application`, `customer_service`, `cinema_service`, `other`.

```json
{
  "feedbacks": [
    {
      "category": "booking",
      "comment": "Aplikasi sering lag saat pilih kursi."
    },
    {
      "category": "payment",
      "comment": "QRIS cepat dan lancar."
    }
  ]
}
```

### Response
Jika berhasil (Status HTTP `201 Created`), backend akan langsung memproses teks tersebut melalui ML IndoBERT (secara *under the hood*) dan merespons:

```json
{
  "message": "Feedback berhasil disimpan.",
  "data": [
    {
      "id": 1,
      "user_id": 4,
      "category": "booking",
      "comment": "Aplikasi sering lag saat pilih kursi.",
      "sentiment": "negative",
      "confidence": 0.9921
    },
    {
      "id": 2,
      "user_id": 4,
      "category": "payment",
      "comment": "QRIS cepat dan lancar.",
      "sentiment": "positive",
      "confidence": 0.9812
    }
  ]
}
```

> [!NOTE]
> User **tidak perlu** melihat nilai `sentiment` dan `confidence` di UI. Tampilkan saja notifikasi sukses seperti "Terima kasih atas masukan Anda!".

---

## 2. Halaman Admin: Dashboard Sentiment

### Endpoint View
Untuk mengakses halaman admin sentiment, masuk ke:
```http
GET /admin/feedback
```
Tampilan dasar sudah dikonfigurasi di `resources/views/admin/feedback/index.blade.php`. Anda bebas mengubah *styling*-nya (misalnya menggunakan chart.js atau tabel HTML).

### Variabel Blade yang Tersedia
Backend mengirimkan 3 variabel utama ke view `admin.feedback.index` yang siap di-render:

1. **`$summary`** (Array)
   Berisi total per sentimen. Contoh:
   ```php
   [
       'positive' => 45,
       'neutral' => 12,
       'negative' => 8
   ]
   ```

2. **`$byCategory`** (Collection)
   Distribusi sentimen per kategori. Contoh data:
   ```php
   [
       ['category' => 'payment', 'sentiment' => 'positive', 'total' => 20],
       ['category' => 'booking', 'sentiment' => 'negative', 'total' => 5],
   ]
   ```

3. **`$latestFeedbacks`** (Collection Model `Feedback`)
   20 komentar terbaru beserta relasi ke user (`$feedback->user->name`). Kolom yang tersedia:
   - `$feedback->category`
   - `$feedback->comment`
   - `$feedback->sentiment` (Bisa diberi *badge* warna: merah untuk negative, abu-abu untuk neutral, hijau untuk positive)
   - `$feedback->created_at`

### Saran Tampilan UI Admin
- **Pie Chart**: Menggunakan `$summary` untuk perbandingan positif vs negatif.
- **Bar Chart**: Menggunakan `$byCategory` untuk melihat kategori mana yang paling bermasalah.
- **Tabel Terbaru**: Menampilkan `$latestFeedbacks` secara kronologis.

Selamat mengerjakan UI/UX-nya! Hubungi tim Backend jika ada penyesuaian payload.
