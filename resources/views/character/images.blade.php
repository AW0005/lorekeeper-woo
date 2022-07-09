@extends('character.layout', ['isMyo' => $character->is_myo_slot])

@section('profile-title') {{ $character->fullName }}'s Images @endsection

@section('meta-img') {{ $character->image->thumbnailUrl }} @endsection

@section('profile-content')
{!! breadcrumbs([($character->category->masterlist_sub_id ? $character->category->sublist->name.' Masterlist' : 'Character masterlist') => ($character->category->masterlist_sub_id ? 'sublist/'.$character->category->sublist->key : 'masterlist' ), $character->fullName => $character->url, 'Images' => $character->url . '/images']) !!}

@include('character._header', ['character' => $character])
<?php $canManage = Auth::check() && Auth::user()->hasPower('manage_characters'); ?>
@if($canManage)
<div class="text-right mb-5" style="margin-top: -70px;">
    <a href="{{ url('admin/character/'.$character->slug.'/image') }}" class="btn btn-outline-info btn-sm"><i class="fas fa-plus"></i> Add Form</a>
</div>
@endif

@foreach($character->images($user)->get() as $image)
<div class="album">
    <div class="d-flex align-items-center bg-dark p-2 ui-corner-all text-white">
        <div class="text-center nav-item mr-3" data-id="{{ $image->id }}" style="width: 15%;">
            @php
                $canViewFull = $image->canViewFull(Auth::check() ? Auth::user() : null);
                $fileExists = file_exists(public_path($image->imageDirectory.'/'.$image->fullsizeFileName));
            @endphp
            <a href="{{ $canViewFull && $fileExists ? $image->fullsizeUrl : $image->imageUrl }}" data-lightbox="entry" data-title="{{ $image->fullName }} [#{{ $image->id }}] {{ $canViewFull && $fileExists ? ' : Full-size Image' : '' }}">
                <img class="bg-light ui-corner-all p-1" src="{{ $image->thumbnailUrl }}"/>
            </a>
        </div>
        <h3>{!! $image->formType !!} Form</h3>
    </div>
    <h5 class="mb-2 mt-4">Additional Images</h5>
    <div id="ref-images">
        @if(count($image->refImages))
        <ul class="row nav image-nav mb-1 w-100">
            @foreach($image->refImages as $displayImage)
                <li class="col-sm-3 col-4 text-center nav-item" data-id="{{ $displayImage->id }}">
                    <div class="d-flex justify-content-center">
                        @php
                            $canViewFull = $displayImage->canViewFull(Auth::check() ? Auth::user() : null);
                            $fileExists = file_exists(public_path($displayImage->imageDirectory.'/'.$displayImage->fullsizeFileName));
                        @endphp
                        <a href="{{ $canViewFull && $fileExists ? $displayImage->fullsizeUrl : $displayImage->imageUrl }}" data-lightbox="entry" data-title="{{ $character->fullName }} [#{{ $displayImage->id }}] {{ $canViewFull && $fileExists ? ' : Full-size Image' : '' }}">
                            <img src="{{ $displayImage->thumbnailUrl }}" class="img-thumbnail" />
                        </a>
                    </div>
                    <div class="form-group">
                    {!! Form::label('Artist(s)') !!}
                    <div id="ref_artistList_{{ $displayImage->id }}" class="text-left">
                        @foreach($displayImage->artists as $artist)
                            <div>{!! $artist->displayLink() !!}</div>
                        @endforeach
                    </div>
                </li>
            @endforeach
        </ul>
        @endif
    </div>
    @if($canManage)
        <a href="{{ url('admin/character/'.$character->slug.'/'.$image->id.'/ref') }}" class="btn btn-outline-info btn-sm mt-2"><i class="fas fa-plus"></i> Add Additional Image</a>
    @endif
    <hr class="mt-4" />
</div>
@endforeach

{{-- @if($canManage)
    {!! Form::open(['url' => 'admin/character/' . $character->slug . '/images/sort', 'class' => 'text-right']) !!}
    {!! Form::hidden('sort', '', ['id' => 'sortableOrder']) !!}
    {!! Form::submit('Save Order', ['class' => 'btn btn-primary']) !!}
    {!! Form::close() !!}
@endif --}}

@endsection
@section('scripts')
    @parent
    @include('character._image_js')
    @if($canManage)
        <script>
            $( document ).ready(function() {
                $( "#sortable" ).sortable({
                    characters: '.sort-item',
                    placeholder: "sortable-placeholder",
                    stop: function( event, ui ) {
                        $('#sortableOrder').val($(this).sortable("toArray", {attribute:"data-id"}));
                    },
                    create: function() {
                        $('#sortableOrder').val($(this).sortable("toArray", {attribute:"data-id"}));
                    }
                });
                $( "#sortable" ).disableSelection();
            });
        </script>
    @endif
    <style>
        .album h3 a {
            color: white;
        }
    </style>
@endsection
