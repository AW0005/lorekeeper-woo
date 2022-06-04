@extends('world.layout')
<style>
div.inventory-item {
    align-self: flex-end !important;
    padding: 0px 5px;
}

.trait {
    flex: 1 1 41px;
    margin-top: 3px;
}

.card-body {
    padding: 1rem;
}

.inventory-item img {
    height: 100px;
    max-width: 100%;
    object-fit: contain;
}
</style>
@section('title') {{ $species->name }} Traits @endsection

@section('content')
{!! breadcrumbs(['World' => 'world', 'Species' => 'world/species', $species->name => $species->url, 'Traits' => 'world/species/'.$species->id.'traits']) !!}
<h1>{{ $species->name }} Traits</h1>

<p>This is a visual index of all {!! $species->displayName !!}-specific traits. Click a trait to view more info on it!</p>

@foreach($features as $categoryId=>$categoryFeatures)
<h5 class="card-header inventory-header mb-3">
    {!! isset($categories[$categoryId]) ? '<a href="'.$categories[$categoryId]->searchUrl.'">'.$categories[$categoryId]->name.'</a>' : 'Miscellaneous' !!}
</h5>
    @if($categories[$categoryId]->description)
    <div class="row mb-4">
        <div class="col-12">
            {!! $categories[$categoryId]->description !!}
        </div>
    </div>
    @endif
    @foreach($categoryFeatures->chunk(4) as $chunk)
        <div class="row mb-3">
            @foreach($chunk as $featureId=>$feature)
                <div class="col-sm-3 col-6 text-center align-self-center inventory-item">
                    @if($feature->first()->has_image)
                        <a href="{{ $feature->first()->url }}">
                            <img class="my-1" src="{{ $feature->first()->imageUrl }}" alt="{{ $feature->first()->name }}" />
                        </a>
                    @endif
                    <p class="trait" data-id="{{ $feature->first()->id }}">
                        {!! $feature->first()->displayName !!}
                    </p>
                </div>
            @endforeach
        </div>
    @endforeach
@endforeach

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.inventory-item').on('click', function(e) {
            e.preventDefault();

            loadModal("{{ url('world/species/'.$species->id.'/trait') }}/" + $(this).data('id'), 'Trait Detail');
        });
    });

</script>
@endsection
