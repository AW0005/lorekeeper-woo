<nav class="navbar navbar-expand-md navbar-light">
    <ul class="navbar-nav ml-auto mr-auto">
        <li class="nav-item"><a href="{{ url('info/welcome') }}" class="nav-link">About</a></li>
        <li class="nav-item"><a href="{{ url('info/affiliates') }}" class="nav-link">Affiliates</a></li>
        <li class="nav-item"><a href="{{ url('info/terms') }}" class="nav-link">Terms</a></li>
        <li class="nav-item"><a href="{{ url('info/privacy') }}" class="nav-link">Privacy</a></li>
        <li class="nav-item"><a href="{{ url('reports/bug-reports') }}" class="nav-link">Bug Reports</a></li>
        <li class="nav-item"><a href="https://github.com/corowne/lorekeeper" class="nav-link">Lorekeeper</a></li>
        <li class="nav-item"><a href="{{ url('credits') }}" class="nav-link">Credits</a></li>
    </ul>
</nav>
<div class="copyright">
    &copy; {{ config('lorekeeper.settings.site_name', 'Lorekeeper') }} v{{ config('lorekeeper.settings.version') }} {{ Carbon\Carbon::now()->year }}
    <a style="margin-left: 20px;" href="https://www.digitalocean.com/?refcode=6b3a8a289e71&utm_campaign=Referral_Invite&utm_medium=Referral_Program&utm_source=badge"><img style="height: 30px" src="https://web-platforms.sfo2.digitaloceanspaces.com/WWW/Badge%203.svg" alt="DigitalOcean Referral Badge" /></a>
</div>

@if(Config::get('lorekeeper.extensions.scroll_to_top'))
    @include('widgets/_scroll_to_top')
@endif
