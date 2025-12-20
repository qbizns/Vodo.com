@extends('layouts.backend')

@section('title', 'System Logs')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3>System Logs</h3>
                <p class="text-muted">{{ $logPath }}</p>
            </div>
            <div class="card-body">
                <div class="bg-dark text-white p-3" style="height: 500px; overflow-y: scroll; font-family: monospace;">
                    @forelse($logs as $log)
                        <div class="log-line">{{ $log }}</div>
                    @empty
                        <div class="text-center text-muted">No logs found.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
