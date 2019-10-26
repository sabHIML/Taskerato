<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateTaskAPIRequest;
use App\Http\Requests\API\UpdateTaskAPIRequest;
use App\Models\Task;
use App\Services\UserServices;
use App\Services\TaskServices;
use App\Http\Controllers\AppBaseController;
use Exception;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;


/**
 * Class TaskController
 * @package App\Http\Controllers\API
 */
class TaskAPIController extends AppBaseController
{
    /** @var  TaskServices */
    private $taskServices;

    public function __construct(TaskServices $taskServices)
    {
        $this->taskServices = $taskServices;
    }

    /**
     * Display a listing of the task and sub-tasks group by each user.
     * GET|HEAD /tasks
     *
     * @param UserServices $userServices
     * @return Response
     */
    public function index(UserServices $userServices)
    {
        try {
            $users = $userServices->mapTasks(
                $this->taskServices->getTaskTree()
            );
        } catch (Exception $e) {
            // todo log Exception $e->getMessage()
            //echo $e->getMessage();
            return $this->sendError('Response error!');
        }

        return $this->sendResponse($users, JsonResponse::HTTP_OK, 'user found');
    }

    /**
     * Store a newly created Task in storage.
     * POST /tasks
     *
     * @param CreateTaskAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateTaskAPIRequest $request)
    {
        try {
            $task = $this->taskServices->create($request->all());
            return $this->sendResponse($task->toArray(), JsonResponse::HTTP_CREATED);

        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
//            echo $e->getMessage();
            $msg = is_string($e) ? $e : 'Task creation failed';
            return $this->sendError($msg);
        }
    }

    /**
     * Update the specified Task in storage.
     * PUT/PATCH /tasks/{id}
     *
     * @param int $id
     * @param UpdateTaskAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateTaskAPIRequest $request)
    {
        try {
            $input = $request->all();

            /** @var Task $task */
            $thisTask = $this->taskServices->findWithChildCount($id);

            if (empty($thisTask)) {
                return $this->sendError('Task not found');
            }

            $updatedTask = $this->taskServices->update($input, $thisTask->toArray());

            return $this->sendResponse($updatedTask->toArray(), JsonResponse::HTTP_CREATED);

        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
//            echo $e->getMessage();//todo remove it and write in log
            $msg = is_string($e) ? $e : 'Task update failed';
            return $this->sendError($msg);
        }

    }
}
