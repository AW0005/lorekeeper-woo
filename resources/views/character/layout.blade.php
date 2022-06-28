@extends('layouts.app')

@section('title') Character ::@yield('profile-title')@endsection

@section('sidebar')
    @include('character.'.($isMyo ? 'myo.' : '').'_sidebar')
@endsection

@section('content')
    @yield('profile-content')
@endsection

@section('scripts')
@parent
<style>
    .tags {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .tags .badge {
        padding: 4px 5px;
    }
    .tags .badge a {
        color: white !important;
        padding: 5px;
    }

    .tags > i {
        font-size: 10px;
        padding: 0px 5px;
        vertical-align: middle;
    }

    .tags a {
        font-weight: bold;
    }

    ol.breadcrumb {
        margin-bottom: 0;
    }

    div.toggle.btn {
        min-width: 95px !important;
    }

    .character-bio {
        min-height: 0;
    }

    .character-bio > .tab-pane {
        height: 100%;
    }

    .traits {
        height: calc(100% - 77px);
        overflow: auto;
    }

    .nav-item > a {
        border: 5px solid transparent;
    }

    .form-type {
        font-size: 10px;
        text-transform: none;
        background-color: white;
        position: absolute;
        bottom: -5px;
        left: 10px;
        right: 10px;
        border-radius: 5px;
        border: 1px solid #ccc;
        padding: 0;
    }

    .nav-item .form-type > a {
        border: none;
    }
</style>
<script>
    $( document ).ready(function(){
        $('.bookmark-button').on('click', function(e) {
            e.preventDefault();
            var $this = $(this);
            loadModal($this.data('id') ? "{{ url('account/bookmarks/edit') }}" + '/' + $this.data('id') : "{{ url('account/bookmarks/create') }}?character_id=" + $this.data('character-id'), $this.data('id') ? 'Edit Bookmark' : 'Bookmark Character');
        });
    });
</script>
@endsection
