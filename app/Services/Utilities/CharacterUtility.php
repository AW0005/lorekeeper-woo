<?php namespace App\Services\Utilities;

use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Config;

use App\Services\Service;

use App\Models\User\User;
use App\Models\User\UserItem;
use App\Models\Feature\Feature;
use App\Models\Character\Character;
use App\Models\Character\CharacterFeature;
use App\Models\Character\CharacterLink;

use App\Services\InventoryManager;
use App\Services\CurrencyManager;
use App\Services\CharacterManager;

class CharacterUtility extends Service
{

    /* Check Data - non-existant yet */

    /* Perform Actions */
    public static function handleCharacterFeatures($imageId, $features, $featureData, $speciesId = false, $characterType = 'Character') {
        $featuresDetailed = Feature::whereIn('id', $features)->with('rarity')->get()->keyBy('id');
        foreach($features as $key => $featureId) {
            // featureID exists, and either we're not checking by speciesID,
            // or we make sure that the feature species matches the species id
            if($featureId && (!$speciesId || $featuresDetailed[$featureId]->species_id === $speciesId)) {
                $feature = CharacterFeature::create([
                    'character_image_id' => $imageId,
                    'feature_id' => $featureId,
                    'data' => $featureData[$key],
                    'character_type' => $characterType
                ]);
            }
        }
    }

    public static function handleImageCredits($imageId, $data) {
        // Check that users with the specified id(s) exist on site
        self::checkUsersExist(@$data['designer_id'], 'designers');
        self::checkUsersExist(@$data['artist_id'], 'artists');


        // Check if entered url(s) have aliases associated with any on-site users
        self::convertAliasToUser(@$data['designer_id'], @$data['designer_url']);
        self::convertAliasToUser(@$data['artist_id'], @$data['artist_url']);

        // initialize this if it doens't exist to avoid bugs without killing the code completely
        if(!isset($data['designer_type'])) $data['designer_type'] = [];
        if(!isset($data['artist_type'])) $data['artist_type'] = [];
        // Attach artists/designers
        self::attachCredits($imageId, @$data['designer_id'], @$data['designer_url'], $data['designer_type']);
        self::attachCredits($imageId, @$data['artist_id'], @$data['artist_url'], $data['artist_type'], 'Artist');
    }

    public static function removeInventory($requestData, $staff, $user, $logMsg, $displayName) {
        $inventoryManager = new InventoryManager;
        if(isset($requestData['user']) && isset($requestData['user']['user_items'])) {
            $stacks = $requestData['user']['user_items'];
            foreach($requestData['user']['user_items'] as $userItemId=>$quantity) {
                $userItemRow = UserItem::find($userItemId);
                if(!$userItemRow) throw new \Exception("Cannot return an invalid item. (".$userItemId.")");
                if($userItemRow->update_count < $quantity) throw new \Exception("Cannot return more items than was held. (".$userItemId.")");
                $userItemRow->update_count -= $quantity;
                $userItemRow->save();
            }

            foreach($stacks as $stackId=>$quantity) {
                $stack = UserItem::find($stackId);
                if(!$inventoryManager->debitStack($user, $logMsg, ['data' => 'Item used in '.$logMsg.' ('.$displayName.')'], $stack, $quantity)) throw new \Exception("Failed to create log for item stack.");
            }
            $user = $staff;
        }
    }

    public static function logCurrencyRemoval($userId, $logMsg, $displayName) {
        $currencyManager = new CurrencyManager;
        $usedInMsg = 'Used in ' . ($logMsg) . ' ('.$displayName.')';
        if(isset($requestData['user']['currencies']) && $requestData['user']['currencies'])
        {
            foreach($requestData['user']['currencies'] as $currencyId=>$quantity) {
                if(!$currencyManager->createLog($userId, 'User', null, null,
                $logMsg,
                $usedInMsg,
                $currencyId, $quantity))
                    throw new \Exception("Failed to create log for user currency.");
            }
        }
        // if(isset($requestData['character']['currencies']) && $requestData['character']['currencies'])
        // {
        //     foreach($requestData['character']['currencies'] as $currencyId=>$quantity) {
        //         if(!$currencyManager->createLog($request->character_id, 'Character', null, null,
        //         $logMsg,
        //         $usedInMsg,
        //         $currencyId, $quantity))
        //             throw new \Exception("Failed to create log for character currency.");
        //     }
        // }

    }

    public static function createAttachedHolobot($holobotImage, $data, $characterId, $userId) {
        $holobot = Character::create([
            'character_image_id' => $holobotImage->id,
            'character_category_id' => $data['holobot_category_id'],
            'rarity_id' => $holobotImage->rarity->id,
            'user_id' => $userId,
            'number' => $data['holobot_number'],
            'year' => $data['year'],
            'slug' => $data['holobot_slug'],
            'is_visible' => 1,
            'is_myo_slot' => 0
        ]);

        $holobot->profile()->create([]);

        // Shift things over to the new character
        $holobotImage->update(['character_id' => $holobot->id, 'is_design_update' => 0]);
        $holobotImage->updateFeatures()->update(['character_type' => 'Character']);
        self::processImage($holobotImage);

        // Bind the holobot to the main character
        CharacterLink::create([
            'parent_id' => $characterId,
            'child_id' => $holobot->id
        ]);
    }

    public static function moveFormToCharacter($form, $characterId) {
        // Move form model to character
        $form->update(['character_id' => $characterId, 'is_design_update' => 0]);
        // Make the features official
        $form->updateFeatures()->update(['character_type' => 'Character']);
        // Process and save the uploaded image
        self::processImage($form);
    }

    /** Set character data and other info such as cooldown time, resell cost and terms etc.
     * since those might be updated with the new design update */
    public static function staffDataUpdates($data, $character) {
        if(isset($data['transferrable_at'])) $character->transferrable_at = $data['transferrable_at'];
        if(isset($data['character_category_id'])) $character->character_category_id = $data['character_category_id'];
        if(isset($data['number'])) $character->number = $data['number'];
        if(isset($data['year'])) $character->year = $data['year'];
        if(isset($data['slug'])) $character->slug = $data['slug'];

        $character->is_sellable = isset($data['is_sellable']);
        $character->is_tradeable = isset($data['is_tradeable']);
        $character->is_giftable = isset($data['is_giftable']);
        $character->sale_value = isset($data['sale_value']) ? $data['sale_value'] : 0;
    }

    public static function deleteMYOImage($oldImage, $staff) {
        $characterManager = new CharacterManager;
        if (!$characterManager->deleteImage($oldImage, $staff, true)) {
            foreach ($characterManager->errors()->getMessages()['error'] as $error) {
                flash($error)->error();
            }
            throw new \Exception('Failed to delete MYO image.');
        }
    }

    /**
     * Trims and optionally resizes and watermarks an image.
     *
     *
     * @param  \App\Models\Character\CharacterImage  $characterImage
     */
    public static function processImage($characterImage) {
        $image = Image::make($characterImage->imagePath . '/' . $characterImage->imageFileName);

        if(Config::get('lorekeeper.settings.store_masterlist_fullsizes') == 1) {
            // Generate fullsize hash if not already generated,
            // then save the full-sized image
            if(!$characterImage->fullsize_hash) {
                $characterImage->fullsize_hash = randomString(15);
                $characterImage->save();
            }

            // Save the processed image
            $image->save($characterImage->imagePath . '/' . $characterImage->fullsizeFileName, 100, Config::get('lorekeeper.settings.masterlist_image_format'));
        }
        else {
            // Delete fullsize if it was previously created.
            if(isset($characterImage->fullsize_hash) ? file_exists( public_path($characterImage->imageDirectory.'/'.$characterImage->fullsizeFileName)) : FALSE) unlink($characterImage->imagePath . '/' . $characterImage->fullsizeFileName);
        }


        $isWide = $image->width() > $image->height();
        $size = Config::get('lorekeeper.settings.masterlist_image_dimension');
        // Scale the largest side down to 1000px
        $image->resize($isWide ? $size : null, $isWide ? null : $size, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        // Make the image be square
        $image->resizeCanvas($isWide ? null : $image->height(), $isWide ? $image->width() : null, 'center');

        // Watermark the image
        $watermark = Image::make('images/watermarks/'.$characterImage->rarity->name.'.png');
        //Downsize the watermark if we need to.
        if($watermark->width() > $image->width()){
            $watermark->resize($image->width(), null, function($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        $image->insert($watermark, 'center');

        // Save the processed image
        $image->save($characterImage->imagePath . '/' . $characterImage->imageFileName, 100, Config::get('lorekeeper.settings.masterlist_image_format'));
    }


    /* Internal Utility methods */
    private static function checkUsersExist($users, $type = 'users') {
        if(isset($users)) {
            foreach($users as $id) {
                if(isset($id) && $id) {
                    $user = User::find($id);
                    if(!$user) throw new \Exception('One or more '.$type.' is invalid.');
                }
            }
        }
    }

    private static function convertAliasToUser($users, $urls) {
        if(isset($urls)) {
            foreach($urls as $key => $url) {
                $recipient = checkAlias($url, false);
                if(is_object($recipient)) {
                    $users[$key] = $recipient->id;
                    $urls[$key] = null;
                }
            }
        }
    }

    private static function attachCredits($imageId, $users, $urls, $types, $type = 'Designer') {
        if(isset($users)) {
            foreach($users as $key => $id) {
                if($id || $urls[$key])
                    DB::table('character_image_creators')->insert([
                        'character_image_id' => $imageId,
                        'type' => $type,
                        'url' => $urls[$key],
                        'user_id' => $id,
                        'credit_type' => isset($types[$key]) ? $types[$key] : null
                    ]);
            }
        }
    }

}

?>
