@extends('character.layout')

@section('profile-title') {{ $character->fullName }} - Upload New Image @endsection

@section('profile-content')
{!! breadcrumbs(['Masterlist' => 'masterlist', $character->fullName => $character->url]) !!}

@include('character._header', ['character' => $character])

<p>This will add a new additional image / reference image to a form. If the character is marked as visible, the owner of the character will be notified of the upload.</p>

{!! Form::open(['url' => 'admin/character/'.$character->slug.'/'.$form->id.'/ref', 'files' => true]) !!}

<h3>Image Upload</h3>

<div class="form-group">
    {!! Form::label('Character Image') !!}
    <div>{!! Form::file('image', ['id' => 'mainImage']) !!}</div>
</div>
<div class="form-group">
    {!! Form::label('Artist(s)') !!}
    <div id="artistList">
        <div class="mb-2 d-flex">
            {!! Form::select('artist_id[]', $users, null, ['class'=> 'form-control mr-2 selectize', 'placeholder' => 'Select an Artist']) !!}
            {!! Form::text('artist_url[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Artist URL']) !!}
            @if(Config::get('lorekeeper.extensions.extra_image_credits'))
                {!! Form::text('artist_type[]', null, ['class' => 'form-control mr-2', 'placeholder' => 'Other Info']) !!}
            @endif
            <a href="#" class="add-artist btn btn-link" data-toggle="tooltip" title="Add another artist">+</a>
        </div>
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

<div class="text-right">
    {!! Form::submit('Create Image', ['class' => 'btn btn-primary']) !!}
</div>
{!! Form::close() !!}

@endsection

@section('scripts')
@parent
<script>
$( document ).ready(function() {

    // Designers and artists //////////////////////////////////////////////////////////////////////
    $('.add-artist').on('click', function(e) {
        e.preventDefault();
        addArtistRow($(this));
    });
    function addArtistRow($trigger) {
        var $clone = $('.artist-row').clone();
        $('#artistList').append($clone);
        $clone.removeClass('hide artist-row');
        $clone.addClass('d-flex');
        $clone.find('.add-artist').on('click', function(e) {
            e.preventDefault();
            addArtistRow($(this));
        })
        $clone.find('.artist-select').selectize();
        $trigger.css({ visibility: 'hidden' });
    }
});

</script>
@endsection
