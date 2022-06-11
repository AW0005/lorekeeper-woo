@extends('world.layout')

@section('title') AW0005 Species Overview @endsection

@section('content')
{!! breadcrumbs(['Lore' => 'world', 'Species' => 'world/species']) !!}
<h1>AW0005 Species Overview</h1>
<p class="mt-3">AW0005 are kemonomimi sentient AIs that mostly live in a digital realm, but can also exist in the physical as an Android.</p>
<h3>Where did they come from?</h3>
<p>
Between Verse Year (VY) 166 and 173, a 15 year old Jason Jin created, further developed, and fought for the rights of the digital character AIs and Androids that would come to be known as AW0001.
<br /><br />
In VY195, Jason Jin, the creator of AW0005, died of a terminal illness and left his company in the hands of the first Sentient AI that he had created. By then he had left behind a hidden generator in the AW0001 HQs servers that would continue to generate new intelligent AIs on an ongoing basis.
<br /><br />
In VY197 Jason returned in the form of a new BNNUY02 based on his memory scans not long before he died, and with him an entire new digital world full of BNNUY02s that he had developed and put into place much as he had the AW0001 generator code.
</p>

<h3>What Makes Up an AW0005?</h3>
<p>
All AW0005 are required to have at least one trait from each of the categories shown below!<br/>
<a class="text-primary" href="{{ url('/world/species/1/traits') }}">We have another page that has Category and Trait Details.</a>
<div class="d-flex justify-content-center"><img src="https://aw0005.com/files/images/TraitCategoriesTransparent.png" /></div>
</p>

<h3>What are SubTypes?</h3>
<p>SubTypes are the different types of AW0005!<br/>There are two today:</p>
<ol><li><b>AW0001</b> - A <b>Wolf</b> Based AI and the first generation, and an affinity for <b>music</b>, and <b>robotics</b>.</li>
<li><b>BNNUY02</b> - A <b>Bunny</b> Based AI made to be more humanoid with an affinity for <b>plants</b>.</li>
</ol>
<p>By default some traits are reserved for a specific subtype, but you can buy an upgrade item to allow for usage of other subtype traits!</p>

<h5>What's the Difference?</h5>
<p>There are four ways in which each subtype differs from another:</p>
<ul>
    <li><b>Ears</b> - Comes from the subtype's associated animal and must always appear to be shaped similar to the animal's ears, even if the material differs!</li>
    <li><b>Tail</b> - Comes from the subtype's associated animal by default but there are traits to allow other tails!</li>
    <li><b>CTRL</b> - Each subtype has a different form for their CTRl, the device by which they interact with their systems.</li>
    <li><b>Other Reserved Traits</b> - Each subtype also has other traits reserved for them, generally associated to the affinities listed above.</li>
</ul>
<h5>Anthro or Humanoid?</h5>
<p><b>All</b> AW0005, regardless of subtype are able to interchangeably present themselves as Anthro or Humanoid at their discretion. While many AW0005 will generally
settle for one or the other, it is also not uncommon for some to shift regularly between the two!</p>
<h5>What's allowed for Skin / Fur Colors?</h5>
<p>Since AW0005 can be humanoid or Anthro - or any combination as well - they can have any simple skin or fur color without the need for a trait. This also includes
gentle gradients into other colors - for example to transition from fur to skin tones. The only thing not permitted without a trait is shiny, translucent, shimmer, or holographic
skin.</p><br />

<h3>Can I Re-Design my AW0005?</h3>
<p>Since they're forms are able to be augmented, it's not uncommen for an AW0005 to change up their
looks by acquiring new traits, or even have multiple forms with different traits! This means as long
as you have the trait item for it, you are always welcome to re-design your AW0005, and they will
always keep access to the prior traits they had as well. The only restriction is that they are still recognizable
as the same character.</p>
<p>You can start a re-design request the same way you submit a normal MYO!</p>
@endsection

@section('scripts')
<style>
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
