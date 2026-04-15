<?php

namespace App\Repositories;

use App\Interfaces\SebaranAlumniRepositoryInterface;
use App\Models\Alumni;
use App\Models\BidangUsaha;
use App\Models\Jurusan;
use App\Models\Kota;
use App\Models\Pekerjaan;
use App\Models\Perusahaan;
use App\Models\Provinsi;
use App\Models\Kuliah;
use App\Models\RiwayatStatus;
use App\Models\Status;
use App\Models\Universitas;
use App\Models\Wirausaha;
use App\Traits\GeneratesThumbnail;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SebaranAlumniRepository implements SebaranAlumniRepositoryInterface
{
    /**
     * Check whether an entity with optional city relation passes location filters.
     */
    private function passesLocationFilter($entity, array $filters): bool
    {
        if (!empty($filters['kota_id']) && (int) ($entity->id_kota ?? 0) !== (int) $filters['kota_id']) {
            return false;
        }

        if (!empty($filters['provinsi_id']) && $entity->kota && (int) ($entity->kota->id_provinsi ?? 0) !== (int) $filters['provinsi_id']) {
            return false;
        }

        return true;
    }

    /**
     * Resolve coordinates using entity coordinates with fallback to city coordinates.
     */
    private function resolveCoordinates($entity): array
    {
        // If entity already has explicit coordinates, always use them.
        if (!is_null($entity->latitude) && !is_null($entity->longitude)) {
            return [
                'latitude' => (float) $entity->latitude,
                'longitude' => (float) $entity->longitude,
            ];
        }

        // Prevent inaccurate center-of-city pin when detailed address exists but geocode failed.
        if ($this->hasDetailedAddress($entity)) {
            return [
                'latitude' => null,
                'longitude' => null,
            ];
        }

        $lat = $entity->kota->latitude ?? null;
        $lng = $entity->kota->longitude ?? null;

        return [
            'latitude' => $lat ? (float) $lat : null,
            'longitude' => $lng ? (float) $lng : null,
        ];
    }

    /**
     * Check whether an entity has meaningful address detail.
     */
    private function hasDetailedAddress($entity): bool
    {
        $address = null;

        if (property_exists($entity, 'jalan')) {
            $address = $entity->jalan;
        } elseif (property_exists($entity, 'alamat')) {
            $address = $entity->alamat;
        }

        if (!$address) {
            return false;
        }

        $normalized = trim(mb_strtolower((string) $address));
        return $normalized !== '' && $normalized !== '-' && $normalized !== 'n/a';
    }

    /**
     * Build small alumni preview payload for marker cards.
     */
    private function mapAlumniPreview($alumni): array
    {
        return [
            'id' => $alumni->id_alumni,
            'nama' => $alumni->nama_alumni,
            'foto' => $alumni->foto ? $this->fotoUrl($alumni->foto) : null,
            'foto_thumbnail' => $alumni->foto ? $this->fotoUrl(GeneratesThumbnail::thumbnailPath($alumni->foto)) : null,
        ];
    }

    /**
     * Build full alumni item payload for location popup.
     */
    private function mapPopupAlumni($riwayat, string $statusKarir, array $detail): array
    {
        return [
            'id_alumni' => $riwayat->alumni->id_alumni,
            'nama' => $riwayat->alumni->nama_alumni,
            'foto' => $riwayat->alumni->foto ? $this->fotoUrl($riwayat->alumni->foto) : null,
            'foto_thumbnail' => $riwayat->alumni->foto ? $this->fotoUrl(GeneratesThumbnail::thumbnailPath($riwayat->alumni->foto)) : null,
            'jurusan' => $riwayat->alumni->jurusan->nama_jurusan ?? null,
            'tahun_masuk' => $riwayat->alumni->tahun_masuk,
            'tahun_lulus' => $riwayat->alumni->tahun_lulus?->format('Y-m-d'),
            'status_karir' => $statusKarir,
            'detail' => $detail,
        ];
    }

    // ── Helper: apply common alumni filters to a riwayat_status query ──

    private function applyFilters($query, array $filters)
    {
        // Filter alumni yang sudah disetujui saja
        $query->whereHas('alumni', function ($q) use ($filters) {
            $q->where('status_create', 'ok');

            if (!empty($filters['angkatan'])) {
                $q->where('tahun_masuk', $filters['angkatan']);
            }
            if (!empty($filters['jurusan_id'])) {
                $q->where('id_jurusan', $filters['jurusan_id']);
            }
        });

        // Only approved career records
        $query->where('approval_status', 'approved');

        // Active career (belum selesai)
        $query->whereNull('tahun_selesai');

        if (!empty($filters['provinsi_id'])) {
            // Filter by provinsi — needs to be applied differently per type
            // Handled in each marker builder method
        }

        if (!empty($filters['kota_id'])) {
            // Same
        }

        return $query;
    }

    /**
     * Build foto URL from storage path.
     */
    private function fotoUrl(?string $foto): ?string
    {
        if (!$foto) return null;

        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');

        return $publicDisk->url($foto);
    }

    // ══════════════════════════════════════════════════════
    //  1. getAlumniMapMarkers — Markers for the map
    // ══════════════════════════════════════════════════════

    public function getAlumniMapMarkers(array $filters = []): array
    {
        $markers = [];
        $tipeKarir = $filters['tipe_karir'] ?? null;

        // ── Bekerja markers (grouped by perusahaan) ──
        if (!$tipeKarir || $tipeKarir === 'bekerja') {
            $markers = array_merge($markers, $this->getBekerjaMarkers($filters));
        }

        // ── Kuliah markers (grouped by universitas) ──
        if (!$tipeKarir || $tipeKarir === 'kuliah') {
            $markers = array_merge($markers, $this->getKuliahMarkers($filters));
        }

        // ── Wirausaha markers (individual locations) ──
        if (!$tipeKarir || $tipeKarir === 'wirausaha') {
            $markers = array_merge($markers, $this->getWirausahaMarkers($filters));
        }

        // Filter markers yang punya koordinat valid
        $markers = array_values(array_filter($markers, fn($m) => $m['latitude'] && $m['longitude']));

        // Calculate bounds
        $bounds = $this->calculateBounds($markers);

        $totalAlumni = array_sum(array_column($markers, 'alumni_count'));

        return [
            'markers' => $markers,
            'bounds' => $bounds,
            'total_markers' => count($markers),
            'total_alumni' => $totalAlumni,
        ];
    }

    private function getBekerjaMarkers(array $filters): array
    {
        $statusBekerja = Status::where('nama_status', 'Bekerja')->first();
        if (!$statusBekerja) return [];

        $query = RiwayatStatus::where('id_status', $statusBekerja->id_status);
        $query = $this->applyFilters($query, $filters);

        if (!empty($filters['perusahaan_id'])) {
            $query->whereHas('pekerjaan', function ($q) use ($filters) {
                $q->where('id_perusahaan', $filters['perusahaan_id']);
            });
        }

        $riwayats = $query->with([
            'alumni:id_alumni,nama_alumni,foto,id_jurusan,tahun_masuk,tahun_lulus',
            'alumni.jurusan:id_jurusan,nama_jurusan',
            'pekerjaan.perusahaan.kota.provinsi',
        ])->get();

        // Group by perusahaan
        $grouped = [];
        foreach ($riwayats as $riwayat) {
            $pekerjaan = $riwayat->pekerjaan;
            if (!$pekerjaan || !$pekerjaan->perusahaan) continue;

            $perusahaan = $pekerjaan->perusahaan;
            $key = $perusahaan->id_perusahaan;

            // Apply kota/provinsi filter
            if (!$this->passesLocationFilter($perusahaan, $filters)) continue;

            if (!isset($grouped[$key])) {
                $coords = $this->resolveCoordinates($perusahaan);

                $grouped[$key] = [
                    'id' => "perusahaan_{$key}",
                    'type' => 'bekerja',
                    'entity_id' => $key,
                    'entity_name' => $perusahaan->nama_perusahaan,
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'kota' => $perusahaan->kota->nama_kota ?? null,
                    'provinsi' => $perusahaan->kota->provinsi->nama_provinsi ?? null,
                    'alumni_count' => 0,
                    'alumni_preview' => [],
                ];
            }

            $grouped[$key]['alumni_count']++;

            // Max 5 preview alumni
            if (count($grouped[$key]['alumni_preview']) < 5 && $riwayat->alumni) {
                $grouped[$key]['alumni_preview'][] = $this->mapAlumniPreview($riwayat->alumni);
            }
        }

        return array_values($grouped);
    }

    private function getKuliahMarkers(array $filters): array
    {
        $statusKuliah = Status::where('nama_status', 'Kuliah')->first();
        if (!$statusKuliah) return [];

        $query = RiwayatStatus::where('id_status', $statusKuliah->id_status);
        $query = $this->applyFilters($query, $filters);

        if (!empty($filters['universitas_id'])) {
            $query->whereHas('kuliah', function ($q) use ($filters) {
                $q->where('id_universitas', $filters['universitas_id']);
            });
        }

        $riwayats = $query->with([
            'alumni:id_alumni,nama_alumni,foto,id_jurusan,tahun_masuk,tahun_lulus',
            'alumni.jurusan:id_jurusan,nama_jurusan',
            'kuliah.universitas.kota.provinsi',
            'kuliah.jurusanKuliah',
        ])->get();

        // Group by universitas
        $grouped = [];
        foreach ($riwayats as $riwayat) {
            $kuliah = $riwayat->kuliah;
            if (!$kuliah || !$kuliah->universitas) continue;

            $universitas = $kuliah->universitas;
            $key = $universitas->id_universitas;

            // Apply kota/provinsi filter
            if (!$this->passesLocationFilter($universitas, $filters)) continue;

            if (!isset($grouped[$key])) {
                $coords = $this->resolveCoordinates($universitas);

                $grouped[$key] = [
                    'id' => "universitas_{$key}",
                    'type' => 'kuliah',
                    'entity_id' => $key,
                    'entity_name' => $universitas->nama_universitas,
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'kota' => $universitas->kota->nama_kota ?? null,
                    'provinsi' => $universitas->kota->provinsi->nama_provinsi ?? null,
                    'alumni_count' => 0,
                    'alumni_preview' => [],
                ];
            }

            $grouped[$key]['alumni_count']++;

            if (count($grouped[$key]['alumni_preview']) < 5 && $riwayat->alumni) {
                $grouped[$key]['alumni_preview'][] = $this->mapAlumniPreview($riwayat->alumni);
            }
        }

        return array_values($grouped);
    }

    private function getWirausahaMarkers(array $filters): array
    {
        $statusWirausaha = Status::where('nama_status', 'Wirausaha')->first();
        if (!$statusWirausaha) return [];

        $query = RiwayatStatus::where('id_status', $statusWirausaha->id_status);
        $query = $this->applyFilters($query, $filters);

        if (!empty($filters['bidang_usaha_id'])) {
            $query->whereHas('wirausaha', function ($q) use ($filters) {
                $q->where('id_bidang', $filters['bidang_usaha_id']);
            });
        }

        $riwayats = $query->with([
            'alumni:id_alumni,nama_alumni,foto,id_jurusan,tahun_masuk,tahun_lulus',
            'alumni.jurusan:id_jurusan,nama_jurusan',
            'wirausaha.kota.provinsi',
            'wirausaha.bidangUsaha',
        ])->get();

        // Group by wirausaha entity (each usaha is unique)
        // But if multiple alumni at same usaha name, group them
        $grouped = [];
        foreach ($riwayats as $riwayat) {
            $wirausaha = $riwayat->wirausaha;
            if (!$wirausaha) continue;

            $key = $wirausaha->id_wirausaha;

            // Apply kota/provinsi filter
            if (!$this->passesLocationFilter($wirausaha, $filters)) continue;

            if (!isset($grouped[$key])) {
                $coords = $this->resolveCoordinates($wirausaha);

                $grouped[$key] = [
                    'id' => "wirausaha_{$key}",
                    'type' => 'wirausaha',
                    'entity_id' => $key,
                    'entity_name' => $wirausaha->nama_usaha,
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'kota' => $wirausaha->kota->nama_kota ?? null,
                    'provinsi' => $wirausaha->kota->provinsi->nama_provinsi ?? null,
                    'bidang_usaha' => $wirausaha->bidangUsaha->nama_bidang ?? null,
                    'alumni_count' => 0,
                    'alumni_preview' => [],
                ];
            }

            $grouped[$key]['alumni_count']++;

            if (count($grouped[$key]['alumni_preview']) < 5 && $riwayat->alumni) {
                $grouped[$key]['alumni_preview'][] = $this->mapAlumniPreview($riwayat->alumni);
            }
        }

        return array_values($grouped);
    }

    private function calculateBounds(array $markers): array
    {
        if (empty($markers)) {
            // Default bounds: seluruh Indonesia
            return [
                'north' => 5.88,
                'south' => -10.36,
                'east' => 141.02,
                'west' => 95.01,
            ];
        }

        $lats = array_filter(array_column($markers, 'latitude'));
        $lngs = array_filter(array_column($markers, 'longitude'));

        return [
            'north' => max($lats),
            'south' => min($lats),
            'east' => max($lngs),
            'west' => min($lngs),
        ];
    }

    // ══════════════════════════════════════════════════════
    //  2. getAlumniAtLocation — Detail popup saat klik marker
    // ══════════════════════════════════════════════════════

    public function getAlumniAtLocation(string $type, int $entityId, array $filters = []): array
    {
        $entity = null;
        $alumni = collect();

        switch ($type) {
            case 'bekerja':
            case 'perusahaan':
                $entity = Perusahaan::with('kota.provinsi')->find($entityId);
                if (!$entity) break;

                $statusBekerja = Status::where('nama_status', 'Bekerja')->first();
                if (!$statusBekerja) break;

                $query = RiwayatStatus::where('id_status', $statusBekerja->id_status)
                    ->where('approval_status', 'approved')
                    ->whereNull('tahun_selesai')
                    ->whereHas('pekerjaan', function ($q) use ($entityId) {
                        $q->where('id_perusahaan', $entityId);
                    })
                    ->whereHas('alumni', function ($q) use ($filters) {
                        $q->where('status_create', 'ok');
                        if (!empty($filters['angkatan'])) $q->where('tahun_masuk', $filters['angkatan']);
                    });

                $riwayats = $query->with([
                    'alumni.jurusan',
                    'pekerjaan',
                ])->get();

                $alumni = $riwayats->map(function ($r) use ($entity) {
                    return $this->mapPopupAlumni($r, 'Bekerja', [
                        'posisi' => $r->pekerjaan->posisi ?? null,
                        'perusahaan' => $entity->nama_perusahaan,
                        'tahun_mulai' => $r->tahun_mulai,
                    ]);
                });

                $coords = $this->resolveCoordinates($entity);

                $entityData = [
                    'id' => $entity->id_perusahaan,
                    'name' => $entity->nama_perusahaan,
                    'type' => 'perusahaan',
                    'alamat' => $entity->jalan,
                    'kota' => $entity->kota->nama_kota ?? null,
                    'provinsi' => $entity->kota->provinsi->nama_provinsi ?? null,
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                ];
                break;

            case 'kuliah':
            case 'universitas':
                $entity = Universitas::with('kota.provinsi')->find($entityId);
                if (!$entity) break;

                $statusKuliah = Status::where('nama_status', 'Kuliah')->first();
                if (!$statusKuliah) break;

                $query = RiwayatStatus::where('id_status', $statusKuliah->id_status)
                    ->where('approval_status', 'approved')
                    ->whereNull('tahun_selesai')
                    ->whereHas('kuliah', function ($q) use ($entityId) {
                        $q->where('id_universitas', $entityId);
                    })
                    ->whereHas('alumni', function ($q) use ($filters) {
                        $q->where('status_create', 'ok');
                        if (!empty($filters['angkatan'])) $q->where('tahun_masuk', $filters['angkatan']);
                    });

                $riwayats = $query->with([
                    'alumni.jurusan',
                    'kuliah.jurusanKuliah',
                ])->get();

                $alumni = $riwayats->map(function ($r) use ($entity) {
                    return $this->mapPopupAlumni($r, 'Kuliah', [
                        'universitas' => $entity->nama_universitas,
                        'jurusan_kuliah' => $r->kuliah->jurusanKuliah->nama_jurusan ?? null,
                        'jenjang' => $r->kuliah->jenjang ?? null,
                        'jalur_masuk' => $r->kuliah->jalur_masuk ?? null,
                        'tahun_mulai' => $r->tahun_mulai,
                    ]);
                });

                $coords = $this->resolveCoordinates($entity);

                $entityData = [
                    'id' => $entity->id_universitas,
                    'name' => $entity->nama_universitas,
                    'type' => 'universitas',
                    'alamat' => $entity->alamat,
                    'kota' => $entity->kota->nama_kota ?? null,
                    'provinsi' => $entity->kota->provinsi->nama_provinsi ?? null,
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                ];
                break;

            case 'wirausaha':
                $entity = Wirausaha::with('bidangUsaha', 'kota.provinsi')->find($entityId);
                if (!$entity) break;

                $riwayat = RiwayatStatus::where('id_riwayat', $entity->id_riwayat)
                    ->where('approval_status', 'approved')
                    ->whereNull('tahun_selesai')
                    ->whereHas('alumni', function ($q) use ($filters) {
                        $q->where('status_create', 'ok');
                        if (!empty($filters['angkatan'])) $q->where('tahun_masuk', $filters['angkatan']);
                    })
                    ->with('alumni.jurusan')
                    ->first();

                if ($riwayat && $riwayat->alumni) {
                    $alumni = collect([
                        $this->mapPopupAlumni($riwayat, 'Wirausaha', [
                            'nama_usaha' => $entity->nama_usaha,
                            'bidang_usaha' => $entity->bidangUsaha->nama_bidang ?? null,
                            'tahun_mulai' => $riwayat->tahun_mulai,
                        ])
                    ]);
                }

                $coords = $this->resolveCoordinates($entity);

                $entityData = [
                    'id' => $entity->id_wirausaha,
                    'name' => $entity->nama_usaha,
                    'type' => 'wirausaha',
                    'alamat' => $entity->alamat,
                    'kota' => $entity->kota->nama_kota ?? null,
                    'provinsi' => $entity->kota->provinsi->nama_provinsi ?? null,
                    'bidang_usaha' => $entity->bidangUsaha->nama_bidang ?? null,
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                ];
                break;

            default:
                return ['entity' => null, 'alumni' => [], 'total' => 0];
        }

        return [
            'entity' => $entityData ?? null,
            'alumni' => $alumni->values()->toArray(),
            'total' => $alumni->count(),
        ];
    }

    // ══════════════════════════════════════════════════════
    //  3. getFilterOptions
    // ══════════════════════════════════════════════════════

    public function getFilterOptions(): array
    {
        // Angkatan (tahun masuk) yang ada di alumni accepted
        $angkatan = Alumni::where('status_create', 'ok')
            ->whereNotNull('tahun_masuk')
            ->selectRaw('DISTINCT tahun_masuk')
            ->orderByDesc('tahun_masuk')
            ->pluck('tahun_masuk')
            ->toArray();

        // Jurusan SMK
        $jurusan = Jurusan::orderBy('nama_jurusan')
            ->get(['id_jurusan', 'nama_jurusan'])
            ->map(fn($j) => ['id' => $j->id_jurusan, 'nama' => $j->nama_jurusan])
            ->toArray();

        // Perusahaan yang punya alumni bekerja aktif
        $statusBekerja = Status::where('nama_status', 'Bekerja')->first();
        $perusahaan = [];
        if ($statusBekerja) {
            $perusahaan = Perusahaan::whereHas('pekerjaan.riwayatStatus', function ($q) use ($statusBekerja) {
                $q->where('id_status', $statusBekerja->id_status)
                  ->where('approval_status', 'approved')
                  ->whereNull('tahun_selesai')
                  ->whereHas('alumni', fn($aq) => $aq->where('status_create', 'ok'));
            })
            ->withCount(['pekerjaan as alumni_count' => function ($q) use ($statusBekerja) {
                $q->whereHas('riwayatStatus', function ($rq) use ($statusBekerja) {
                    $rq->where('id_status', $statusBekerja->id_status)
                       ->where('approval_status', 'approved')
                       ->whereNull('tahun_selesai')
                       ->whereHas('alumni', fn($aq) => $aq->where('status_create', 'ok'));
                });
            }])
            ->orderBy('nama_perusahaan')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id_perusahaan,
                'nama' => $p->nama_perusahaan,
                'alumni_count' => $p->alumni_count,
            ])
            ->toArray();
        }

        // Universitas yang punya alumni kuliah aktif
        $statusKuliah = Status::where('nama_status', 'Kuliah')->first();
        $universitas = [];
        if ($statusKuliah) {
            $universitas = Universitas::whereHas('kuliah.riwayatStatus', function ($q) use ($statusKuliah) {
                $q->where('id_status', $statusKuliah->id_status)
                  ->where('approval_status', 'approved')
                  ->whereNull('tahun_selesai')
                  ->whereHas('alumni', fn($aq) => $aq->where('status_create', 'ok'));
            })
            ->orderBy('nama_universitas')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id_universitas,
                'nama' => $u->nama_universitas,
            ])
            ->toArray();
        }

        // Bidang Usaha yang punya alumni wirausaha aktif
        $statusWirausaha = Status::where('nama_status', 'Wirausaha')->first();
        $bidangUsaha = [];
        if ($statusWirausaha) {
            $bidangUsaha = BidangUsaha::whereHas('wirausaha.riwayatStatus', function ($q) use ($statusWirausaha) {
                $q->where('id_status', $statusWirausaha->id_status)
                  ->where('approval_status', 'approved')
                  ->whereNull('tahun_selesai')
                  ->whereHas('alumni', fn($aq) => $aq->where('status_create', 'ok'));
            })
            ->orderBy('nama_bidang')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id_bidang,
                'nama' => $b->nama_bidang,
            ])
            ->toArray();
        }

        // Provinsi
        $provinsi = Provinsi::orderBy('nama_provinsi')
            ->get(['id_provinsi', 'nama_provinsi'])
            ->map(fn($p) => ['id' => $p->id_provinsi, 'nama' => $p->nama_provinsi])
            ->toArray();

        // Status Karir tipe
        $tipeKarir = [
            ['key' => 'bekerja', 'label' => 'Bekerja'],
            ['key' => 'kuliah', 'label' => 'Kuliah'],
            ['key' => 'wirausaha', 'label' => 'Wirausaha'],
        ];

        return [
            'angkatan' => $angkatan,
            'jurusan' => $jurusan,
            'perusahaan' => $perusahaan,
            'universitas' => $universitas,
            'bidang_usaha' => $bidangUsaha,
            'provinsi' => $provinsi,
            'tipe_karir' => $tipeKarir,
        ];
    }

    // ══════════════════════════════════════════════════════
    //  4. getSebaranStats — Summary statistics
    // ══════════════════════════════════════════════════════

    public function getSebaranStats(array $filters = []): array
    {
        $statusBekerja = Status::where('nama_status', 'Bekerja')->first();
        $statusKuliah = Status::where('nama_status', 'Kuliah')->first();
        $statusWirausaha = Status::where('nama_status', 'Wirausaha')->first();

        $baseCondition = function ($query) use ($filters) {
            $query->where('approval_status', 'approved')
                  ->whereNull('tahun_selesai')
                  ->whereHas('alumni', function ($q) use ($filters) {
                      $q->where('status_create', 'ok');
                      if (!empty($filters['angkatan'])) $q->where('tahun_masuk', $filters['angkatan']);
                      if (!empty($filters['jurusan_id'])) $q->where('id_jurusan', $filters['jurusan_id']);
                  });
        };

        // Count per type
        $countBekerja = $statusBekerja
            ? RiwayatStatus::where('id_status', $statusBekerja->id_status)->where(function ($q) use ($baseCondition) { $baseCondition($q); })->count()
            : 0;
        $countKuliah = $statusKuliah
            ? RiwayatStatus::where('id_status', $statusKuliah->id_status)->where(function ($q) use ($baseCondition) { $baseCondition($q); })->count()
            : 0;
        $countWirausaha = $statusWirausaha
            ? RiwayatStatus::where('id_status', $statusWirausaha->id_status)->where(function ($q) use ($baseCondition) { $baseCondition($q); })->count()
            : 0;

        $total = $countBekerja + $countKuliah + $countWirausaha;

        // Unique locations
        $lokBekerja = $statusBekerja
            ? Pekerjaan::whereHas('riwayatStatus', function ($q) use ($statusBekerja, $baseCondition) {
                $q->where('id_status', $statusBekerja->id_status);
                $baseCondition($q);
            })->distinct('id_perusahaan')->count('id_perusahaan')
            : 0;
        $lokKuliah = $statusKuliah
            ? Kuliah::whereHas('riwayatStatus', function ($q) use ($statusKuliah, $baseCondition) {
                $q->where('id_status', $statusKuliah->id_status);
                $baseCondition($q);
            })->distinct('id_universitas')->count('id_universitas')
            : 0;

        // Top 5 perusahaan
        $topPerusahaan = [];
        if ($statusBekerja) {
            $topPerusahaan = Pekerjaan::select('id_perusahaan', DB::raw('count(*) as alumni_count'))
                ->whereHas('riwayatStatus', function ($q) use ($statusBekerja, $baseCondition) {
                    $q->where('id_status', $statusBekerja->id_status);
                    $baseCondition($q);
                })
                ->groupBy('id_perusahaan')
                ->orderByDesc('alumni_count')
                ->limit(5)
                ->with('perusahaan:id_perusahaan,nama_perusahaan')
                ->get()
                ->map(fn($p) => [
                    'nama' => $p->perusahaan->nama_perusahaan ?? 'Unknown',
                    'alumni_count' => $p->alumni_count,
                ])
                ->toArray();
        }

        // Top 5 universitas
        $topUniversitas = [];
        if ($statusKuliah) {
            $topUniversitas = Kuliah::select('id_universitas', DB::raw('count(*) as alumni_count'))
                ->whereHas('riwayatStatus', function ($q) use ($statusKuliah, $baseCondition) {
                    $q->where('id_status', $statusKuliah->id_status);
                    $baseCondition($q);
                })
                ->groupBy('id_universitas')
                ->orderByDesc('alumni_count')
                ->limit(5)
                ->with('universitas:id_universitas,nama_universitas')
                ->get()
                ->map(fn($u) => [
                    'nama' => $u->universitas->nama_universitas ?? 'Unknown',
                    'alumni_count' => $u->alumni_count,
                ])
                ->toArray();
        }

        return [
            'total_alumni_mapped' => $total,
            'breakdown' => [
                'bekerja' => [
                    'count' => $countBekerja,
                    'percentage' => $total > 0 ? round(($countBekerja / $total) * 100, 1) : 0,
                    'locations' => $lokBekerja,
                ],
                'kuliah' => [
                    'count' => $countKuliah,
                    'percentage' => $total > 0 ? round(($countKuliah / $total) * 100, 1) : 0,
                    'locations' => $lokKuliah,
                ],
                'wirausaha' => [
                    'count' => $countWirausaha,
                    'percentage' => $total > 0 ? round(($countWirausaha / $total) * 100, 1) : 0,
                    'locations' => $countWirausaha, // each wirausaha is unique location
                ],
            ],
            'top_perusahaan' => $topPerusahaan,
            'top_universitas' => $topUniversitas,
        ];
    }

    // ══════════════════════════════════════════════════════
    //  5. getHeatmapData — Per-provinsi distribution
    // ══════════════════════════════════════════════════════

    public function getHeatmapData(array $filters = []): array
    {
        $statusBekerja = Status::where('nama_status', 'Bekerja')->first();

        if (!$statusBekerja) return [];

        // Build base filtered alumni IDs
        $alumniQuery = Alumni::where('status_create', 'ok');
        if (!empty($filters['angkatan'])) $alumniQuery->where('tahun_masuk', $filters['angkatan']);
        if (!empty($filters['jurusan_id'])) $alumniQuery->where('id_jurusan', $filters['jurusan_id']);
        $alumniIds = $alumniQuery->pluck('id_alumni');

        if ($alumniIds->isEmpty()) return [];

        // Bekerja per provinsi
        $bekerjaPerProvinsi = DB::table('riwayat_status')
            ->join('pekerjaan', 'riwayat_status.id_riwayat', '=', 'pekerjaan.id_riwayat')
            ->join('perusahaan', 'pekerjaan.id_perusahaan', '=', 'perusahaan.id_perusahaan')
            ->join('kota', 'perusahaan.id_kota', '=', 'kota.id_kota')
            ->join('provinsi', 'kota.id_provinsi', '=', 'provinsi.id_provinsi')
            ->where('riwayat_status.approval_status', 'approved')
            ->whereNull('riwayat_status.tahun_selesai')
            ->whereIn('riwayat_status.id_alumni', $alumniIds)
            ->select(
                'provinsi.id_provinsi',
                'provinsi.nama_provinsi',
                'provinsi.latitude',
                'provinsi.longitude',
                DB::raw('count(*) as alumni_count')
            )
            ->groupBy('provinsi.id_provinsi', 'provinsi.nama_provinsi', 'provinsi.latitude', 'provinsi.longitude')
            ->orderByDesc('alumni_count')
            ->get();

        $total = $bekerjaPerProvinsi->sum('alumni_count');

        return $bekerjaPerProvinsi->map(fn($item) => [
            'id_provinsi' => $item->id_provinsi,
            'nama_provinsi' => $item->nama_provinsi,
            'latitude' => $item->latitude ? (float)$item->latitude : null,
            'longitude' => $item->longitude ? (float)$item->longitude : null,
            'alumni_count' => $item->alumni_count,
            'percentage' => $total > 0 ? round(($item->alumni_count / $total) * 100, 1) : 0,
        ])->toArray();
    }

    // ══════════════════════════════════════════════════════
    //  6. searchLocations — Autocomplete
    // ══════════════════════════════════════════════════════

    public function searchLocations(string $query, ?string $type = null): array
    {
        $results = [];
        $search = "%{$query}%";

        // Search Perusahaan
        if (!$type || $type === 'perusahaan') {
            $perusahaan = Perusahaan::where('nama_perusahaan', 'like', $search)
                ->with('kota')
                ->limit(10)
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id_perusahaan,
                    'name' => $p->nama_perusahaan,
                    'type' => 'perusahaan',
                    'kota' => $p->kota->nama_kota ?? null,
                    'latitude' => $p->latitude ? (float)$p->latitude : ($p->kota->latitude ? (float)$p->kota->latitude : null),
                    'longitude' => $p->longitude ? (float)$p->longitude : ($p->kota->longitude ? (float)$p->kota->longitude : null),
                ]);
            $results = array_merge($results, $perusahaan->toArray());
        }

        // Search Universitas
        if (!$type || $type === 'universitas') {
            $universitas = Universitas::where('nama_universitas', 'like', $search)
                ->limit(10)
                ->get()
                ->map(fn($u) => [
                    'id' => $u->id_universitas,
                    'name' => $u->nama_universitas,
                    'type' => 'universitas',
                    'kota' => null,
                    'latitude' => $u->latitude ? (float)$u->latitude : null,
                    'longitude' => $u->longitude ? (float)$u->longitude : null,
                ]);
            $results = array_merge($results, $universitas->toArray());
        }

        // Search Kota
        if (!$type || $type === 'kota') {
            $kota = Kota::where('nama_kota', 'like', $search)
                ->with('provinsi')
                ->limit(10)
                ->get()
                ->map(fn($k) => [
                    'id' => $k->id_kota,
                    'name' => $k->nama_kota,
                    'type' => 'kota',
                    'provinsi' => $k->provinsi->nama_provinsi ?? null,
                    'latitude' => $k->latitude ? (float)$k->latitude : null,
                    'longitude' => $k->longitude ? (float)$k->longitude : null,
                ]);
            $results = array_merge($results, $kota->toArray());
        }

        return array_slice($results, 0, 20);
    }
}
