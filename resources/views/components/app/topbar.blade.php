@props([
    'user',
    'sectionLabel',
    'items',
])

<header class="app-topbar">
    <span class="app-topbar-context">MEH / {{ $sectionLabel }}</span>

    <details class="app-mobile-navigation">
        <summary>Navigation</summary>
        <div class="app-mobile-navigation-panel">
            <x-app.navigation :items="$items" />
        </div>
    </details>

    <div class="app-topbar-user">
        <span class="app-avatar" aria-hidden="true">{{ mb_substr($user->firstname, 0, 1) }}{{ mb_substr($user->name, 0, 1) }}</span>
        <span class="app-user-name">{{ $user->firstname }} {{ $user->name }}</span>
        <form class="app-logout-form" method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="app-button app-button--secondary" type="submit">Déconnexion</button>
        </form>
    </div>
</header>
