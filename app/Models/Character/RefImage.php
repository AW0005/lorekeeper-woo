<?php

namespace App\Models\Character;

use Illuminate\Support\Facades\DB;
use App\Models\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class RefImage extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'image_id', 'extension', 'hash', 'fullsize_hash', 'sort',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ref_images';

    /**
     * Whether the model contains timestamps to be saved and updated.
     *
     * @var string
     */
    public $timestamps = true;

    /**
     * Validation rules for image creation.
     *
     * @var array
     */
    public static $createRules = [
        'image' => 'required|mimes:jpeg,jpg,gif,png',
        'thumbnail' => 'nullable|mimes:jpeg,jpg,gif,png|max:20000',
    ];

    /**
     * Validation rules for image updating.
     *
     * @var array
     */
    public static $updateRules = [
        'image_id' => 'required',
    ];

    /**********************************************************************************************

        RELATIONS

    **********************************************************************************************/

    public function image()
    {
        return $this->belongsTo('App\Models\Character\CharacterImage', 'image_id');
    }

    /**
     * Get the artists attached to the character image.
     */
    public function artists()
    {
        return $this->hasMany('App\Models\Character\CharacterImageCreator', 'character_image_id')->where('type', 'Artist')->where('character_type', 'RefImage');
    }

    /**********************************************************************************************

        ACCESSORS

    **********************************************************************************************/

    /**
     * Gets the file directory containing the model's image.
     *
     * @return string
     */
    public function getImageDirectoryAttribute()
    {
        return 'images/refs/'.floor($this->id / 1000);
    }

    /**
     * Gets the file name of the model's image.
     *
     * @return string
     */
    public function getImageFileNameAttribute()
    {
        return $this->id . '_'.$this->hash.'.'.$this->extension;
    }

    /**
     * Gets the path to the file directory containing the model's image.
     *
     * @return string
     */
    public function getImagePathAttribute()
    {
        return public_path($this->imageDirectory);
    }

    /**
     * Gets the URL of the model's image.
     *
     * @return string
     */
    public function getImageUrlAttribute()
    {
        return asset($this->imageDirectory . '/' . $this->imageFileName);
    }

    /**
     * Gets the file name of the model's fullsize image.
     *
     * @return string
     */
    public function getFullsizeFileNameAttribute()
    {
        return $this->id . '_'.$this->hash.'_'.$this->fullsize_hash.'_full.'.$this->extension;
    }

    /**
     * Gets the file name of the model's fullsize image.
     *
     * @return string
     */
    public function getFullsizeUrlAttribute()
    {
        return asset($this->imageDirectory . '/' . $this->fullsizeFileName);
    }

    /**
     * Gets the file name of the model's fullsize image.
     *
     * @param  user
     * @return string
     */
    public function canViewFull($user = null)
    {
        return $this->image->canViewFull($user);
    }

    /**
     * Gets the file name of the model's thumbnail image.
     *
     * @return string
     */
    public function getThumbnailFileNameAttribute()
    {
        return $this->id . '_'.$this->hash.'_th.'.$this->extension;
    }

    /**
     * Gets the path to the file directory containing the model's thumbnail image.
     *
     * @return string
     */
    public function getThumbnailPathAttribute()
    {
        return $this->imagePath;
    }

    /**
     * Gets the URL of the model's thumbnail image.
     *
     * @return string
     */
    public function getThumbnailUrlAttribute()
    {
        return asset($this->imageDirectory . '/' . $this->thumbnailFileName);
    }

}

?>
