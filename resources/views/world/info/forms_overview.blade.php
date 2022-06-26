@extends('world.layout')

@section('title') AW0005 Forms @endsection

@section('content')
{!! breadcrumbs(['Lore' => 'world']) !!}

<h1>AW0005 Forms</h1>
<p>Given their technological nature, AW0005 will often have interchangeable "forms" that they can change between.</p>
<h3>What counts as a form?</h3>
<p>Any distinct set of traits than an AW0005 might use together! Some forms may only have a single trait different, or every trait could be different!<br/>
The only requirement is that <i>all</i> forms must be recognizable as being the same character, primarily with similar facial features and body shape.</p>

<h3>What about Androids?</h3>
<p>While most forms are Digital
- meaning they are used within the scope of the digital realm, or projected digitally using a holobot -
some AW0005 are able to acquire an Android form.</p>
<p>Android forms allow AW0005 to exist within The Physical Realm,
interact with players, and other organic creatures!</p>
<h5>How can I get an Android?</h5>
<a class="text-primary" href="{{ url('/shops/1') }}">Alba's Shop</a> sells an 'Android' item! When used with a Design Submission
or a Design Update, it creates a form tagged as an Android for your AW0005.

<h3 class="mt-4">How Do I get Additional Forms?</h3>
<p>While an Android form can be added at initial submission, any other additional forms must be submitted after.
These forms will need trait items to cover the traits that are different from the original form.

You can submit a new form from....</p>
@endsection
