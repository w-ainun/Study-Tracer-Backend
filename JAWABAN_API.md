# API Submit Jawaban Kuesioner

## Endpoint

```
POST /api/alumni/kuesioner/{kuesionerId}/jawaban
```

## Authentication

- Requires: `Bearer Token` (Alumni role)
- Header: `Authorization: Bearer {token}`

## Request Body

```json
{
    "jawaban": [
        {
            "id_pertanyaan": 6,
            "id_opsiJawaban": 26,
            "status": "Selesai"
        },
        {
            "id_pertanyaan": 7,
            "id_opsiJawaban": 31,
            "status": "Selesai"
        },
        {
            "id_pertanyaan": 8,
            "jawaban": "Jawaban teks bebas untuk pertanyaan essay",
            "status": "Selesai"
        }
    ]
}
```

## Request Parameters

### Path Parameter

- `kuesionerId` (integer, required): ID kuesioner yang akan dijawab

### Body Fields

- `jawaban` (array, required): Array berisi jawaban-jawaban
    - `id_pertanyaan` (integer, required): ID pertanyaan yang dijawab
    - `id_opsiJawaban` (integer, nullable): ID opsi jawaban untuk multiple choice
    - `jawaban` (string, nullable): Teks jawaban untuk pertanyaan essay/text
    - `status` (string, optional): Status jawaban, nilai: "Selesai" atau "Draft". Default: "Selesai"

### Validation Rules

- Minimal 1 jawaban harus diisi
- `id_pertanyaan` harus ada di database
- `id_opsiJawaban` (jika diisi) harus ada di database
- Untuk pertanyaan pilihan ganda: isi `id_opsiJawaban`
- Untuk pertanyaan essay/text: isi `jawaban`

## Response Success (200)

```json
{
    "status": "success",
    "message": "Jawaban berhasil disimpan",
    "data": null
}
```

## Response Error (422)

```json
{
    "status": "error",
    "message": "Validasi gagal",
    "errors": {
        "jawaban.0.id_pertanyaan": [
            "The jawaban.0.id_pertanyaan field is required."
        ]
    }
}
```

## Response Error (500)

```json
{
    "status": "error",
    "message": "Gagal menyimpan jawaban: [error message]",
    "data": null
}
```

## Contoh Penggunaan (JavaScript/Frontend)

### Menggunakan API Helper yang sudah ada

```javascript
import { alumniApi } from "@/api/alumni";

// Contoh data jawaban
const answers = [
    {
        id_pertanyaan: 6,
        id_opsiJawaban: 26,
        status: "Selesai",
    },
    {
        id_pertanyaan: 7,
        id_opsiJawaban: 31,
        status: "Selesai",
    },
    {
        id_pertanyaan: 8,
        jawaban: "Ini adalah jawaban essay",
        status: "Selesai",
    },
];

// Submit jawaban
try {
    const response = await alumniApi.submitKuesionerAnswers(1, {
        jawaban: answers,
    });

    console.log("Jawaban berhasil disimpan:", response.data);
} catch (error) {
    console.error("Gagal menyimpan jawaban:", error.response?.data);
}
```

### Menggunakan Fetch API

```javascript
const kuesionerId = 1;
const payload = {
    jawaban: [
        {
            id_pertanyaan: 6,
            id_opsiJawaban: 26,
            status: "Selesai",
        },
        {
            id_pertanyaan: 7,
            id_opsiJawaban: 31,
            status: "Selesai",
        },
    ],
};

fetch(`/api/alumni/kuesioner/${kuesionerId}/jawaban`, {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
    },
    body: JSON.stringify(payload),
})
    .then((response) => response.json())
    .then((data) => {
        console.log("Success:", data);
    })
    .catch((error) => {
        console.error("Error:", error);
    });
```

## Catatan

1. User ID diambil otomatis dari token authentication
2. `created_at` dan `updated_at` dibuat otomatis oleh Laravel
3. Jika `status` tidak dikirim, default akan "Selesai"
4. Untuk pertanyaan multiple choice, wajib kirim `id_opsiJawaban`
5. Untuk pertanyaan essay/text, wajib kirim `jawaban`
6. Satu request bisa mengirim multiple jawaban sekaligus

## Status Codes

- `200 OK`: Jawaban berhasil disimpan
- `401 Unauthorized`: Token tidak valid atau tidak ada
- `403 Forbidden`: User bukan alumni
- `404 Not Found`: Kuesioner tidak ditemukan
- `422 Unprocessable Entity`: Validasi gagal
- `500 Internal Server Error`: Server error
