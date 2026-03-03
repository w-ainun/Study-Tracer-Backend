# API Dokumentasi: Detail Jawaban Alumni

## Endpoint: Get Detail Jawaban Alumni

Endpoint ini digunakan untuk mengambil detail lengkap jawaban kuesioner dari seorang alumni spesifik, termasuk data alumni, semua pertanyaan dengan opsi jawaban, dan jawaban yang dipilih.

### Request

**URL:** `/api/admin/kuesioner/{kuesionerId}/jawaban/{alumniId}`  
**Method:** `GET`  
**Auth Required:** Yes (Bearer Token - Admin Only)

#### Path Parameters

| Parameter   | Type    | Required | Description                                  |
| ----------- | ------- | -------- | -------------------------------------------- |
| kuesionerId | integer | Yes      | ID kuesioner yang ingin dilihat              |
| alumniId    | integer | Yes      | ID user alumni yang jawabannya ingin dilihat |

### Response

#### Success Response (200 OK)

```json
{
    "status": "success",
    "message": "Detail jawaban berhasil diambil",
    "data": {
        "alumni": {
            "id": 12,
            "nama": "Budi Santoso",
            "nis": "12345",
            "nisn": "0012345678",
            "email": "budi.santoso@example.com",
            "foto": "http://localhost:8000/storage/alumni/foto.jpg",
            "no_hp": "081234567890",
            "alamat": "Jl. Contoh No. 123, Jakarta",
            "jenis_kelamin": "Laki-laki",
            "tempat_lahir": "Jakarta",
            "tanggal_lahir": "2005-03-15",
            "jurusan": "Rekayasa Perangkat Lunak",
            "tahun_masuk": "2020",
            "tahun_lulus": "2023-06-01"
        },
        "kuesioner": {
            "id": 2,
            "judul": "Tracer Study Alumni Bekerja 2024",
            "deskripsi": "Kuesioner untuk mengetahui kondisi alumni yang sudah bekerja",
            "status_karir": "Bekerja",
            "total_pertanyaan": 15,
            "tanggal_publikasi": "2024-01-01"
        },
        "pertanyaan": [
            {
                "id_pertanyaan": 1,
                "isi_pertanyaan": "Apakah Anda saat ini sedang bekerja?",
                "opsi_jawaban": [
                    {
                        "id_opsi": 1,
                        "opsi": "Ya, sedang bekerja"
                    },
                    {
                        "id_opsi": 2,
                        "opsi": "Tidak, sedang mencari pekerjaan"
                    },
                    {
                        "id_opsi": 3,
                        "opsi": "Tidak, sedang melanjutkan pendidikan"
                    }
                ],
                "jawaban": {
                    "id_jawaban": 101,
                    "jawaban_text": null,
                    "opsi_dipilih": {
                        "id_opsi": 1,
                        "opsi": "Ya, sedang bekerja"
                    },
                    "created_at": "2024-01-15T10:30:00.000000Z",
                    "status": "Selesai"
                }
            },
            {
                "id_pertanyaan": 2,
                "isi_pertanyaan": "Berapa lama Anda mendapatkan pekerjaan pertama setelah lulus?",
                "opsi_jawaban": [
                    {
                        "id_opsi": 4,
                        "opsi": "Kurang dari 3 bulan"
                    },
                    {
                        "id_opsi": 5,
                        "opsi": "3-6 bulan"
                    },
                    {
                        "id_opsi": 6,
                        "opsi": "Lebih dari 6 bulan"
                    }
                ],
                "jawaban": {
                    "id_jawaban": 102,
                    "jawaban_text": null,
                    "opsi_dipilih": {
                        "id_opsi": 4,
                        "opsi": "Kurang dari 3 bulan"
                    },
                    "created_at": "2024-01-15T10:31:00.000000Z",
                    "status": "Selesai"
                }
            },
            {
                "id_pertanyaan": 3,
                "isi_pertanyaan": "Ceritakan pengalaman Anda dalam mencari pekerjaan (essay)",
                "opsi_jawaban": [],
                "jawaban": {
                    "id_jawaban": 103,
                    "jawaban_text": "Saya mendapatkan pekerjaan pertama melalui job fair yang diadakan oleh sekolah. Prosesnya cukup mudah karena sudah memiliki portfolio yang baik.",
                    "opsi_dipilih": null,
                    "created_at": "2024-01-15T10:32:00.000000Z",
                    "status": "Selesai"
                }
            },
            {
                "id_pertanyaan": 4,
                "isi_pertanyaan": "Apa posisi pekerjaan Anda saat ini?",
                "opsi_jawaban": [
                    {
                        "id_opsi": 7,
                        "opsi": "Junior Developer"
                    },
                    {
                        "id_opsi": 8,
                        "opsi": "Senior Developer"
                    }
                ],
                "jawaban": null
            }
        ],
        "statistik": {
            "total_pertanyaan": 15,
            "terjawab": 12,
            "belum_dijawab": 3,
            "persentase_selesai": 80.0
        }
    }
}
```

#### Error Response (404 Not Found)

```json
{
    "status": "error",
    "message": "Gagal mengambil detail jawaban: Kuesioner tidak ditemukan"
}
```

```json
{
    "status": "error",
    "message": "Gagal mengambil detail jawaban: User tidak ditemukan"
}
```

#### Error Response (401 Unauthorized)

```json
{
    "status": "error",
    "message": "Tidak memiliki akses"
}
```

### Response Structure Explanation

#### `alumni` Object

Berisi informasi lengkap tentang alumni yang mengisi kuesioner:

- Data pribadi (nama, NIS, NISN, email, foto, no HP, alamat)
- Data kelahiran (tempat, tanggal, jenis kelamin)
- Data pendidikan (jurusan, tahun masuk, tahun lulus)

#### `kuesioner` Object

Berisi informasi tentang kuesioner yang diisi:

- Judul dan deskripsi kuesioner
- Status karir yang ditargetkan (Bekerja, Kuliah, Wirausaha, dll)
- Total pertanyaan dan tanggal publikasi

#### `pertanyaan` Array

Array yang berisi semua pertanyaan dalam kuesioner beserta jawabannya:

- **isi_pertanyaan**: Teks pertanyaan
- **opsi_jawaban**: Array opsi jawaban (kosong untuk pertanyaan essay)
- **jawaban**: Object jawaban dari alumni
    - `jawaban_text`: Untuk pertanyaan essay/text
    - `opsi_dipilih`: Untuk pertanyaan pilihan ganda
    - `created_at`: Waktu menjawab
    - `status`: Status jawaban
    - `null` jika pertanyaan belum dijawab

#### `statistik` Object

Statistik pengisian kuesioner:

- Total pertanyaan dalam kuesioner
- Jumlah pertanyaan yang sudah dijawab
- Jumlah pertanyaan yang belum dijawab
- Persentase kelengkapan pengisian

### Frontend Usage Example

```javascript
import { adminApi } from "@/api/admin";

// Get detail jawaban alumni
const fetchJawabanDetail = async (kuesionerId, alumniId) => {
    try {
        const response = await adminApi.getKuesionerJawabanDetail(
            kuesionerId,
            alumniId,
        );

        const { alumni, kuesioner, pertanyaan, statistik } = response.data.data;

        console.log("Alumni:", alumni.nama);
        console.log("Kuesioner:", kuesioner.judul);
        console.log("Progress:", `${statistik.persentase_selesai}%`);

        // Loop through pertanyaan
        pertanyaan.forEach((p) => {
            console.log(`Pertanyaan: ${p.isi_pertanyaan}`);
            if (p.jawaban) {
                if (p.jawaban.opsi_dipilih) {
                    console.log(`Jawaban: ${p.jawaban.opsi_dipilih.opsi}`);
                } else {
                    console.log(`Jawaban: ${p.jawaban.jawaban_text}`);
                }
            } else {
                console.log("Belum dijawab");
            }
        });

        return response.data.data;
    } catch (error) {
        console.error("Failed to fetch jawaban detail:", error);
        throw error;
    }
};

// Example usage with React
const JawabanDetailPage = () => {
    const [data, setData] = useState(null);
    const { kuesionerId, alumniId } = useParams();

    useEffect(() => {
        const fetchData = async () => {
            const result = await fetchJawabanDetail(kuesionerId, alumniId);
            setData(result);
        };
        fetchData();
    }, [kuesionerId, alumniId]);

    return (
        <div>
            <h1>{data?.alumni.nama}</h1>
            <h2>{data?.kuesioner.judul}</h2>
            <p>Progress: {data?.statistik.persentase_selesai}%</p>

            {data?.pertanyaan.map((p, index) => (
                <div key={p.id_pertanyaan}>
                    <h3>
                        Pertanyaan {index + 1}: {p.isi_pertanyaan}
                    </h3>

                    {p.jawaban ? (
                        <div>
                            {p.jawaban.opsi_dipilih ? (
                                <p>Jawaban: {p.jawaban.opsi_dipilih.opsi}</p>
                            ) : (
                                <p>Jawaban: {p.jawaban.jawaban_text}</p>
                            )}
                        </div>
                    ) : (
                        <p>Belum dijawab</p>
                    )}
                </div>
            ))}
        </div>
    );
};
```

### Features

1. ✅ **Complete Alumni Data**: Data lengkap alumni termasuk foto, kontak, dan riwayat pendidikan
2. ✅ **Kuesioner Information**: Informasi lengkap tentang kuesioner yang diisi
3. ✅ **All Questions**: Semua pertanyaan dalam kuesioner, termasuk yang belum dijawab
4. ✅ **Question Options**: Semua opsi jawaban untuk setiap pertanyaan pilihan ganda
5. ✅ **Answer Details**: Jawaban lengkap dengan timestamp dan status
6. ✅ **Essay Support**: Support untuk pertanyaan essay/text dengan `jawaban_text`
7. ✅ **Multiple Choice Support**: Support untuk pertanyaan pilihan ganda dengan `opsi_dipilih`
8. ✅ **Progress Statistics**: Statistik lengkap tentang kelengkapan pengisian
9. ✅ **Null Handling**: Pertanyaan yang belum dijawab dikembalikan dengan `jawaban: null`

### Use Cases

1. **Detail Review**: Admin melihat detail jawaban alumni untuk review
2. **Progress Tracking**: Melihat sejauh mana alumni mengisi kuesioner
3. **Data Analysis**: Analisis jawaban individual untuk keperluan tracer study
4. **Export Individual**: Export jawaban alumni individual ke PDF/Excel
5. **Answer Verification**: Verifikasi jawaban alumni sebelum approve

### Related Endpoints

- **List All Jawaban**: `/api/admin/kuesioner/{kuesionerId}/jawaban` - List semua alumni yang menjawab
- **Submit Jawaban**: `/api/alumni/kuesioner/{kuesionerId}/jawaban` - Alumni submit jawaban

### Notes

- Endpoint ini hanya accessible untuk admin
- Semua pertanyaan di kuesioner akan ditampilkan, termasuk yang belum dijawab
- Untuk pertanyaan pilihan ganda, cek `jawaban.opsi_dipilih`
- Untuk pertanyaan essay, cek `jawaban.jawaban_text`
- Jika `jawaban` adalah `null`, berarti pertanyaan belum dijawab sama sekali
- Response mencakup foto alumni (dengan URL lengkap) untuk ditampilkan di UI
- Statistik membantu admin melihat kelengkapan pengisian kuesioner
