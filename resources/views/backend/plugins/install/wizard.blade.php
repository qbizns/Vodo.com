{{-- Plugin Installation Wizard (Screen 4) --}}
{{-- PJAX Layout for SPA navigation --}}

@extends('backend.layouts.pjax')

@section('title', __t('plugins.install_plugin'))
@section('page-id', 'system/plugins/install')
@section('require-css', 'plugins')

@section('header', __t('plugins.install_plugin'))

@section('content')
<div class="install-wizard-page">
    {{-- Progress Steps --}}
    <div class="wizard-steps">
        <div class="step active" data-step="1">
            <div class="step-number">1</div>
            <span class="step-label">{{ __t('plugins.upload') }}</span>
        </div>
        <div class="step-connector"></div>
        <div class="step" data-step="2">
            <div class="step-number">2</div>
            <span class="step-label">{{ __t('plugins.dependencies') }}</span>
        </div>
        <div class="step-connector"></div>
        <div class="step" data-step="3">
            <div class="step-number">3</div>
            <span class="step-label">{{ __t('plugins.permissions') }}</span>
        </div>
        <div class="step-connector"></div>
        <div class="step" data-step="4">
            <div class="step-number">4</div>
            <span class="step-label">{{ __t('plugins.install') }}</span>
        </div>
        <div class="step-connector"></div>
        <div class="step" data-step="5">
            <div class="step-number">5</div>
            <span class="step-label">{{ __t('plugins.complete') }}</span>
        </div>
    </div>

    {{-- Step Content --}}
    <div class="wizard-content">
        {{-- Step 1: Upload/Select --}}
        <div class="wizard-step active" id="step-1">
            <h2>{{ __t('plugins.upload_or_select') }}</h2>
            
            {{-- Upload Zone --}}
            <div class="upload-zone" id="uploadZone">
                <div class="upload-icon">
                    @include('backend.partials.icon', ['icon' => 'upload'])
                </div>
                <p class="upload-text">{{ __t('plugins.drop_zip_here') }}</p>
                <p class="upload-hint">{{ __t('plugins.or_click_browse') }}</p>
                <p class="upload-accepted">{{ __t('plugins.accepted_zip', ['size' => '50MB']) }}</p>
                <input type="file" id="pluginFile" accept=".zip" style="display: none;">
            </div>

            <div class="upload-divider">
                <span>{{ __t('common.or') }}</span>
            </div>

            {{-- Marketplace Search --}}
            @if(!$fromMarketplace)
                <div class="marketplace-search-section">
                    <label>{{ __t('plugins.install_from_marketplace') }}:</label>
                    <div class="search-input-wrapper">
                        @include('backend.partials.icon', ['icon' => 'search'])
                        <input type="text" 
                               id="marketplaceSearchInput" 
                               class="search-input"
                               placeholder="{{ __t('plugins.search_marketplace') }}...">
                    </div>
                    <div id="marketplaceResults" class="marketplace-results"></div>
                </div>
            @else
                {{-- Pre-selected from marketplace --}}
                <div class="selected-plugin-card">
                    <div class="plugin-info">
                        <h3>{{ $pluginInfo['name'] ?? $slug }}</h3>
                        <p>{{ $pluginInfo['description'] ?? '' }}</p>
                        <span class="version">v{{ $pluginInfo['version'] ?? 'Unknown' }}</span>
                    </div>
                    <input type="hidden" id="selectedSlug" value="{{ $slug }}">
                    <input type="hidden" id="fromMarketplace" value="1">
                </div>
            @endif

            <div class="wizard-actions">
                <a href="{{ route('admin.plugins.index') }}" class="btn-secondary">
                    {{ __t('common.cancel') }}
                </a>
                <button type="button" class="btn-primary" id="nextStep1" onclick="goToStep2()" {{ $fromMarketplace ? '' : 'disabled' }}>
                    {{ __t('plugins.next_dependencies') }}
                    @include('backend.partials.icon', ['icon' => 'arrowRight'])
                </button>
            </div>
        </div>

        {{-- Step 2: Dependencies Check --}}
        <div class="wizard-step" id="step-2">
            <h2>{{ __t('plugins.checking_dependencies') }}</h2>
            
            <div id="dependenciesLoading" class="loading-state">
                <div class="spinner"></div>
                <p>{{ __t('plugins.checking_dependencies') }}...</p>
            </div>

            <div id="dependenciesContent" style="display: none;">
                <div id="dependenciesList"></div>
                
                <div id="requirementsSection">
                    <h3>{{ __t('plugins.system_requirements') }}</h3>
                    <div id="requirementsList"></div>
                </div>
            </div>

            <div class="wizard-actions">
                <button type="button" class="btn-secondary" onclick="goToStep(1)">
                    @include('backend.partials.icon', ['icon' => 'arrowLeft'])
                    {{ __t('common.back') }}
                </button>
                <button type="button" class="btn-secondary" onclick="cancelInstall()">
                    {{ __t('common.cancel') }}
                </button>
                <button type="button" class="btn-primary" id="nextStep2" onclick="goToStep3()" disabled>
                    {{ __t('plugins.next_permissions') }}
                    @include('backend.partials.icon', ['icon' => 'arrowRight'])
                </button>
            </div>
        </div>

        {{-- Step 3: Permissions Review --}}
        <div class="wizard-step" id="step-3">
            <h2>{{ __t('plugins.review_permissions') }}</h2>
            
            <div id="permissionsContent">
                <p class="permissions-intro">{{ __t('plugins.permissions_intro') }}</p>
                
                <div id="permissionsList"></div>
                
                <div id="accessRequestsList"></div>

                <label class="accept-checkbox">
                    <input type="checkbox" id="acceptPermissions">
                    <span>{{ __t('plugins.accept_permissions') }}</span>
                </label>
            </div>

            <div class="wizard-actions">
                <button type="button" class="btn-secondary" onclick="goToStep(2)">
                    @include('backend.partials.icon', ['icon' => 'arrowLeft'])
                    {{ __t('common.back') }}
                </button>
                <button type="button" class="btn-secondary" onclick="cancelInstall()">
                    {{ __t('common.cancel') }}
                </button>
                <button type="button" class="btn-primary" id="nextStep3" onclick="goToStep4()" disabled>
                    {{ __t('plugins.next_install') }}
                    @include('backend.partials.icon', ['icon' => 'arrowRight'])
                </button>
            </div>
        </div>

        {{-- Step 4: Installation Progress --}}
        <div class="wizard-step" id="step-4">
            <h2>{{ __t('plugins.installing') }}...</h2>
            
            <div class="install-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressBar" style="width: 0%"></div>
                </div>
                <span class="progress-percent" id="progressPercent">0%</span>
            </div>

            <div class="install-steps-list" id="installStepsList">
                <div class="install-step pending" data-step="download">
                    <span class="step-icon">○</span>
                    <span class="step-text">{{ __t('plugins.step_download') }}</span>
                    <span class="step-status"></span>
                </div>
                <div class="install-step pending" data-step="verify">
                    <span class="step-icon">○</span>
                    <span class="step-text">{{ __t('plugins.step_verify') }}</span>
                    <span class="step-status"></span>
                </div>
                <div class="install-step pending" data-step="extract">
                    <span class="step-icon">○</span>
                    <span class="step-text">{{ __t('plugins.step_extract') }}</span>
                    <span class="step-status"></span>
                </div>
                <div class="install-step pending" data-step="dependencies">
                    <span class="step-icon">○</span>
                    <span class="step-text">{{ __t('plugins.step_dependencies') }}</span>
                    <span class="step-status"></span>
                </div>
                <div class="install-step pending" data-step="migrations">
                    <span class="step-icon">○</span>
                    <span class="step-text">{{ __t('plugins.step_migrations') }}</span>
                    <span class="step-status"></span>
                </div>
                <div class="install-step pending" data-step="register">
                    <span class="step-icon">○</span>
                    <span class="step-text">{{ __t('plugins.step_register') }}</span>
                    <span class="step-status"></span>
                </div>
                <div class="install-step pending" data-step="assets">
                    <span class="step-icon">○</span>
                    <span class="step-text">{{ __t('plugins.step_assets') }}</span>
                    <span class="step-status"></span>
                </div>
                <div class="install-step pending" data-step="cache">
                    <span class="step-icon">○</span>
                    <span class="step-text">{{ __t('plugins.step_cache') }}</span>
                    <span class="step-status"></span>
                </div>
            </div>

            <div class="install-log" id="installLog"></div>

            <div class="wizard-actions">
                <button type="button" class="btn-secondary" id="cancelInstallBtn" onclick="cancelInstall()">
                    {{ __t('common.cancel') }}
                </button>
            </div>
        </div>

        {{-- Step 5: Complete --}}
        <div class="wizard-step" id="step-5">
            <div class="complete-success">
                <div class="success-icon">
                    @include('backend.partials.icon', ['icon' => 'checkCircle'])
                </div>
                <h2>{{ __t('plugins.installation_complete') }}</h2>
                <p id="completeMessage"></p>
            </div>

            <div class="install-summary" id="installSummary"></div>

            <div class="next-steps">
                <h3>{{ __t('plugins.whats_next') }}</h3>
                <ul>
                    <li>{{ __t('plugins.next_configure') }}</li>
                    <li>{{ __t('plugins.next_permissions') }}</li>
                    <li>{{ __t('plugins.next_documentation') }}</li>
                </ul>
            </div>

            <div class="wizard-actions complete-actions">
                <a href="#" id="goToSettingsBtn" class="btn-secondary">
                    {{ __t('plugins.go_to_settings') }}
                </a>
                <a href="#" id="viewPluginBtn" class="btn-secondary">
                    {{ __t('plugins.view_plugin') }}
                </a>
                <a href="{{ route('admin.plugins.index') }}" class="btn-primary">
                    {{ __t('common.done') }}
                </a>
            </div>
        </div>
    </div>
</div>

@push('inline-scripts')
<script nonce="{{ csp_nonce() }}">
let currentStep = 1;
let selectedSlug = '{{ $slug ?? '' }}';
let uploadedFile = null;

// Initialize
$(document).ready(function() {
    initUploadZone();
    initMarketplaceSearch();
    
    if (selectedSlug) {
        // Pre-selected from marketplace
        checkRequirements();
    }
    
    $('#acceptPermissions').on('change', function() {
        $('#nextStep3').prop('disabled', !this.checked);
    });
});

function initUploadZone() {
    const zone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('pluginFile');
    
    zone.addEventListener('click', () => fileInput.click());
    
    zone.addEventListener('dragover', (e) => {
        e.preventDefault();
        zone.classList.add('drag-over');
    });
    
    zone.addEventListener('dragleave', () => {
        zone.classList.remove('drag-over');
    });
    
    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        
        if (e.dataTransfer.files.length) {
            handleFile(e.dataTransfer.files[0]);
        }
    });
    
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) {
            handleFile(fileInput.files[0]);
        }
    });
}

function handleFile(file) {
    if (!file.name.endsWith('.zip')) {
        alert('{{ __t("plugins.only_zip") }}');
        return;
    }
    
    uploadedFile = file;
    selectedSlug = null;
    
    // Update UI
    $('#uploadZone').addClass('file-selected');
    $('#uploadZone .upload-text').text(file.name);
    $('#nextStep1').prop('disabled', false);
}

function initMarketplaceSearch() {
    let timeout;
    $('#marketplaceSearchInput').on('input', function() {
        clearTimeout(timeout);
        const query = $(this).val();
        
        if (query.length < 2) {
            $('#marketplaceResults').empty();
            return;
        }
        
        timeout = setTimeout(() => searchMarketplace(query), 300);
    });
}

function searchMarketplace(query) {
    $.get('{{ route("admin.plugins.marketplace") }}', { q: query, per_page: 5 }, function(data) {
        // Parse results and show
        $('#marketplaceResults').html('<!-- Results here -->');
    });
}

function selectMarketplacePlugin(slug, name) {
    selectedSlug = slug;
    uploadedFile = null;
    
    $('#marketplaceSearchInput').val(name);
    $('#marketplaceResults').empty();
    $('#nextStep1').prop('disabled', false);
}

function goToStep(step) {
    currentStep = step;
    
    // Update step indicators
    $('.wizard-steps .step').each(function(index) {
        const stepNum = index + 1;
        $(this).removeClass('active completed');
        if (stepNum < step) $(this).addClass('completed');
        if (stepNum === step) $(this).addClass('active');
    });
    
    // Show step content
    $('.wizard-step').removeClass('active');
    $(`#step-${step}`).addClass('active');
}

function goToStep2() {
    goToStep(2);
    checkRequirements();
}

function checkRequirements() {
    $('#dependenciesLoading').show();
    $('#dependenciesContent').hide();
    
    const formData = new FormData();
    if (uploadedFile) {
        formData.append('plugin', uploadedFile);
    } else if (selectedSlug) {
        formData.append('slug', selectedSlug);
    }
    
    $.ajax({
        url: '{{ route("admin.plugins.install.requirements") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        success: function(response) {
            selectedSlug = response.slug;
            displayRequirements(response);
            checkDependencies();
        },
        error: function(xhr) {
            alert(xhr.responseJSON?.message || '{{ __t("plugins.requirements_check_failed") }}');
            goToStep(1);
        }
    });
}

function displayRequirements(data) {
    let html = '<table class="requirements-table"><tbody>';
    
    for (const [key, req] of Object.entries(data.requirements)) {
        const statusClass = req.status === 'ok' ? 'status-ok' : (req.status === 'warning' ? 'status-warning' : 'status-error');
        const icon = req.status === 'ok' ? '✓' : (req.status === 'warning' ? '⚠' : '✗');
        
        html += `<tr>
            <td>${req.name}</td>
            <td>${req.required}</td>
            <td>${req.current}</td>
            <td class="${statusClass}">${icon}</td>
        </tr>`;
    }
    
    html += '</tbody></table>';
    $('#requirementsList').html(html);
}

function checkDependencies() {
    $.ajax({
        url: '{{ route("admin.plugins.install.dependencies") }}',
        method: 'POST',
        data: { slug: selectedSlug },
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        success: function(response) {
            displayDependencies(response);
            $('#dependenciesLoading').hide();
            $('#dependenciesContent').show();
            
            if (response.can_proceed) {
                $('#nextStep2').prop('disabled', false);
            }
        },
        error: function(xhr) {
            alert(xhr.responseJSON?.message || '{{ __t("plugins.dependency_check_failed") }}');
        }
    });
}

function displayDependencies(data) {
    if (!data.dependencies.length) {
        $('#dependenciesList').html('<p class="no-deps">{{ __t("plugins.no_dependencies") }}</p>');
        return;
    }
    
    let html = '<table class="dependencies-table"><thead><tr><th>{{ __t("plugins.dependency") }}</th><th>{{ __t("plugins.required") }}</th><th>{{ __t("plugins.status") }}</th></tr></thead><tbody>';
    
    for (const dep of data.dependencies) {
        const statusClass = dep.status === 'satisfied' ? 'status-ok' : (dep.status === 'inactive' ? 'status-warning' : 'status-error');
        const statusText = dep.status === 'satisfied' ? '✓ Installed' : (dep.status === 'inactive' ? '○ Inactive' : '✗ Missing');
        
        html += `<tr>
            <td>${dep.slug}</td>
            <td>${dep.required_version}</td>
            <td class="${statusClass}">${statusText}</td>
        </tr>`;
    }
    
    html += '</tbody></table>';
    
    if (data.to_install.length) {
        html += `<div class="auto-install-notice">
            <p>⚠ {{ __t("plugins.will_install_deps") }}:</p>
            <ul>${data.to_install.map(d => `<li>${d.name} v${d.version}</li>`).join('')}</ul>
        </div>`;
    }
    
    $('#dependenciesList').html(html);
}

function goToStep3() {
    goToStep(3);
    loadPermissions();
}

function loadPermissions() {
    $.ajax({
        url: '{{ route("admin.plugins.install.permissions") }}',
        method: 'POST',
        data: { slug: selectedSlug },
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        success: function(response) {
            displayPermissions(response);
        }
    });
}

function displayPermissions(data) {
    let html = '';
    
    if (data.permissions.length) {
        html += '<h3>{{ __t("plugins.registered_permissions") }}</h3><table class="permissions-table"><tbody>';
        for (const perm of data.permissions) {
            html += `<tr><td><code>${perm.name || perm}</code></td><td>${perm.description || ''}</td></tr>`;
        }
        html += '</tbody></table>';
    }
    
    $('#permissionsList').html(html);
    
    if (data.access_requests.length) {
        html = '<h3>{{ __t("plugins.access_requests") }}</h3><ul class="access-list">';
        for (const req of data.access_requests) {
            html += `<li><strong>${req.description}</strong><br><small>${req.details}</small></li>`;
        }
        html += '</ul>';
        $('#accessRequestsList').html(html);
    }
}

function goToStep4() {
    goToStep(4);
    startInstallation();
}

function startInstallation() {
    $('#cancelInstallBtn').prop('disabled', true);
    
    // Start progress updates
    updateInstallProgress('download', 'running', 10);
    
    $.ajax({
        url: '{{ route("admin.plugins.install.install") }}',
        method: 'POST',
        data: { 
            slug: selectedSlug,
            install_dependencies: true
        },
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        success: function(response) {
            if (response.success) {
                completeInstallation(response);
            }
        },
        error: function(xhr) {
            failInstallation(xhr.responseJSON?.message || '{{ __t("plugins.installation_failed") }}');
        }
    });
    
    // Simulate progress (in real impl, use SSE)
    simulateProgress();
}

function simulateProgress() {
    const steps = ['download', 'verify', 'extract', 'dependencies', 'migrations', 'register', 'assets', 'cache'];
    let current = 0;
    
    const interval = setInterval(() => {
        if (current > 0) {
            updateInstallProgress(steps[current - 1], 'complete', (current / steps.length) * 100);
        }
        if (current < steps.length) {
            updateInstallProgress(steps[current], 'running', ((current + 0.5) / steps.length) * 100);
        }
        current++;
        
        if (current > steps.length) {
            clearInterval(interval);
        }
    }, 800);
}

function updateInstallProgress(step, status, percent) {
    const $step = $(`.install-step[data-step="${step}"]`);
    
    $step.removeClass('pending running complete error')
         .addClass(status === 'running' ? 'running' : (status === 'complete' ? 'complete' : status));
    
    if (status === 'running') {
        $step.find('.step-icon').html('●');
    } else if (status === 'complete') {
        $step.find('.step-icon').html('✓');
    } else if (status === 'error') {
        $step.find('.step-icon').html('✗');
    }
    
    $('#progressBar').css('width', percent + '%');
    $('#progressPercent').text(Math.round(percent) + '%');
}

function completeInstallation(data) {
    updateInstallProgress('cache', 'complete', 100);
    
    setTimeout(() => {
        goToStep(5);
        
        $('#completeMessage').text(`${data.plugin.name} v${data.plugin.version} {{ __t("plugins.has_been_installed") }}`);
        $('#goToSettingsBtn').attr('href', '{{ url("admin/system/plugins") }}/' + data.plugin.slug + '/settings');
        $('#viewPluginBtn').attr('href', '{{ url("admin/system/plugins") }}/' + data.plugin.slug);
    }, 500);
}

function failInstallation(message) {
    $('.install-step.running').removeClass('running').addClass('error')
        .find('.step-icon').html('✗');
    
    $('#installLog').html(`<div class="error-message">${message}</div>`);
    $('#cancelInstallBtn').prop('disabled', false).text('{{ __t("common.close") }}');
}

function cancelInstall() {
    if (confirm('{{ __t("plugins.confirm_cancel_install") }}')) {
        window.location.href = '{{ route("admin.plugins.index") }}';
    }
}
</script>
@endpush
@endsection
