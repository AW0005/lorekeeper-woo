<?php

namespace App\Services;

use App\Services\Service;

use DB;
use Config;

use App\Models\Item\ItemCategory;
use App\Models\Item\Item;
use App\Models\Item\ItemTag;
use App\Models\LogEvent;

class ItemService extends Service {
    /*
    |--------------------------------------------------------------------------
    | Item Service
    |--------------------------------------------------------------------------
    |
    | Handles the creation and editing of item categories and items.
    |
    */

    /**********************************************************************************************

        ITEM CATEGORIES

     **********************************************************************************************/

    /**
     * Create a category.
     *
     * @param  array                 $data
     * @param  \App\Models\User\User $user
     * @return \App\Models\Item\ItemCategory|bool
     */
    public function createItemCategory($data, $user) {
        DB::beginTransaction();

        try {

            $data = $this->populateCategoryData($data);

            $image = null;
            if (isset($data['image']) && $data['image']) {
                $data['has_image'] = 1;
                $image = $data['image'];
                unset($data['image']);
            } else $data['has_image'] = 0;

            $category = ItemCategory::create($data);

            if ($image) $this->handleImage($image, $category->categoryImagePath, $category->categoryImageFileName);

            return $this->commitReturn($category);
        } catch (\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Update a category.
     *
     * @param  \App\Models\Item\ItemCategory  $category
     * @param  array                          $data
     * @param  \App\Models\User\User          $user
     * @return \App\Models\Item\ItemCategory|bool
     */
    public function updateItemCategory($category, $data, $user) {
        DB::beginTransaction();

        try {
            // More specific validation
            if (ItemCategory::where('name', $data['name'])->where('id', '!=', $category->id)->exists()) throw new \Exception("The name has already been taken.");

            $data = $this->populateCategoryData($data, $category);

            $image = null;
            if (isset($data['image']) && $data['image']) {
                $data['has_image'] = 1;
                $image = $data['image'];
                unset($data['image']);
            }

            $category->update($data);

            if ($category) $this->handleImage($image, $category->categoryImagePath, $category->categoryImageFileName);

            return $this->commitReturn($category);
        } catch (\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Handle category data.
     *
     * @param  array                               $data
     * @param  \App\Models\Item\ItemCategory|null  $category
     * @return array
     */
    private function populateCategoryData($data, $category = null) {
        if (isset($data['description']) && $data['description']) $data['parsed_description'] = parse($data['description']);

        isset($data['is_character_owned']) && $data['is_character_owned'] ? $data['is_character_owned'] : $data['is_character_owned'] = 0;
        isset($data['character_limit']) && $data['character_limit'] ? $data['character_limit'] : $data['character_limit'] = 0;
        isset($data['can_name']) && $data['can_name'] ? $data['can_name'] : $data['can_name'] = 0;

        if (isset($data['remove_image'])) {
            if ($category && $category->has_image && $data['remove_image']) {
                $data['has_image'] = 0;
                $this->deleteImage($category->categoryImagePath, $category->categoryImageFileName);
            }
            unset($data['remove_image']);
        }

        return $data;
    }

    /**
     * Delete a category.
     *
     * @param  \App\Models\Item\ItemCategory  $category
     * @return bool
     */
    public function deleteItemCategory($category) {
        DB::beginTransaction();

        try {
            // Check first if the category is currently in use
            if (Item::where('item_category_id', $category->id)->exists()) throw new \Exception("An item with this category exists. Please change its category first.");

            if ($category->has_image) $this->deleteImage($category->categoryImagePath, $category->categoryImageFileName);
            $category->delete();

            return $this->commitReturn(true);
        } catch (\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Sorts category order.
     *
     * @param  array  $data
     * @return bool
     */
    public function sortItemCategory($data) {
        DB::beginTransaction();

        try {
            // explode the sort array and reverse it since the order is inverted
            $sort = array_reverse(explode(',', $data));

            foreach ($sort as $key => $s) {
                ItemCategory::where('id', $s)->update(['sort' => $key]);
            }

            return $this->commitReturn(true);
        } catch (\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**********************************************************************************************

        ITEMS

     **********************************************************************************************/

    /**
     * Creates a new item.
     *
     * @param  array                  $data
     * @param  \App\Models\User\User  $user
     * @return bool|\App\Models\Item\Item
     */
    public function createItem($data, $user) {
        DB::beginTransaction();

        try {
            if (isset($data['item_category_id']) && $data['item_category_id'] == 'none') $data['item_category_id'] = null;

            if ((isset($data['item_category_id']) && $data['item_category_id']) && !ItemCategory::where('id', $data['item_category_id'])->exists()) throw new \Exception("The selected item category is invalid.");

            $data = $this->populateData($data);

            $image = null;
            if (isset($data['image']) && $data['image']) {
                $data['has_image'] = 1;
                $image = $data['image'];
                unset($data['image']);
            } else $data['has_image'] = 0;

            $item = Item::create($data);

            $item->update([
                'data' => json_encode([
                    'rarity' => isset($data['rarity']) && $data['rarity'] ? $data['rarity'] : null,
                    'uses' => isset($data['uses']) && $data['uses'] ? $data['uses'] : null,
                    'release' => isset($data['release']) && $data['release'] ? $data['release'] : null,
                    'prompts' => isset($data['prompts']) && $data['prompts'] ? $data['prompts'] : null,
                    'resell' => isset($data['currency_quantity']) ? [$data['currency_id'] => $data['currency_quantity']] : null,
                ]) // rarity, availability info (original source, purchase locations, drop locations)
            ]);

            if ($image) $this->handleImage($image, $item->imagePath, $item->imageFileName);

            return $this->commitReturn($item);
        } catch (\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Updates an item.
     *
     * @param  \App\Models\Item\Item  $item
     * @param  array                  $data
     * @param  \App\Models\User\User  $user
     * @return bool|\App\Models\Item\Item
     */
    public function updateItem($item, $data, $user) {
        DB::beginTransaction();

        try {
            if (isset($data['item_category_id']) && $data['item_category_id'] == 'none') $data['item_category_id'] = null;

            // More specific validation
            if (Item::where('name', $data['name'])->where('id', '!=', $item->id)->exists()) throw new \Exception("The name has already been taken.");
            if ((isset($data['item_category_id']) && $data['item_category_id']) && !ItemCategory::where('id', $data['item_category_id'])->exists()) throw new \Exception("The selected item category is invalid.");

            $data = $this->populateData($data);

            $image = null;
            if (isset($data['image']) && $data['image']) {
                $data['has_image'] = 1;
                $image = $data['image'];
                unset($data['image']);
            }

            $item->update($data);

            $item->update([
                'data' => json_encode([
                    'rarity' => isset($data['rarity']) && $data['rarity'] ? $data['rarity'] : null,
                    'uses' => isset($data['uses']) && $data['uses'] ? $data['uses'] : null,
                    'release' => isset($data['release']) && $data['release'] ? $data['release'] : null,
                    'prompts' => isset($data['prompts']) && $data['prompts'] ? $data['prompts'] : null,
                    'resell' => isset($data['currency_quantity']) ? [$data['currency_id'] => $data['currency_quantity']] : null,
                ]) // rarity, availability info (original source, purchase locations, drop locations)
            ]);

            if ($item) $this->handleImage($image, $item->imagePath, $item->imageFileName);

            return $this->commitReturn($item);
        } catch (\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Processes user input for creating/updating an item.
     *
     * @param  array                  $data
     * @param  \App\Models\Item\Item  $item
     * @return array
     */
    private function populateData($data, $item = null) {
        if (isset($data['description']) && $data['description']) $data['parsed_description'] = parse($data['description']);
        else $data['parsed_description'] = null;

        if (!isset($data['allow_transfer'])) $data['allow_transfer'] = 0;
        if (!isset($data['is_released']) && Config::get('lorekeeper.extensions.item_entry_expansion.extra_fields')) $data['is_released'] = 0;
        else $data['is_released'] = 1;

        if (isset($data['remove_image'])) {
            if ($item && $item->has_image && $data['remove_image']) {
                $data['has_image'] = 0;
                $this->deleteImage($item->imagePath, $item->imageFileName);
            }
            unset($data['remove_image']);
        }

        return $data;
    }

    /**
     * Deletes an item.
     *
     * @param  \App\Models\Item\Item  $item
     * @return bool
     */
    public function deleteItem($item) {
        DB::beginTransaction();

        try {
            // Check first if the item is currently owned or if some other site feature uses it
            if (DB::table('user_items')->where([['item_id', '=', $item->id], ['count', '>', 0]])->exists()) throw new \Exception("At least one user currently owns this item. Please remove the item(s) before deleting it.");
            if (DB::table('character_items')->where([['item_id', '=', $item->id], ['count', '>', 0]])->exists()) throw new \Exception("At least one character currently owns this item. Please remove the item(s) before deleting it.");
            if (DB::table('loots')->where('rewardable_type', 'Item')->where('rewardable_id', $item->id)->exists()) throw new \Exception("A loot table currently distributes this item as a potential reward. Please remove the item before deleting it.");
            if (DB::table('prompt_rewards')->where('rewardable_type', 'Item')->where('rewardable_id', $item->id)->exists()) throw new \Exception("A prompt currently distributes this item as a reward. Please remove the item before deleting it.");
            if (DB::table('shop_stock')->where('item_id', $item->id)->exists()) throw new \Exception("A shop currently stocks this item. Please remove the item before deleting it.");

            DB::table('items_log')->where('item_id', $item->id)->delete();
            DB::table('user_items')->where('item_id', $item->id)->delete();
            DB::table('character_items')->where('item_id', $item->id)->delete();
            $item->tags()->delete();
            if ($item->has_image) $this->deleteImage($item->imagePath, $item->imageFileName);
            $item->delete();

            return $this->commitReturn(true);
        } catch (\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**********************************************************************************************

        ITEM TAGS

     **********************************************************************************************/

    /**
     * Gets a list of item tags for selection.
     *
     * @return array
     */
    public function getItemTags() {
        $tags = Config::get('lorekeeper.item_tags');
        $result = [];
        foreach ($tags as $tag => $tagData)
            $result[$tag] = $tagData['name'];

        return $result;
    }

    /**
     * Adds an item tag to an item.
     *
     * @param  \App\Models\Item\Item  $item
     * @param  string                 $tag
     * @return string|bool
     */
    public function addItemTag($item, $tag) {
        DB::beginTransaction();

        try {
            if (!$item) throw new \Exception("Invalid item selected.");
            if ($item->tags()->where('tag', $tag)->exists()) throw new \Exception("This item already has this tag attached to it.");
            if (!$tag) throw new \Exception("No tag selected.");

            $tag = ItemTag::create([
                'item_id' => $item->id,
                'tag' => $tag
            ]);

            return $this->commitReturn($tag);
        } catch (\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Edits the data associated with an item tag on an item.
     *
     * @param  \App\Models\Item\Item  $item
     * @param  string                 $tag
     * @param  array                  $data
     * @return string|bool
     */
    public function editItemTag($item, $tag, $data) {
        DB::beginTransaction();

        try {
            if (!$item) throw new \Exception("Invalid item selected.");
            if (!$item->tags()->where('tag', $tag)->exists()) throw new \Exception("This item does not have this tag attached to it.");

            $tag = $item->tags()->where('tag', $tag)->first();

            $service = $tag->service;
            if (!$service->updateData($tag, $data)) {
                $this->setErrors($service->errors());
                throw new \Exception('sdlfk');
            }

            // Update the tag's active setting
            $tag->is_active = isset($data['is_active']);
            $tag->save();

            return $this->commitReturn($tag);
        } catch (\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }

    /**
     * Removes an item tag from an item.
     *
     * @param  \App\Models\Item\Item  $item
     * @param  string                 $tag
     * @return string|bool
     */
    public function deleteItemTag($item, $tag) {
        DB::beginTransaction();

        try {
            if (!$item) throw new \Exception("Invalid item selected.");
            if (!$item->tags()->where('tag', $tag)->exists()) throw new \Exception("This item does not have this tag attached to it.");

            $item->tags()->where('tag', $tag)->delete();

            return $this->commitReturn(true);
        } catch (\Exception $e) {
            $this->setError('error', $e->getMessage());
        }
        return $this->rollbackReturn(false);
    }
}
