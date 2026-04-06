<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CaptchaController extends Controller
{
    /**
     * Generate CAPTCHA image.
     *
     * Uses Cache instead of Session so it works with stateless API routes,
     * where Sanctum token-based auth is used (no session middleware).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate()
    {
        // Create CAPTCHA builder
        $builder = new CaptchaBuilder();
        $builder->build(150, 40);

        // Generate a unique key and store the phrase in cache (5 minutes TTL)
        $captchaKey = 'captcha_' . Str::random(40);
        $phrase = $builder->getPhrase();

        Cache::put($captchaKey, strtolower($phrase), now()->addMinutes(5));

        // Return base64 encoded image + the cache key
        return response()->json([
            'success' => true,
            'captcha' => [
                'image' => 'data:image/jpeg;base64,' . base64_encode($builder->get()),
                'key'   => $captchaKey,
            ]
        ]);
    }

    /**
     * Verify CAPTCHA input.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $request->validate([
            'captcha'     => 'required|string',
            'captcha_key' => 'required|string',
        ]);

        $userInput = strtolower(trim($request->captcha));
        $storedPhrase = Cache::pull($request->captcha_key); // Get and delete (one-time use)

        if ($storedPhrase && $userInput === $storedPhrase) {
            return response()->json([
                'success' => true,
                'message' => 'CAPTCHA terverifikasi'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'CAPTCHA tidak valid'
        ], 400);
    }

    /**
     * Refresh CAPTCHA (same as generate but with explicit refresh intent).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->generate();
    }
}
