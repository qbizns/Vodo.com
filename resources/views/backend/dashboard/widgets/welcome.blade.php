{{-- Welcome Widget Component --}}
<div class="widget-welcome">
    <div class="welcome-content">
        <div class="welcome-text">
            <h3 class="welcome-greeting" data-greeting 
                data-morning="{{ __t('widgets.good_morning') }}"
                data-afternoon="{{ __t('widgets.good_afternoon') }}"
                data-evening="{{ __t('widgets.good_evening') }}">{{ __t('widgets.good_morning') }}</h3>
            <p class="welcome-date" data-date>{{ now()->format('l, F j, Y') }}</p>
        </div>
        <div class="welcome-stats">
            <div class="welcome-stat">
                <span class="stat-value" data-stat="plugins">{{ \App\Models\Plugin::active()->count() }}</span>
                <span class="stat-label">{{ __t('widgets.active_plugins') }}</span>
            </div>
            <div class="welcome-stat">
                <span class="stat-value" data-stat="users">1</span>
                <span class="stat-label">{{ __t('widgets.online_users') }}</span>
            </div>
        </div>
    </div>
</div>
