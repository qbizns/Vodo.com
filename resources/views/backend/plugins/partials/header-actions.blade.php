<div class="flex items-center gap-3">
    <a href="{{ route('admin.plugins.updates') }}" class="btn-secondary flex items-center gap-2" onclick="event.preventDefault(); navigateToPage(this.href, 'system/plugins/updates', '{{ __t('plugins.updates') }}', 'refresh-cw');">
        @include('backend.partials.icon', ['icon' => 'refreshCw'])
        <span>{{ __t('plugins.check_updates') }}</span>
        @if(($stats['updates'] ?? 0) > 0)
            <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $stats['updates'] }}</span>
        @endif
    </a>
    <a href="{{ route('admin.plugins.install') }}" class="btn-secondary flex items-center gap-2" onclick="event.preventDefault(); navigateToPage(this.href, 'system/plugins/install', '{{ __t('plugins.install') }}', 'upload');">
        @include('backend.partials.icon', ['icon' => 'upload'])
        <span>{{ __t('plugins.upload') }}</span>
    </a>
    <a href="{{ route('admin.plugins.marketplace') }}" class="btn-primary flex items-center gap-2" onclick="event.preventDefault(); navigateToPage(this.href, 'system/plugins/marketplace', '{{ __t('plugins.marketplace') }}', 'store');">
        @include('backend.partials.icon', ['icon' => 'store'])
        <span>{{ __t('plugins.marketplace') }}</span>
    </a>
</div>
