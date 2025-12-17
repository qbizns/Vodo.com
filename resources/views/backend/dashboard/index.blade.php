{{-- Dashboard Page Layout --}}
{{-- Simple widget grid layout --}}

<div class="dashboard-page" data-dashboard="{{ $currentDashboard }}">
    {{-- Widget Grid --}}
    <div class="widget-grid" id="widgetGrid">
        @foreach($widgets as $widget)
            @include('backend.dashboard.widgets.wrapper', ['widget' => $widget])
        @endforeach
    </div>

    {{-- Empty State --}}
    @if($widgets->isEmpty())
        <div class="dashboard-empty">
            <div class="dashboard-empty-icon">
                @include('backend.partials.icon', ['icon' => 'layoutDashboard'])
            </div>
            <h3>{{ __t('widgets.no_widgets') }}</h3>
            <p>{{ __t('widgets.add_widgets_hint') }}</p>
            @if(count($unusedWidgets ?? []) > 0)
                <button type="button" class="btn-primary" id="addFirstWidgetBtn">
                    @include('backend.partials.icon', ['icon' => 'plus'])
                    {{ __t('widgets.add_widget') }}
                </button>
            @endif
        </div>
    @endif
</div>

{{-- Add Widget Modal --}}
@include('backend.dashboard.partials.add-widget-modal', ['unusedWidgets' => $unusedWidgets ?? []])

@include('backend.dashboard.styles')
@include('backend.dashboard.scripts')
