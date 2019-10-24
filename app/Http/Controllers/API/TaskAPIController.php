<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateTaskAPIRequest;
use App\Http\Requests\API\UpdateTaskAPIRequest;
use App\Models\Task;
use App\Repositories\TaskRepository;
use App\Services\UserApi;
use App\Http\Controllers\AppBaseController;
use Exception;

/**
 * Class TaskController
 * @package App\Http\Controllers\API
 */

class TaskAPIController extends AppBaseController
{
    /** @var  TaskRepository */
    private $taskRepository;

    public function __construct(TaskRepository $taskRepo)
    {
        $this->taskRepository = $taskRepo;
    }

    /**
     * Display a listing of the task and sub-tasks group by each user.
     * GET|HEAD /tasks
     *
     * @param UserApi $userApi
     * @return Response
     */
    public function index(UserApi $userApi)
    {
        try {
            $users = $userApi->mapTasks(
                $this->taskRepository->getTaskTree()->toArray()
            );
        } catch (Exception $e) {
            // todo log Exception
            return $this->sendError('Users not found');
        }

        return $this->sendResponse($users);
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
        $input = $request->all();

        $task = $this->taskRepository->create($input);

        return $this->sendResponse($task->toArray(), 201);
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
        $input = $request->all();

        /** @var Task $task */
        $task = $this->taskRepository->find($id);

        if (empty($task)) {
            return $this->sendError('Task not found');
        }

        $task = $this->taskRepository->update($input, $id);

        return $this->sendResponse($task->toArray(), 201);
    }
}
