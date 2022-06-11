@extends('world.layout')

@section('title') {{ $species->name }} Traits @endsection

@section('content')
{!! breadcrumbs(['Lore' => 'world', 'Species' => 'world/species', $species->name => $species->url, 'Traits' => 'world/species/'.$species->id.'traits']) !!}
<div class="d-flex flex-wrap justify-content-center" style="gap: 7.2px;">
<a class="badge badge-primary" href="#CTRLs">CTRLs <i class="fas fa-caret-down"></i></a>
<a class="badge badge-primary" href="#Cooling Pockets">Cooling Pockets <i class="fas fa-caret-down"></i></a>
<a class="badge badge-primary" href="#Thermosensors">Thermosensors <i class="fas fa-caret-down"></i></a>
<a class="badge badge-primary" href="#Head">Head <i class="fas fa-caret-down"></i></a>
<a class="badge badge-primary" href="#Ears">Ears <i class="fas fa-caret-down"></i></a>
<a class="badge badge-primary" href="#Eyes">Eyes <i class="fas fa-caret-down"></i></a>
<a class="badge badge-primary" href="#Tail">Tail <i class="fas fa-caret-down"></i></a>
<a class="badge badge-primary" href="#Legs">Legs <i class="fas fa-caret-down"></i></a>
<a class="badge badge-primary" href="#Optional Mods">Optional Mods <i class="fas fa-caret-down"></i></a>
<a class="badge badge-primary" href="#Android">Android <i class="fas fa-caret-down"></i></a>
</div>
<center>
<h1 class="mt-4 mb-0">{{ $species->name }} Traits</h1>
<p class="mt-0" style="color: red"><b>WIP::</b> This page is still partially in progress as we're working on new images for the traits!</p>

<p class="mt-3">AW0005 are kemonomimi sentient AIs!<br/>
<a class="text-primary" href="{{ url('/world/species-overview') }}">Click here for an overview of the species!</a>
</p>

<h5 class="mb-0">Required Traits</h5>
<p>
One trait from each of the below categories <i>except</i> for optional!
</p>

<h5 class="mb-1">Rarities</h5>
<div class="d-flex flex-wrap justify-content-center pb-3" style="gap: 7.2px;">
    @foreach($rarities as $rarity)
    @if($rarity->name !== 'Voided')
    <span class="badge badge-primary" style="background: #{{ $rarity->color }};">{!! $rarity->name !!}</span>
    @endif
    @endforeach
</div>

<p class="mb-1 mt-3">
<b>Clicking on trait images will show more info!</b>
</p>
</center>
@foreach($features as $categoryId=>$categoryFeatures)
<a data-toggle="collapse" href="#category-{{ $categoryId }}" aria-expanded="true">
    <h4 id="{{$categories[$categoryId]->name}}" class="card-header inventory-header mb-3">
        {!! isset($categories[$categoryId]) ? $categories[$categoryId]->name : 'Miscellaneous' !!}
        <i class="fas fa-angle-down float-right"></i>
    </h4>
</a>
<div class="collapse show" id="category-{{ $categoryId }}">
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
                <div class="col-sm-3 col-6 text-center align-self-center inventory-item"  data-id="{{ $feature->first()->id }}">
                    @if($feature->first()->has_image)
                        <a href="{{ $feature->first()->url }}">
                            <img class="my-1" src="{{ $feature->first()->imageUrl }}" alt="{{ $feature->first()->name }}" />
                        </a>
                    @endif
                    <p class="trait">
                        {!! $feature->first()->displayName !!}
                    </p>
                </div>
                @if($categories[$categoryId]->name === 'CTRLs' && $feature->first()->name === '5 CTRLs') <hr /> @endif
                @if($categories[$categoryId]->name === 'Cooling Pockets' && $feature->first()->name === 'Whole Body') <hr /> @endif
            @endforeach
        </div>
    @endforeach
    </div>
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
<style>
div.inventory-item {
    align-self: flex-end !important;
    padding: 0px 12px;
}

div.inventory-item:not(:last-of-type)::after {
    content: '';
    height: 50%;
    position: absolute;
    right: 0;
    top: 25%;
    border-right: 1px solid #dfdfdf;
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

.row hr {
    flex: 1 1 90%;
    margin: 0px 50px;
    margin-bottom: 15px;
}

html {
    scroll-behavior: smooth;
}

a.badge {
    font-size: 88%;
}

span.badge {
    font-size: 100%;
}
</style>
@endsection
