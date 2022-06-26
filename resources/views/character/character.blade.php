@extends('character.layout', ['isMyo' => $character->is_myo_slot])

@section('profile-title') {{ $character->fullName }} @endsection

@section('meta-img') {{ $character->image->thumbnailUrl }} @endsection

@section('profile-content')

@include('widgets._awardcase_feature', ['target' => $character, 'count' => Config::get('lorekeeper.extensions.awards.character_featured'), 'float' => true])
<div class="d-flex justify-content-between align-items-center">
@if($character->is_myo_slot)
{!! breadcrumbs([$character->fullName => $character->url]) !!}
@else
{!! breadcrumbs([($character->category->masterlist_sub_id ? $character->category->sublist->name.' Masterlist' : 'Character masterlist') => ($character->category->masterlist_sub_id ? 'sublist/'.$character->category->sublist->key : 'masterlist' ), $character->fullName => $character->url]) !!}
@endif
@if(Config::get('lorekeeper.extensions.character_status_badges'))
    <!-- character trade/gift status badges -->
    <div>
        <span class="btn {{ $character->is_trading ? 'badge-success' : 'badge-danger' }} float-right ml-2" data-toggle="tooltip" title="{{ $character->is_trading ? 'OPEN for sale and trade offers.' : 'CLOSED for sale and trade offers.' }}"><i class="fas fa-comments-dollar"></i></span>
        @if(!$character->is_myo_slot)
            <span class="btn {{ $character->is_gift_writing_allowed == 1 ? 'badge-success' : ($character->is_gift_writing_allowed == 2 ? 'badge-warning text-light' : 'badge-danger') }} float-right ml-2" data-toggle="tooltip" title="{{ $character->is_gift_writing_allowed == 1 ? 'OPEN for gift writing.' : ($character->is_gift_writing_allowed == 2 ? 'PLEASE ASK before gift writing.' : 'CLOSED for gift writing.') }}"><i class="fas fa-file-alt"></i></span>
            <span class="btn {{ $character->is_gift_art_allowed == 1 ? 'badge-success' : ($character->is_gift_art_allowed == 2 ? 'badge-warning text-light' : 'badge-danger') }} float-right ml-2" data-toggle="tooltip" title="{{ $character->is_gift_art_allowed == 1 ? 'OPEN for gift art.' : ($character->is_gift_art_allowed == 2 ? 'PLEASE ASK before gift art.' : 'CLOSED for gift art.') }}"><i class="fas fa-pencil-ruler"></i></span>
        @endif
    </div>
@endif
</div>

@include('character._header', ['character' => $character])

{{-- Main Image --}}
<div class="tab-content">
    @foreach($character->images()->with('features.feature')->with('species')->with('rarity')->get() as $image)
        <div class="tab-pane fade {{ $image->id == $character->character_image_id ? 'show active' : '' }}" id="image-{{ $image->id }}">
            <div class="row mb-3">
                <div class="col-md-7">
                    <div class="text-center">
                        <a href="{{ $image->canViewFull(Auth::check() ? Auth::user() : null) && file_exists( public_path($image->imageDirectory.'/'.$image->fullsizeFileName)) ? $image->fullsizeUrl : $image->imageUrl }}" data-lightbox="entry" data-title="{{ $character->fullName }} [#{{ $image->id }}] {{ $image->canViewFull(Auth::check() ? Auth::user() : null) && file_exists( public_path($image->imageDirectory.'/'.$image->fullsizeFileName)) ? ' : Full-size Image' : ''}}">
                            <img style="max-height: calc(100vh - 250px);" src="{{ $image->canViewFull(Auth::check() ? Auth::user() : null) && file_exists( public_path($image->imageDirectory.'/'.$image->fullsizeFileName)) ? $image->fullsizeUrl : $image->imageUrl }}" class="image" alt="{{ $image->character->fullName }}" />
                        </a>
                    </div>
                    @if($image->canViewFull(Auth::check() ? Auth::user() : null) && file_exists( public_path($image->imageDirectory.'/'.$image->fullsizeFileName)))
                        <div class="text-right">You are viewing the full-size image. <a href="{{ $image->imageUrl }}">View watermarked image</a>?</div>
                    @endif
                </div>
                @include('character._image_info', ['image' => $image])
            </div>
        </div>
    @endforeach
</div>
<?php $canManage = Auth::check() && Auth::user()->hasPower('manage_characters'); ?>

{{-- Info --}}
<div class="card character-bio">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link active" id="statsTab" data-toggle="tab" href="#stats" role="tab">Stats</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="notesTab" data-toggle="tab" href="#notes" role="tab">Description</a>
            </li>
            @if(Auth::check() && Auth::user()->hasPower('manage_characters'))
                <li class="nav-item">
                    <a class="nav-link" id="settingsTab" data-toggle="tab" href="#settings-{{ $character->slug }}" role="tab">Admin <i class="fas fa-cog"></i></a>
                </li>
            @endif
        </ul>
    </div>
    <div class="card-body tab-content">
        <div class="tab-pane fade show active" id="stats">
            @include('character._tab_stats', ['character' => $character, 'parent' => $parent, 'children' => $children])
        </div>
        <div class="tab-pane fade" id="notes">
            @include('character._tab_notes', ['character' => $character])
        </div>
        @if(Auth::check() && Auth::user()->hasPower('manage_characters'))
            <div class="tab-pane fade" id="settings-{{ $character->slug }}">
                {!! Form::open(['url' => $character->is_myo_slot ? 'admin/myo/'.$character->id.'/settings' : 'admin/character/'.$character->slug.'/settings']) !!}
                    <div class="form-group">
                        {!! Form::checkbox('is_visible', 1, $character->is_visible, ['class' => 'form-check-input', 'data-toggle' => 'toggle']) !!}
                        {!! Form::label('is_visible', 'Is Visible', ['class' => 'form-check-label ml-3']) !!} {!! add_help('Turn this off to hide the character. Only mods with the Manage Masterlist power (that\'s you!) can view it - the owner will also not be able to see the character\'s page.') !!}
                    </div>
                    <div class="text-right">
                        {!! Form::submit('Edit', ['class' => 'btn btn-primary']) !!}
                    </div>
                {!! Form::close() !!}
                <hr />
                <div class="d-flex justify-content-end" style="gap: 5px">
                    @if($canManage)
                        <a href="{{ url('admin/character/'.$character->slug.'/image') }}" class="float-right btn btn-outline-info btn-sm"><i class="fas fa-plus"></i> Add Image</a>
                    @endif
                    <a href="#" class="btn btn-outline-danger btn-sm delete-character" data-slug="{{ $character->slug }}">Delete</a>
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

@section('scripts')
    @parent
    @include('character._image_js', ['character' => $character])
@endsection
