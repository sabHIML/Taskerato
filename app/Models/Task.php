<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;

/**
 * Class Task
 * @package App\Models
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
        'is_done',
        'email',
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
        'email' => 'string',
        'title' => 'string',
        'points' => 'integer',
        'is_done' => 'integer',
        'done_count' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'user_id' => 'required|numeric',
        'parent_id' => 'nullable|numeric',
        'title' => 'required',
        'points' => 'required|numeric|min:1|max:10',
        'is_done' => 'required|numeric|min:0|max:1',
        'email' => 'email'
    ];

    /**
     * each task might have multiple children
     */
    public function children()
    {
        return $this->hasMany(static::class, 'parent_id');
    }

}
