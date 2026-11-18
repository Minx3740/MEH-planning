@props([
    'title',
    'description' => null,
    'eyebrow' => null,
])

<header class="app-page-header">
    @if ($eyebrow)
        <p class="app-page-eyebrow">{{ $eyebrow }}</p>
    @endif
    <h1>{{ $title }}</h1>
    @if ($description)
        <p>{{ $description }}</p>
    @endif
</header>
