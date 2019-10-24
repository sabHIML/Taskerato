<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

/**
 * Class Task
 * @package App\Models
 * @version October 23, 2019, 1:16 pm UTC
 *
 * @property integer parent_id
 * @property integer user_id
 * @property string title
 * @property integer points
 * @property integer is_done
 */
class Task extends Model
{
    public $table = 'tasks';

    public $fillable = [
        'parent_id',
        'user_id',
        'title',
        'points',
        'is_done'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'parent_id' => 'integer',
        'user_id' => 'integer',
        'title' => 'string',
        'points' => 'integer',
        'is_done' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'user_id' => 'required',
        'title' => 'required',
        'points' => 'required|min:1|max:10',
        'is_done' => 'required|min:0|max:1'
    ];

}
