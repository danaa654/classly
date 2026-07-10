<?php

namespace App\Services;

use App\Models\ActiveSession;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * The single write path for every ActiveSession row — mirrors
 * AuditLogService's role for AuditLog. No controller, middleware, or
 * listener should ever touch ActiveSession directly; always go
 * through one of the three methods below so the parsing/labeling
 * logic (browser, OS, current page) only lives in one place.
 *
 *   - startSession()  — called once, from the Login event listener.
 *   - touchActivity()  — called on every authenticated request, from
 *                         the TrackActiveSession middleware.
 *   - endSession()     — called once, from the Logout event listener.
 *   - pruneStale()      — called on a schedule (see console Kernel),
 *                         physically removing sessions nobody ever
 *                         logged out of.
 */
class ActiveSessionService
{
    /**
     * Human-readable "Current Page" labels, keyed by route name
     * prefix — matches the "Current Page Examples" list in the spec.
     * Checked longest-prefix-first isn't necessary here since route
     * names in Classly don't collide across modules; a simple
     * str_starts_with scan is enough.
     */
    private const PAGE_LABELS = [
        'dashboard' => 'Dashboard',
        'teaching-assignments' => 'Faculty Loading',
        'faculty-load-overloads' => 'Faculty Loading',
        'subject-offerings' => 'Subject Offerings',
        'master-grid' => 'Master Grid',
        'academic-terms' => 'Academic Terms',
        'curriculums' => 'Curriculum',
        'curriculum-items' => 'Curriculum',
        'rooms' => 'Rooms',
        'faculty' => 'Faculty',
        'subjects' => 'Subjects',
        'sections' => 'Sections',
        'departments' => 'Colleges',
        'programs' => 'Programs',
        'specializations' => 'Specializations',
        'users' => 'Users',
        'settings' => 'Settings',
        'working-term' => 'Settings',
        'audit-logs' => 'Audit Logs',
        'activity-history' => 'Activity History',
        'block-schedule' => 'Block Schedule',
        'active-users' => 'Active Users',
        'profile' => 'My Account',
    ];

    /**
     * First touch of a session — writes login_at, so it's never
     * clobbered by later touchActivity() calls within the same
     * session.
     */
    public static function startSession(Request $request, User $user): ActiveSession
    {
        $now = now();

        return ActiveSession::updateOrCreate(
            ['session_id' => $request->session()->getId()],
            [
                'user_id' => $user->id,
                'login_at' => $now,
                'last_activity_at' => $now,
                'current_page' => self::resolvePageLabel($request),
                'browser' => self::parseBrowser($request->userAgent()),
                'operating_system' => self::parseOperatingSystem($request->userAgent()),
                'ip_address' => $request->ip(),
            ]
        );
    }

    /**
     * Called by TrackActiveSession on every authenticated request.
     * Deliberately does NOT touch login_at — that's set once, by
     * startSession(). Falls back to creating the row (with login_at
     * = now) if one doesn't exist yet, which covers session-driver
     * edge cases (e.g. "remember me" auto-login) where the Login
     * event never fired for this particular session id.
     */
    public static function touchActivity(Request $request, User $user): void
    {
        $sessionId = $request->session()->getId();
        $now = now();

        $existing = ActiveSession::where('session_id', $sessionId)->first();

        ActiveSession::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id' => $user->id,
                'login_at' => $existing?->login_at ?? $now,
                'last_activity_at' => $now,
                'current_page' => self::resolvePageLabel($request),
                'browser' => self::parseBrowser($request->userAgent()),
                'operating_system' => self::parseOperatingSystem($request->userAgent()),
                'ip_address' => $request->ip(),
            ]
        );
    }

    /**
     * Called from the Logout listener — the session disappears from
     * Active Users immediately, without waiting for the idle timeout.
     */
    public static function endSession(string $sessionId): void
    {
        ActiveSession::where('session_id', $sessionId)->delete();
    }

    /**
     * Idle cleanup — physically removes any session that has gone
     * past ActiveSession::STALE_AFTER_MINUTES without a fresh touch
     * (tab closed, connection lost, etc. with no proper logout).
     * Scheduled to run every minute — see console Kernel.
     */
    public static function pruneStale(): int
    {
        return ActiveSession::where(
            'last_activity_at',
            '<',
            now()->subMinutes(ActiveSession::STALE_AFTER_MINUTES)
        )->delete();
    }

    /**
     * Resolves the current route into a "Current Page" label. Falls
     * back to a title-cased guess from the route name's first
     * segment so a module added later without a PAGE_LABELS entry
     * still shows something reasonable instead of null.
     */
    private static function resolvePageLabel(Request $request): ?string
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return null;
        }

        $prefix = explode('.', $routeName)[0];

        if (isset(self::PAGE_LABELS[$prefix])) {
            return self::PAGE_LABELS[$prefix];
        }

        return str($prefix)->replace('-', ' ')->title()->toString();
    }

    /**
     * Deliberately simple pattern matching — same spirit as
     * AuditLog::getBrowserLabelAttribute() — not a full UA-parsing
     * library, since this is only ever shown as a light descriptive
     * hint on a card, never relied on for a security decision.
     */
    private static function parseBrowser(?string $userAgent): string
    {
        $ua = (string) $userAgent;

        return match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/') && ! str_contains($ua, 'Chrome/') => 'Safari',
            $ua === '' => 'Unknown',
            default => 'Other',
        };
    }

    private static function parseOperatingSystem(?string $userAgent): string
    {
        $ua = (string) $userAgent;

        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS X') => 'macOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Linux') => 'Linux',
            $ua === '' => 'Unknown',
            default => 'Other',
        };
    }
}