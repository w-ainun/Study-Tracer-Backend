<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Support\Facades\Session;

class CaptchaController extends Controller
{
    /**
     * Generate CAPTCHA image
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate()
    {
        // Create CAPTCHA builder
        $builder = new CaptchaBuilder();
        $builder->build(150, 40);
        
        // Store phrase in session
        $phrase = $builder->getPhrase();
        Session::put('captcha_phrase', $phrase);
        
        // Return base64 encoded image
        return response()->json([
            'success' => true,
            'captcha' => [
                'image' => 'data:image/jpeg;base64,' . base64_encode($builder->get()),
                'key' => Session::getId()
            ]
        ]);
    }
    
    /**
     * Verify CAPTCHA input
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $request->validate([
            'captcha' => 'required|string'
        ]);
        
        $userInput = strtolower(trim($request->captcha));
        $storedPhrase = strtolower(Session::get('captcha_phrase'));
        
        // Clear captcha from session after verification attempt
        Session::forget('captcha_phrase');
        
        if ($userInput === $storedPhrase) {
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
     * Refresh CAPTCHA (same as generate but with explicit refresh intent)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->generate();
    }
}
