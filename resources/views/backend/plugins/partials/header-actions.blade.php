<div class="flex items-center gap-3">
    {{-- Navigation handled by Vodo.router - no inline onclick needed --}}
    <a href="{{ route('admin.plugins.licenses') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'key'])
        <span>{{ __t('plugins.licenses') }}</span>
    </a>
    <a href="{{ route('admin.plugins.updates') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'refreshCw'])
        <span>{{ __t('plugins.check_updates') }}</span>
        @if(($stats['updates'] ?? 0) > 0)
            <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $stats['updates'] }}</span>
        @endif
    </a>
    <a href="{{ route('admin.plugins.install') }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'upload'])
        <span>{{ __t('plugins.upload') }}</span>
    </a>
    <a href="{{ route('admin.plugins.marketplace') }}" class="btn-primary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'store'])
        <span>{{ __t('plugins.marketplace') }}</span>
    </a>
</div>
