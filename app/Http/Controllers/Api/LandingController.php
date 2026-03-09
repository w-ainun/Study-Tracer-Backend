<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LandingService;
use App\Traits\ApiResponse;

class LandingController extends Controller
{
    use ApiResponse;

    private LandingService $landingService;

    public function __construct(LandingService $landingService)
    {
        $this->landingService = $landingService;
    }

    /**
     * GET /landing/stats
     * Get statistics for landing page
     */
    public function stats()
    {
        try {
            $stats = $this->landingService->getStats();
            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik');
        }
    }

    /**
     * GET /landing/featured-jobs
     * Get featured job listings
     */
    public function featuredJobs()
    {
        try {
            $jobs = $this->landingService->getFeaturedJobs();
            return $this->successResponse($jobs);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil lowongan pekerjaan');
        }
    }

    /**
     * GET /landing/featured-alumni
     * Get featured alumni
     */
    public function featuredAlumni()
    {
        try {
            $alumni = $this->landingService->getFeaturedAlumni();
            return $this->successResponse($alumni);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data alumni');
        }
    }
}
