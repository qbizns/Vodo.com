{{-- Add Widget Modal --}}
<div class="modal-overlay" id="addWidgetModal" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3>{{ __t('widgets.add_widget') }}</h3>
            <button type="button" class="modal-close" id="closeWidgetModal">
                @include('backend.partials.icon', ['icon' => 'x'])
            </button>
        </div>
        <div class="modal-body">
            @if(count($unusedWidgets) > 0)
                <div class="widget-list">
                    @foreach($unusedWidgets as $widgetId => $widget)
                        <div class="widget-option" data-widget-id="{{ $widgetId }}" data-plugin-slug="{{ $widget['plugin_slug'] ?? '' }}">
                            <div class="widget-option-icon">
                                @include('backend.partials.icon', ['icon' => $widget['icon'] ?? 'box'])
                            </div>
                            <div class="widget-option-info">
                                <h4>{{ $widget['title'] ?? 'Widget' }}</h4>
                                <p>{{ $widget['description'] ?? '' }}</p>
                                @if(isset($widget['plugin_name']))
                                    <span class="widget-option-plugin">{{ $widget['plugin_name'] }}</span>
                                @endif
                            </div>
                            <button type="button" class="btn-add-this-widget">
                                @include('backend.partials.icon', ['icon' => 'plus'])
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="modal-empty">
                    <p>{{ __t('widgets.all_widgets_added') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
