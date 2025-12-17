{{-- Widget Wrapper --}}
<div class="widget" 
     data-widget-id="{{ $widget->widget_id }}"
     data-plugin-slug="{{ $widget->plugin_slug ?? '' }}"
     data-position="{{ $widget->position }}"
     data-col="{{ $widget->col }}"
     data-row="{{ $widget->row }}"
     data-width="{{ $widget->width }}"
     data-height="{{ $widget->height }}"
     style="grid-column: span {{ $widget->width }}; grid-row: span {{ $widget->height }};">
    
    {{-- Widget Header --}}
    <div class="widget-header">
        <div class="widget-drag-handle">
            @include('backend.partials.icon', ['icon' => 'gripVertical'])
        </div>
        <div class="widget-title-group">
            <span class="widget-icon">
                @include('backend.partials.icon', ['icon' => $widget->definition['icon'] ?? 'box'])
            </span>
            <h4 class="widget-title">{{ $widget->definition['title'] ?? 'Widget' }}</h4>
        </div>
        <div class="widget-actions">
            @if($widget->definition['refreshable'] ?? false)
                <button type="button" class="widget-action-btn widget-refresh" title="{{ __t('widgets.refresh') }}">
                    @include('backend.partials.icon', ['icon' => 'refreshCw'])
                </button>
            @endif
            @if($widget->definition['configurable'] ?? false)
                <button type="button" class="widget-action-btn widget-settings" title="{{ __t('widgets.settings') }}">
                    @include('backend.partials.icon', ['icon' => 'settings'])
                </button>
            @endif
            <button type="button" class="widget-action-btn widget-remove" title="{{ __t('widgets.remove') }}">
                @include('backend.partials.icon', ['icon' => 'x'])
            </button>
        </div>
    </div>

    {{-- Widget Content --}}
    <div class="widget-content" data-component="{{ $widget->definition['component'] ?? 'custom' }}">
        <div class="widget-loading">
            <div class="widget-spinner"></div>
        </div>
        <div class="widget-body">
            @php
                $component = $widget->definition['component'] ?? 'custom';
            @endphp
            
            @switch($component)
                @case('stats')
                    @include('backend.dashboard.widgets.stats', ['widget' => $widget])
                    @break
                @case('chart')
                    @include('backend.dashboard.widgets.chart', ['widget' => $widget])
                    @break
                @case('list')
                    @include('backend.dashboard.widgets.list', ['widget' => $widget])
                    @break
                @case('table')
                    @include('backend.dashboard.widgets.table', ['widget' => $widget])
                    @break
                @case('welcome')
                    @include('backend.dashboard.widgets.welcome', ['widget' => $widget])
                    @break
                @case('quick-actions')
                    @include('backend.dashboard.widgets.quick-actions', ['widget' => $widget])
                    @break
                @default
                    @include('backend.dashboard.widgets.custom', ['widget' => $widget])
            @endswitch
        </div>
    </div>

    {{-- Resize Handle --}}
    <div class="widget-resize-handle"></div>
</div>
