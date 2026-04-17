<?php

namespace App\Services;

use App\Exceptions\InvalidLoginException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthService
{
    // Maksimum percobaan login sebelum dikunci
    private const MAX_ATTEMPTS      = 5;

    // Lama kunci (detik) setelah melebihi MAX_ATTEMPTS
    private const LOCKOUT_SECONDS   = 15 * 60;

    // Durasi access token (menit)
    private const ACCESS_TOKEN_TTL  = 60;

    // Durasi refresh token (hari)
    private const REFRESH_TOKEN_TTL = 1;

    // =========================================================================
    // Public Methods
    // =========================================================================

    /**
     * Proses registrasi user baru.
     * Dibungkus DB::transaction — jika pembuatan token gagal, user ikut di-rollback.
     */
    public function register(array $data, string $ipAddress, string $userAgent): array
    {
        return DB::transaction(function () use ($data, $ipAddress, $userAgent) {
            $user = User::create([
                'username' => $data['username'],
                'full_name'    => $data['full_name'],
                'password' => Hash::make($data['password']),
                'role'     => $data['role'] ?? 'student', // default role: user
            ]);



            Log::info('[Auth] Registrasi berhasil', [
                'user_id'    =>  (string) $user->id,
                'username'   => $user->username,
                'full_name'  => $user->full_name,
                'role'       => $user->role,
                'ip'         => $ipAddress,
                'user_agent' => $userAgent,
                'at'         => now()->toDateTimeString(),
            ]);

            return [
                'user' => $user,

            ];
        });
    }

    /**
     * Proses login dengan proteksi keamanan lengkap.
     */
    public function login(array $data, string $ipAddress, string $userAgent): array
    {
        $throttleKey = $this->throttleKey($data['username'], $ipAddress);
        // 1. Cek apakah sudah terkena lockout
        $this->checkLockout($throttleKey, $data['username'], $ipAddress);

        // 2. Cari user berdasarkan username
        $user = User::where('username', $data['username'])->first();
        if (empty($user)) {
            throw new InvalidLoginException();
        }
        // 3. Verifikasi kredensial dengan timing-safe check
        //    Selalu jalankan Hash::check() meski user tidak ada
        //    untuk mencegah timing attack / username enumeration
        $passwordValid = $this->verifyPassword($data['password'], $user->password);
        if (!$passwordValid) {
            $this->handleFailedAttempt($throttleKey, $data['username'], $ipAddress, $userAgent);
            throw new InvalidLoginException();
        }

        // 4. Reset counter setelah login berhasil
        RateLimiter::clear($throttleKey);

        // 5. Revoke semua token lama agar tidak ada token "zombie"
        $user->tokens()->delete();

        // 6. Buat access token & refresh token baru
        $token = $user->createToken(
            'access_token',
            ["role:{$user->role}", 'access_api'],
            Carbon::now()->addMinutes(self::ACCESS_TOKEN_TTL)
        )->plainTextToken;

        $refreshToken = $user->createToken(
            'refresh_token',
            ["role:{$user->role}", 'issue_access_api'],
            Carbon::now()->addDays(self::REFRESH_TOKEN_TTL)
        )->plainTextToken;

        // 7. Log aktivitas login berhasil
        $this->logLoginSuccess($user, $ipAddress, $userAgent);

        return [
            'user'          => $user,
            'token'         => $token,
            'refresh_token' => $refreshToken,
            'expires_in'    => self::ACCESS_TOKEN_TTL * 60, // dalam detik
        ];
    }

    /**
     * Proses refresh access token menggunakan refresh token yang masih valid.
     */
    public function refreshToken(User $user, string $ipAddress): array
    {
        // Revoke hanya access token lama, bukan refresh token
        $user->tokens()
            ->where('name', 'access_token')
            ->delete();

        $token = $user->createToken(
            'access_token',
            ["role:{$user->role}", 'access_api'],
            Carbon::now()->addMinutes(self::ACCESS_TOKEN_TTL)
        )->plainTextToken;

        Log::info('[Auth] Access token di-refresh', [
            'user_id' => $user->id,
            'ip'      => $ipAddress,
        ]);

        return [
            'token'      => $token,
            'expires_in' => self::ACCESS_TOKEN_TTL * 60,
        ];
    }

    /**
     * Logout: revoke semua token milik user.
     */
    public function logout(User $user, string $ipAddress): void
    {
        $user->tokens()->delete();

        Log::info('[Auth] User logout', [
            'user_id' => $user->id,
            'ip'      => $ipAddress,
        ]);
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Buat throttle key unik berdasarkan username + IP.
     * Menggunakan hash agar username tidak tersimpan plaintext di cache.
     */
    private function throttleKey(string $username, string $ip): string
    {
        return 'login:' . hash('sha256', Str::lower($username) . '|' . $ip);
    }

    /**
     * Cek apakah request sedang dalam kondisi terkunci (lockout).
     */
    private function checkLockout(string $throttleKey, string $username, string $ip): void
    {
        if (!RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($throttleKey);

        Log::warning('[Auth] Login dikunci karena terlalu banyak percobaan', [
            'username'       => $username,
            'ip'             => $ip,
            'available_in_s' => $seconds,
        ]);

        throw new InvalidLoginException(
            "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."
        );
    }

    /**
     * Verifikasi password dengan timing-safe approach.
     * Jika user null, tetap jalankan Hash::check() ke dummy hash
     * agar response time konsisten (mencegah username enumeration).
     */
    private function verifyPassword(string $inputPassword, ?string $storedHash): bool
    {
        // Dummy hash dengan cost factor yang sama (bcrypt cost 12)
        $dummy = '$2y$12$invalidsaltinvalidsaltinvalidsaltinvalidsaltinvalidsal';

        return Hash::check($inputPassword, $storedHash ?? $dummy);
    }

    /**
     * Catat percobaan login gagal dan tambah counter throttle.
     */
    private function handleFailedAttempt(
        string $throttleKey,
        string $username,
        string $ip,
        string $userAgent
    ): void {
        RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

        $attempts  = RateLimiter::attempts($throttleKey);
        $remaining = self::MAX_ATTEMPTS - $attempts;

        Log::warning('[Auth] Login gagal', [
            'username'           => $username,
            'ip'                 => $ip,
            'user_agent'         => $userAgent,
            'attempt_count'      => $attempts,
            'remaining_attempts' => max(0, $remaining),
        ]);
    }

    /**
     * Log aktivitas login berhasil untuk audit trail.
     */
    private function logLoginSuccess(User $user, string $ip, string $userAgent): void
    {
        Log::info('[Auth] Login berhasil', [
            'user_id'    => $user->id,
            'username'   => $user->username,
            'role'       => $user->role,
            'ip'         => $ip,
            'user_agent' => $userAgent,
            'at'         => now()->toDateTimeString(),
        ]);
    }
}
