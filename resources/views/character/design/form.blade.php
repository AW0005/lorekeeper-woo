@extends('character.design.layout')

@section('design-title') Design Approval Request (#{{ $request->id }}) :: Image @endsection

@section('design-content')
{!! breadcrumbs(['Design Approvals' => 'designs', 'Request (#' . $request->id . ')' => 'designs/' . $request->id, 'Masterlist Image' => 'designs/' . $request->id . '/image']) !!}

@include('character.design._header', ['request' => $request])


{{--
TODO:
TODO:
TODO:
TODO:

Design Updates that are adding a new form will NEED to mark which features / traits
are already on any of the old forms, for ease of approval
since only *new* features will require trait items

--}}

<h2>Masterlist Image</h2>
{!! Form::open(['url' => 'designs/'.$request->id.($image->is_android ? '/android-form' : '/digital-form'), 'files' => true]) !!}
@if($has_image)
    <div class="row mb-2">
        <div class="col-6">
            <div class="p-3 text-center bg-secondary text-white">
                <a href="{{ $request->status !== 'Approved' ? $image->imageUrl : $image->fullsizeUrl }}">
                    <img style="max-height: 200px;" src="{{ $image->thumbnailUrl }}" alt="Thumbnail for request {{ $request->id }}" />
                </a>
            </div>
        </div>
        <div class="col-6">

            @if(!($request->status == 'Draft' && $request->user_id == Auth::user()->id))
                <div class="card-body">
                    <h4 class="mb-3">Credits</h4>
                    <div class="row">
                        <div class="col-lg-4 col-md-6 col-4"><h5>Design</h5></div>
                        <div class="col-lg-8 col-md-6 col-8">
                            @foreach($image->designers as $designer)
                                <div>{!! $designer->displayLink() !!}</div>
                            @endforeach
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4 col-md-6 col-4"><h5>Art</h5></div>
                        <div class="col-lg-8 col-md-6 col-8">
                            @foreach($image->artists as $artist)
                                <div>{!! $artist->displayLink() !!}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@else
    <div class="row">
    <div class="col-12">
@endif

@if(($request->status == 'Draft' && $request->user_id == Auth::user()->id))
    @if($request->status == 'Draft' && $request->user_id == Auth::user()->id)
        <p>Select the image you would like to use on the masterlist. <br />Please only upload images that you are allowed to use AND are able to credit to the artist!</p>
    @else
        <p>As a staff member, you may modify the thumbnail of the uploaded image and/or the credits, but not the image itself. If you have recropped the thumbnail, you may need to hard refresh to see the new one.</p>
    @endif
        @if($request->status == 'Draft' && $request->user_id == Auth::user()->id)
            <div class="form-group">
                {!! Form::label('Image') !!} {!! add_help('This is the image that will be used on the masterlist. Note that the image is not protected in any way, so take precautions to avoid art/design theft.') !!}
                <div>{!! Form::file('image', ['id' => 'mainImage']) !!}</div>
            </div>

        </div>
        </div>
        @else
            <div class="form-group">
                {!! Form::checkbox('modify_thumbnail', 1, 0, ['class' => 'form-check-input', 'data-toggle' => 'toggle']) !!}
                {!! Form::label('modify_thumbnail', 'Modify Thumbnail', ['class' => 'form-check-label ml-3']) !!} {!! add_help('Toggle this option to modify the thumbnail, otherwise only the credits will be saved.') !!}
            </div>
        @endif

        <p>
            This section is for crediting the image creators.<br/> The first box is for the designer or artist's on-site username (if any). The second is for a link to the designer or artist if they don't have an account on the site.
        </p>
        <div class="form-group">
            {!! Form::label('Designer(s)') !!}
            <div id="designerList">
                <?php $designerCount = count($image->designers); ?>
                @foreach($image->designers as $count=>$designer)
                    <div class="mb-2 d-flex">
                        {!! Form::select('designer_id['.$designer->id.']', $users, $designer->user_id, ['class'=> 'form-control mr-2 selectize', 'placeholder' => 'Select a Designer']) !!}
                        {!! Form::text('designer_url['.$designer->id.']', $designer->url, ['class' => 'form-control mr-2', 'placeholder' => 'Designer URL']) !!}
                        @if(Config::get('lorekeeper.extensions.extra_image_credits'))
                        {!! Form::text('designer_type['.$designer->id.']', $designer->credit_type, ['class' => 'form-control mr-2', 'placeholder' => 'Other Info']) !!}
                        @endif
                        <a href="#" class="add-designer btn btn-link" data-toggle="tooltip" title="Add another designer"
                        @if($count != $designerCount - 1)
                            style="visibility: hidden;"
                        @endif
                        >+</a>
                    </div>
                @endforeach
                @if(!count($image->designers))
                    <div class="mb-2 d-flex">
                        {!! Form::select('designer_id[]', $users, null, ['class'=> 'form-control mr-2 selectize', 'placeholder' => 'Select a Designer']) !!}
                        {!! Form::text('designer_url[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Designer URL']) !!}
                        @if(Config::get('lorekeeper.extensions.extra_image_credits'))
                        {!! Form::text('designer_type[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Other Info']) !!}
                        @endif
                        <a href="#" class="add-designer btn btn-link" data-toggle="tooltip" title="Add another designer">+</a>
                    </div>
                @endif
            </div>
            <div class="designer-row hide mb-2">
                {!! Form::select('designer_id[]', $users, null, ['class'=> 'form-control mr-2 designer-select', 'placeholder' => 'Select a Designer']) !!}
                {!! Form::text('designer_url[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Designer URL']) !!}
                @if(Config::get('lorekeeper.extensions.extra_image_credits'))
                    {!! Form::text('designer_type[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Other Info']) !!}
                @endif
                <a href="#" class="add-designer btn btn-link" data-toggle="tooltip" title="Add another designer">+</a>
            </div>
        </div>
        <div class="form-group">
            {!! Form::label('Artist(s)') !!}
            <div id="artistList">
                <?php $artistCount = count($image->artists); ?>
                @foreach($image->artists as $count=>$artist)
                    <div class="mb-2 d-flex">
                        {!! Form::select('artist_id['.$artist->id.']', $users, $artist->user_id, ['class'=> 'form-control mr-2 selectize', 'placeholder' => 'Select an Artist']) !!}
                        {!! Form::text('artist_url['.$artist->id.']', $artist->url, ['class' => 'form-control mr-2', 'placeholder' => 'Artist URL']) !!}
                        @if(Config::get('lorekeeper.extensions.extra_image_credits'))
                            {!! Form::text('artist_type['.$artist->id.']', $artist->credit_type, ['class' => 'form-control mr-2', 'placeholder' => 'Other Info']) !!}
                        @endif
                        <a href="#" class="add-artist btn btn-link" data-toggle="tooltip" title="Add another artist"
                        @if($count != $artistCount - 1)
                            style="visibility: hidden;"
                        @endif
                        >+</a>
                    </div>
                @endforeach
                @if(!count($image->artists))
                    <div class="mb-2 d-flex">
                        {!! Form::select('artist_id[]', $users, null, ['class'=> 'form-control mr-2 selectize', 'placeholder' => 'Select an Artist']) !!}
                        {!! Form::text('artist_url[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Artist URL']) !!}
                        @if(Config::get('lorekeeper.extensions.extra_image_credits'))
                            {!! Form::text('artist_type[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Other Info']) !!}
                        @endif
                        <a href="#" class="add-artist btn btn-link" data-toggle="tooltip" title="Add another artist">+</a>
                    </div>
                @endif
            </div>
            <div class="artist-row hide mb-2">
                {!! Form::select('artist_id[]', $users, null, ['class'=> 'form-control mr-2 artist-select', 'placeholder' => 'Select an Artist']) !!}
                {!! Form::text('artist_url[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Artist URL']) !!}
                @if(Config::get('lorekeeper.extensions.extra_image_credits'))
                    {!! Form::text('artist_type[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Other Info']) !!}
                @endif
                <a href="#" class="add-artist btn btn-link mb-2" data-toggle="tooltip" title="Add another artist">+</a>
            </div>
        </div>

    <h2 class="mt-5">Traits</h2>
    <p>Select the traits for the {{ $request->character->is_myo_slot ? 'created' : 'updated' }} character.
    @if($request->character->is_myo_slot) <br />Some traits may be restricted - you cannot change them.</p>@endif
        <div class="form-group">
            {!! Form::label('species_id', 'Species') !!}
            @if(!$request->character->is_myoSlot || ($request->character->is_myo_slot && $request->character->image->species_id))
                <div class="alert alert-secondary">{!! $request->character->image->species->displayName !!}</div>
            @else
                {!! Form::select('species_id', $specieses, $image->species_id, ['class' => 'form-control', 'id' => 'species']) !!}
            @endif

        </div>

        <div class="form-group">
            {!! Form::label('subtype_id', 'Species Subtype') !!}
            @if(!$request->character->is_myo_slot || ($request->character->is_myo_slot && $request->character->image->subtype_id))
                <div class="alert alert-secondary">{!! $request->character->image->subtype->displayName !!}</div>
            @else
                <div id="subtypes">
                    {!! Form::select('subtype_id', $subtypes, $image->subtype_id, ['class' => 'form-control', 'id' => 'subtype']) !!}
                </div>
            @endif

        </div>

        <div class="form-group">
            {!! Form::label('rarity_id', 'Character Rarity') !!}
            @if($request->character->is_myo_slot && $request->character->image->rarity_id)
                <div class="alert alert-secondary">{!! $request->character->image->rarity->displayName !!}</div>
            @else
                {!! Form::select('rarity_id', $rarities, $image->rarity_id, ['class' => 'form-control', 'id' => 'rarity']) !!}
            @endif
        </div>

        <div class="form-group">
            {!! Form::label('Traits') !!}
            <div id="featureList">
                {{-- Add in the compulsory traits for MYO slots --}}
                @if($request->character->is_myo_slot && $request->character->image->features)
                    @foreach($request->character->image->features as $feature)
                        <div class="mb-2 d-flex align-items-center">
                            {!! Form::text('', $feature->name, ['class' => 'form-control mr-2', 'disabled']) !!}
                            {!! Form::text('', $feature->data, ['class' => 'form-control mr-2', 'disabled']) !!}
                            <div>{!! add_help('This trait is required.') !!}</div>
                        </div>
                    @endforeach
                @endif

                {{-- Add in the ones that currently exist --}}
                @if($image->updateFeatures)
                    @foreach($image->updateFeatures as $feature)
                        <div class="mb-2 d-flex original">
                            {!! Form::select('feature_id[]', $features, $feature->feature_id, ['class' => 'form-control mr-2 feature-select', 'placeholder' => 'Select Trait', 'value' => $feature->feature_id]) !!}
                            {!! Form::text('feature_data[]', $feature->data, ['class' => 'form-control mr-2', 'placeholder' => 'Extra Info (Optional)']) !!}
                            <a href="#" class="remove-feature btn btn-danger mb-2"><i class="fas fa-times"></i></a>
                        </div>
                    @endforeach
                @endif
            </div>
            <div><a href="#" class="btn btn-primary" id="add-feature">Add Trait</a></div>
            <div class="feature-row hide mb-2">
                {!! Form::select('feature_id[]', $features, null, ['class' => 'form-control mr-2 feature-select', 'placeholder' => 'Select Trait']) !!}
                {!! Form::text('feature_data[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Extra Info (Optional)']) !!}
                <a href="#" class="remove-feature btn btn-danger mb-2"><i class="fas fa-times"></i></a>
            </div>
        </div>
        <div class="text-right">
            {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
        </div>

    {!! Form::close() !!}
@else
    <div class="mb-1">
        <div class="row">
            <div class="col-md-2 col-4"><h5>Species</h5></div>
            <div class="col-md-10 col-8">{!! $image->species ? $image->species->displayName : 'None Selected' !!}</div>
        </div>
        @if($image->subtype_id || $request->character->image->subtype_id)
        <div class="row">
            <div class="col-md-2 col-4"><h5>Subtype</h5></div>
            <div class="col-md-10 col-8">
            @if($request->character->is_myo_slot && $request->character->image->subtype_id)
                {!! $request->character->image->subtype->displayName !!}
            @else
                {!! $image->subtype_id ? $image->subtype->displayName : 'None Selected' !!}
            @endif
            </div>
        </div>
        @endif
        <div class="row">
            <div class="col-md-2 col-4"><h5>Rarity</h5></div>
            <div class="col-md-10 col-8">{!! $image->rarity ? $image->rarity->displayName : 'None Selected' !!}</div>
        </div>
    </div>
    <h5>Traits</h5>
    <div>
        @if($request->character && $request->character->is_myo_slot && $request->character->image->features)
            @foreach($request->character->image->features as $feature)
                <div>@if($feature->feature->feature_category_id) <strong>{!! $feature->feature->category->displayName !!}:</strong> @endif {!! $feature->feature->displayName !!} @if($feature->data) ({{ $feature->data }}) @endif <span class="text-danger">*Required</span></div>
            @endforeach
        @endif
        @foreach(($request->status !== 'Approved' ? $image->updateFeatures : $image->features) as $feature)
            <div>@if($feature->feature->feature_category_id) <strong>{!! $feature->feature->category->displayName !!}:</strong> @endif {!! $feature->feature->displayName !!} @if($feature->data) ({{ $feature->data }}) @endif</div>
        @endforeach
    </div>
@endif

@endsection

@section('scripts')
@include('widgets._image_upload_js', ['useUploaded' => ($request->status == 'Pending' && Auth::user()->hasPower('manage_characters'))])
<script>
$( "#species" ).change(function() {
    var species = $('#species').val();
    var id = '<?php echo($request->id); ?>';
    $.ajax({
        type: "GET", url: "{{ url('designs/traits/subtype') }}?species="+species+"&id="+id, dataType: "text"
    }).done(function (res) { $("#subtypes").html(res); }).fail(function (jqXHR, textStatus, errorThrown) { alert("AJAX call failed: " + textStatus + ", " + errorThrown); });

});
</script>
@endsection
