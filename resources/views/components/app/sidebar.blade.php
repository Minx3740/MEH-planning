@props([
    'items',
    'sectionLabel',
    'brandLabel',
])

<aside class="app-sidebar">
    <a class="app-brand" href="{{ route('dashboard') }}">
        <span class="app-brand-mark" aria-hidden="true">MEH</span>
        <span>{{ $brandLabel }}<small>Gestion des plannings</small></span>
    </a>

    <p class="app-nav-label">{{ $sectionLabel }}</p>
    <x-app.navigation :items="$items" />

    <p class="app-sidebar-footer">Ministère de l'Énergie et des Hydrocarbures</p>
</aside>
