{{--
    Cell Renderer Partial
    
    Renders a single cell value in a list table based on column configuration.
    
    Variables:
    - $record: EntityRecord
    - $column: array - column configuration
    - $columnName: string
    - $entityName: string
--}}

@php
    $value = $record->{$columnName} ?? $record->meta[$columnName] ?? $record->fields[$columnName] ?? null;
    $widget = $column['widget'] ?? 'text';
    $link = $column['link'] ?? false;
    $truncate = $column['truncate'] ?? null;
    $format = $column['format'] ?? null;
@endphp

@switch($widget)
    @case('badge')
        @php
            $colors = $column['colors'] ?? [
                'draft' => 'bg-gray-100 text-gray-800',
                'published' => 'bg-green-100 text-green-800',
                'active' => 'bg-green-100 text-green-800',
                'inactive' => 'bg-gray-100 text-gray-800',
                'suspended' => 'bg-red-100 text-red-800',
                'archived' => 'bg-yellow-100 text-yellow-800',
                'pending' => 'bg-blue-100 text-blue-800',
            ];
            $badgeClass = $colors[$value] ?? 'bg-gray-100 text-gray-800';
        @endphp
        <span class="badge {{ $badgeClass }}">
            {{ ucfirst($value ?? 'N/A') }}
        </span>
        @break

    @case('date')
        @if($value)
            <span title="{{ $value }}">
                {{ \Carbon\Carbon::parse($value)->format($format ?? 'M j, Y') }}
            </span>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('datetime')
        @if($value)
            <span title="{{ $value }}">
                {{ \Carbon\Carbon::parse($value)->format($format ?? 'M j, Y g:i A') }}
            </span>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('currency')
    @case('monetary')
        @if($value !== null)
            <span class="font-mono">
                {{ number_format((float)$value, 2) }}
            </span>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('thumbnail')
    @case('image')
        @if($value)
            <img src="{{ $value }}" 
                 alt="" 
                 class="w-8 h-8 rounded object-cover"
                 loading="lazy">
        @else
            <div class="w-8 h-8 rounded bg-gray-100 flex items-center justify-center">
                @include('backend.partials.icon', ['icon' => 'image', 'class' => 'w-4 h-4 text-gray-400'])
            </div>
        @endif
        @break

    @case('email')
        @if($value)
            <a href="mailto:{{ $value }}" class="text-primary hover:underline">
                {{ $value }}
            </a>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('url')
    @case('link')
        @if($value)
            <a href="{{ $value }}" 
               target="_blank" 
               rel="noopener noreferrer"
               class="text-primary hover:underline inline-flex items-center gap-1">
                {{ Str::limit($value, 30) }}
                @include('backend.partials.icon', ['icon' => 'externalLink', 'class' => 'w-3 h-3'])
            </a>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('boolean')
        @if($value)
            <span class="inline-flex items-center text-green-600">
                @include('backend.partials.icon', ['icon' => 'check', 'class' => 'w-4 h-4'])
            </span>
        @else
            <span class="inline-flex items-center text-gray-400">
                @include('backend.partials.icon', ['icon' => 'x', 'class' => 'w-4 h-4'])
            </span>
        @endif
        @break

    @case('relation')
        @if($value)
            @php
                // Handle relation display - could be object or ID
                $displayValue = is_object($value) ? ($value->name ?? $value->title ?? $value->id) : $value;
            @endphp
            <span>{{ $displayValue }}</span>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @case('tags')
        @if($value && is_array($value))
            <div class="flex flex-wrap gap-1">
                @foreach(array_slice($value, 0, 3) as $tag)
                    <span class="tag">{{ $tag }}</span>
                @endforeach
                @if(count($value) > 3)
                    <span class="tag">+{{ count($value) - 3 }}</span>
                @endif
            </div>
        @else
            <span class="text-tertiary">-</span>
        @endif
        @break

    @default
        @php
            $displayValue = $value;
            if (is_array($displayValue) || is_object($displayValue)) {
                $displayValue = json_encode($displayValue);
            }
            if ($truncate && strlen($displayValue) > $truncate) {
                $displayValue = Str::limit($displayValue, $truncate);
            }
        @endphp
        
        @if($link && $value)
            <a href="{{ route('admin.entities.show', [$entityName, $record->id]) }}" 
               class="text-primary hover:underline font-medium">
                {{ $displayValue ?? '-' }}
            </a>
        @else
            <span>{{ $displayValue ?? '-' }}</span>
        @endif
@endswitch

