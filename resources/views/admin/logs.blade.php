@extends('admin.layout')

@section('admin-title') Log of Logs @endsection

@section('admin-content')
{!! breadcrumbs(['Admin Panel' => 'admin', 'Log of Logs' => 'logs']) !!}

<h1>Log of Logs</h1>
{!! $logs->render() !!}
<div class="row ml-md-2 mb-4">
  <div class="d-flex row flex-wrap col-12 mt-1 pt-1 px-0 ubt-bottom">
    <div class="col-6 col-md-2 font-weight-bold">Log Date</div>
    <div class="col-6 col-md-2 font-weight-bold">Sender</div>
    <div class="col-6 col-md-2 font-weight-bold">Recipient</div>
    <div class="col-6 col-md-4 font-weight-bold">Log</div>
    <div class="col-6 col-md-2 font-weight-bold">Character</div>
  </div>
    @foreach($logs as $log)
        <div class="d-flex row flex-wrap col-12 mt-1 pt-1 px-0 ubt-top">
        <div class="col-6 col-md-2">{!! pretty_date($log->created_at) !!}</div>
        <div class="col-6 col-md-2">
            {!! $log->user ? $log->user->displayName : '' !!}
            {!! $log->sender ? $log->sender->displayName.' <i style="color: red;" class="fas fa-minus-circle"></i>' : '' !!}
        </div>
        <div class="col-6 col-md-2">{!! $log->recipient ? $log->recipient->displayName.' <i style="color: green;" class="fas fa-plus-circle"></i>' : '' !!}</div>
        <div class="col-6 col-md-4">{!! $log->log || $log->data !!}</div>
        <div class="col-6 col-md-2">{!! $log->character ? $log->character->displayName : 'n/a' !!}</div>
        </div>
    @endforeach
    {!! $logs->render() !!}
</div>
@endsection
