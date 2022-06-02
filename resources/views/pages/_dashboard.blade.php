<style>
    .compact {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding-bottom: 10px;
    }

    .compact .row {
        margin-bottom: 10px;
    }
</style>

<div class="row" style="justify-content: space-between; align-items: flex-end;">
    <div class="col-sm-9">
        <h1 class="m-0">Welcome, {!! Auth::user()->displayName !!}!</h1>
    </div>
    <div class="col-sm-3 text-right">
        <h5>{!! isset($currency) ? $currency->display($currency->quantity) : 0 !!}</h5>
    </div>
</div>
<hr class="mt-2 mb-4" />
<div class="d-flex mb-4 align-items-center">
    <h6 class="m-0 pl-1" style="writing-mode:tb;transform: rotate(180deg);">Badges</h6>
    <div class="card flex-grow-1">
    @if(count($awards))
        <div class="row no-gutters">
            @foreach($awards as $item)
                <div class="col-sm-1 col-2 p-1">
                    @if($item->imageUrl)
                        <a href="{{ $item->url }}" class="h6 mb-0"><img src="{{ $item->imageUrl }}"  style="max-width: 100%" data-toggle="tooltip" title="{{ $item->name }}" alt="{{ $item->name }}"/></a>
                    @else
                    <div class="h6 mt-1">
                        {!! $item->displayName !!}
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="p-2">No Badges Owned.</div>
    @endif
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <h3>News & Sales</h2>
        <div class="card mb-4">
        @foreach($posts as $post)
            <div class="card-header">
                <h5 class="card-title mb-0">{!! $post->displayName !!}</h5>
                <small>
                    Posted {!! $post->post_at ? pretty_date($post->post_at) : pretty_date($post->created_at) !!} by {!! $post->user->displayName !!}
                </small>
            </div>
        @endforeach
        </div>
    </div>
    <div class="col-md-6">
    <div class="d-flex flex-row justify-content-between align-items-center"><h3>Open Prompts</h3><span class="text-right"><a href="{{ url('prompts/prompts') }}">View all...</a></span></div>
        <div class="card mb-4">
        @foreach($prompts as $post)
            <div class="card-header">
                <h5 class="card-title mb-0">{!! $post->displayName !!}</h5>
                <small>
                    @if($post->summary){{ $post->summary }} <br/>@endif
                    @if($post->start_at && $post->start_at->isFuture())<strong>Starts: </strong>{!! pretty_date($post->start_at) !!}@endif :: @if($post->end_at)<strong>Ends: </strong>{!! pretty_date($post->end_at) !!}@endif
                </small>
            </div>
        @endforeach
        </div>
    </div>
</div>
<div class="row mb-4">
    <div class="col-md-6" style="display: flex;flex-direction: column;">
        <div class="d-flex flex-row justify-content-between align-items-center"><h3>Inventory</h3><span class="text-right"><a href="{{ $user->url.'/inventory' }}">View all...</a></span></div>
        <div class="card" style="flex: 1;">
            <div class="card-body text-center compact">
                @if(count($items))
                    <div class="row no-gutters">
                        @foreach($items as $item)
                            <div class="col-3 p-1">
                                @if($item->imageUrl)
                                    <a href="{{ $item->url }}" class="h6 mb-0"><img src="{{ $item->imageUrl }}"  style="max-width: 100%" data-toggle="tooltip" title="{{ $item->name }}" alt="{{ $item->name }}"/></a>
                                @else
                                <div class="h6 mt-1">
                                    {!! $item->displayName !!}
                                </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div>No items owned.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
    <div class="d-flex flex-row justify-content-between align-items-center"><h3>MYOs</h3><span><a href="{{ $user->url.'/myos' }}">View all...</a></span></div>
        <div class="card">
            <div class="card-body text-center compact">
            @if(count($myos))
                <div class="row no-gutters">
                    @foreach($myos as $myo)
                    <div class="col-3 text-center p-1">
                        @if($myo->image->thumbnailUrl)
                        <div>
                            <a href="{{ $myo->url }}"><img src="{{ $myo->image->thumbnailUrl }}" data-toggle="tooltip"  title="{{ $myo->fullName }}" alt="{{ $myo->fullName }}" style="max-width: 100%" /></a>
                        </div>
                        @else
                        <div class="mt-1">
                            <a href="{{ $myo->url }}" class="h6 mb-0"> @if(!$myo->is_visible) <i class="fas fa-eye-slash"></i> @endif {{ $myo->fullName }}</a>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                    <div>No MYOs owned.</div>
                @endif
            </div>
        </div>
    </div>
</div>
