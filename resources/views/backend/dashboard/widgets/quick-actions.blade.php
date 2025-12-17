{{-- Quick Actions Widget Component --}}
<div class="widget-quick-actions">
    <div class="quick-action-grid">
        <a href="/system/settings" class="quick-action-item">
            @include('backend.partials.icon', ['icon' => 'settings'])
            <span>{{ __t('navigation.settings') }}</span>
        </a>
        <a href="/system/plugins" class="quick-action-item">
            @include('backend.partials.icon', ['icon' => 'plug'])
            <span>{{ __t('navigation.plugins') }}</span>
        </a>
        <a href="/navigation-board" class="quick-action-item">
            @include('backend.partials.icon', ['icon' => 'layoutGrid'])
            <span>{{ __t('widgets.navigation') }}</span>
        </a>
        <a href="/users" class="quick-action-item">
            @include('backend.partials.icon', ['icon' => 'users'])
            <span>{{ __t('navigation.users') }}</span>
        </a>
    </div>
</div>
