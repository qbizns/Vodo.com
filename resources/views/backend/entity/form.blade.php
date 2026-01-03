{{-- Dynamic Entity Form Template --}}

@extends('backend.layouts.pjax')

@section('title', $pageTitle)
@section('page-id', "entities/{$entityName}/" . ($mode === 'edit' ? 'edit' : 'create'))

@section('header', $pageTitle)

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ $backUrl }}" class="btn-secondary">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to List</span>
    </a>
    <button type="submit" form="entityForm" class="btn-primary">
        @include('backend.partials.icon', ['icon' => 'save'])
        <span>{{ $mode === 'edit' ? 'Update' : 'Create' }}</span>
    </button>
</div>
@endsection

@section('content')
<div class="max-w-4xl">
    <form id="entityForm" 
          action="{{ $submitUrl }}" 
          method="POST"
          data-entity="{{ $entityName }}"
          data-mode="{{ $mode }}"
          data-redirect="{{ $cancelUrl }}"
          enctype="multipart/form-data">
        @csrf
        @if($submitMethod === 'PUT')
            @method('PUT')
        @endif

        @foreach($sections as $sectionKey => $section)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6 overflow-hidden">
                @if($section['label'])
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $section['label'] }}</h3>
                    </div>
                @endif

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($section['fields'] as $fieldName => $field)
                            @php
                                $widget = $field['widget'] ?? 'char';
                                $span = $field['span'] ?? 1;
                            @endphp
                            
                            <div class="{{ $span > 1 ? 'md:col-span-2' : '' }}">
                                @include('backend.entity.widgets.' . $widget, [
                                    'name' => $fieldName,
                                    'field' => $field,
                                    'value' => $field['value'] ?? old($fieldName),
                                    'config' => $field['config'] ?? [],
                                    'readonly' => $field['readonly'] ?? false,
                                    'required' => $field['required'] ?? false,
                                    'mode' => $mode,
                                ])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Form Actions --}}
        <div class="flex items-center justify-between py-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <a href="{{ $cancelUrl }}" class="btn-secondary">Cancel</a>
                @if($mode === 'edit' && !empty($deleteUrl))
                    <button type="button" class="btn-danger" onclick="confirmDelete()">
                        @include('backend.partials.icon', ['icon' => 'trash'])
                        <span>Delete</span>
                    </button>
                @endif
            </div>
            <button type="submit" class="btn-primary">
                @include('backend.partials.icon', ['icon' => 'save'])
                <span>{{ $mode === 'edit' ? 'Update' : 'Create' }}</span>
            </button>
        </div>
    </form>
</div>

<script>
(function() {
    const form = document.getElementById('entityForm');
    const mode = form.dataset.mode;
    const redirectUrl = form.dataset.redirect;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        
        for (const [key, value] of formData.entries()) {
            if (key === '_token' || key === '_method') continue;
            if (key.endsWith('[]')) {
                const cleanKey = key.slice(0, -2);
                if (!data[cleanKey]) data[cleanKey] = [];
                data[cleanKey].push(value);
            } else {
                data[key] = value;
            }
        }

        const method = mode === 'edit' ? 'put' : 'post';
        
        Vodo.api[method](this.action, data)
            .then(response => {
                if (response.success) {
                    Vodo.notification.success(response.message || 'Saved successfully');
                    // Clear AJAX cache for the redirect URL to ensure fresh data
                    const targetUrl = response.redirect || redirectUrl;
                    if (Vodo.ajax && Vodo.ajax.invalidate) {
                        Vodo.ajax.invalidate(targetUrl);
                    }
                    if (Vodo.router && Vodo.router.navigate) {
                        Vodo.router.navigate(targetUrl);
                    } else {
                        window.location.href = targetUrl;
                    }
                }
            })
            .catch(error => {
                if (error.errors) {
                    // Clear previous errors
                    document.querySelectorAll('.text-red-500.text-sm.mt-1').forEach(el => el.remove());
                    document.querySelectorAll('.border-red-500').forEach(el => {
                        el.classList.remove('border-red-500');
                        el.classList.add('border-gray-300');
                    });

                    // Show new errors
                    for (const [field, messages] of Object.entries(error.errors)) {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.remove('border-gray-300');
                            input.classList.add('border-red-500');
                            const errorEl = document.createElement('p');
                            errorEl.className = 'text-red-500 text-sm mt-1';
                            errorEl.textContent = Array.isArray(messages) ? messages[0] : messages;
                            input.parentNode.appendChild(errorEl);
                        }
                    }
                } else {
                    Vodo.notification.error(error.message || 'Failed to save');
                }
            });
    });

    @if($mode === 'edit' && !empty($deleteUrl))
    window.confirmDelete = function() {
        Vodo.modal.confirm({
            title: 'Delete {{ $entity?->getSingularLabel() ?? "Record" }}',
            message: 'Are you sure? This action cannot be undone.',
            confirmText: 'Delete',
            confirmClass: 'btn-danger',
            onConfirm: function() {
                Vodo.api.delete('{{ $deleteUrl }}')
                    .then(response => {
                        if (response.success) {
                            Vodo.notification.success('Deleted successfully');
                            // Clear AJAX cache for list URL
                            if (Vodo.ajax && Vodo.ajax.invalidate) {
                                Vodo.ajax.invalidate(redirectUrl);
                            }
                            if (Vodo.router && Vodo.router.navigate) {
                                Vodo.router.navigate(redirectUrl);
                            } else {
                                window.location.href = redirectUrl;
                            }
                        }
                    })
                    .catch(error => {
                        Vodo.notification.error(error.message || 'Failed to delete');
                    });
            }
        });
    };
    @endif
})();
</script>
@endsection
