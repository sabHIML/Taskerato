<?php

namespace App\Services;

use Exception;
use Config;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;

/**
 * Class UserApi
 * @package App\Services
 */
class UserServices
{
    protected $client;

    public function __construct(GuzzleHttpClient $client)
    {
        $this->client = $client;
    }

    public function mapTasks(array $allTasks)
    {
        try {
            $guzzleResponse = $this->client->get(Config::get('constants.users.api.endpoint'));
            if ($guzzleResponse->getStatusCode() == 200) {
                $users = json_decode($guzzleResponse->getBody());
            }

        } catch (RequestException $e) {
            throw new Exception("RequestException : " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Exception : " . $e->getMessage());
        }

        return $this->prepareUserRes($users->data, $allTasks);
    }

    /**
     * @param array $users
     * @param array $allTasks
     * @return array
     */
    private function prepareUserRes(array $users, array $allTasks)
    {
        foreach ($users as $user) {

            $user->tasks = property_exists($user, 'tasks') ? $user->tasks : [];
            $user->totalTaskPoint = property_exists($user, 'totalTaskPoint') ? $user->totalTaskPoint : 0;
            $user->totalDoneTaskPoint = property_exists($user, 'totalDoneTaskPoint') ? $user->totalDoneTaskPoint : 0;

            foreach ($allTasks as $key => $userTask) {

                if ($userTask['user_id'] == $user->id && $userTask['email'] == $user->email) {

                    $user->tasks[] = $userTask;

                    $user->totalDoneTaskPoint = property_exists($user, 'totalDoneTaskPoint') ?
                        ($user->totalDoneTaskPoint + $userTask['done_points']) :
                        $userTask['done_points'];

                    $user->totalTaskPoint = property_exists($user, 'totalTaskPoint') ?
                        ($user->totalTaskPoint + $userTask['points']) :
                        $userTask['points'];

                    unset($allTasks[$key]);
                }
            }
        }
        return $users;
    }

}
