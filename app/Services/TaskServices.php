<?php

namespace App\Services;

use App\Repositories\TaskRepository;
use Config;
use Exception;
use InvalidArgumentException;

/**
 * Class UserApi
 * @package App\Services
 */
class TaskServices
{
    /** @var  TaskRepository */
    private $taskRepository;

    public function __construct(TaskRepository $taskRepo)
    {
        $this->taskRepository = $taskRepo;
    }

    /**
     * Fetch all task from database
     * and organize them in child-parent tree
     *
     * @return array
     */
    public function getTaskTree()
    {
        return $this->buildTree(
            $this->taskRepository->getTaskTree()
        );
    }

    /**
     * Orginize tasks into parent-child tree
     *
     * @param $dbRawData
     * @return array
     */
    private function buildTree($dbRawData)
    {
        $depth = Config::get('constants.tasks.subtask.max');

        $treeL1 = [];
        foreach ($dbRawData as $data) {

            $leaf_done_points = null;

            // $treeL2 = $treeL3 = $treeL4 = $treeL5 = [];
            for ($i = 2; $i <= $depth; $i++) {
                $arr = "treeL{$i}";
                $$arr = [];
            }

            for ($i = $depth; $i > 1; $i--) {
                $level = 'treeL' . $i;
                $upperLevel = 'treeL' . ($i + 1);
                $id = "taskLevel{$i}id";
                $title = "title{$i}";
                $points = "taskL{$i}points";
                $is_done = "taskL{$i}is_done";

                if (!is_null($data->$id)) {
                    if (!isset($$level[$data->$id])) {
                        $$level[$data->$id] = [
                            'id' => $data->$id,
                            'title' => $data->$title,
                            'points' => $data->$points,
                            'is_done' => $data->$is_done,
                            'children' => []

                        ];
                        if (is_null($leaf_done_points)) {
                            $leaf_done_points = $data->$is_done == 1 ? $data->$points : 0;
                        }
                    }

                    if ($i != $depth) {
                        $$level[$data->$id]['children'] =
                            array_replace_recursive($$level[$data->$id]['children'], $$upperLevel);
                    }
                }
            }

            if (!is_null($data->taskLevel1id)) {

                if (!array_key_exists($data->taskLevel1id, $treeL1)) {
                    $treeL1[$data->taskLevel1id] = [
                        'id' => $data->taskLevel1id,
                        'title' => $data->title1,
                        'user_id' => $data->user_id,
                        'email' => $data->email,
                        'done_points' => 0,
                        'points' => $data->points,
                        'is_done' => $data->taskLevel1is_done,
                        'children' => []
                    ];
                }
                $treeL1[$data->taskLevel1id]['children'] =
                    array_replace_recursive($treeL1[$data->taskLevel1id]['children'], $treeL2);

                if (!is_null($leaf_done_points)) {
                    $treeL1[$data->taskLevel1id]['done_points'] += $leaf_done_points;
                } else { // this is root level task, no sub task available to this task
                    if ($data->taskLevel1is_done == 1) {
                        $treeL1[$data->taskLevel1id]['done_points'] = $data->points;
                    }
                }
            }
        }
        return $treeL1;
    }

    /**
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function find(int $id)
    {
        return $this->taskRepository->find($id);
    }

    /**
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function findWithChildCount(int $id)
    {
        return $this->taskRepository->findWithChildCount($id);
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception
     */
    public function create(array $input)
    {
        $parentTask = null;
        if (!is_null($input['parent_id'])) { // this is a sub task
            $parentTask = $this->taskRepository->findWithChildCount($input['parent_id'])->toArray();

            $this->validation($parentTask, $input);
        }
        return $this->taskRepository->createNewTask($input, $parentTask);
    }

    /**
     * @param array $parentTask
     * @param array $input
     * @throws InvalidArgumentException
     */
    private function validation(array $parentTask, array $input)
    {
        if (empty($parentTask)) {
            throw new InvalidArgumentException('parent task not found');
        } elseif (
            $parentTask['user_id'] != $input['user_id'] ||
            $parentTask['email'] != $input['email']
        ) {
            throw new InvalidArgumentException('user not match with parent task!!');
        }

        // todo validation : task can have children. Maximum depth: 5.
//            if($parentTask['children_count'] >= 5) {
//                // return error : maximum sub task retched
//                throw new \Exception('maximum sub task retched');
//            }
    }

    /**
     * @param array $input
     * @param array $thisTask
     * @return mixed
     * @throws Exception
     */
    public function update(array $input, array $thisTask)
    {
        $parentTask = null;
        if (!is_null($input['parent_id'])) { // this is a sub task

            if ($input['parent_id'] == $thisTask['id']) { // validation
                throw new InvalidArgumentException('Task can\'t child of its own');
            }

            $parentTask = $this->taskRepository
                ->findWithChildCount($input['parent_id'])->toArray();

            $this->validation($parentTask, $input);
        }

        // don't update my point if I have subtask
        // because my points should be sum of my subtask
        if ($thisTask['children_count'] > 0) {
            unset($input['points']);
        }

        return $this->taskRepository
            ->updateTask($input, $thisTask, $parentTask);
    }


}
