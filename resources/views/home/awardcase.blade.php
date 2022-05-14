@extends('home.layout')

@section('home-title') Badges @endsection

@section('home-content')
{!! breadcrumbs(['Badges' => 'Awards']) !!}

<h1>
    Badges
</h1>

<p>These are the badges you've earned for participating on this site.</p>
@foreach($awards as $categoryId=>$categoryAwards)
    <div class="card mb-3 awardcase-category">
        <h5 class="card-header awardcase-header">
            {!! isset($categories[$categoryId]) ? '<a href="'.$categories[$categoryId]->searchUrl.'">'.$categories[$categoryId]->name.'</a>' : 'Miscellaneous' !!}
        </h5>
        <div class="card-body awardcase-body">
            @foreach($categoryAwards->chunk(4) as $chunk)
                <div class="row mb-3">
                    @foreach($chunk as $awardId=>$stack)
                        <div class="col-sm-3 col-6 text-center awardcase-award" data-id="{{ $stack->first()->pivot->id }}" data-name="{{ $user->name }}'s {{ $stack->first()->name }}">
                            <div class="mb-1">
                                <a href="#" class="awardcase-stack {{ $stack->first()->is_featured ? 'alert alert-success' : '' }}"><img src="{{ $stack->first()->imageUrl }}" /></a>
                            </div>
                            <div>
                                <a href="#" class="awardcase-stack awardcase-stack-name">{{ $stack->first()->name }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@endforeach
<div class="text-right mb-4">
    <a href="{{ url(Auth::user()->url.'/award-logs') }}">View logs...</a>
</div>

@endsection
@section('scripts')
<script>

$(document).ready(function() {
    $('.awardcase-stack').on('click', function(e) {
        e.preventDefault();
        var $parent = $(this).parent().parent();
        loadModal("{{ url('awards') }}/" + $parent.data('id'), $parent.data('name'));
    });
});

</script>
@endsection
