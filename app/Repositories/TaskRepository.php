<?php

namespace App\Repositories;

use App\Models\Task;
use Config;
use DB;
use Exception;

/**
 * Class TaskRepository
 * @package App\Repositories
 */
class TaskRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [];

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
        $depth = Config::get('constants.tasks.subtask.max');
        $query = $this->model->newQuery();

        $selectQry = [];
        for ($i = 2; $i <= $depth; $i++) {
            $selectQry[] = "
                taskLevel{$i}.title as title{$i},
                taskLevel{$i}.id as taskLevel{$i}id,
                taskLevel{$i}.points as taskL{$i}points,
                taskLevel{$i}.is_done as taskL{$i}is_done
            ";
        };
        $query->selectRaw('
                taskLevel1.title AS title1,
                taskLevel1.id as taskLevel1id,
                taskLevel1.parent_id as parent_id,
                taskLevel1.user_id as user_id,
                taskLevel1.email as email,
                taskLevel1.points as points,
                taskLevel1.is_done as taskLevel1is_done,
                ' . join(', ', $selectQry)
        );

        $query->from('tasks as taskLevel1');

        for ($i = 2; $i <= $depth; $i++) {
            $query->leftJoin("tasks as taskLevel{$i}",
                "taskLevel{$i}.parent_id",
                '=',
                "taskLevel" . ($i - 1) . ".id"
            );
        }

        $query->whereNull('taskLevel1.parent_id');
        return $query->get();

    }

    /**
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function findWithChildCount(int $id)
    {
        $query = $this->model->newQuery();

        return $query->withCount([
            'children',
            'children as children_point_sum' => function ($qry) use ($id) {

                $qry->select(DB::raw('IFNULL(sum(points), 0)'))
                    ->where('parent_id', '=', $id);
            },
            'children as done_children_count' => function ($qry) use ($id) {

                $qry->where('parent_id', '=', $id)
                    ->where('is_done', '=', 1);
            }
        ])
            ->where('id', $id)
            ->first();
    }

    /**
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getChildPointSum(int $id)
    {
        $query = $this->model->newQuery();

        return $query->withCount([
            'children as children_point_sum' => function ($qry) use ($id) {
                $qry->select(DB::raw('IFNULL(sum(points), 0)'))
                    ->where('parent_id', '=', $id);
            },

            'children as done_children_count' => function ($qry) use ($id) {
                $qry->where('parent_id', '=', $id)
                    ->where('is_done', '=', 1);
            }
        ])
            ->where('id', $id)
            ->first();

    }

    /**
     * @param array $input
     * @param array|null $parentTask
     * @return array
     */
    public function createNewTask(array $input, ?array $parentTask)
    {
        try {
            return DB::transaction(function () use ($input, $parentTask) {
                $createdTask = $this->create($input);
                if (!is_null($parentTask)) { // this is a sub task

                    // check and update grand parent tasks based on changes in this task
                    $freshParentTask = $this->findWithChildCount($createdTask->id)
                        ->toArray();

                    if (!is_null($freshParentTask['parent_id'])) {
                        $this->reCalculateGrandParentState(
                            $freshParentTask
                        );
                    }
                }
                return $createdTask;
            });
        } catch (Exception $e) {
//            echo $e->getMessage();//todo write log exception
            throw new Exception('task creation failed!');
        }
    }

    /**
     * @param array $input
     * @param array $thisTask
     * @param array|null $parentTask
     * @return mixed
     * @throws Exception
     */
    public function updateTask(array $input, array $thisTask, ?array $parentTask)
    {
        try {
            return DB::transaction(function () use ($input, $thisTask, $parentTask) {

                // if parent id changed, cache old parent tree
                $oldParentTask = null;
                if ($thisTask['parent_id'] != $input['parent_id']) {
                    $oldParentTask = $this->findWithChildCount($thisTask['id'])
                        ->toArray();
                }

                // update this task
                $updatedData = $this->update($input, $thisTask['id']);

                // if parent id changed, re calculate task's old parent tree
                if (!is_null($oldParentTask['parent_id'])) {
                    $this->reCalculateGrandParentState(
                        $oldParentTask
                    );
                }

                if (!is_null($parentTask)) {
                    // this is a sub task, update it's parent task
                    $this->updateParentTask($parentTask, $input);
                }

                // If task "is_done" = 1 then
                // all subtasks = done, recursively
                if ($input['is_done'] == 1 &&
                    $input['is_done'] != $thisTask['is_done']) {

                    $this->updateSubTaskDoneState(
                        $input['is_done'], $thisTask['id']
                    );
                }

                // check and update grand parent tasks based on changes in this task
                if (!is_null($parentTask)) {
                    $freshParentTask = $this->findWithChildCount($parentTask['id'])->toArray();

                    if (!is_null($freshParentTask['parent_id'])) {
                        $this->reCalculateGrandParentState(
                            $freshParentTask
                        );
                    }
                }
                return $updatedData;
            });
        } catch (Exception $e) {
//            echo $e->getMessage();//todo write log exception
            throw new Exception('task update failed!');
        }

    }

    /**
     * Recalculate and update immediate parent state:
     * update parent's:
     *       Self points based on child point sum
     *      is_done
     *
     * @param array $parentTask
     * @param array $input
     */
    private function updateParentTask(array $parentTask, array $input)
    {
        $updatedParentTaskData = [];

        // if update request for my points
        //update parent-task.points = sum of subtasks points;
        $updatedParentTaskData['points'] =
            $this->getChildPointSum($parentTask['id'])['children_point_sum'];

        //updating undone sub-task into a parent where parent is maked as done.
        //so change parent as undone.
        if ($input['is_done'] == 0 && $parentTask['is_done'] = 1) {
            $updatedParentTaskData['is_done'] = 0;
        }

        // I am done and I was last undone subtask among my siblings
        //so, make my parent done.
        if (
            $input['is_done'] == 1 &&
            $parentTask['children_count'] == $parentTask['done_children_count'] + 1
        ) {
            $updatedParentTaskData['is_done'] = 1;
        }

        // update parent info on database
        if (!empty($updatedParentTaskData)) {
            $this->update($updatedParentTaskData, $parentTask['id']);
        }
    }

    /**
     * @param array $parentTask
     */
    private function reCalculateGrandParentState(array $parentTask)
    {
        $grandParentTask = $this->findWithChildCount($parentTask['parent_id'])
            ->toArray();

        $updatedGrandParentTaskData = [];

        //update parent-task.points = sum of subtasks points;
        $updatedGrandParentTaskData['points'] = $grandParentTask['children_point_sum'];

        //updating undone sub-task into a parent where parent is maked as done.
        //so change parent as undone.
        if ($parentTask['is_done'] == 0 && $grandParentTask['is_done'] == 1) {
            $updatedGrandParentTaskData['is_done'] = 0;
        }

        // I am done and I was last undone subtask among my siblings
        //so, make my parent done.
        if (
            $grandParentTask['is_done'] == 0 &&
            $grandParentTask['children_count'] == $grandParentTask['done_children_count']
        ) {
            $updatedGrandParentTaskData['is_done'] = 1;
        }

        //update parent info on database
        if (!empty($updatedGrandParentTaskData)) {
            $this->update($updatedGrandParentTaskData, $grandParentTask['id']);
        }

        if (is_null($grandParentTask['parent_id'])) {
            return; // Break recursion
        } else {
            $freshGrandParentTask = $this->findWithChildCount($grandParentTask['id'])->toArray();

            if (!is_null($freshGrandParentTask['parent_id'])) {
                $this->reCalculateGrandParentState(
                    $freshGrandParentTask
                );
            }

        }
    }

    /**
     * @param $isDone
     * @param $parent_id
     */
    private function updateSubTaskDoneState(int $isDone, int $parent_id)
    {
        $query = $this->model->newQuery();

        $subTasks = $query->where([
            'parent_id' => $parent_id
        ])->get(['id']);

        if (empty($subTasks)) {
            return;
        }

        $query->where('parent_id', $parent_id)
            ->update(['is_done' => $isDone]);

        foreach ($subTasks as $subTask) {
            $this->updateSubTaskDoneState($isDone, $subTask->id);
        }
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Task::class;
    }
}
