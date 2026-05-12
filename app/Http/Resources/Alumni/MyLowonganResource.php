<?php

namespace App\Http\Resources\Alumni;

use App\Http\Resources\SkillResource;
use App\Http\Resources\PerusahaanResource;
use App\Traits\GeneratesThumbnail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyLowonganResource extends JsonResource
{
    /**
     * Transform lowongan data for the alumni's OWN lowongan view.
     * Includes approval_status & status timestamps for progress tracking.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_lowongan,
            'judul' => $this->judul_lowongan,
            'deskripsi' => $this->deskripsi,
            'nomor_kontak' => $this->nomor_kontak,
            'kebutuhan_lainnya' => $this->kebutuhan_lainnya,
            'tipe_pekerjaan' => $this->tipe_pekerjaan,
            'lokasi' => $this->lokasi,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'lowongan_selesai' => $this->lowongan_selesai?->format('Y-m-d'),
            'jam_mulai' => $this->jam_mulai,
            'jam_berakhir' => $this->jam_berakhir,
            'foto' => $this->foto_lowongan,
            'foto_thumbnail' => GeneratesThumbnail::thumbnailPath($this->foto_lowongan),
            'perusahaan' => new PerusahaanResource($this->whenLoaded('perusahaan')),
            'pekerjaan' => $this->whenLoaded('pekerjaan', function () {
                return [
                    'id' => $this->pekerjaan->id_pekerjaan,
                    'posisi' => $this->pekerjaan->posisi,
                ];
            }),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),

            // ── Status Timeline / Progress ──
            'timeline' => $this->buildTimeline(),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'approved_at' => $this->approved_at,
            'rejected_at' => $this->rejected_at,
        ];
    }

    /**
     * Build a step-by-step progress timeline for the lowongan.
     */
    private function buildTimeline(): array
    {
        $timeline = [];

        // Step 1: Submitted
        $timeline[] = [
            'step' => 1,
            'label' => 'Diajukan',
            'status' => 'completed',
            'date' => $this->created_at?->toIso8601String(),
        ];

        // Step 2: Review
        $reviewStatus = 'in_progress';
        $reviewDate = null;
        if ($this->approval_status === 'approved') {
            $reviewStatus = 'completed';
            $reviewDate = $this->approved_at?->toIso8601String();
        } elseif ($this->approval_status === 'rejected') {
            $reviewStatus = 'rejected';
            $reviewDate = $this->rejected_at?->toIso8601String();
        }

        $timeline[] = [
            'step' => 2,
            'label' => 'Ditinjau Admin',
            'status' => $reviewStatus,
            'date' => $reviewDate,
        ];

        // Step 3: Published (only relevant if approved)
        if ($this->approval_status === 'approved') {
            $publishedStatus = $this->status === 'published' ? 'completed' : 'pending';
            $timeline[] = [
                'step' => 3,
                'label' => 'Dipublikasikan',
                'status' => $publishedStatus,
                'date' => $this->status === 'published' ? $this->approved_at?->toIso8601String() : null,
            ];

            // Step 4: Active/Closed
            if ($this->status === 'closed') {
                $timeline[] = [
                    'step' => 4,
                    'label' => 'Berakhir',
                    'status' => 'completed',
                    'date' => $this->updated_at?->toIso8601String(),
                ];
            }
        }

        return $timeline;
    }
}
