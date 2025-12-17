{{-- Stats Widget Component --}}
<div class="widget-stats">
    <div class="stats-items" data-widget-id="{{ $widget->widget_id }}">
        {{-- Stats will be loaded via AJAX --}}
        <div class="stats-placeholder">
            <span>{{ __t('widgets.loading_statistics') }}</span>
        </div>
    </div>
</div>
