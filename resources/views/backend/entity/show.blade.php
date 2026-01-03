{{--
    Dynamic Entity Show/Detail Template
    
    Renders read-only detail views dynamically from ViewRegistry form definitions.
    
    Variables:
    - $entity: EntityDefinition model
    - $entityName: string
    - $viewDefinition: array - form view definition from ViewRegistry
    - $sections: array - normalized sections with fields
    - $record: EntityRecord
    - $pageTitle: string
    - $editUrl: string
    - $backUrl: string
--}}

@extends('backend.layouts.pjax')

@section('title', $pageTitle)
@section('page-id', "entities/{$entityName}/show")
@section('require-css', 'entity-show')

@section('header', $pageTitle)

@section('header-actions')
<div class="flex items-center gap-3">
    <a href="{{ $backUrl }}" class="btn-secondary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'arrowLeft'])
        <span>Back to List</span>
    </a>
    <a href="{{ $editUrl }}" class="btn-primary flex items-center gap-2">
        @include('backend.partials.icon', ['icon' => 'edit'])
        <span>Edit</span>
    </a>
</div>
@endsection

@section('content')
<div class="entity-show-page">
    {{-- Record Sections --}}
    @foreach($sections as $sectionKey => $section)
        <div class="card mb-6 entity-show-section" id="section-{{ $sectionKey }}">
            @if($section['label'])
                <div class="card-header">
                    <h3 class="card-title">{{ $section['label'] }}</h3>
                </div>
            @endif

            <div class="card-body">
                <dl class="detail-grid cols-{{ $section['columns'] ?? 2 }}">
                    @foreach($section['fields'] as $fieldName => $field)
                        @php
                            $span = $field['span'] ?? 1;
                            $spanClass = $span > 1 ? "col-span-{$span}" : '';
                            $value = $field['value'];
                            $widget = $field['widget'] ?? 'char';
                        @endphp
                        
                        <div class="detail-item {{ $spanClass }}">
                            <dt class="detail-label">{{ $field['label'] }}</dt>
                            <dd class="detail-value">
                                @include('backend.entity.partials.display-value', [
                                    'value' => $value,
                                    'widget' => $widget,
                                    'field' => $field,
                                ])
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    @endforeach

    {{-- Record Metadata --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Record Information</h3>
        </div>
        <div class="card-body">
            <dl class="detail-grid cols-2">
                <div class="detail-item">
                    <dt class="detail-label">ID</dt>
                    <dd class="detail-value font-mono">{{ $record->id }}</dd>
                </div>
                
                @if($record->slug)
                    <div class="detail-item">
                        <dt class="detail-label">Slug</dt>
                        <dd class="detail-value font-mono">{{ $record->slug }}</dd>
                    </div>
                @endif

                @if($record->status)
                    <div class="detail-item">
                        <dt class="detail-label">Status</dt>
                        <dd class="detail-value">
                            @php
                                $statusColors = [
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'published' => 'bg-green-100 text-green-800',
                                    'archived' => 'bg-yellow-100 text-yellow-800',
                                    'trash' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="badge {{ $statusColors[$record->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($record->status) }}
                            </span>
                        </dd>
                    </div>
                @endif

                @if($record->author)
                    <div class="detail-item">
                        <dt class="detail-label">Created By</dt>
                        <dd class="detail-value">{{ $record->author->name ?? 'Unknown' }}</dd>
                    </div>
                @endif

                <div class="detail-item">
                    <dt class="detail-label">Created At</dt>
                    <dd class="detail-value">{{ $record->created_at?->format('M j, Y g:i A') ?? '-' }}</dd>
                </div>

                <div class="detail-item">
                    <dt class="detail-label">Updated At</dt>
                    <dd class="detail-value">{{ $record->updated_at?->format('M j, Y g:i A') ?? '-' }}</dd>
                </div>

                @if($record->published_at)
                    <div class="detail-item">
                        <dt class="detail-label">Published At</dt>
                        <dd class="detail-value">{{ $record->published_at?->format('M j, Y g:i A') }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>
</div>

@push('inline-styles')
<style>
.entity-show-page {
    max-width: 1200px;
}

.entity-show-section {
    margin-bottom: 1.5rem;
}

.detail-grid {
    display: grid;
    gap: 1.5rem;
}

.detail-grid.cols-1 {
    grid-template-columns: 1fr;
}

.detail-grid.cols-2 {
    grid-template-columns: repeat(2, 1fr);
}

.detail-grid.cols-3 {
    grid-template-columns: repeat(3, 1fr);
}

.col-span-2 {
    grid-column: span 2;
}

.col-span-3 {
    grid-column: span 3;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-secondary, #6b7280);
}

.detail-value {
    font-size: 0.9375rem;
    color: var(--text-primary, #1f2937);
}

.detail-value:empty::before {
    content: '-';
    color: var(--text-tertiary, #9ca3af);
}

@media (max-width: 768px) {
    .detail-grid.cols-2,
    .detail-grid.cols-3 {
        grid-template-columns: 1fr;
    }
    
    .col-span-2,
    .col-span-3 {
        grid-column: span 1;
    }
}
</style>
@endpush
@endsection

