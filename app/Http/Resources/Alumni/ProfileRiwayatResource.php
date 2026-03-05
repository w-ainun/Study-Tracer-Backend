<?php

namespace App\Http\Resources\Alumni;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileRiwayatResource extends JsonResource
{
    /**
     * Transform riwayat status for alumni profile view.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_riwayat,
            'status' => $this->whenLoaded('status', function () {
                return [
                    'id' => $this->status->id_status,
                    'nama' => $this->status->nama_status,
                ];
            }),
            'tahun_mulai' => $this->tahun_mulai,
            'tahun_selesai' => $this->tahun_selesai,
            'pekerjaan' => $this->whenLoaded('pekerjaan', function () {
                if (!$this->pekerjaan) return null;
                $perusahaan = $this->pekerjaan->perusahaan;
                return [
                    'id' => $this->pekerjaan->id_pekerjaan,
                    'posisi' => $this->pekerjaan->posisi,
                    'perusahaan' => $perusahaan ? [
                        'id' => $perusahaan->id_perusahaan,
                        'nama' => $perusahaan->nama_perusahaan,
                        'kota' => $perusahaan->kota?->nama_kota ?? null,
                        'provinsi' => $perusahaan->kota?->provinsi?->nama_provinsi ?? null,
                    ] : null,
                ];
            }),
            'kuliah' => $this->whenLoaded('kuliah', function () {
                if (!$this->kuliah) return null;
                return [
                    'id' => $this->kuliah->id_kuliah,
                    'universitas' => $this->kuliah->universitas ? [
                        'id' => $this->kuliah->universitas->id_universitas,
                        'nama' => $this->kuliah->universitas->nama_universitas,
                    ] : null,
                    'jurusan_kuliah' => $this->kuliah->jurusanKuliah ? [
                        'id' => $this->kuliah->jurusanKuliah->id_jurusanKuliah,
                        'nama' => $this->kuliah->jurusanKuliah->nama ?? $this->kuliah->jurusanKuliah->nama_jurusan ?? null,
                    ] : null,
                    'jalur_masuk' => $this->kuliah->jalur_masuk,
                    'jenjang' => $this->kuliah->jenjang,
                ];
            }),
            'wirausaha' => $this->whenLoaded('wirausaha', function () {
                if (!$this->wirausaha) return null;
                return [
                    'id' => $this->wirausaha->id_wirausaha,
                    'nama_usaha' => $this->wirausaha->nama_usaha,
                    'bidang_usaha' => $this->wirausaha->bidangUsaha ? [
                        'id' => $this->wirausaha->bidangUsaha->id_bidang,
                        'nama' => $this->wirausaha->bidangUsaha->nama_bidang,
                    ] : null,
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}
