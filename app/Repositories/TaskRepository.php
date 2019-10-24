<?php

namespace App\Repositories;

use App\Models\Task;

/**
 * Class TaskRepository
 * @package App\Repositories
*/

class TaskRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'parent_id',
        'user_id',
        'title',
        'points',
        'is_done'
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * Fetch tasks in task/sub-task tree order
     */
    public function getTaskTree()
    {
        $query = $this->model->newQuery();

        return $query->with('children')
            ->whereNull('parent_id')
            ->orderBy('user_id', 'asc')
            ->get();

    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Task::class;
    }
}
