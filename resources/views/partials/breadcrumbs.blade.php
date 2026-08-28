@php
    $crumbs = $breadcrumbs ?? [];

    if (empty($crumbs)) {
        $segments = collect(request()->segments())
            ->reject(fn ($segment) => $segment === 'admin' || is_numeric($segment))
            ->values();

        $crumbs = $segments->map(function ($segment, $index) use ($segments) {
            $path = $segments->slice(0, $index + 1)->implode('/');
            return [
                'label' => ucwords(str_replace(['-', '_'], ' ', $segment)),
                'href' => $index === $segments->count() - 1 ? null : url($path),
            ];
        })->all();
    }
@endphp

<nav class="admin-breadcrumbs mb-4" aria-label="Breadcrumb">
    <div class="admin-breadcrumb-item">
        <a href="{{ route(Auth::user()?->adminHomeRouteName() ?? 'admin.finance.dashboard') }}" class="admin-breadcrumb-link inline-flex items-center gap-2" title="Home">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span class="font-medium">Home</span>
        </a>
    </div>

    @foreach ($crumbs as $index => $crumb)
        <span class="admin-breadcrumb-separator px-2 text-slate-300" aria-hidden="true">/</span>

        <div class="admin-breadcrumb-item">
            @if (empty($crumb['href']) || $index === count($crumbs) - 1)
                <span class="admin-breadcrumb-current font-bold text-slate-900">{{ $crumb['label'] }}</span>
            @else
                <a href="{{ $crumb['href'] }}" class="admin-breadcrumb-link text-slate-500 transition-colors hover:text-slate-900">
                    {{ $crumb['label'] }}
                </a>
            @endif
        </div>
    @endforeach
</nav>
