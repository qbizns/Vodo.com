{{-- Dependency Tree Node --}}
@php
$statusClass = match($dep['status'] ?? 'missing') {
    'satisfied' => 'node-ok',
    'missing' => 'node-missing',
    'inactive' => 'node-inactive',
    default => 'node-error',
};
@endphp

<div class="tree-branch" style="--level: {{ $level ?? 1 }}">
    <div class="tree-node {{ $statusClass }}">
        <span class="node-status">
            @if(($dep['status'] ?? '') === 'satisfied')
                ✓
            @elseif(($dep['status'] ?? '') === 'missing')
                ✗
            @elseif(($dep['status'] ?? '') === 'inactive')
                ○
            @else
                ⚠
            @endif
        </span>
        <span class="node-name">{{ $dep['name'] ?? $dep['slug'] ?? 'Unknown' }}</span>
        <span class="node-version">
            @if(!empty($dep['installed_version']))
                v{{ $dep['installed_version'] }}
            @else
                {{ $dep['required_version'] ?? '*' }}
            @endif
        </span>
    </div>
    
    @if(!empty($dep['children']))
        <div class="tree-children">
            @foreach($dep['children'] as $child)
                @include('backend.plugins.partials.dependency-node', ['dep' => $child, 'level' => ($level ?? 1) + 1])
            @endforeach
        </div>
    @endif
</div>
