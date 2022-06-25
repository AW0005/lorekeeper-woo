<?php namespace App\Services\Utilities;

use DB;

use App\Services\Service;

use App\Models\User\User;
use App\Models\Feature\Feature;
use App\Models\Character\CharacterFeature;

class CharacterUtility extends Service
{
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

    public static function handleImageCredits($imageId, $data) {
        // Check that users with the specified id(s) exist on site
        self::checkUsersExist($data['designer_id'], 'designers');
        self::checkUsersExist($data['artist_id'], 'artists');


        // Check if entered url(s) have aliases associated with any on-site users
        self::convertAliasToUser($data['designer_id'], $data['designer_url']);
        self::convertAliasToUser($data['artist_id'], $data['artist_url']);


        // Attach artists/designers
        self::attachCredits($imageId, $data['designer_id'], $data['designer_url'], $data['designer_type']);
        self::attachCredits($imageId, $data['artist_id'], $data['artist_url'], $data['artist_type'], 'Artist');
    }

}

?>
