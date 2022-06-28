<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">@if(!$character->is_visible) <i class="fas fa-eye-slash"></i> @endif {!! $character->displayName !!}</h1>
            <div style="margin-top: -5px;">Owned by {!! $character->displayOwner !!}</div>
        </div>
        <div class="tags">
            @if(Config::get('lorekeeper.extensions.character_TH_profile_link') && $character->profile && $character->profile->link)
                <a class="badge badge-lg btn-primary" data-character-id="{{ $character->id }}" href="{{ $character->profile->link }}"><i class="fas fa-home"></i> Off-Site Profile</a>
            @endif
        </div>
    </div>
</div>
