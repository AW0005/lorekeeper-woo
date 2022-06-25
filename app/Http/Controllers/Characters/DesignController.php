<?php

namespace App\Http\Controllers\Characters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

use DB;
use Auth;
use Settings;
use Config;

use App\Models\Item\Item;
use App\Models\User\User;
use App\Models\User\UserItem;
use App\Models\Character\Character;
use App\Models\Character\CharacterDesignUpdate;
use App\Models\Species\Species;
use App\Models\Species\Subtype;
use App\Models\Rarity;
use App\Models\Feature\Feature;
use App\Models\Item\ItemCategory;
use App\Services\CharacterManager;
use App\Models\Character\CharacterImage;

use App\Http\Controllers\Controller;

class DesignController extends Controller
{
    /**
     * Shows the index of character design update submissions.
     *
     * @param  string  $type
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getDesignUpdateIndex($type = null)
    {
        $requests = CharacterDesignUpdate::where('user_id', Auth::user()->id);
        if(!$type) $type = 'draft';
        $requests->where('status', ucfirst($type));

        return view('character.design.index', [
            'requests' => $requests->orderBy('id', 'DESC')->paginate(20),
            'status' => $type
        ]);
    }

    /**
     * Shows a design update request.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getDesignUpdate($id)
    {
        $r = CharacterDesignUpdate::find($id);
        if(!$r || ($r->user_id != Auth::user()->id && !Auth::user()->hasPower('manage_characters'))) abort(404);
        return view('character.design.request', [
            'request' => $r
        ]);
    }

    /**
     * Shows a design update request's comments section.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getComments($id)
    {
        $r = CharacterDesignUpdate::find($id);
        if(!$r || ($r->user_id != Auth::user()->id && !Auth::user()->hasPower('manage_characters'))) abort(404);
        return view('character.design.comments', [
            'request' => $r
        ]);
    }

    /**
     * Edits a design update request's comments section.
     *
     * @param  \Illuminate\Http\Request       $request
     * @param  App\Services\CharacterManager  $service
     * @param  int                            $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postComments(Request $request, CharacterManager $service, $id)
    {
        $r = CharacterDesignUpdate::find($id);
        if(!$r) abort(404);
        if($r->user_id != Auth::user()->id) abort(404);

        if($service->saveRequestComment($request->only(['comments']), $r)) {
            flash('Request edited successfully.')->success();
        }
        else {
            foreach($service->errors()->getMessages()['error'] as $error) flash($error)->error();
        }
        return redirect()->back();
    }

    /**
     * Shows a design update request's image section.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getForm($id)
    {
        $r = CharacterDesignUpdate::find($id);
        if(!$r || ($r->user_id != Auth::user()->id && !Auth::user()->hasPower('manage_characters'))) abort(404);

        $image = $r->image;
        if($r->status === 'Draft' && !isset($image)) {
            $image = $this->instantiateImage($r);
        }

        return view('character.design.form', [
            'request' => $r,
            'image' => isset($image) ? $image : $r,
            'users' => User::query()->orderBy('name')->pluck('name', 'id')->toArray(),
            'specieses' => ['0' => 'Select Species'] + Species::orderBy('sort', 'DESC')->pluck('name', 'id')->toArray(),
            'subtypes' => ['0' => 'No Subtype'] + Subtype::where('species_id','=',$r->species_id)->orderBy('sort', 'DESC')->pluck('name', 'id')->toArray(),
            'rarities' => ['0' => 'Select Rarity'] + Rarity::orderBy('sort', 'DESC')->pluck('name', 'id')->toArray(),
            'features' => Feature::getFeaturesByCategory(true)
        ]);
    }


    /**
     * This should only ever get hit if we have a deprecated in-progress design update
     * that needs to be moved onto the new setup.
     */
    private function instantiateImage($request) {
        $image = CharacterImage::create([
            'character_id' => $request->id,
            'is_visible' => 1,
            'hash' => $request->hash,
            'fullsize_hash' => $request->fullsize_hash ? $request->fullsize_hash : randomString(15),
            'extension' => Config::get('lorekeeper.settings.masterlist_image_format'),

            'species_id' => $request->species_id,
            'subtype_id' => ($request->character->is_myo_slot && isset($request->character->image->subtype_id)) ? $request->character->image->subtype_id : $request->subtype_id,
            'rarity_id' => $request->rarity_id,
            'sort' => 0,
            'is_design_update' => 1,
        ]);

        if(File::exists($request->imagePath . '/' . $request->imageFileName)){
            // Move the pre-existing image file to the new image
            File::move($request->imagePath . '/' . $request->imageFileName, $image->imagePath . '/' . $image->imageFileName);
            File::move($request->thumbnailPath . '/' . $request->thumbnailFileName, $image->thumbnailPath . '/' . $image->thumbnailFileName);
        }

        if(count($request->rawFeatures) > 0) {
            // Move pre-existing features
            $request->rawFeatures()->update(['character_image_id' => $image->id]);
        }

        // Shift the image credits over to the new image
        if(count($request->designers) > 0) {
            $request->designers()->update(['character_image_id' => $image->id, 'character_type' => 'Character']);
        }
        if(count($request->artists)) {
            $request->artists()->update(['character_image_id' => $image->id, 'character_type' => 'Character']);
        }

        return $image;
    }

    /**
     * Edits a design update request's image upload section.
     *
     * @param  \Illuminate\Http\Request       $request
     * @param  App\Services\CharacterManager  $service
     * @param  int                            $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postForm(Request $request, CharacterManager $service, $id)
    {
        $r = CharacterDesignUpdate::find($id);
        if(!$r) abort(404);
        if($r->user_id != Auth::user()->id && !Auth::user()->hasPower('manage_characters')) abort(404);
        $request->validate(CharacterDesignUpdate::$imageRules);

        $useAdmin = ($r->status != 'Draft' || $r->user_id != Auth::user()->id) && Auth::user()->hasPower('manage_characters');
        if($service->saveRequestImage($request->all(), $r, $useAdmin)) {
            if($service->saveRequestFeatures($request->all(), $r)) {
                flash('Request edited successfully.')->success();
            }
        }
        else {
            foreach($service->errors()->getMessages()['error'] as $error) flash($error)->error();
        }
        return redirect()->back();
    }

    /**
     * Shows a design update request's addons section.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getAddons($id)
    {
        $r = CharacterDesignUpdate::find($id);
        if(!$r || ($r->user_id != Auth::user()->id && !Auth::user()->hasPower('manage_characters'))) abort(404);
        if($r->status == 'Draft' && $r->user_id == Auth::user()->id)
            $inventory = UserItem::with('item')->whereNull('deleted_at')->where('count', '>', '0')->where('user_id', $r->user_id)->get();
        else
            $inventory = isset($r->data['user']) ? parseAssetData($r->data['user']) : null;
        return view('character.design.addons', [
            'request' => $r,
            'categories' => ItemCategory::orderBy('sort', 'DESC')->get(),
            'inventory' => $inventory,
            'items' => Item::all()->keyBy('id'),
            'item_filter' => Item::orderBy('name')->get()->keyBy('id'),
            'page' => 'update'
        ]);
    }

    /**
     * Edits a design update request's addons section.
     *
     * @param  \Illuminate\Http\Request       $request
     * @param  App\Services\CharacterManager  $service
     * @param  int                            $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postAddons(Request $request, CharacterManager $service, $id)
    {
        $r = CharacterDesignUpdate::find($id);
        if(!$r) abort(404);
        if($r->user_id != Auth::user()->id) abort(404);

        if($service->saveRequestAddons($request->all(), $r)) {
            flash('Request edited successfully.')->success();
        }
        else {
            foreach($service->errors()->getMessages()['error'] as $error) flash($error)->error();
        }
        return redirect()->back();
    }

    /**
     * Shows the edit image subtype portion of the modal
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getFeaturesSubtype(Request $request) {
        $species = $request->input('species');
        $id = $request->input('id');
        return view('character.design._features_subtype', [
            'subtypes' => ['0' => 'Select Subtype'] + Subtype::where('species_id','=',$species)->orderBy('sort', 'DESC')->pluck('name', 'id')->toArray(),
            'subtype' => $id
        ]);
    }

    /**
     * Shows the design update request submission confirmation modal.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getConfirm($id)
    {
        $r = CharacterDesignUpdate::find($id);
        if(!$r || ($r->user_id != Auth::user()->id && !Auth::user()->hasPower('manage_characters'))) abort(404);
        return view('character.design._confirm_modal', [
            'request' => $r
        ]);
    }

    /**
     * Submits a design update request for approval.
     *
     * @param  App\Services\CharacterManager  $service
     * @param  int                            $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postSubmit(CharacterManager $service, $id)
    {
        $r = CharacterDesignUpdate::find($id);
        if(!$r) abort(404);
        if($r->user_id != Auth::user()->id) abort(404);

        if($service->submitRequest($r)) {
            flash('Request submitted successfully.')->success();
        }
        else {
            foreach($service->errors()->getMessages()['error'] as $error) flash($error)->error();
        }
        return redirect()->back();
    }

    /**
     * Shows the design update request deletion confirmation modal.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getDelete($id)
    {
        $r = CharacterDesignUpdate::find($id);
        if(!$r || ($r->user_id != Auth::user()->id && !Auth::user()->hasPower('manage_characters'))) abort(404);
        return view('character.design._delete_modal', [
            'request' => $r
        ]);
    }

    /**
     * Deletes a design update request.
     *
     * @param  App\Services\CharacterManager  $service
     * @param  int                            $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postDelete(CharacterManager $service, $id)
    {
        $r = CharacterDesignUpdate::find($id);
        if(!$r) abort(404);
        if($r->user_id != Auth::user()->id) abort(404);

        if($service->deleteRequest($r)) {
            flash('Request deleted successfully.')->success();
        }
        else {
            foreach($service->errors()->getMessages()['error'] as $error) flash($error)->error();
        }
        return redirect()->to('designs');
    }
}
