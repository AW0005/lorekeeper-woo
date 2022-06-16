@extends('shops.layout')

@section('shops-title') {{ $shop->name }} @endsection

@section('shops-content')
{!! breadcrumbs(['Shops' => 'shops', $shop->name => $shop->url]) !!}

<h1>
    {!! $shop->name !!} Logs
</h1>

<div class="row ml-md-2 mb-4">
  <div class="d-flex row flex-wrap col-12 mt-1 pt-1 px-0 ubt-bottom">
    <div class="col-6 col-md-2 font-weight-bold">Sender or Buyer</div>
    <div class="col-6 col-md-4 font-weight-bold">Item</div>
    <div class="col-6 col-md-2 font-weight-bold">Cost</div>
    <div class="col-6 col-md-2 font-weight-bold">Quantity</div>
    <div class="col-6 col-md-2 font-weight-bold">Date</div>
  </div>
    @foreach($logs as $log)
        <div class="d-flex row flex-wrap col-12 mt-1 pt-1 px-0 ubt-top">
        <div class="col-6 col-md-2">
            {!! $log->user ? $log->user->displayName : '' !!}
            {!! $log->recipient ? $log->recipient->displayName.' <i style="color: green;" class="fas fa-plus-circle"></i>' : '' !!}
            {!! $log->sender ? $log->sender->displayName.' <i style="color: red;" class="fas fa-minus-circle"></i>' : '' !!}
        </div>
        <div class="col-6 col-md-4">{!! $log->item ? $log->item->displayName : '(Deleted Item)' !!} (×{!! $log->quantity !!})</div>
        <div class="col-6 col-md-2">{!! $log->currency && $log->cost ? $log->currency->display($log->cost) : 'n/a' !!}</div>
        <div class="col-6 col-md-2">{!! $log->quantity !!}</div>
        <div class="col-6 col-md-2">{!! pretty_date($log->created_at) !!}</div>
        </div>
    @endforeach
</div>

@endsection
