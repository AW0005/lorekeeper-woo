@extends('admin.layout')

@section('admin-title') Log of Logs @endsection

@section('scripts')
<style>
table tr td { border: none !important; }
table tr { border-bottom: 1px solid #ccc }

tr > td:first-child,
tr > td:last-child {
    white-space: nowrap;
}
</style>
@endsection

@section('admin-content')
{!! breadcrumbs(['Admin Panel' => 'admin', 'Log of Logs' => 'logs']) !!}

<h1>Log of Logs</h1>
{!! $logs->render() !!}
<table class="mb-2">
    <thead>
        <tr>
            <th>Log Date</th>
            <th>Sender</th>
            <th></th>
            <th>Recipient</th>
            <th>Log</th>
            <th>Character / Item</th>
        </tr>
    </thead>
    <tbody>
    @foreach($logs as $log)
        <tr>
            <td>{!! pretty_date($log->created_at) !!}</td>
            <td>
                {!! $log->user ? $log->user->displayName : '' !!}
                {!! $log->sender ? $log->sender->displayName : '' !!}
            </td>
            <td><i class="fas fa-long-arrow-alt-right"></i></td>
            <td>{!! $log->recipient ? $log->recipient->displayName : '' !!}</td>
            <td>{!! $log->log ? $log->log : $log->type !!}</td>
            <td>{!! $log->character ? $log->character->displayName : ($log->item ? $log->item->displayName.' (×'.$log->quantity.')' : 'n/a') !!}</td>
        </tr>
    @endforeach
    </tbody>
</table>
{!! $logs->render() !!}
@endsection
