@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('pluginFile');
    const fileSelectedName = document.getElementById('fileSelectedName');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    
    if (fileInput && fileNameDisplay) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                const fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);
                fileNameDisplay.textContent = fileName + ' (' + fileSize + ' MB)';
                if (fileSelectedName) {
                    fileSelectedName.style.display = 'block';
                }
            } else {
                if (fileSelectedName) {
                    fileSelectedName.style.display = 'none';
                }
            }
        });
    }
});
</script>
@endpush
