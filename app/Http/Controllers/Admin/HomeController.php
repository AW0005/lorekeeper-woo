<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use Settings;
use Auth;
use DB;
use Config;

use App\Models\Submission\Submission;
use App\Models\Gallery\GallerySubmission;
use App\Models\Character\CharacterDesignUpdate;
use App\Models\Character\CharacterTransfer;
use App\Models\Trade;
use App\Models\Report\Report;

use App\Http\Controllers\Controller;
use App\Models\Currency\Currency;

class HomeController extends Controller
{
    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getIndex()
    {
        $openTransfersQueue = Settings::get('open_transfers_queue');
        // Removing Galleries: $galleryRequireApproval = Settings::get('gallery_submissions_require_approval');
        // Removing Galleries: $galleryCurrencyAwards = Settings::get('gallery_submissions_reward_currency');
        return view('admin.index', [
            'submissionCount' => Submission::where('status', 'Pending')->whereNotNull('prompt_id')->count(),
            'claimCount' => Submission::where('status', 'Pending')->whereNull('prompt_id')->count(),
            'designCount' => CharacterDesignUpdate::characters()->where('status', 'Pending')->count(),
            'myoCount' => CharacterDesignUpdate::myos()->where('status', 'Pending')->count(),
            'reportCount' => Report::where('status', 'Pending')->count(),
            'assignedReportCount' => Report::assignedToMe(Auth::user())->count(),
            'openTransfersQueue' => $openTransfersQueue,
            'transferCount' => $openTransfersQueue ? CharacterTransfer::active()->where('is_approved', 0)->count() : 0,
            'tradeCount' => $openTransfersQueue ? Trade::where('status', 'Pending')->count() : 0,
            'galleryRequireApproval' => false, //Removing Galleries: $galleryRequireApproval,
            'galleryCurrencyAwards' => false, //Removing Galleries: $galleryCurrencyAwards,
            // Removing Galleries: 'gallerySubmissionCount' => GallerySubmission::collaboratorApproved()->where('status', 'Pending')->count(),
            // Removing Galleries: 'galleryAwardCount' => GallerySubmission::requiresAward()->where('is_valued', 0)->count()
        ]);
    }

    /**
     * Shows the staff reward settings index.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getStaffRewardSettings()
    {
        return view('admin.staff_reward_settings', [
            'currency' => Currency::find(Config::get('lorekeeper.extensions.staff_rewards.currency_id')),
            'settings' => DB::table('staff_actions')->orderBy('key')->paginate(20)
        ]);
    }

    /**
     * Edits a staff reward setting.
     *
     * @param  \Illuminate\Http\Request       $request
     * @param  string                         $key
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postEditStaffRewardSetting(Request $request, $key)
    {
        if(DB::table('staff_actions')->where('key', $key)->update(['value' => $request->get('value')])) {
            flash('Setting updated successfully.')->success();
        }
        else {
            flash('Invalid setting selected.')->success();
        }
        return redirect()->back();
    }
}
