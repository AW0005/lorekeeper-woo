@extends('world.layout')

@section('title') All About MYOs @endsection

@section('content')
<div class="d-flex justify-content-between">
    {!! breadcrumbs(['Lore' => 'world']) !!}
    <div class="d-inline-flex flex-wrap justify-content-center" style="gap: 7.2px;">
    <a class="badge badge-primary" href="#get">How to Get MYOs <i class="fas fa-caret-down"></i></a>
    <a class="badge badge-primary" href="#use">How to Use MYOs <i class="fas fa-caret-down"></i></a>
    </div>
</div>
<h1 class="mt-4">All About MYOs</h1>
<p>A Make-Your-Own (MYO) Slot gives you the ability to make your own AW0005 (or Holo)!
<h3>What are the types of MYOs?</h3>
<p>There are three main types of MYOs!<br/>
No matter the rarity of the MYO, they are all limited to traits within  your selected subtype!</p>
<div class="row text-center">
    <div class="col-4">
        <img src="https://aw0005.com/images/data/items/6-image.png" />
        <b class="mt-2 d-block">Common (CMYO)</b>
        <p>Common Traits</p>
    </div>
    <div class="col-4">
        <img src="https://aw0005.com/images/data/items/1-image.png" />
        <b class="mt-2 d-block">Standard (SMYO)</b>
        <p>Common or Rare Traits</p>
    </div>
    <div class="col-4">
        <img src="https://aw0005.com/images/data/items/2-image.png" />
        <b class="mt-2 d-block">Epic (EMYO)</b>
        <p>Any Rarity Traits</p>
    </div>
</div>
<h5 class="d-flex align-items-center">What about Holo MYOs? <a class="badge badge-primary ml-3" href="https://aw0001.weebly.com/holobotbud.html">Holo Guide</a></h5>
<p>There are only two types of Holo MYOs <br/>
Holo's do not have traits limited by rarity, so it's just a matter of which type you're making.</p>
<div class="row text-center justify-content-center">
    <div class="col-4">
        <img src="https://aw0005.com/images/data/items/12-image.png" />
        <b class="mt-2 d-block">HOLOBOT-MYO</b>
        <p>Allows creation of a HOLOBOT</p>
    </div>
    <div class="col-4">
        <img src="https://aw0005.com/images/data/items/13-image.png" />
        <b class="mt-2 d-block">HOLOBUDDY-MYO</b>
        <p>Allows creation of a HOLOBUDDY</p>
    </div>
</div><br />
<h5>Can I Upgrade my MYOs?</h5>
<div class="d-flex" style="gap: 20px">
    <img src="https://aw0005.com/images/data/items/7-image.png" />
    <p><a class="text-primary" href="{{ url('/shops/1') }}">Alba's Shop</a> sells an item called the MYO Upgrade. This item can be used on <b>any</b>
    Common MYO to make it a Standard MYO, regardless of how it was acquired</p>
</div><br />
<h3 id="get" class="mt-5">How do I get them?</h3>
<h5>Member MYOs (MBR)</h5>
<p>Each year, you can buy 2 Member MYOs at 400cc from <a class="text-primary" href="{{ url('/shops/1') }}">Alba's Shop.</a></p>
<h5>Birthday MYOs (BDAY)</h5>
<div class="d-flex" style="gap: 20px">
    <img style="max-width: 100px;" src="https://aw0005.com/files/images/BDAY-MYO.png" />
    <p>Once a year on during your birthday month you'll be granted a BDAY-MYO.<br />
    BDAY-MYOs are special in that they are a Common MYO with a <b>floating trait</b> of any rarity.<br/><br/>
    Right now these are exclusive to the Discord Server, but we're hoping to automate them in Lorekeeper soon!</p>
</div><br />

<h5>Purchase</h5>
<p><a class="text-primary" href="{{ url('/shops/1') }}">Alba's Shop</a> sells Standard and Epic MYOs, at 1000cc and 35000cc respectively. <br/>
You can also purchase MYOs (and other items) for USD from the Discord server, but we're also hoping to move this into Lorekeeper eventually as well!</p>
<h5>Donation Tree</h5>
<p><a class="text-primary" href="{{ url('/shops/donation-shop') }}">The Donation Tree</a> has MYOs (and other items) available for free, as donated by other players.<br />
The donation tree has a two week cooldown though, so you'll want to be choosy about what you grab!
</p>
<h5>MYO events</h5>
<p>Twice a year we host MYO events on the Discord Server (and advertise elsewhere). During these events you can fufill
a series of requirements to get a free Common MYO. Usually designing for these events is timed.</p>

<h5>Raffles, Trading, etc.</h5>
<p>There are also a myriad of other ways to acquire a MYO. Ocassionally we do raffles in the Discord server, and other
users will also offer their MYOs up for trade.</p>

<h5>Finding Your Owned MYOs</h5>
<div class="d-flex" style="gap: 20px">
    <img style="max-width: 100px;" src="https://aw0005.com/files/Screenshots/MYOSlots.png" />
    <p>While logged in, you can click on the AW0005 logo, which will take you to your dashboard.<br/><br/>
    That page should display some of your owned MYOs, or you can click the link in the sidebar of that page (screenshot on the left) to get to the entire list.</p>
</div><br/>
<h3 id="use" class="mt-5">How do I use one?</h3>
<div class="d-flex" style="gap: 20px">
    <img style="max-width: 100px" src="https://aw0005.com/files/Screenshots/MYOSubmission" />
    <p>Once you've selected a MYO you want to use, you'll want to click on the link shown in the screenshot on the left, and hit "Create Request".<br/><br/>
    This will open up a new page with 5 tabs. <br/>
    You have to go through each tab and fill it out before you can submit the design as a whole.<br/> <br />
    Hitting save on each of the tabs will save your <b>draft</b> and you're welcome to work on it over multiple days. <br/>
    </p>
</div>
<p>The design won't be submitted to us until you hit the "Submit" button on the first tab.
<h5 class="mt-3">Comments</h5>
<p>You don't have to put anything in this section, you can just hit submit, but if you have any written notes you want to include to explain your design or why you're picking the traits you did, or links to additional images, you're welcome to put them here.</p>

<h5>Masterlist Image</h5>
<p>This is where you add the main image for the masterlist.</p>
<ul>
<li>Your masterlist image <i>can</i> be unclothed with ken-doll-esque anatomy.</li>
<li>We prefer a clothed version of your design, and either an unclothed image or separate partial references be provided via link in the comments section for trait verification.</li>
<li>If you have multiple reference images, please either provide links to the additional images via the comments box.</li>
</ul>
<h5>Add-Ons</h5>
<p>This is where you'll add any traits or items that you are using on top of the MYO in exchange for the final list of traits on your design. They'll be consumed only once the design is approved.</p>

<h5>Traits</h5>
<p>The important part!<br/>
Feel free to use the "save" button often to save your progress.<br/>
If the traits you select include a trait above the rarity locked in for your slot please mention that in the comments tab so we can make sure we update it to the correct rarity when your design is approved.
</p>
<p class="text-center"><b>
If you have any questions don't be afraid to ask!</b></p>
@endsection

@section('scripts')
<style>
.breadcrumb {
    margin-bottom: 0px;
}

.row hr {
    flex: 1 1 90%;
    margin: 0px 50px;
    margin-bottom: 15px;
}

html {
    scroll-behavior: smooth;
}

a.badge {
    font-size: 11px;
    text-transform: initial;
}

span.badge {
    font-size: 100%;
}
</style>
@endsection
