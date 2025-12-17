{{-- Custom Widget Component --}}
<div class="widget-custom" data-widget-id="{{ $widget->widget_id }}">
    @if(isset($widget->definition['view']))
        @include($widget->definition['view'], ['widget' => $widget])
    @else
        <div class="custom-placeholder">
            <p>Custom widget content</p>
        </div>
    @endif
</div>
