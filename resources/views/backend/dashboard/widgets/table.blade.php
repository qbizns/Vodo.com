{{-- Table Widget Component --}}
<div class="widget-table" data-widget-id="{{ $widget->widget_id }}">
    <table class="mini-table">
        <thead>
            <tr>
                <th>Column</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            {{-- Table data will be loaded via AJAX --}}
            <tr class="table-placeholder">
                <td colspan="2">Loading data...</td>
            </tr>
        </tbody>
    </table>
</div>
