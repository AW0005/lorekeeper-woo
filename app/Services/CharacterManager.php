<?php namespace App\Services;

use App\Services\Service;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use App\Facades\Notifications;
use App\Facades\Settings;


use App\Services\CurrencyManager;
use App\Services\Utilities\CharacterUtility;

use Illuminate\Support\Arr;
use App\Models\User\User;
use App\Models\User\UserItem;
use App\Models\Character\Character;
use App\Models\Character\CharacterCurrency;
use App\Models\Character\CharacterCategory;
use App\Models\Character\CharacterImage;
use App\Models\Character\CharacterFeature;
use App\Models\Character\CharacterTransfer;
use App\Models\Character\CharacterDesignUpdate;
use App\Models\Character\CharacterBookmark;
use App\Models\Character\CharacterLink;
use App\Models\Species\Species;
use App\Models\Species\Subtype;
use App\Models\Rarity;
use App\Models\Currency\Currency;

class CharacterManager extends Service
{
    /*
    |--------------------------------------------------------------------------
    | Character Manager
    |--------------------------------------------------------------------------
    |
    | Handles creation and modification of character data.
    |
    */

    /**
     * Retrieves the next number to be used for a character's masterlist code.
     *
     * @param  int  $categoryId
     * @return string
     */
    public function pullNumber($categoryId, $year)
    {
        $digits = Config::get('lorekeeper.settings.character_number_digits');
        $result = str_pad('', $digits, '0'); // A default value, in case
        $number = 0;

        // First check if the number needs to be the overall next
        // or next in category, and retrieve the highest number
        if(Config::get('lorekeeper.settings.character_pull_number') == 'all')
        {
            $character = Character::myo(0)->orderBy('number', 'DESC')->first();
            if($character) $number = ltrim($character->number, 0);
            if(!strlen($number)) $number = '0';
        }
        else if (Config::get('lorekeeper.settings.character_pull_number') == 'category' && $categoryId)
        {
            $character = Character::myo(0)->where('character_category_id', $categoryId)->where('year', $year)->orderBy('number', 'DESC')->first();
            if($character) $number = ltrim($character->number, 0);
            if(!strlen($number)) $number = '0';
        }

        $result = format_masterlist_number($number + 1, $digits);

        return $result;
    }

    /**
     * Creates a new character or MYO slot.
     *
     * @param  array                  $data
     * @param  \App\Models\User\User  $user
     * @param  bool                   $isMyo
     * @return \App\Models\Character\Character|bool
     */
    public function createCharacter($data, $user, $isMyo = false)
    {
        DB::beginTransaction();

        try {
            if(!$isMyo && Character::where('slug', $data['slug'])->exists()) throw new \Exception("Please enter a unique character code.");

            if(!(isset($data['user_id']) && $data['user_id']) && !(isset($data['owner_url']) && $data['owner_url']) && !(isset($data['owner_alias']) && $data['owner_alias']) && !(isset($data['parent_id']) && $data['parent_id']))
                throw new \Exception("Please select an owner.");
            if(!$isMyo)
            {
                if(!(isset($data['species_id']) && $data['species_id'])) throw new \Exception('Characters require a species.');
                if(!(isset($data['rarity_id']) && $data['rarity_id'])) throw new \Exception('Characters require a rarity.');
            }
            if(isset($data['subtype_id']) && $data['subtype_id'])
            {
                $subtype = Subtype::find($data['subtype_id']);
                if(!(isset($data['species_id']) && $data['species_id'])) throw new \Exception('Species must be selected to select a subtype.');
                if(!$subtype || $subtype->species_id != $data['species_id']) throw new \Exception('Selected subtype invalid or does not match species.');
            }
            else $data['subtype_id'] = null;

            // Get owner info
            $url = null;
            $recipientId = null;
            $alias = null;
            if(isset($data['parent_id']) && $data['parent_id'])
            {
                // Find the new parent of the character
                $parent = Character::where('id', $data['parent_id'])->first();
                //find new owner based on parent
                $recipient = User::find($parent->user_id);
                if(!$recipient) $recipient = Character::where('id', $data['parent_id'])->first()->owner_alias;
                //we dont want the child to be tradeable/transferrable...
                $data['is_sellable'] = null;
                $data['is_tradeable'] = null;
                $data['is_giftable'] = null;
            }
            elseif(isset($data['user_id']) && $data['user_id']) $recipient = User::find($data['user_id']);
            elseif(isset($data['owner_url']) && $data['owner_url']) $recipient = checkAlias($data['owner_url']);

            if(is_object($recipient)) {
                $recipientId = $recipient->id;
                $data['user_id'] = $recipient->id;
            }
            else {
                $url = $recipient;
            }

            // Create character
            $character = $this->handleCharacter($data, $isMyo);
            if(!$character) throw new \Exception("Error happened while trying to create character.");

            // Create character link
            if(isset($data['parent_id']) && $data['parent_id'])
            {
                CharacterLink::create([
                    'parent_id' => $data['parent_id'],
                    'child_id' => $character->id
                ]);
            }

            // Create character image
            $data['is_valid'] = true; // New image of new characters are always valid
            $image = $this->handleCharacterImage($data, $character, $isMyo);
            if(!$image) throw new \Exception("Error happened while trying to create image.");

            // Update the character's image ID
            $character->character_image_id = $image->id;
            $character->save();

            // Add a log for the character
            // This logs all the updates made to the character
            $this->createLog($user->id, null, $recipientId, $url, $character->id, $isMyo ? 'MYO Slot Created' : 'Character Created', 'Initial upload', 'character');

            // Add a log for the user
            // This logs ownership of the character
            $this->createLog($user->id, null, $recipientId, $url, $character->id, $isMyo ? 'MYO Slot Created' : 'Character Created', 'Initial upload', 'user');

            // Update the user's FTO status and character count
            if(is_object($recipient)) {
                if(!$isMyo) {
                    $recipient->settings->is_fto = 0; // MYO slots don't affect the FTO status - YMMV
                }
                $recipient->settings->save();
            }

            // If the recipient has an account, send them a Notifications
            if(is_object($recipient) && $user->id != $recipient->id) {
                Notifications::create($isMyo ? 'MYO_GRANT' : 'CHARACTER_UPLOAD', $recipient, [
                    'character_url' => $character->url,
                ] + ($isMyo ?
                    ['name' => $character->name] :
                    ['character_slug' => $character->slug]
                ));
            }

            return $this->commitReturn($character);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Handles character data.
     *
     * @param  array                  $data
     * @param  bool                   $isMyo
     * @return \App\Models\Character\Character|bool
     */
    private function handleCharacter($data, $isMyo = false)
    {
        try {
            if($isMyo)
            {
                $data['character_category_id'] = null;
                $data['number'] = null;
                $data['slug'] = null;
                $data['species_id'] = isset($data['species_id']) && $data['species_id'] ? $data['species_id'] : null;
                $data['subtype_id'] = isset($data['subtype_id']) && $data['subtype_id'] ? $data['subtype_id'] : null;
                $data['rarity_id'] = isset($data['rarity_id']) && $data['rarity_id'] ? $data['rarity_id'] : null;
            }

            $characterData = Arr::only($data, [
                'character_category_id', 'rarity_id', 'user_id',
                'number', 'year', 'slug', 'description',
                'sale_value', 'transferrable_at', 'is_visible'
            ]);

            $characterData['name'] = ($isMyo && isset($data['name'])) ? $data['name'] : null;
            $characterData['owner_url'] = isset($characterData['user_id']) ? null : $data['owner_url'];
            $characterData['is_sellable'] = isset($data['is_sellable']);
            $characterData['is_tradeable'] = isset($data['is_tradeable']);
            $characterData['is_giftable'] = isset($data['is_giftable']);
            $characterData['is_visible'] = $isMyo ? 1 : isset($data['is_visible']);
            $characterData['sale_value'] = isset($data['sale_value']) ? $data['sale_value'] : 0;
            $characterData['is_gift_art_allowed'] = 0;
            $characterData['is_gift_writing_allowed'] = 0;
            $characterData['is_trading'] = 0;
            $characterData['parsed_description'] = isset($data['description']) ? parse($data['description']) : null;
            if($isMyo) $characterData['is_myo_slot'] = 1;

            $character = Character::create($characterData);

            // Create character profile row
            $profile = isset($data['profile_link']) ? ['link' => $data['profile_link']] : [];
            $character->profile()->create($profile);

            return $character;
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return false;
    }

    /**
     * Handles character image data.
     *
     * @param  array                            $data
     * @return \App\Models\Character\Character  $character
     * @param  bool                             $isMyo
     * @return \App\Models\Character\CharacterImage|bool
     */
    private function handleCharacterImage($data, $character, $isMyo = false)
    {
        try {
            if($isMyo)
            {
                $data['species_id'] = (isset($data['species_id']) && $data['species_id']) ? $data['species_id'] : null;
                $data['subtype_id'] = isset($data['subtype_id']) && $data['subtype_id'] ? $data['subtype_id'] : null;
                $data['rarity_id'] = (isset($data['rarity_id']) && $data['rarity_id']) ? $data['rarity_id'] : null;


                // Use default images for MYO slots without an image provided
                if(!isset($data['image']))
                {
                    $data['extension'] = 'png';
                    $data['default_image'] = true;
                }
            }
            $imageData = Arr::only($data, [
                'species_id', 'subtype_id', 'rarity_id',
            ]);
            $imageData['description'] = isset($data['image_description']) ? $data['image_description'] : null;
            $imageData['parsed_description'] = parse($imageData['description']);
            $imageData['hash'] = randomString(10);
            $imageData['fullsize_hash'] = randomString(15);
            $imageData['sort'] = 0;
            $imageData['is_valid'] = isset($data['is_valid']);
            $imageData['is_visible'] = isset($data['is_visible']);
            $imageData['extension'] = (Config::get('lorekeeper.settings.masterlist_image_format') ? Config::get('lorekeeper.settings.masterlist_image_format') : (isset($data['extension']) ? $data['extension'] : $data['image']->getClientOriginalExtension()));
            $imageData['character_id'] = $character->id;

            $image = CharacterImage::create($imageData);

            // Easier to pick these up after the image model exists and we can grab names
            if(isset($data['default_image'])) {
                $data['image'] = public_path('images/'.$image->species->name.'-myo/'.$image->rarity->name.'.png');
                $data['thumbnail'] = public_path('images/'.$image->species->name.'-myo/'.$image->rarity->name.'.png');
            }

            CharacterUtility::handleImageCredits($image->id, $data);

            // Save image
            $this->handleImage($data['image'], $image->imageDirectory, $image->imageFileName, null, isset($data['default_image']));

            // Save thumbnail first before processing full image
            $this->cropThumbnail($image, $isMyo);

            // Process and save the image itself
            if(!$isMyo) CharacterUtility::processImage($image);

            // Attach features
            if(!$isMyo) CharacterUtility::handleCharacterFeatures($image->id, $data['feature_id'], $data['feature_data']);


            return $image;
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return false;

    }



    /**
     * Crops a thumbnail for the given image.
     *
     * @param  array                                 $points
     * @param  \App\Models\Character\CharacterImage  $characterImage
     */
    private function cropThumbnail($characterImage, $isMyo = false)
    {
        $image = Image::make($characterImage->imagePath . '/' . $characterImage->imageFileName);

        // Make the image be square
        $isWide = $image->width() > $image->height();
        $image->resizeCanvas($isWide ? null : $image->height(), $isWide ? $image->width() : null, 'center');

        // Resize to fit the thumbnail size
        $image->resize(Config::get('lorekeeper.settings.masterlist_thumbnails.width'), Config::get('lorekeeper.settings.masterlist_thumbnails.height'));

        // Save the thumbnail
        $image->save($characterImage->thumbnailPath . '/' . $characterImage->thumbnailFileName, 100, Config::get('lorekeeper.settings.masterlist_image_format'));
    }

    /**
     * Creates a character log.
     *
     * @param  int     $senderId
     * @param  string  $senderUrl
     * @param  int     $recipientId
     * @param  string  $recipientUrl
     * @param  int     $characterId
     * @param  string  $type
     * @param  string  $data
     * @param  string  $logType
     * @param  bool    $isUpdate
     * @param  string  $oldData
     * @param  string  $newData
     * @return bool
     */
    public function createLog($senderId, $senderUrl, $recipientId, $recipientUrl, $characterId, $type, $data, $logType, $isUpdate = false, $oldData = null, $newData = null)
    {
        return DB::table($logType == 'character' ? 'character_log' : 'user_character_log')->insert(
            [
                'sender_id' => $senderId,
                'sender_url' => $senderUrl,
                'recipient_id' => $recipientId,
                'recipient_url' => $recipientUrl,
                'character_id' => $characterId,
                'log' => $type . ($data ? ' (' . $data . ')' : ''),
                'log_type' => $type,
                'data' => $data,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ] + ($logType == 'character' ?
                [
                    'change_log' => $isUpdate ? json_encode([
                        'old' => $oldData,
                        'new' => $newData
                    ]) : null
                ] : [])
        );
    }

    /**
     * Creates a character image.
     *
     * @param  array                            $data
     * @param  \App\Models\Character\Character  $character
     * @param  \App\Models\User\User            $user
     * @return  \App\Models\Character\Character|bool
     */
    public function createImage($data, $character, $user)
    {
        DB::beginTransaction();

        try {
            if(!$character->is_myo_slot)
            {
                if(!(isset($data['species_id']) && $data['species_id'])) throw new \Exception('Characters require a species.');
                if(!(isset($data['rarity_id']) && $data['rarity_id'])) throw new \Exception('Characters require a rarity.');
            }
            if(isset($data['subtype_id']) && $data['subtype_id'])
            {
                $subtype = Subtype::find($data['subtype_id']);
                if(!(isset($data['species_id']) && $data['species_id'])) throw new \Exception('Species must be selected to select a subtype.');
                if(!$subtype || $subtype->species_id != $data['species_id']) throw new \Exception('Selected subtype invalid or does not match species.');
            }
            else $data['subtype_id'] = null;

            $data['is_visible'] = 1;

            // Create character image
            $image = $this->handleCharacterImage($data, $character);
            if(!$image) throw new \Exception("Error happened while trying to create image.");

            // Update the character's image ID
            if(isset($data['set_active']) && $data['set_active'] == 1) {
                $character->character_image_id = $image->id;
                $character->save();
            }

            // Add a log for the character
            // This logs all the updates made to the character
            $this->createLog($user->id, null, $character->user_id, ($character->user_id ? null : $character->owner_url), $character->id, 'Character Image Uploaded', '[#'.$image->id.']', 'character');

            // If the recipient has an account, send them a Notifications
            if($character->user && $user->id != $character->user_id && $character->is_visible) {
                Notifications::create('IMAGE_UPLOAD', $character->user, [
                    'character_url' => $character->url,
                    'character_slug' => $character->slug,
                    'character_name' => $character->name,
                    'sender_url' => $user->url,
                    'sender_name' => $user->name
                ]);
            }

            // Notify bookmarkers
            $character->notifyBookmarkers('BOOKMARK_IMAGE');

            return $this->commitReturn($character);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Updates a character image.
     *
     * @param  array                                 $data
     * @param  \App\Models\Character\CharacterImage  $image
     * @param  \App\Models\User\User                 $user
     * @return  bool
     */
    public function updateImageFeatures($data, $image, $user)
    {
        DB::beginTransaction();

        try {
            // Check that the subtype matches
            if(isset($data['subtype_id']) && $data['subtype_id'])
            {
                $subtype = Subtype::find($data['subtype_id']);
                if(!(isset($data['species_id']) && $data['species_id'])) throw new \Exception('Species must be selected to select a subtype.');
                if(!$subtype || $subtype->species_id != $data['species_id']) throw new \Exception('Selected subtype invalid or does not match species.');
            }

            // Log old features
            $old = [];
            $old['features'] = $this->generateFeatureList($image);
            $old['species'] = $image->species_id ? $image->species->displayName : null;
            $old['subtype'] = $image->subtype_id ? $image->subtype->displayName : null;
            $old['rarity'] = $image->rarity_id ? $image->rarity->displayName : null;
            $old['is_android'] = $image->is_android;

            // Clear old features
            $image->features()->delete();

            CharacterUtility::handleCharacterFeatures($image->id, $data['feature_id'], $data['feature_data']);

            // Update other stats
            $image->species_id = $data['species_id'];
            $image->subtype_id = $data['subtype_id'] ?: null;
            $image->rarity_id = $data['rarity_id'];
            $image->is_android = isset($data['is_android']) ? 1 : 0;
            $image->save();

            $new = [];
            $new['features'] = $this->generateFeatureList($image);
            $new['species'] = $image->species_id ? $image->species->displayName : null;
            $new['subtype'] = $image->subtype_id ? $image->subtype->displayName : null;
            $new['rarity'] = $image->rarity_id ? $image->rarity->displayName : null;
            $new['is_android'] = $image->is_android;

            // Character also keeps track of these features
            $image->character->rarity_id = $image->rarity_id;
            $image->character->save();

            // Add a log for the character
            // This logs all the updates made to the character
            $this->createLog($user->id, null, null, null, $image->character_id, 'Traits Updated', '#'.$image->id, 'character', true, $old, $new);

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Generates a list of features for displaying.
     *
     * @param  \App\Models\Character\CharacterImage  $image
     * @return  string
     */
    private function generateFeatureList($image)
    {
        $result = '';
        foreach($image->features as $feature)
            $result .= '<div>' . ($feature->feature->category ? '<strong>' . $feature->feature->category->displayName . ':</strong> ' : '') . $feature->feature->displayName . '</div>';
        return $result;
    }

    /**
     * Updates image data.
     *
     * @param  array                                 $data
     * @param  \App\Models\Character\CharacterImage  $image
     * @param  \App\Models\User\User                 $user
     * @return  bool
     */
    public function updateImageNotes($data, $image, $user)
    {
        DB::beginTransaction();

        try {
            $old = $image->parsed_description;

            // Update the image's notes
            $image->description = $data['description'];
            $image->parsed_description = parse($data['description']);
            $image->save();

            // Add a log for the character
            // This logs all the updates made to the character
            $this->createLog($user->id, null, null, null, $image->character_id, 'Image Notes Updated', '[#'.$image->id.']', 'character', true, $old, $image->parsed_description);

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Updates image credits.
     *
     * @param  array                                 $data
     * @param  \App\Models\Character\CharacterImage  $image
     * @param  \App\Models\User\User                 $user
     * @return  bool
     */
    public function updateImageCredits($data, $image, $user)
    {
        DB::beginTransaction();

        try {
            $old = $this->generateCredits($image);

            // Clear old artists/designers
            $image->creators()->delete();

            // Check if entered url(s) have aliases associated with any on-site users
            CharacterUtility::handleImageCredits($image->id, $data);

            // Add a log for the character
            // This logs all the updates made to the character
            $this->createLog($user->id, null, null, null, $image->character_id, 'Image Credits Updated', '[#'.$image->id.']', 'character', true, $old, $this->generateCredits($image));

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Generates a list of image credits for displaying.
     *
     * @param  \App\Models\Character\CharacterImage  $image
     * @return  string
     */
    private function generateCredits($image)
    {
        $result = ['designers' => '', 'artists' => ''];
        foreach($image->designers as $designer)
            $result['designers'] .= '<div>' . $designer->displayLink() . '</div>';
        foreach($image->artists as $artist)
            $result['artists'] .= '<div>' . $artist->displayLink() . '</div>';
        return $result;
    }

    /**
     * Reuploads an image.
     *
     * @param  array                                 $data
     * @param  \App\Models\Character\CharacterImage  $image
     * @param  \App\Models\User\User                 $user
     * @return  bool
     */
    public function reuploadImage($data, $image, $user)
    {
        DB::beginTransaction();

        try {
            if(Config::get('lorekeeper.settings.masterlist_image_format') != null) {
                // Remove old versions so that images in various filetypes don't pile up
                unlink($image->imagePath . '/' . $image->imageFileName);
                if(isset($image->fullsize_hash) ? file_exists( public_path($image->imageDirectory.'/'.$image->fullsizeFileName)) : FALSE) unlink($image->imagePath . '/' . $image->fullsizeFileName);
                unlink($image->imagePath . '/' . $image->thumbnailFileName);

                // Set the image's extension in the DB as defined in settings
                $image->extension = Config::get('lorekeeper.settings.masterlist_image_format');
                $image->save();
            }
            else {
                // Get uploaded image's extension and save it to the DB
                $image->extension = $data['image']->getClientOriginalExtension();
                $image->save();
            }

            // Save image
            $this->handleImage($data['image'], $image->imageDirectory, $image->imageFileName);

            $isMyo = $image->character->is_myo_slot ? true : false;
            // Save thumbnail
            $this->cropThumbnail($image, $isMyo);

            // Process and save the image itself
            if(!$isMyo) CharacterUtility::processImage($image);

            // Add a log for the character
            // This logs all the updates made to the character
            $this->createLog($user->id, null, null, null, $image->character_id, 'Image Reuploaded', '[#'.$image->id.']', 'character');

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Deletes an image.
     *
     * @param  \App\Models\Character\CharacterImage  $image
     * @param  \App\Models\User\User                 $user
     * @return  bool
     */
    public function deleteImage($image, $user)
    {
        DB::beginTransaction();

        try {
            if($image->character->character_image_id == $image->id) throw new \Exception("Cannot delete a character's active image.");

            $image->features()->delete();

            $image->delete();

            // Delete the image files
            unlink($image->imagePath . '/' . $image->imageFileName);
            if(isset($image->fullsize_hash) ? file_exists( public_path($image->imageDirectory.'/'.$image->fullsizeFileName)) : FALSE) unlink($image->imagePath . '/' . $image->fullsizeFileName);
            unlink($image->imagePath . '/' . $image->thumbnailFileName);

            // Add a log for the character
            // This logs all the updates made to the character
            $this->createLog($user->id, null, null, null, $image->character_id, 'Image Deleted', '[#'.$image->id.']', 'character');

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Updates image settings.
     *
     * @param  array                                 $data
     * @param  \App\Models\Character\CharacterImage  $image
     * @param  \App\Models\User\User                 $user
     * @return  bool
     */
    public function updateImageSettings($data, $image, $user)
    {
        DB::beginTransaction();

        try {
            if($image->character->character_image_id == $image->id && !isset($data['is_visible'])) throw new \Exception("Cannot hide a character's active image.");

            $image->is_valid = isset($data['is_valid']);
            $image->is_visible = isset($data['is_visible']);
            $image->save();

            // Add a log for the character
            // This logs all the updates made to the character
            $this->createLog($user->id, null, null, null, $image->character_id, 'Image Visibility/Validity Updated', '[#'.$image->id.']', 'character');

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Updates a character's active image.
     *
     * @param  \App\Models\Character\CharacterImage  $image
     * @param  \App\Models\User\User                 $user
     * @return  bool
     */
    public function updateActiveImage($image, $user)
    {
        DB::beginTransaction();

        try {
            if($image->character->character_image_id == $image->id) return true;
            if(!$image->is_visible) throw new \Exception("Cannot set a non-visible image as the character's active image.");

            $image->character->character_image_id = $image->id;
            $image->character->save();

            // Add a log for the character
            // This logs all the updates made to the character
            $this->createLog($user->id, null, null, null, $image->character_id, 'Active Image Updated', '[#'.$image->id.']', 'character');

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Sorts a character's images
     *
     * @param  array                            $data
     * @param  \App\Models\Character\Character  $image
     * @param  \App\Models\User\User            $user
     * @return  bool
     */
    public function sortImages($data, $character, $user)
    {
        DB::beginTransaction();

        try {
            $ids = explode(',', $data['sort']);
            $images = CharacterImage::whereIn('id', $ids)->where('character_id', $character->id)->orderByRaw(DB::raw('FIELD(id, '.implode(',', $ids).')'))->get();

            if(count($images) != count($ids)) throw new \Exception("Invalid image included in sorting order.");
            if(!$images->first()->is_visible) throw new \Exception("Cannot set a non-visible image as the character's active image.");

            $count = 0;
            foreach($images as $image)
            {
                //if($count == 1)
                //{
                //    // Set the first one as the active image
                //    $image->character->image_id = $image->id;
                //    $image->character->save();
                //}
                $image->sort = $count;
                $image->save();
                $count++;
            }

            // Add a log for the character
            // This logs all the updates made to the character
            $this->createLog($user->id, null, null, null, $image->character_id, 'Image Order Updated', '', 'character');

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Sorts a user's characters.
     *
     * @param  array                                 $data
     * @param  \App\Models\User\User                 $user
     * @return  bool
     */
    public function sortCharacters($data, $user)
    {
        DB::beginTransaction();

        try {
            $ids = array_reverse(explode(',', $data['sort']));
            $characters = Character::myo(0)->whereIn('id', $ids)->where('user_id', $user->id)->where('is_visible', 1)->orderByRaw(DB::raw('FIELD(id, '.implode(',', $ids).')'))->get();

            if(count($characters) != count($ids)) throw new \Exception("Invalid character included in sorting order.");

            $count = 0;
            foreach($characters as $character)
            {
                $character->sort = $count;
                $character->save();
                $count++;
            }

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Updates a character's stats.
     *
     * @param  array                            $data
     * @param  \App\Models\Character\Character  $image
     * @param  \App\Models\User\User            $user
     * @return  bool
     */
    public function updateCharacterStats($data, $character, $user)
    {
        DB::beginTransaction();

        try {
            if(!$character->is_myo_slot && Character::where('slug', $data['slug'])->where('id', '!=', $character->id)->exists()) throw new \Exception("Character code must be unique.");

            $characterData = Arr::only($data, [
                'character_category_id',
                'number', 'slug', 'year',
            ]);
            $characterData['is_sellable'] = isset($data['is_sellable']);
            $characterData['is_tradeable'] = isset($data['is_tradeable']);
            $characterData['is_giftable'] = isset($data['is_giftable']);
            $characterData['sale_value'] = isset($data['sale_value']) ? $data['sale_value'] : 0;
            $characterData['transferrable_at'] = isset($data['transferrable_at']) ? $data['transferrable_at'] : null;
            if($character->is_myo_slot) $characterData['name'] = (isset($data['name']) && $data['name']) ? $data['name'] : null;

            // Needs to be cleaned up
            $result = [];
            $old = [];
            $new = [];
            if(!$character->is_myo_slot) {
                if($characterData['character_category_id'] != $character->character_category_id) {
                    $result[] = 'character category';
                    $old['character_category'] = $character->category->displayName;
                    $new['character_category'] = CharacterCategory::find($characterData['character_category_id'])->displayName;
                }
                if($characterData['number'] != $character->number) {
                    $result[] = 'character number';
                    $old['number'] = $character->number;
                    $new['number'] = $characterData['number'];
                }
                if($characterData['slug'] != $character->number) {
                    $result[] = 'character code';
                    $old['slug'] = $character->slug;
                    $new['slug'] = $characterData['slug'];
                }
                if($characterData['year'] != $character->year) {
                    $result[] = 'character year';
                    $old['year'] = $character->year;
                    $new['year'] = $characterData['year'];
                }
            }
            else {
                if($characterData['name'] != $character->name) {
                    $result[] = 'name';
                    $old['name'] = $character->name;
                    $new['name'] = $characterData['name'];
                }
            }
            if($characterData['is_sellable'] != $character->is_sellable) {
                $result[] = 'sellable status';
                $old['is_sellable'] = $character->is_sellable;
                $new['is_sellable'] = $characterData['is_sellable'];
            }
            if($characterData['is_tradeable'] != $character->is_tradeable) {
                $result[] = 'tradeable status';
                $old['is_tradeable'] = $character->is_tradeable;
                $new['is_tradeable'] = $characterData['is_tradeable'];
            }
            if($characterData['is_giftable'] != $character->is_giftable) {
                $result[] = 'giftable status';
                $old['is_giftable'] = $character->is_giftable;
                $new['is_giftable'] = $characterData['is_giftable'];
            }
            if($characterData['sale_value'] != $character->sale_value) {
                $result[] = 'sale value';
                $old['sale_value'] = $character->sale_value;
                $new['sale_value'] = $characterData['sale_value'];
            }
            if($characterData['transferrable_at'] != $character->transferrable_at) {
                $result[] = 'transfer cooldown';
                $old['transferrable_at'] = $character->transferrable_at;
                $new['transferrable_at'] = $characterData['transferrable_at'];
            }

            if(count($result))
            {
                $character->update($characterData);

                // Add a log for the character
                // This logs all the updates made to the character
                $this->createLog($user->id, null, null, null, $character->id, 'Character Updated', ucfirst(implode(', ', $result)) . ' edited', 'character', true, $old, $new);
            }

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Updates a character's description.
     *
     * @param  array                            $data
     * @param  \App\Models\Character\Character  $character
     * @param  \App\Models\User\User            $user
     * @return  bool
     */
    public function updateCharacterDescription($data, $character, $user)
    {
        DB::beginTransaction();

        try {
            $old = $character->parsed_description;

            // Update the image's notes
            $character->description = $data['description'];
            $character->parsed_description = parse($data['description']);
            $character->save();

            // Add a log for the character
            // This logs all the updates made to the character
            $this->createLog($user->id, null, null, null, $character->id, 'Character Description Updated', '', 'character', true, $old, $character->parsed_description);

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Updates a character's settings.
     *
     * @param  array                            $data
     * @param  \App\Models\Character\Character  $image
     * @param  \App\Models\User\User            $user
     * @return  bool
     */
    public function updateCharacterSettings($data, $character, $user)
    {
        DB::beginTransaction();

        try {
            $old = ['is_visible' => $character->is_visible];

            $character->is_visible = isset($data['is_visible']);
            $character->save();

            // Add a log for the character
            // This logs all the updates made to the character
            $this->createLog($user->id, null, null, null, $character->id, 'Character Visibility Updated', '', 'character', true, $old, ['is_visible' => $character->is_visible]);

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Updates a character's profile.
     *
     * @param  array                            $data
     * @param  \App\Models\Character\Character  $character
     * @param  \App\Models\User\User            $user
     * @param  bool                             $isAdmin
     * @return  bool
     */
    public function updateCharacterProfile($data, $character, $user, $isAdmin = false)
    {
        DB::beginTransaction();

        try {
            $notifyTrading = false;
            $notifyGiftArt = false;
            $notifyGiftWriting = false;

            // Allow updating the gift art/trading options if the editing
            // user owns the character
            if(!$isAdmin)
            {
                if($character->user_id != $user->id) throw new \Exception("You cannot edit this character.");

                if($character->is_trading != isset($data['is_trading'])) $notifyTrading = true;
                if(isset($data['is_gift_art_allowed']) && $character->is_gift_art_allowed != $data['is_gift_art_allowed']) $notifyGiftArt = true;
                if(isset($data['is_gift_writing_allowed']) && $character->is_gift_writing_allowed != $data['is_gift_writing_allowed']) $notifyGiftWriting = true;

                $character->is_gift_art_allowed = isset($data['is_gift_art_allowed']) && $data['is_gift_art_allowed'] <= 2 ? $data['is_gift_art_allowed'] : 0;
                $character->is_gift_writing_allowed = isset($data['is_gift_writing_allowed']) && $data['is_gift_writing_allowed'] <= 2 ? $data['is_gift_writing_allowed'] : 0;
                $character->is_trading = isset($data['is_trading']);
                $character->save();
            }

            // Update the character's profile
            if(!$character->is_myo_slot) $character->name = $data['name'];
            $character->save();

            if(!$character->is_myo_slot && Config::get('lorekeeper.extensions.character_TH_profile_link')) $character->profile->link = $data['link'];
            $character->profile->save();

            $character->profile->text = $data['text'];
            $character->profile->parsed_text = parse($data['text']);
            $character->profile->save();

            if($isAdmin && isset($data['alert_user']) && $character->is_visible && $character->user_id)
            {
                Notifications::create('CHARACTER_PROFILE_EDIT', $character->user, [
                    'character_name' => $character->name,
                    'character_slug' => $character->slug,
                    'sender_url' => $user->url,
                    'sender_name' => $user->name
                ]);
            }

            if($notifyTrading) $character->notifyBookmarkers('BOOKMARK_TRADING');
            if($notifyGiftArt) $character->notifyBookmarkers('BOOKMARK_GIFTS');
            if($notifyGiftWriting) $character->notifyBookmarkers('BOOKMARK_GIFT_WRITING');

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Deletes a character.
     *
     * @param  \App\Models\Character\Character  $character
     * @param  \App\Models\User\User            $user
     * @return  bool
     */
    public function deleteCharacter($character, $user, $message)
    {
        DB::beginTransaction();

        try {
            if($character->user_id) {
                $character->user->settings->save();
        }

            // Delete associated bookmarks
            CharacterBookmark::where('character_id', $character->id)->delete();

            // Delete associated features and images
            // Images use soft deletes
            foreach($character->images as $image) {
                $image->features()->delete();
                $image->delete();
            }

            // Delete associated currencies
            CharacterCurrency::where('character_id', $character->id)->delete();

            // Delete associated design updates
            // Design updates use soft deletes
            CharacterDesignUpdate::where('character_id', $character->id)->delete();

            // Log that we deleted the character so it's known for later
            $this->createLog($user->id, null, $character->user_id, ($character->user_id ? null : $character->owner_url), $character->id, $character->is_myo_slot ? 'MYO Deleted' : 'Character Deleted', $message, 'user');

            // Delete character
            // This is a soft delete, so the character still kind of exists
            $character->delete();

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Creates a character transfer.
     *
     * @param  array                            $data
     * @param  \App\Models\Character\Character  $character
     * @param  \App\Models\User\User            $user
     * @return  bool
     */
    public function createTransfer($data, $character, $user)
    {
        DB::beginTransaction();

        try {
            if($user->id != $character->user_id) throw new \Exception("You do not own this character.");
            if(!$character->is_sellable && !$character->is_tradeable && !$character->is_giftable) throw new \Exception("This character is not transferrable.");
            if($character->transferrable_at && $character->transferrable_at->isFuture()) throw new \Exception("This character is still on transfer cooldown and cannot be transferred.");
            if(CharacterTransfer::active()->where('character_id', $character->id)->exists()) throw new \Exception("This character is in an active transfer.");
            if($character->trade_id) throw new \Exception("This character is in an active trade.");

            $recipient = User::find($data['recipient_id']);
            if(!$recipient) throw new \Exception("Invalid user selected.");
            if($recipient->is_banned) throw new \Exception("Cannot transfer character to a banned member.");

            // deletes any pending design drafts
            foreach($character->designUpdate as $update)
            {
                if($update->status == 'Draft')
                {
                   if(!$this->rejectRequest('Cancelled by '.$user->displayName.' in order to transfer character to another user', $update, $user, true, false)) throw new \Exception('Could not cancel pending request.');
                }
            }

            $queueOpen = Settings::get('open_transfers_queue');

            CharacterTransfer::create([
                'user_reason' => $data['user_reason'],  # pulls from this characters user_reason collum
                'character_id' => $character->id,
                'sender_id' => $user->id,
                'recipient_id' => $recipient->id,
                'status' => 'Pending',

                // if the queue is closed, all transfers are auto-approved
                'is_approved' => !$queueOpen
            ]);

            if(!$queueOpen)
                Notifications::create('CHARACTER_TRANSFER_RECEIVED', $recipient, [
                    'character_url' => $character->url,
                    'character_name' => $character->slug,
                    'sender_name' => $user->name,
                    'sender_url' => $user->url
                ]);

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Forces an admin transfer of a character.
     *
     * @param  array                            $data
     * @param  \App\Models\Character\Character  $character
     * @param  \App\Models\User\User            $user
     * @return  bool
     */
    public function adminTransfer($data, $character, $user)
    {
        DB::beginTransaction();

        try {
            if(isset($data['recipient_id']) && $data['recipient_id']) {
                $recipient = User::find($data['recipient_id']);
                if(!$recipient) throw new \Exception("Invalid user selected.");
                if($character->user_id == $recipient->id) throw new \Exception("Cannot transfer a character to the same user.");
            }
            else if(isset($data['recipient_url']) && $data['recipient_url']) {
                // Transferring to an off-site user
                $recipient = checkAlias($data['recipient_url']);
            }
            else throw new \Exception("Please enter a recipient for the transfer.");

            // If the character is in an active transfer, cancel it
            $transfer = CharacterTransfer::active()->where('character_id', $character->id)->first();
            if($transfer) {
                $transfer->status = 'Canceled';
                $transfer->reason = 'Transfer canceled by '.$user->displayName.' in order to transfer character to another user';
                $transfer->save();
            }
            // deletes any pending design drafts
            foreach($character->designUpdate as $update)
            {
                if($update->status == 'Draft')
                {
                   if(!$this->rejectRequest('Cancelled by '.$user->displayName.' in order to transfer character to another user', $update, $user, true, false)) throw new \Exception('Could not cancel pending request.');
                }
            }

            $sender = $character->user;

            // Move the character
            $this->moveCharacter($character, $recipient, 'Transferred by ' . $user->displayName . (isset($data['reason']) ? ': ' . $data['reason'] : ''), isset($data['cooldown']) ? $data['cooldown'] : -1);

            // Find all of the children of this character
            if($childrenArray =  CharacterLink::where('parent_id', $character->id)->get()->pluck('child_id')->toArray())
            {
                //get ALL the children
                foreach($childrenArray as $child) {
                    if(!isset($children))
                    {
                        $children = Character::where('id', $child)->get();
                    } else {
                        $children = $children->merge(Character::where('id', $child)->get());
                    }
                }
                //get all the children of children
                $search = 5;
                while($search >= 0)
                {
                    foreach($children as $child)
                    {
                        $grandchildren = null;
                        if ($grandchildren = CharacterLink::where('parent_id', $child->id)->get()->pluck('child_id')->toArray()) {
                            foreach($grandchildren as $grandchild) {
                                $children = $children->merge(Character::where('id', $grandchild)->get());
                            }
                        }
                    }
                    $search -= 1;
                }
            } else $children = false;

            // Move all children of this character
            if($children) {
                foreach($children as $child)
                {
                    $this->moveCharacter($child, $recipient, 'Parent ' . $character->slug . ' transferred to ' . $user->displayName . (isset($data['reason']) ? ': ' . $data['reason'] : ''), isset($data['cooldown']) ? $data['cooldown'] : -1);
                }
            }

            // Add Notificationss for the old and new owners
            if($sender) {
                Notifications::create('CHARACTER_SENT', $sender, [
                    'character_name' => $character->slug,
                    'character_slug' => $character->slug,
                    'sender_name' => $user->name,
                    'sender_url' => $user->url,
                    'recipient_name' => is_object($recipient) ? $recipient->name : prettyProfileName($recipient),
                    'recipient_url' => is_object($recipient) ? $recipient->url : $recipient,
                ]);
            }
            if(is_object($recipient)) {
                Notifications::create('CHARACTER_RECEIVED', $recipient, [
                    'character_name' => $character->slug,
                    'character_slug' => $character->slug,
                    'sender_name' => $user->name,
                    'sender_url' => $user->url,
                ]);
            }

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Processes a character transfer.
     *
     * @param  array                            $data
     * @param  \App\Models\User\User            $user
     * @return  bool
     */
    public function processTransfer($data, $user)
    {
        DB::beginTransaction();

        try {
            $transfer = CharacterTransfer::where('id', $data['transfer_id'])->active()->where('recipient_id', $user->id)->first();
            if(!$transfer) throw new \Exception("Invalid transfer selected.");

            if($data['action'] == 'Accept') {
                $cooldown = Settings::get('transfer_cooldown');

                $transfer->status = 'Accepted';

                // Process the character move if the transfer has already been approved
                if ($transfer->is_approved) {
                    //check the cooldown saved
                    if(isset($transfer->data['cooldown'])) $cooldown = $transfer->data['cooldown'];
                    {
                        $this->moveCharacter($transfer->character, $transfer->recipient, 'User Transfer', $cooldown);

                        // Find all of the children of this character
                        if($childrenArray =  CharacterLink::where('parent_id', $transfer->character->id)->get()->pluck('child_id')->toArray())
                        {
                            //get ALL the children
                            foreach($childrenArray as $child) {
                                if(!isset($children))
                                {
                                    $children = Character::where('id', $child)->get();
                                } else {
                                    $children = $children->merge(Character::where('id', $child)->get());
                                }
                            }
                            //get all the children of children
                            $search = 5;
                            while($search >= 0)
                            {
                                foreach($children as $child)
                                {
                                    $grandchildren = null;
                                    if ($grandchildren = CharacterLink::where('parent_id', $child->id)->get()->pluck('child_id')->toArray()) {
                                        foreach($grandchildren as $grandchild) {
                                            $children = $children->merge(Character::where('id', $grandchild)->get());
                                        }
                                    }
                                }
                                $search -= 1;
                            }
                        } else $children = false;

                        // Move all children of this character
                        if($children) {
                            foreach($children as $child)
                            {
                                $this->moveCharacter($child, $transfer->recipient, 'Parent ' . $transfer->character->slug . ' transferred to ' . $transfer->recipient->name, $cooldown);
                            }
                        }
                    }
                    if(!Settings::get('open_transfers_queue'))
                        $transfer->data = json_encode([
                            'cooldown' => $cooldown,
                            'staff_id' => null
                        ]);

                    // Notify sender of the successful transfer
                    Notifications::create('CHARACTER_TRANSFER_ACCEPTED', $transfer->sender, [
                        'character_name' => $transfer->character->slug,
                        'character_url' => $transfer->character->url,
                        'sender_name' => $transfer->recipient->name,
                        'sender_url' => $transfer->recipient->url,
                    ]);
                }
            }
            else {
                $transfer->status = 'Rejected';
                $transfer->data = json_encode([
                    'staff_id' => null
                ]);

                // Notify sender that transfer has been rejected
                Notifications::create('CHARACTER_TRANSFER_REJECTED', $transfer->sender, [
                    'character_name' => $transfer->character->slug,
                    'character_url' => $transfer->character->url,
                    'sender_name' => $transfer->recipient->name,
                    'sender_url' => $transfer->recipient->url,
                ]);
            }
            $transfer->save();

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Cancels a character transfer.
     *
     * @param  array                            $data
     * @param  \App\Models\User\User            $user
     * @return  bool
     */
    public function cancelTransfer($data, $user)
    {
        DB::beginTransaction();

        try {
            $transfer = CharacterTransfer::where('id', $data['transfer_id'])->active()->where('sender_id', $user->id)->first();
            if(!$transfer) throw new \Exception("Invalid transfer selected.");

            $transfer->status = 'Canceled';
            $transfer->save();

            // Notify recipient of the cancelled transfer
            Notifications::create('CHARACTER_TRANSFER_CANCELED', $transfer->recipient, [
                'character_name' => $transfer->character->slug,
                'character_url' => $transfer->character->url,
                'sender_name' => $transfer->sender->name,
                'sender_url' => $transfer->sender->url,
            ]);

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Processes a character transfer in the approvals queue.
     *
     * @param  array                            $data
     * @param  \App\Models\User\User            $user
     * @return  bool
     */
    public function processTransferQueue($data, $user)
    {
        DB::beginTransaction();

        try {
            if(isset($data['transfer_id'])) $transfer = CharacterTransfer::where('id', $data['transfer_id'])->active()->first();
            else $transfer = $data['transfer'];
            if(!$transfer) throw new \Exception("Invalid transfer selected.");

            if($data['action'] == 'Approve') {
                $transfer->is_approved = 1;
                $transfer->data = json_encode([
                    'staff_id' => $user->id,
                    'cooldown' => isset($data['cooldown']) ? $data['cooldown'] : Settings::get('transfer_cooldown')
                ]);

                // Process the character move if the recipient has already accepted the transfer
                if($transfer->status == 'Accepted') {
                    $this->moveCharacter($transfer->character, $transfer->recipient, 'User Transfer', isset($data['cooldown']) ? $data['cooldown'] : -1);

                    // Find all of the children of this character
                    if($childrenArray =  CharacterLink::where('parent_id', $transfer->character->id)->get()->pluck('child_id')->toArray())
                    {
                        //get ALL the children
                        foreach($childrenArray as $child) {
                            if(!isset($children))
                            {
                                $children = Character::where('id', $child)->get();
                            } else {
                                $children = $children->merge(Character::where('id', $child)->get());
                            }
                        }
                        //get all the children of children
                        $search = 5;
                        while($search >= 0)
                        {
                            foreach($children as $child)
                            {
                                $grandchildren = null;
                                if ($grandchildren = CharacterLink::where('parent_id', $child->id)->get()->pluck('child_id')->toArray()) {
                                    foreach($grandchildren as $grandchild) {
                                        $children = $children->merge(Character::where('id', $grandchild)->get());
                                    }
                                }
                            }
                            $search -= 1;
                        }
                    } else $children = false;

                    // Move all children of this character
                    if($children) {
                        foreach($children as $child)
                        {
                            $this->moveCharacter($child, $transfer->recipient, 'Parent ' . $transfer->character->slug . ' transferred to ' . $transfer->recipient->name, isset($data['cooldown']) ? $data['cooldown'] : -1);
                        }
                    }


                    // Notify both parties of the successful transfer
                    Notifications::create('CHARACTER_TRANSFER_APPROVED', $transfer->sender, [
                        'character_name' => $transfer->character->slug,
                        'character_url' => $transfer->character->url,
                        'sender_name' => $user->name,
                        'sender_url' => $user->url,
                    ]);
                    Notifications::create('CHARACTER_TRANSFER_APPROVED', $transfer->recipient, [
                        'character_name' => $transfer->character->slug,
                        'character_url' => $transfer->character->url,
                        'sender_name' => $user->name,
                        'sender_url' => $user->url,
                    ]);

                }
                else {
                    // Still pending a response from the recipient
                    Notifications::create('CHARACTER_TRANSFER_ACCEPTABLE', $transfer->recipient, [
                        'character_name' => $transfer->character->slug,
                        'character_url' => $transfer->character->url,
                        'sender_name' => $user->name,
                        'sender_url' => $user->url,
                    ]);

                }
            }
            else {
                $transfer->status = 'Rejected';
                $transfer->reason = isset($data['reason']) ? $data['reason'] : null;
                $transfer->data = json_encode([
                    'staff_id' => $user->id
                ]);

                // Notify both parties that the request was denied
                Notifications::create('CHARACTER_TRANSFER_DENIED', $transfer->sender, [
                    'character_name' => $transfer->character->slug,
                    'character_url' => $transfer->character->url,
                    'sender_name' => $user->name,
                    'sender_url' => $user->url,
                ]);
                Notifications::create('CHARACTER_TRANSFER_DENIED', $transfer->recipient, [
                    'character_name' => $transfer->character->slug,
                    'character_url' => $transfer->character->url,
                    'sender_name' => $user->name,
                    'sender_url' => $user->url,
                ]);
            }
            $transfer->save();

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Handles bound characters.
     *
     * @param  array                            $data
     * @param  \App\Models\Character\Character  $character
     * @param  \App\Models\User\User            $user
     * @return  bool
     */
    public function boundTransfer($data, $character, $user)
    {
        DB::beginTransaction();

        try {
            if(isset($data['parent_id']) && $data['parent_id']) {
                // Find the new parent of the character
                $parent = Character::where('id', $data['parent_id'])->first();

                // Find all of the children of this character
                if($childrenArray =  CharacterLink::where('parent_id', $character->id)->get()->pluck('child_id')->toArray())
                {
                    //get ALL the children
                    foreach($childrenArray as $child) {
                        if(!isset($children))
                        {
                            $children = Character::where('id', $child)->get();
                        } else {
                            $children = $children->merge(Character::where('id', $child)->get());
                        }
                    }
                    //get all the children of children
                    $search = 5;
                    while($search >= 0)
                    {
                        foreach($children as $child)
                        {
                            $grandchildren = null;
                            if ($grandchildren = CharacterLink::where('parent_id', $child->id)->get()->pluck('child_id')->toArray()) {
                                foreach($grandchildren as $grandchild) {
                                    $children = $children->merge(Character::where('id', $grandchild)->get());
                                }
                            }
                        }
                        $search -= 1;
                    }
                } else $children = false;

                //find new owner based on parent
                $recipient = User::find($parent->user_id);
                if(!$recipient) $recipient = Character::where('id', $data['parent_id'])->first()->owner_alias;

                //remove old parent and create new link
                CharacterLink::where('child_id', $character->id)->delete();
                CharacterLink::create([
                    'parent_id' => $data['parent_id'],
                    'child_id' => $character->id
                ]);

                //we dont want the child to be tradeable/transferrable...
                $transfer['is_sellable'] = false;
                $transfer['is_tradeable'] = false;
                $transfer['is_giftable'] = false;
                $character->update($transfer);

                // Move the character
                $this->moveCharacter($character, $recipient, 'Bound to ' . $parent->slug . (isset($data['reason']) ? ': ' . $data['reason'] : ''), isset($data['cooldown']) ? $data['cooldown'] : -1);

                // Move all children of this character
                if($children) {
                    foreach($children as $child)
                    {
                        $this->moveCharacter($child, $recipient, 'Parent ' . $character->slug . ' transferred by ' . $user->displayName . (isset($data['reason']) ? ': ' . $data['reason'] : ''), isset($data['cooldown']) ? $data['cooldown'] : -1);
                    }
                }
            } else {
                //if no parent is set, simply unbind
                CharacterLink::where('child_id', $character->id)->delete();
                flash('Character has been unbound.')->success();
            }
        return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Moves a character from one user to another.
     *
     * @param  \App\Models\Character\Character  $character
     * @param  \App\Models\User\User            $recipient
     * @param  string                           $data
     * @param  int                              $cooldown
     * @param  string                           $logType
     */
    public function moveCharacter($character, $recipient, $data, $cooldown = -1, $logType = null)
    {
        $sender = $character->user;
        if(!$sender) $sender = $character->owner_url;

        // Update character counts if the sender has an account
        if(is_object($sender)) {
            $sender->settings->save();
        }

        if(is_object($recipient)) {
            if(!$character->is_myo_slot) $recipient->settings->is_fto = 0;
            $recipient->settings->save();
        }

        // Update character owner, sort order and cooldown
        $character->sort = 0;
        if(is_object($recipient)) {
            $character->user_id = $recipient->id;
            $character->owner_url = null;
        }
        else {
            $character->owner_url = $recipient;
            $character->user_id = null;
        }
        if ($cooldown < 0) {
            // Add the default amount from settings
            $cooldown = Settings::get('transfer_cooldown');
        }
        if($cooldown > 0) {
            if ($character->transferrable_at && $character->transferrable_at->isFuture())
                $character->transferrable_at->addDays($cooldown);
            else $character->transferrable_at = Carbon::now()->addDays($cooldown);
        }
        $character->save();

        // Notify bookmarkers
        $character->notifyBookmarkers('BOOKMARK_OWNER');

        if(Config::get('lorekeeper.settings.reset_character_status_on_transfer')) {
            // Reset trading status, gift art status, and writing status
            $character->update([
                'is_gift_art_allowed'     => 0,
                'is_gift_writing_allowed' => 0,
                'is_trading'              => 0,
            ]);
        }

        if(Config::get('lorekeeper.settings.reset_character_profile_on_transfer') && !$character->is_myo_slot) {
            // Reset name and profile
            $character->update(['name' => null]);

            // Reset profile
            $character->profile->update([
                'text'        => null,
                'parsed_text' => null
            ]);
        }

        // Add a log for the ownership change
        $this->createLog(
is_object($sender) ? $sender->id : null,
            is_object($sender) ? null : $sender,
            $recipient && is_object($recipient) ? $recipient->id : null,
            $recipient && is_object($recipient) ? $recipient->url : ($recipient ? : null),
            $character->id, $logType ? $logType : ($character->is_myo_slot ? 'MYO Slot Transferred' : 'Character Transferred'), $data, 'user');
    }

    /**
     * Creates a character design update request (or a MYO design approval request).
     *
     * @param  \App\Models\Character\Character  $character
     * @param  \App\Models\User\User            $user
     * @return  \App\Models\Character\CharacterDesignUpdate|bool
     */
    public function createDesignUpdateRequest($character, $user, $oldImage, $type = null)
    {
        DB::beginTransaction();

        try {
            if($character->user_id != $user->id) throw new \Exception("You do not own this character.");
            if(CharacterDesignUpdate::where('character_id', $character->id)->active()->exists()) throw new \Exception("This ".($character->is_myo_slot ? 'MYO slot' : 'character')." already has an existing request. Please update that one, or delete it before creating a new one.");
            if(!$character->isAvailable) throw new \Exception("This ".($character->is_myo_slot ? 'MYO slot' : 'character')." is currently in an open trade or transfer. Please cancel the trade or transfer before creating a design update.");

            $data = [
                'user_id' => $user->id,
                'character_id' => $character->id,
                'status' => 'Draft',
                'hash' => randomString(10),
                'fullsize_hash' => randomString(15),
                'update_type' => $type ?? ($character->is_myo_slot ? 'MYO' : 'Character'),

                'rarity_id' => $oldImage->rarity_id,
                'species_id' => $oldImage->species_id,
                'subtype_id' => $type === 'New Form' ? null : $oldImage->subtype_id,
                // Overriding this since we aren't using it anymore to store the image id
                'x0' => $oldImage->id
            ];

            $request = CharacterDesignUpdate::create($data);

            $isHolo = $oldImage->species_id === 2;
            $subtype = $isHolo ? 3 :
                ($request->character->is_myo_slot && isset($oldImage->subtype_id) ?
                    $oldImage->subtype_id : $request->subtype_id);
            $isFormUpdate = $type === 'Character';

            $image = CharacterImage::create([
                'character_id' => $request->id,
                'is_visible' => 1,
                'hash' => $request->hash,
                'fullsize_hash' => $request->fullsize_hash ? $request->fullsize_hash : randomString(15),
                'extension' => Config::get('lorekeeper.settings.masterlist_image_format'),

                'species_id' => $request->species_id,
                'subtype_id' => $isFormUpdate ? $oldImage->subtype_id : $subtype,
                // Base Holo is a common rarity regardless of if it has a holoBUDDY attached
                'rarity_id' => $isFormUpdate ? $oldImage->rarity_id : ($isHolo ? 1 : $request->rarity_id),
                // Holos are androids by default
                'is_android' => $isFormUpdate ? $oldImage->is_android : ($isHolo ? 1 : 0),
                'is_design_update' => 1,
                'sort' => 0,
            ]);

            if($isFormUpdate && File::exists($oldImage->imagePath . '/' . $oldImage->imageFileName)) {
                // For update requests, copy the pre-existing image file and credits to the new image
                File::copy($oldImage->imagePath . '/' . $oldImage->fullsizeFileName, $image->imagePath . '/' . $image->imageFileName);
                File::copy($oldImage->thumbnailPath . '/' . $oldImage->thumbnailFileName, $image->thumbnailPath . '/' . $image->thumbnailFileName);
                 // Shift the image credits over to the new image
                if(count($oldImage->designers) > 0) {
                    $oldImage->designers->each(function($item) use($image) {
                        $copy = $item->replicate()->fill(['character_image_id' => $image->id, 'character_type' => 'Character']);
                        $copy->save();
                    });
                }
                if(count($oldImage->artists) > 0) {
                    $oldImage->artists->each(function($item) use($image) {
                        $copy = $item->replicate()->fill(['character_image_id' => $image->id, 'character_type' => 'Character']);
                        $copy->save();
                    });
                }
            }

            // If the character is not a MYO slot, make a copy of the previous image's traits
            // as presumably, we will not want to make major modifications to them.
            // This is skipped for MYO slots as it complicates things later on - we don't want
            // users to edit compulsory traits, so we'll only add them when the design is approved.
            if(!$character->is_myo_slot)
            {
                if($type === 'New Form') {
                    $features = CharacterFeature::whereIn('character_image_id', $character->images->pluck('id'))->get()->unique('feature_id');
                } else {
                    // this will be what happens for a form update request
                    $features = $oldImage->features;
                }

                foreach($features as $feature)
                {
                    $image->features()->create([
                        'character_image_id' => $image->id,
                        'character_type' => 'Update',
                        'feature_id' => $feature->feature_id,
                        'data' => $feature->data
                    ]);
                }
            }

            return $this->commitReturn($request);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Saves the comment section of a character design update request.
     *
     * @param  array                                        $data
     * @param  \App\Models\Character\CharacterDesignUpdate  $request
     * @return  bool
     */
    public function saveRequestComment($data, $request)
    {
        DB::beginTransaction();

        try {
            // Update the comments section
            $request->comments = (isset($data['comments']) && $data['comments']) ? $data['comments'] : null;
            $request->has_comments = 1;
            $request->save();

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Saves the image upload section of a character design update request.
     *
     * @param  array                                        $data
     * @param  \App\Models\Character\CharacterDesignUpdate  $request
     * @param  bool                                         $isAdmin
     * @return  bool
     */
    public function saveRequestImage($data, $request, $image, $isAdmin = false)
    {
        DB::beginTransaction();

        try {
            if(!$isAdmin || ($isAdmin && isset($data['modify_thumbnail']))) {
                $imageData = [];
                if(!$isAdmin && isset($data['image'])) {
                    $imageData['extension'] = (Config::get('lorekeeper.settings.masterlist_image_format') ? Config::get('lorekeeper.settings.masterlist_image_format') : (isset($data['extension']) ? $data['extension'] : $data['image']->getClientOriginalExtension()));
                    $imageData['has_image'] = true;
                    $request->update(['has_image' => true]);
                }
                $image->update($imageData);
            }

            $image->designers()->delete();
            $image->artists()->delete();

            CharacterUtility::handleImageCredits($image->id, $data);

            // Save image
            if(!$isAdmin && isset($data['image'])) $this->handleImage($data['image'], $image->imageDirectory, $image->imageFileName, null, isset($data['default_image']));

            // Save thumbnail
            if(!$isAdmin && isset($data['image']) || ($isAdmin && isset($data['modify_thumbnail']))) {
                $this->cropThumbnail($image);
            }

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Saves the addons section of a character design update request.
     *
     * @param  array                                        $data
     * @param  \App\Models\Character\CharacterDesignUpdate  $request
     * @return  bool
     */
    public function saveRequestAddons($data, $request)
    {
        DB::beginTransaction();

        try {
            $requestData = $request->data;
            // First return any item stacks associated with this request
            if(isset($requestData['user']) && isset($requestData['user']['user_items'])) {
                foreach($requestData['user']['user_items'] as $userItemId=>$quantity) {
                    $userItemRow = UserItem::find($userItemId);
                    if(!$userItemRow) throw new \Exception("Cannot return an invalid item. (".$userItemId.")");
                    if($userItemRow->update_count < $quantity) throw new \Exception("Cannot return more items than was held. (".$userItemId.")");
                    $userItemRow->update_count -= $quantity;
                    $userItemRow->save();
                }
            }

            // Also return any currency associated with this request
            // This is stored in the data attribute
            $currencyManager = new CurrencyManager;
            if(isset($requestData['user']) && isset($requestData['user']['currencies'])) {
                foreach($requestData['user']['currencies'] as $currencyId=>$quantity) {
                    $currencyManager->creditCurrency(null, $request->user, null, null, $currencyId, $quantity);
                }
            }
            if(isset($requestData['character']) && isset($requestData['character']['currencies'])) {
                foreach($requestData['character']['currencies'] as $currencyId=>$quantity) {
                    $currencyManager->creditCurrency(null, $request->character, null, null, $currencyId, $quantity);
                }
            }

            $userAssets = createAssetsArray();
            $characterAssets = createAssetsArray(true);

            // Attach items. Technically, the user doesn't lose ownership of the item - we're just adding an additional holding field.
            // We're also not going to add logs as this might add unnecessary fluff to the logs and the items still belong to the user.
            // Perhaps later I'll add a way to locate items that are being held by updates/trades.
            if(isset($data['stack_id'])) {
                foreach($data['stack_id'] as $stackId) {
                    $stack = UserItem::with('item')->find($stackId);
                    if(!$stack || $stack->user_id != $request->user_id) throw new \Exception("Invalid item selected.");
                    if(!isset($data['stack_quantity'][$stackId])) throw new \Exception("Invalid quantity selected.");
                    $stack->update_count += $data['stack_quantity'][$stackId];
                    $stack->save();

                    addAsset($userAssets, $stack, $data['stack_quantity'][$stackId]);
                }
            }

            // Attach currencies.
            if(isset($data['currency_id'])) {
                foreach($data['currency_id'] as $holderKey=>$currencyIds) {
                    $holder = explode('-', $holderKey);
                    $holderType = $holder[0];
                    $holderId = $holder[1];

                    // The holder can be obtained from the request, but for sanity's sake we're going to perform a check
                    $holder = ($holderType == 'user' ? User::find($holderId) : Character::find($holderId));
                    if ($holderType == 'user' && $holder->id != $request->user_id) throw new \Exception("Error attaching currencies to this request. (1)");
                    else if ($holderType == 'character' && $holder->id != $request->character_id) throw new \Exception("Error attaching currencies to this request. (2)");

                    foreach($currencyIds as $key=>$currencyId) {
                        $currency = Currency::find($currencyId);
                        if(!$currency) throw new \Exception("Invalid currency selected.");
                        if(!$currencyManager->debitCurrency($holder, null, null, null, $currency, $data['currency_quantity'][$holderKey][$key])) throw new \Exception("Invalid currency/quantity selected.");

                        if($holderType == 'user') addAsset($userAssets, $currency, $data['currency_quantity'][$holderKey][$key]);
                        else addAsset($characterAssets, $currency, $data['currency_quantity'][$holderKey][$key]);

                    }
                }
            }

            $request->has_addons = 1;
            $request->data = json_encode([
                'user' => Arr::only(getDataReadyAssets($userAssets), ['user_items','currencies']),
                'character' => Arr::only(getDataReadyAssets($characterAssets), ['currencies'])
            ]);
            $request->save();

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Saves the character features (traits) section of a character design update request.
     *
     * @param  array                                        $data
     * @param  \App\Models\Character\CharacterDesignUpdate  $request
     * @return  bool
     */
    public function saveRequestFeatures($data, $request, $image)
    {
        DB::beginTransaction();

        try {
            if(!(!$request->character->is_myo_slot || ($request->character->is_myo_slot && $request->character->image->species_id)) && !isset($data['species_id'])) throw new \Exception("Please select a species.");
            if(!($request->character->is_myo_slot && $request->character->image->rarity_id) && (!isset($data['rarity_id']) && !$image->rarity_id)) throw new \Exception("Please select a rarity.");

            $rarity = isset($data['rarity_id']) ? Rarity::find($data['rarity_id']) : $image->rarity ?? $request->character->image->rarity;
            if(isset($data['species_id'])) $species = Species::find($data['species_id']);
            else if(isset($image->species)) $species = $image->species;
            else if($request->character->is_myo_slot && $request->character->image->species_id) $species = $request->character->image->species;

            if($species->id === 1) {
                if(isset($data['subtype_id']) && $data['subtype_id']) {
                    $subtype = Subtype::find($data['subtype_id']);
                } else { $subtype = $request->character->image->subtype; }
            // Do to the setup of the holoBOT tabs this is a lot more strict
            }

            if(!$rarity) throw new \Exception("Invalid rarity selected.");
            if(!$species) throw new \Exception("Invalid species selected.");
            if(isset($subtype) && $subtype->species_id != $species->id) throw new \Exception("Subtype does not match the species.");

            // Clear old features
            $image->updateFeatures()->delete();

            // Attach features
            // We'll do the compulsory ones at the time of approval.
            CharacterUtility::handleCharacterFeatures($image->id, $data['feature_id'], $data['feature_data'], $species->id, 'Update');

            // Update other stats
            $image->species_id = $species->id;
            $image->rarity_id = $rarity->id;
            $image->subtype_id = isset($subtype) ? $subtype->id : $image->subtype_id;
            $request->has_features = 1;
            $image->save();
            $request->save();

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Submit a character design update request to the approval queue.
     *
     * @param  \App\Models\Character\CharacterDesignUpdate  $request
     * @return  bool
     */
    public function submitRequest($request)
    {
        DB::beginTransaction();

        try {
            if($request->status != 'Draft') throw new \Exception("This request cannot be resubmitted to the queue.");

            // Recheck and set update type, as insurance/in case of pre-existing drafts
            if($request->character->is_myo_slot)
                $request->update_type = 'MYO';
            else if($request->update_type === 'MYO') $request->update_type = 'Character';
            // We've done validation and all section by section,
            // so it's safe to simply set the status to Pending here
            $request->status = 'Pending';
            if(!$request->submitted_at) $request->submitted_at = Carbon::now();
            $request->save();
            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Approves a character design update request and processes it.
     *
     * @param  array                                        $data
     * @param  \App\Models\Character\CharacterDesignUpdate  $request
     * @param  \App\Models\User\User                        $user
     * @return  bool
     */
    public function approveMYORequest($data, $request, $user) {
        DB::beginTransaction();

        try {
            if($request->status != 'Pending') throw new \Exception("This request cannot be processed.");
            if(!isset($data['character_category_id'])) throw new \Exception("Please select a character category.");
            if(!isset($data['number'])) throw new \Exception("Please enter a character number.");
            if(!isset($data['year'])) throw new \Exception("Please enter a character year.");
            if((!isset($data['slug']) || Character::where('slug', $data['slug'])->where('id', '!=', $request->character_id)->exists())) throw new \Exception("Please enter a unique character code.");
            if(isset($request->holobotImage) && (!isset($data['holobot_slug']) || Character::where('slug', $data['holobot_slug'])->where('id', '!=', $request->character_id)->exists())) throw new \Exception("Please enter a unique holobot code.");

            $requestData = $request->data;
            CharacterUtility::removeInventory(
                $requestData,
                $user,
                User::find($request->user_id),
                'MYO Design Approved',
                $request->displayName
            );

            CharacterUtility::logCurrencyRemoval(
                $request->user_id,
                'MYO Design Approved',
                $request->displayName
            );

            // Save the base digital form that we have to have, because it's a MYO request
            CharacterUtility::moveFormToCharacter($request->image, $request->character_id);

            // Since this is a MYO, add any compulsory traits it had to the main form
            $features = $request->character->image->features;
            CharacterUtility::handleCharacterFeatures($request->image, $features->pluck('id'), $features->pluck('data'));

            // Save the android form if it exists and should be submitted
            if($request->hasAndroidData) CharacterUtility::moveFormToCharacter($request->androidImage, $request->character_id);
            // Create the holobot if it exists
            if($request->holobotImage) {
                CharacterUtility::createAttachedHolobot(
                    $request->holobotImage,
                    $data,
                    $request->character->id,
                    $request->character->user_id
                );
            }

            // Set character data and other info such as cooldown time, resell cost and terms etc.
            // since those might be updated with the new design update
            CharacterUtility::staffDataUpdates($data, $request->character);

            // Save the existing active image
            $myoIconImage = $request->character->image;

            // Set new image as active
            $request->character->character_image_id = $request->image->id;

            // Log that the design was approved
            $this->createLog($user->id, null, $request->character->user_id, $request->character->user->url, $request->character->id, 'MYO Design Approved', '[#'.$request->image->id.']', 'character');
            $this->createLog($user->id, null, $request->character->user_id, $request->character->user->url, $request->character->id, 'MYO Design Approved', '[#'.$request->image->id.']', 'user');

            // Set user's FTO status and the MYO status of the slot
            // and clear the character's name
            $request->character->name = null;
            $request->character->is_myo_slot = 0;
            $request->user->settings->is_fto = 0;
            $request->user->settings->save();
            $request->character->save();

            // Delete the old image
            CharacterUtility::deleteMYOImage($myoIconImage, $user);


            // Set status to approved
            $request->staff_id = $user->id;
            $request->status = 'Approved';
            $request->save();

            // Notify the user
            Notifications::create('DESIGN_APPROVED', $request->user, [
                'design_url' => $request->url,
                'character_url' => $request->character->url,
                'name' => $request->character->fullName
            ]);

            // Notify bookmarkers
            $request->character->notifyBookmarkers('BOOKMARK_IMAGE');

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }



    public function approveHoloMYORequest($data, $request, $user) {
        DB::beginTransaction();

        try {
            if($request->status != 'Pending') throw new \Exception("This request cannot be processed.");
            if(!isset($data['character_category_id'])) throw new \Exception("Please select a character category.");
            if(!isset($data['number'])) throw new \Exception("Please enter a character number.");
            if(!isset($data['year'])) throw new \Exception("Please enter a character year.");
            if((!isset($data['slug']) || Character::where('slug', $data['slug'])->where('id', '!=', $request->character_id)->exists())) throw new \Exception("Please enter a unique character code.");

            $requestData = $request->data;
            CharacterUtility::removeInventory(
                $requestData,
                $user,
                User::find($request->user_id),
                'MYO Design Approved',
                $request->displayName
            );

            CharacterUtility::logCurrencyRemoval(
                $request->user_id,
                'MYO Design Approved',
                $request->displayName
            );

            // Save the main holobot form
            CharacterUtility::moveFormToCharacter($request->holobotImage, $request->character_id);

            // Since this is a MYO, add any compulsory traits it had to the main form
            $features = $request->character->image->features;
            CharacterUtility::handleCharacterFeatures($request->holobotImage, $features->pluck('id'), $features->pluck('data'));

            // Save the holobuddy form if it exists and should be submitted
            if($request->hasHolobuddyData) CharacterUtility::moveFormToCharacter($request->holobuddyImage, $request->character_id);

            // Set character data and other info such as cooldown time, resell cost and terms etc.
            // since those might be updated with the new design update
            CharacterUtility::staffDataUpdates($data, $request->character);

            // Save the existing active image
            $myoIconImage = $request->character->image;

            // Set new image as active
            $request->character->character_image_id = $request->holobotImage->id;

            // Log that the design was approved
            $this->createLog($user->id, null, $request->character->user_id, $request->character->user->url, $request->character->id, 'MYO Design Approved', '[#'.$request->holobotImage->id.']', 'character');
            $this->createLog($user->id, null, $request->character->user_id, $request->character->user->url, $request->character->id, 'MYO Design Approved', '[#'.$request->holobotImage->id.']', 'user');

            // Set user's FTO status and the MYO status of the slot
            // and clear the character's name
            $request->character->name = null;
            $request->character->is_myo_slot = 0;
            $request->user->settings->is_fto = 0;
            $request->user->settings->save();
            $request->character->save();

            // Delete the old image
            CharacterUtility::deleteMYOImage($myoIconImage, $user);


            // Set status to approved
            $request->staff_id = $user->id;
            $request->status = 'Approved';
            $request->save();

            // Notify the user
            Notifications::create('DESIGN_APPROVED', $request->user, [
                'design_url' => $request->url,
                'character_url' => $request->character->url,
                'name' => $request->character->fullName
            ]);

            // Notify bookmarkers
            $request->character->notifyBookmarkers('BOOKMARK_IMAGE');

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /*
    START: Build Supplemental Images
        1. New Table for images - should be a trimmed down version of the current image table
        2. `form_id` that will be the image it's associated to
        3. Update form page to have a multi-image uploader for supplemental images - they should just get associated to the image and then transfer with the image with no extra code on submission
            -- will need an attribute call on CharacterImage that returns both the supplemental images and the main image as a collection but also one for just the supplemental images probably
            -- make sure it works for updating a form and adding a new form and a new MYO
        4. Update Character Image page to look like TH Flat folder view for the supplemental images (plus main image) - should give modal of bigger view of each
        5. Need admin button on the character image page for adding a supplemental image manually
    */

    public function approveFormRequest($data, $request, $user) {
        DB::beginTransaction();

        try {
            if($request->status != 'Pending') throw new \Exception("This request cannot be processed.");
            if(isset($request->holobotImage) && (!isset($data['holobot_slug']) || Character::where('slug', $data['holobot_slug'])->where('id', '!=', $request->character_id)->exists())) throw new \Exception("Please enter a unique holobot code.");

            $requestData = $request->data;
            CharacterUtility::removeInventory(
                $requestData,
                $user,
                User::find($request->user_id),
                'New Form Design Approved',
                $request->displayName
            );

            CharacterUtility::logCurrencyRemoval(
                $request->user_id,
                'New Form Design Approved',
                $request->displayName
            );

            // Save the applicable form to the character
            if($request->hasDigitalData) CharacterUtility::moveFormToCharacter($request->image, $request->character_id);
            if($request->hasAndroidData) CharacterUtility::moveFormToCharacter($request->androidImage, $request->character_id);
            // Create the holobot if it exists
            if($request->holobotImage) {
                CharacterUtility::createAttachedHolobot(
                    $request->holobotImage,
                    $data,
                    $request->character->id,
                    $request->character->user_id
                );
            }

            // Set character data and other info such as cooldown time, resell cost and terms etc.
            // since those might be updated with the new design update
            CharacterUtility::staffDataUpdates($data, $request->character);

            $image = $request->hasDigitalData ? $request->image : $request->androidImage;
            // Log that the design was approved
            $this->createLog($user->id, null, $request->character->user_id, $request->character->user->url, $request->character->id, 'New Form Design Approved', '[#'.$image->id.']', 'character');
            $this->createLog($user->id, null, $request->character->user_id, $request->character->user->url, $request->character->id, 'New Form Design Approved', '[#'.$image->id.']', 'user');

            // Save things
            $request->user->settings->save();
            $request->character->save();

            // Set status to approved
            $request->staff_id = $user->id;
            $request->status = 'Approved';
            $request->save();

            // Notify the user
            Notifications::create('DESIGN_APPROVED', $request->user, [
                'design_url' => $request->url,
                'character_url' => $request->character->url,
                'name' => $request->character->fullName
            ]);

            // Notify bookmarkers
            $request->character->notifyBookmarkers('BOOKMARK_IMAGE');

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    public function approveHoloFormRequest($data, $request, $user) {
        DB::beginTransaction();

        try {
            if($request->status != 'Pending') throw new \Exception("This request cannot be processed.");

            $requestData = $request->data;
            CharacterUtility::removeInventory(
                $requestData,
                $user,
                User::find($request->user_id),
                'New Form Design Approved',
                $request->displayName
            );

            CharacterUtility::logCurrencyRemoval(
                $request->user_id,
                'New Form Design Approved',
                $request->displayName
            );

            // Save whichever form makes sense
            if($request->hasHolobotData) CharacterUtility::moveFormToCharacter($request->holobotImage, $request->character_id);
            if($request->hasHolobuddyData) CharacterUtility::moveFormToCharacter($request->holobuddyImage, $request->character_id);

            // Set character data and other info such as cooldown time, resell cost and terms etc.
            // since those might be updated with the new design update
            CharacterUtility::staffDataUpdates($data, $request->character);

            $image = $request->hasHolobotData ? $request->holobotImage : $request->holobuddyImage;
            // Log that the design was approved
            $this->createLog($user->id, null, $request->character->user_id, $request->character->user->url, $request->character->id, 'New Form Design Approved', '[#'.$image->id.']', 'character');
            $this->createLog($user->id, null, $request->character->user_id, $request->character->user->url, $request->character->id, 'New Form Design Approved', '[#'.$image->id.']', 'user');

            // Save things
            $request->user->settings->save();
            $request->character->save();

            // Set status to approved
            $request->staff_id = $user->id;
            $request->status = 'Approved';
            $request->save();

            // Notify the user
            Notifications::create('DESIGN_APPROVED', $request->user, [
                'design_url' => $request->url,
                'character_url' => $request->character->url,
                'name' => $request->character->fullName
            ]);

            // Notify bookmarkers
            $request->character->notifyBookmarkers('BOOKMARK_IMAGE');

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    public function approveFormUpdateRequest($data, $request, $user) {
        DB::beginTransaction();

        try {
            $newImage = $request->image ?? $request->androidImage ?? $request->holobotImage ?? $request->holobuddyImage;
            $existingForm = CharacterImage::find($request->x0);

            // Process image file and move it
            // Remove old versions so that images in various filetypes don't pile up
            unlink($existingForm->imagePath . '/' . $existingForm->imageFileName);
            if(isset($existingForm->fullsize_hash) ? file_exists( public_path($existingForm->imageDirectory.'/'.$existingForm->fullsizeFileName)) : FALSE) unlink($existingForm->imagePath . '/' . $existingForm->fullsizeFileName);
            unlink($existingForm->imagePath . '/' . $existingForm->thumbnailFileName);

            // Set the image's extension in the DB as defined in settings
            $existingForm->extension = Config::get('lorekeeper.settings.masterlist_image_format');
            $existingForm->save();

            // Move image into the existing form
            File::move($newImage->imagePath . '/' . $newImage->imageFileName, $existingForm->imagePath . '/' . $existingForm->imageFileName);
            File::move($newImage->thumbnailPath . '/' . $newImage->thumbnailFileName, $existingForm->thumbnailPath . '/' . $existingForm->thumbnailFileName);

            // Process and save the image itself
            CharacterUtility::processImage($existingForm);

            // Update species, subtype, and rarity
            $existingForm->species_id = $newImage->species_id;
            $existingForm->subtype_id = $newImage->subtype_id;
            $existingForm->rarity_id = $newImage->rarity_id;

            // Move Features
            $existingForm->features()->delete();
            $newFeatures = $newImage->updateFeatures;
            CharacterUtility::handleCharacterFeatures($existingForm->id, $newFeatures->pluck('id'), $newFeatures->pluck('data'));

            // Move Credits
            $existingForm->designers()->delete();
            $existingForm->artists()->delete();
            if(count($newImage->designers) > 0) {
                $newImage->designers->each(function($item) use($existingForm) {
                    $copy = $item->replicate()->fill(['character_image_id' => $existingForm->id, 'character_type' => 'Character']);
                    $copy->save();
                });
            }
            if(count($newImage->artists) > 0) {
                $newImage->artists->each(function($item) use($existingForm) {
                    $copy = $item->replicate()->fill(['character_image_id' => $existingForm->id, 'character_type' => 'Character']);
                    $copy->save();
                });
            }

            $existingForm->save();

            // Set character data and other info such as cooldown time, resell cost and terms etc.
            // since those might be updated with the new design update
            CharacterUtility::staffDataUpdates($data, $request->character);

            // Log that the design was approved
            $this->createLog($user->id, null, $request->character->user_id, $request->character->user->url, $request->character->id, 'Form Update Approved', '[#'.$existingForm->id.']', 'character');
            $this->createLog($user->id, null, $request->character->user_id, $request->character->user->url, $request->character->id, 'Form Update Approved', '[#'.$existingForm->id.']', 'user');

            // Save things
            $request->character->save();
            // Set status to approved
            $request->staff_id = $user->id;
            $request->status = 'Approved';
            $request->save();

            // Notify the user
            Notifications::create('DESIGN_APPROVED', $request->user, [
                'design_url' => $request->url,
                'character_url' => $request->character->url,
                'name' => $request->character->fullName
            ]);

            // Notify bookmarkers
            $request->character->notifyBookmarkers('BOOKMARK_IMAGE');

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Rejects a character design update request and processes it.
     * Rejection can be a soft rejection (reopens the request so the user can edit it and resubmit)
     * or a hard rejection (takes the request out of the queue completely).
     *
     * @param  array                                        $data
     * @param  \App\Models\Character\CharacterDesignUpdate  $request
     * @param  \App\Models\User\User                        $user
     * @param  bool                                         $forceReject
     * @return  bool
     */
    public function rejectRequest($data, $request, $user, $forceReject = false, $Notifications = true)
    {
        DB::beginTransaction();

        try {
            if(!$forceReject && $request->status != 'Pending') throw new \Exception("This request cannot be processed.");

            // This hard rejects the request - items/currency are returned to user
            // and the user will need to open a new request to resubmit.
            // Use when rejecting a request the user shouldn't have submitted at all.

            $requestData = $request->data;
            // Return all added items/currency
            if(isset($requestData['user']) && isset($requestData['user']['user_items'])) {
                foreach($requestData['user']['user_items'] as $userItemId=>$quantity) {
                    $userItemRow = UserItem::find($userItemId);
                    if(!$userItemRow) throw new \Exception("Cannot return an invalid item. (".$userItemId.")");
                    if($userItemRow->update_count < $quantity) throw new \Exception("Cannot return more items than was held. (".$userItemId.")");
                    $userItemRow->update_count -= $quantity;
                    $userItemRow->save();
                }
            }

            $currencyManager = new CurrencyManager;
            if(isset($requestData['user']['currencies']) && $requestData['user']['currencies'])
            {
                foreach($requestData['user']['currencies'] as $currencyId=>$quantity) {
                    $currency = Currency::find($currencyId);
                    if(!$currency) throw new \Exception("Cannot return an invalid currency. (".$currencyId.")");
                    if(!$currencyManager->creditCurrency(null, $request->user, null, null, $currency, $quantity)) throw new \Exception("Could not return currency to user. (".$currencyId.")");
                }
            }
            if(isset($requestData['character']['currencies']) && $requestData['character']['currencies'])
            {
                foreach($requestData['character']['currencies'] as $currencyId=>$quantity) {
                    $currency = Currency::find($currencyId);
                    if(!$currency) throw new \Exception("Cannot return an invalid currency. (".$currencyId.")");
                    if(!$currencyManager->creditCurrency(null, $request->character, null, null, $currency, $quantity)) throw new \Exception("Could not return currency to character. (".$currencyId.")");
                }
            }

            // Set staff comment and status
            $request->staff_id = $user->id;
            $request->staff_comments = isset($data['staff_comments']) ? $data['staff_comments'] : null;
            $request->status = 'Rejected';
            $request->save();

            if($Notifications)
            {
                // Notify the user
                Notifications::create('DESIGN_REJECTED', $request->user, [
                    'design_url' => $request->url,
                    'character_url' => $request->character->url,
                    'name' => $request->character->fullName
                ]);
            }

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Cancels a character design update request.
     *
     * @param  array                                        $data
     * @param  \App\Models\Character\CharacterDesignUpdate  $request
     * @param  \App\Models\User\User                        $user
     * @return  bool
     */
    public function cancelRequest($data, $request, $user)
    {
        DB::beginTransaction();

        try {
            if($request->status != 'Pending') throw new \Exception("This request cannot be processed.");

            // Soft removes the request from the queue -
            // it preserves all the data entered, but allows the staff member
            // to add a comment to it. Status is returned to Draft status.
            // Use when rejecting a request that just requires minor modifications to approve.

            // Set staff comment and status
            $request->staff_id = $user->id;
            $request->staff_comments = isset($data['staff_comments']) ? $data['staff_comments'] : null;
            $request->status = 'Draft';
            if(!isset($data['preserve_queue'])) $request->submitted_at = null;
            $request->save();

            // Notify the user
            Notifications::create('DESIGN_CANCELED', $request->user, [
                'design_url' => $request->url,
                'character_url' => $request->character->url,
                'name' => $request->character->fullName
            ]);

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Deletes a character design update request.
     *
     * @param  \App\Models\Character\CharacterDesignUpdate  $request
     * @return  bool
     */
    public function deleteRequest($request)
    {
        DB::beginTransaction();

        try {
            if($request->status != 'Draft') throw new \Exception("This request cannot be processed.");

            // Deletes the request entirely, including images and etc.
            // This returns any attached items/currency
            // Characters with an open draft request cannot be transferred (due to attached items/currency),
            // so this is necessary to transfer a character

            $requestData = $request->data;
            // Return all added items/currency
            if(isset($requestData['user']) && isset($requestData['user']['user_items'])) {
                foreach($requestData['user']['user_items'] as $userItemId=>$quantity) {
                    $userItemRow = UserItem::find($userItemId);
                    if(!$userItemRow) throw new \Exception("Cannot return an invalid item. (".$userItemId.")");
                    if($userItemRow->update_count < $quantity) throw new \Exception("Cannot return more items than was held. (".$userItemId.")");
                    $userItemRow->update_count -= $quantity;
                    $userItemRow->save();
                }
            }

            $currencyManager = new CurrencyManager;
            if(isset($requestData['user']['currencies']) && $requestData['user']['currencies'])
            {
                foreach($requestData['user']['currencies'] as $currencyId=>$quantity) {
                    $currency = Currency::find($currencyId);
                    if(!$currency) throw new \Exception("Cannot return an invalid currency. (".$currencyId.")");
                    if(!$currencyManager->creditCurrency(null, $request->user, null, null, $currency, $quantity)) throw new \Exception("Could not return currency to user. (".$currencyId.")");
                }
            }
            if(isset($requestData['character']['currencies']) && $requestData['character']['currencies'])
            {
                foreach($requestData['character']['currencies'] as $currencyId=>$quantity) {
                    $currency = Currency::find($currencyId);
                    if(!$currency) throw new \Exception("Cannot return an invalid currency. (".$currencyId.")");
                    if(!$currencyManager->creditCurrency(null, $request->character, null, null, $currency, $quantity)) throw new \Exception("Could not return currency to character. (".$currencyId.")");
                }
            }

            // Delete the request
            $request->delete();

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Votes on a a character design update request.
     *
     * @param  string                                       $action
     * @param  \App\Models\Character\CharacterDesignUpdate  $request
     * @param  \App\Models\User\User                        $user
     * @return  bool
     */
    public function voteRequest($action, $request, $user)
    {
        DB::beginTransaction();

        try {
            if($request->status != 'Pending') throw new \Exception("This request cannot be processed.");
            if(!Config::get('lorekeeper.extensions.design_update_voting')) throw new \Exception('This extension is not currently enabled.');

            switch($action) {
                default:
                    flash('Invalid action.')->error();
                    break;
                case 'approve':
                    $vote = 2;
                    break;
                case 'reject':
                    $vote = 1;
                    break;
            }

            $voteData = (isset($request->vote_data) ? collect(json_decode($request->vote_data, true)) : collect([]));
            $voteData->get($user->id) ? $voteData->pull($user->id) : null;
            $voteData->put($user->id, $vote);
            $request->vote_data = $voteData->toJson();

            $request->save();

            return $this->commitReturn(true);
        } catch(\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }
}
