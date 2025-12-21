{{-- Plugin Management Common Scripts --}}
@push('inline-scripts')
<script nonce="{{ csp_nonce() }}">
console.log('[Plugins] Inline scripts loaded');

(function() {
    // Initialize view mode
    // This runs on every page load (initial or PJAX) to restore state
    var savedMode = localStorage.getItem('plugins.viewMode') || 'list';
    var $viewModeSelect = $('#viewMode');
    if ($viewModeSelect.length) {
        $viewModeSelect.val(savedMode);
        if (typeof window.setViewMode === 'function') {
            window.setViewMode(savedMode);
        }
    }
})();
</script>
@endpush
