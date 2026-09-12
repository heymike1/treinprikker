<?php

namespace App\Http\Middleware;

use App\Game\CurrentPlayer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives every browser a persistent random identifier in an encrypted cookie.
 * No fingerprinting, no personal data: just a UUID.
 */
class IdentifyPlayer
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = config('treinprikker.player_cookie.name');
        $anonymousId = $request->cookie($name);

        if (! is_string($anonymousId) || ! Str::isUuid($anonymousId)) {
            $anonymousId = (string) Str::uuid();
        }

        // (Re)issue the cookie on every response so active players never lose their identity.
        Cookie::queue(cookie(
            name: $name,
            value: $anonymousId,
            minutes: (int) config('treinprikker.player_cookie.minutes'),
            secure: $request->isSecure(),
            httpOnly: true,
            sameSite: 'lax',
        ));

        app(CurrentPlayer::class)->setAnonymousId($anonymousId);

        return $next($request);
    }
}
