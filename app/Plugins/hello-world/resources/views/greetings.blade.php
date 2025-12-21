@extends('hello-world::layouts.plugin', [
    'currentPage' => 'hello-world',
    'currentPageLabel' => 'Greetings',
    'currentPageIcon' => 'messageSquare',
    'pageTitle' => 'Greetings',
])

@section('plugin-title', 'Greetings - Hello World')

@section('plugin-header', 'Greetings')

@section('plugin-header-actions')
    <a href="{{ route('plugins.hello-world.index') }}" class="plugin-btn plugin-btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back to Plugin
    </a>
@endsection

@section('plugin-content')
    @if(session('success'))
        <div class="plugin-alert plugin-alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Add New Greeting Form --}}
    <div class="plugin-card">
        <div class="plugin-card-header">
            <h3 class="plugin-card-title">Add New Greeting</h3>
        </div>
        <div class="plugin-card-body">
            <form action="{{ route('plugins.hello-world.greetings.store') }}" method="POST">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: var(--spacing-4); align-items: end;">
                    <div class="plugin-form-group" style="margin-bottom: 0;">
                        <label for="message" class="plugin-form-label">Message</label>
                        <input type="text" name="message" id="message" class="plugin-form-input" 
                               placeholder="Enter your greeting message..." required>
                    </div>
                    <div class="plugin-form-group" style="margin-bottom: 0;">
                        <label for="author" class="plugin-form-label">Author (optional)</label>
                        <input type="text" name="author" id="author" class="plugin-form-input" 
                               placeholder="Your name">
                    </div>
                    <button type="submit" class="plugin-btn plugin-btn-primary" style="height: 42px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add Greeting
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Greetings List --}}
    <div class="plugin-card">
        <div class="plugin-card-header">
            <h3 class="plugin-card-title">All Greetings</h3>
            <span style="color: var(--text-tertiary); font-size: var(--text-sm);">
                {{ $greetings->total() }} {{ Str::plural('greeting', $greetings->total()) }}
            </span>
        </div>
        <div class="plugin-card-body">
            @if($greetings->isEmpty())
                <div class="plugin-empty-state">
                    <div class="plugin-empty-state-icon">💬</div>
                    <h4 class="plugin-empty-state-title">No greetings yet</h4>
                    <p>Be the first to add a greeting using the form above!</p>
                </div>
            @else
                <div class="greetings-list">
                    @foreach($greetings as $greeting)
                        <div class="greeting-item">
                            <div class="greeting-content">
                                <p class="greeting-message">"{{ $greeting->message }}"</p>
                                <p class="greeting-meta">
                                    <span class="greeting-author">{{ $greeting->author }}</span>
                                    <span class="greeting-separator">•</span>
                                    <span class="greeting-time">{{ $greeting->created_at->diffForHumans() }}</span>
                                </p>
                            </div>
                            <form action="{{ route('plugins.hello-world.greetings.destroy', $greeting) }}" 
                                  method="POST"
                                  class="delete-greeting-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="plugin-btn plugin-btn-danger" style="padding: 6px 12px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>

                @if($greetings->hasPages())
                    <div class="pagination-wrapper">
                        {{ $greetings->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection

@push('plugin-scripts')
<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete confirmation forms
    document.querySelectorAll('.delete-greeting-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to delete this greeting?')) {
                e.preventDefault();
                return false;
            }
        });
    });
});
</script>
@endpush

@push('plugin-styles')
<style>
    .greetings-list {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-3);
    }

    .greeting-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--spacing-4);
        background: var(--bg-surface-1);
        border-radius: var(--radius-md);
        transition: background 0.2s ease;
    }

    .greeting-item:hover {
        background: var(--bg-surface-2);
    }

    .greeting-content {
        flex: 1;
        min-width: 0;
    }

    .greeting-message {
        color: var(--text-primary);
        font-size: var(--text-base);
        margin-bottom: var(--spacing-1);
        word-break: break-word;
    }

    .greeting-meta {
        color: var(--text-tertiary);
        font-size: var(--text-sm);
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
    }

    .greeting-author {
        font-weight: 500;
        color: var(--text-secondary);
    }

    .greeting-separator {
        opacity: 0.5;
    }

    .pagination-wrapper {
        margin-top: var(--spacing-6);
        display: flex;
        justify-content: center;
    }

    .pagination-wrapper nav {
        display: flex;
        gap: var(--spacing-1);
    }

    .pagination-wrapper a,
    .pagination-wrapper span {
        padding: var(--spacing-2) var(--spacing-3);
        border-radius: var(--radius-md);
        font-size: var(--text-sm);
        text-decoration: none;
    }

    .pagination-wrapper a {
        background: var(--bg-surface-1);
        color: var(--color-primary);
        border: 1px solid var(--border-default);
    }

    .pagination-wrapper a:hover {
        background: var(--bg-surface-2);
    }

    .pagination-wrapper span.bg-blue-500,
    .pagination-wrapper .active {
        background: var(--color-primary) !important;
        color: white !important;
        border-color: var(--color-primary) !important;
    }
</style>
@endpush
