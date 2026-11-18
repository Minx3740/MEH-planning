@props(['items'])

<nav aria-label="Navigation principale">
    <ul class="app-nav-list">
        @foreach ($items as $item)
            <li>
                @if (isset($item['route']))
                    <a
                        class="app-nav-link"
                        href="{{ route($item['route']) }}"
                        @if (request()->routeIs($item['active'] ?? $item['route'])) aria-current="page" @endif
                    >
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="app-nav-link app-nav-link--disabled" aria-disabled="true">
                        {{ $item['label'] }}
                        <span class="app-nav-status">{{ $item['status'] ?? 'À venir' }}</span>
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
