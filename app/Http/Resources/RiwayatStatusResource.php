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
                    'perusahaan' => $this->pekerjaan->perusahaan ? new PerusahaanResource($this->pekerjaan->perusahaan) : null,
                ];
            }),
            'universitas' => $this->whenLoaded('universitas', function () {
                if (!$this->universitas) return null;
                return [
                    'id' => $this->universitas->id_universitas,
                    'nama' => $this->universitas->nama_universitas,
                    'jurusan_kuliah' => $this->universitas->jurusanKuliah ? [
                        'id' => $this->universitas->jurusanKuliah->id_jurusanKuliah,
                        'nama' => $this->universitas->jurusanKuliah->nama_jurusan,
                    ] : null,
                    'jalur_masuk' => $this->universitas->jalur_masuk,
                    'jenjang' => $this->universitas->jenjang,
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
