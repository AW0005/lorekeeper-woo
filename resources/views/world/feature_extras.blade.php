@extends('world.layout')

@section('title')
    Trait Info
@endsection

@section('content')
    {!! breadcrumbs(['World' => 'world', 'Rarities' => 'world/rarities']) !!}
    <h1>Trait Info</h1>
    <p>This is a list of all text that has been passed in the open input associated to the {!! $feature->displayName !!} trait.</p>

    <div>
        {!! Form::open(['method' => 'GET', 'class' => 'form-inline justify-content-end']) !!}
        <div class="form-group mr-3 mb-3">
            {!! Form::text('name', Request::get('name'), ['class' => 'form-control']) !!}
        </div>
        <div class="form-group mb-3">
            {!! Form::submit('Search', ['class' => 'btn btn-primary']) !!}
        </div>
        {!! Form::close() !!}
    </div>

    {!! $extras->render() !!}
    <div class="row flex-wrap">
        @foreach ($extras as $extra)
            <div class="col-xl-4 col-6">
                <div class="card mb-3">
                    <div class="card-body d-flex" style="gap: 3px;">
                        {{ $extra->data }} <a class="text-muted" href="{{ url('masterlist?feature_id[]=' . $feature->id . '&feature_extra=' . urlencode($extra->data)) }}"><i class="fas fa-search mr-2"></i></a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    {!! $extras->render() !!}

    <div class="text-center mt-4 small text-muted">{{ $extras->total() }} result{{ $extras->total() == 1 ? '' : 's' }} found.</div>
@endsection
