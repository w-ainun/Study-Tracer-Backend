<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiwayatStatusResource extends JsonResource
{
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
                return [
                    'id' => $this->pekerjaan->id_pekerjaan,
                    'posisi' => $this->pekerjaan->posisi,
                    'perusahaan' => $this->pekerjaan->perusahaan ? [
                        'id' => $this->pekerjaan->perusahaan->id_perusahaan,
                        'nama' => $this->pekerjaan->perusahaan->nama_perusahaan,
                    ] : null,
                ];
            }),
            'universitas' => $this->whenLoaded('kuliah', function () {
                if (!$this->kuliah || !$this->kuliah->universitas) return null;
                return [
                    'id' => $this->kuliah->universitas->id_universitas,
                    'nama' => $this->kuliah->universitas->nama_universitas,
                    'jurusan_kuliah' => $this->kuliah->jurusanKuliah ? [
                        'id' => $this->kuliah->jurusanKuliah->id_jurusanKuliah,
                        'nama' => $this->kuliah->jurusanKuliah->nama_jurusan,
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
                        'alamat' => $this->kuliah->universitas->alamat,
                        'id_kota' => $this->kuliah->universitas->id_kota,
                        'kota' => $this->kuliah->universitas->kota?->nama_kota ?? null,
                        'provinsi' => $this->kuliah->universitas->kota?->provinsi?->nama_provinsi ?? null,
                    ] : null,
                    'jurusan_kuliah' => $this->kuliah->jurusanKuliah ? [
                        'id' => $this->kuliah->jurusanKuliah->id_jurusanKuliah,
                        'nama' => $this->kuliah->jurusanKuliah->nama_jurusan,
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
                    'alamat' => $this->wirausaha->alamat,
                    'id_kota' => $this->wirausaha->id_kota,
                    'kota' => $this->wirausaha->kota?->nama_kota ?? null,
                    'provinsi' => $this->wirausaha->kota?->provinsi?->nama_provinsi ?? null,
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
