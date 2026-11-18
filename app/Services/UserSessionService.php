<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserSessionService
{
    /**
     * Crée un enregistrement pour une nouvelle session utilisateur.
     */
    public function create(User $user, Request $request): UserSession
    {
        $sessionId = $request->session()->getId();
        $sessionHash = hash('sha256', $sessionId);

        $userSession = $user->sessions()
            ->where('session_key_hash', $sessionHash)
            ->first();

        if ($userSession === null) {
            $userSession = new UserSession;
        }

        $userSession->fill([
            'user_id' => $user->id,
            'session_key_hash' => $sessionHash,
            'ip_address' => $request->ip(),
            'device_type' => $this->detectDeviceType($request),
            'operating_system' => $this->detectOperatingSystem($request),
            'status' => 'ACTIVE',
            'connected_at' => $userSession->connected_at ?? now(),
            'last_activity_at' => now(),
            'revoked_at' => null,
        ]);

        $userSession->save();

        return $userSession;
    }

    /**
     * Retourne la session active correspondant à l'identifiant réel de session.
     */
    public function findForUser(User $user, string $sessionId): ?UserSession
    {
        return $user->sessions()
            ->where('session_key_hash', hash('sha256', $sessionId))
            ->where('status', 'ACTIVE')
            ->first();
    }

    /**
     * Retourne toutes les sessions d'un utilisateur, triées par dernière activité.
     *
     * @return Collection<int, UserSession>
     */
    public function sessionsForUser(User $user)
    {
        return $user->sessions()
            ->orderByDesc('last_activity_at')
            ->orderByDesc('connected_at')
            ->get();
    }

    /**
     * Actualise la dernière activité d'une session.
     */
    public function updateActivity(UserSession $session): void
    {
        if ($session->status !== 'ACTIVE') {
            return;
        }

        $session->update([
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Révoque une session précise.
     */
    public function revoke(UserSession $session): void
    {
        if ($session->status !== 'ACTIVE') {
            return;
        }

        $session->update([
            'status' => 'REVOKED',
            'revoked_at' => now(),
        ]);
    }

    /**
     * Révoque toutes les sessions actives d'un utilisateur.
     */
    public function revokeAll(User $user): int
    {
        return UserSession::query()
            ->where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->update([
                'status' => 'REVOKED',
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Détermine le type d'appareil.
     */
    private function detectDeviceType(Request $request): string
    {
        $agent = Str::lower($request->userAgent() ?? '');

        if (Str::contains($agent, ['tablet', 'ipad'])) {
            return 'TABLET';
        }

        if (Str::contains($agent, ['mobile', 'android', 'iphone'])) {
            return 'MOBILE';
        }

        return 'DESKTOP';
    }

    /**
     * Détermine le système d'exploitation.
     */
    private function detectOperatingSystem(Request $request): ?string
    {
        $agent = Str::lower($request->userAgent() ?? '');

        return match (true) {
            Str::contains($agent, 'windows') => 'Windows',
            Str::contains($agent, 'android') => 'Android',
            Str::contains($agent, ['iphone', 'ipad', 'ios']) => 'iOS',
            Str::contains($agent, 'mac os') => 'macOS',
            Str::contains($agent, 'linux') => 'Linux',
            default => null,
        };
    }
}
