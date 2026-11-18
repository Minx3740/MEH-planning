@props([
    'title',
    'description' => null,
    'eyebrow' => null,
])

@php
    $user = auth()->user();
    $roleCode = $user?->role?->code;
    $roleNavigation = config('navigation.roles.'.$roleCode, []);
    $navigationItems = $roleNavigation['items'] ?? [];
    $sectionLabel = $roleNavigation['label'] ?? '';
    $brandLabel = $roleNavigation['brand'] ?? 'MEH';
    $sidebarLabel = $roleNavigation['sidebarLabel'] ?? '';
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{{ $title }} - MEH</title>
    @vite('resources/css/meh.css')
</head>
<body class="app-body">
    <div class="app-shell">
        <x-app.sidebar
            :items="$navigationItems"
            :section-label="$sidebarLabel"
            :brand-label="$brandLabel"
        />

        <div class="app-main">
            <x-app.topbar :user="$user" :section-label="$sectionLabel" :items="$navigationItems" />

            <main class="app-content">
                <x-app.page-header :title="$title" :description="$description" :eyebrow="$eyebrow" />
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
