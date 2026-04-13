<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterAlumniRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\GoogleAuthRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterAlumniRequest $request)
    {
        try {
            $accountData = $request->only(['email', 'password', 'google_id', 'auth_provider']);
            $profileData = $request->except(['email', 'password', 'password_confirmation', 'captcha_token', 'captcha_key', 'google_id', 'auth_provider']);

            $token = $this->authService->registerUserAndProfile($accountData, $profileData);

            return $this->createdResponse([
                'token' => $token,
            ], 'Registrasi berhasil');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mendaftar: ' . $e->getMessage());
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login($request->validated());

            // Add can_access_all to user object if available
            if (isset($result['can_access_all'])) {
                $result['user']->can_access_all = $result['can_access_all'];
            }

            return $this->successResponse([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Login berhasil');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal login');
        }
    }

    public function me(Request $request)
    {
        try {
            $user = $this->authService->getAuthenticatedUser($request->user());
            $user->load(['alumni.jurusan', 'alumni.skills', 'alumni.socialMedia', 'alumni.riwayatStatus.status', 'admin']);

            // Calculate can_access_all for alumni users
            if ($user->alumni) {
                $canAccessAllData = $this->authService->calculateCanAccessAll($user->id_users);
                $user->can_access_all = $canAccessAllData;
            }

            return $this->successResponse(
                new UserResource($user)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data user');
        }
    }

    public function logout(Request $request)
    {
        try {
            $this->authService->logout($request->user());

            return $this->successResponse(null, 'Logout berhasil');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal logout');
        }
    }

    /**
     * Send a password reset OTP to the user's email.
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $this->authService->forgotPassword($request->validated()['email']);

            return $this->successResponse(null, 'Kode OTP telah dikirim ke email Anda.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengirim email reset password: ' . $e->getMessage());
        }
    }

    /**
     * Verify OTP and reset password.
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $data = $request->validated();
            $this->authService->resetPassword($data['email'], $data['token'], $data['password']);

            return $this->successResponse(null, 'Password berhasil direset. Silakan login dengan password baru.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mereset password: ' . $e->getMessage());
        }
    }

    /**
     * Validate email availability for registration (Step 1 validation).
     */
    public function validateEmail(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'email', new \App\Rules\EmailNotBanned(), new \App\Rules\UniqueEmailExceptRejected()],
            ]);

            return $this->successResponse(null, 'Email tersedia untuk registrasi.');
        } catch (ValidationException $e) {
            return $this->errorResponse('Email tidak valid', 422, $e->errors());
        }
    }

    /**
     * Login with Google ID token.
     * Skips captcha and password verification.
     */
    public function googleLogin(GoogleAuthRequest $request)
    {
        try {
            $result = $this->authService->loginWithGoogle($request->validated()['google_id_token']);

            // Add can_access_all to user object if available
            if (isset($result['can_access_all'])) {
                $result['user']->can_access_all = $result['can_access_all'];
            }

            return $this->successResponse([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Login berhasil');
        } catch (ValidationException $e) {
            $errors = $e->errors();

            // If user not found, return special response with google data
            if (isset($errors['email'])) {
                return $this->errorResponse($errors['email'][0], 404, $errors);
            }

            return $this->errorResponse($e->getMessage(), 422, $errors);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal login dengan Google: ' . $e->getMessage());
        }
    }

    /**
     * Verify Google token for registration (Step 1).
     * Returns Google user data for frontend to auto-fill the registration form.
     */
    public function googleRegister(GoogleAuthRequest $request)
    {
        try {
            $googleUser = $this->authService->registerGoogle($request->validated()['google_id_token']);

            return $this->successResponse([
                'google_email' => $googleUser['email'],
                'google_name' => $googleUser['name'],
                'google_id' => $googleUser['google_id'],
                'google_picture' => $googleUser['picture'],
            ], 'Verifikasi Google berhasil. Lanjutkan registrasi.');
        } catch (ValidationException $e) {
            return $this->errorResponse('Verifikasi Google gagal', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal verifikasi Google: ' . $e->getMessage());
        }
    }
}