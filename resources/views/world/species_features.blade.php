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
</style>
@section('title') {{ $species->name }} Traits @endsection

@section('content')
{!! breadcrumbs(['World' => 'world', 'Species' => 'world/species', $species->name => $species->url, 'Traits' => 'world/species/'.$species->id.'traits']) !!}
<h1>{{ $species->name }} Traits</h1>
<p class="mb-2">
<b>Clicking specific traits can show more info!</b>
</p>
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

<p class="mt-3">AW0005 are digital kemonomimi sentient AIs!<br/>
The company AW0005 has tried to embrace the digital and malleable nature of the AIs forms by providing
ways to have traits that defy what can be achieved in reality. One of their most recent crowning achievements
has been the development of Fully Synthetic legs and Digital Projections inside of Cooling Pockets.
</p>

<h5>SubTypes</h5>
<p>Different AI subtypes have been developed over the years, each with it's own unique traits!<br/>
Today there are primarily two subtypes:
<ol><li><b>AW0001</b> - A <b>Wolf</b> Based AI and the first generation, and an affinity for <b>music</b>.</li>
<li><b>BNNUY02</b> - A <b>Bunny</b> Based AI made to be more humanoid with an affinity for <b>plants</b>.</li>
</ol>
While each subtype by default only come with traits designed for them,  you can buy an upgrade item to allow for usage of other subtype traits!<br/>
You can see this denoted below by the parenthesis.</p>

<h5>Required Traits</h5>
<p>
All AW0005 are required to have at least one trait from each of the below categories <i>except</i> for optional!
</p>

<h5>Re-Designs</h5>
<p>Since they're forms are able to be augmented, it's not uncommen for an AW0005 to change up their
looks by acquiring new traits, or even have multiple forms with different traits! This means as long
as you have the trait item for it, you are always welcome to re-design your AW0005, and they will
always keep access to the prior traits they had as well.</p>

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
@endsection
