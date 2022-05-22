@extends('layouts.app')
<style>
    .timestamp {
        filter: brightness(550%);
        float: right;
    }
</style>
@section('title') {{ $page->title }} @endsection

@section('content')
{!! breadcrumbs([$page->title => $page->url]) !!}
<h1 class="text-center">{{ $page->title }}</h1>

<div class="site-page-content parsed-text">
    {!! $page->parsed_text !!}
</div>

@if($page->can_comment)
    <div class="container">
        @comments(['model' => $page,
                'perPage' => 5
            ])
    </div>
@endif

<div class="timestamp"><strong>Last updated:</strong> {!! format_date($page->updated_at) !!}</div>
@endsection
