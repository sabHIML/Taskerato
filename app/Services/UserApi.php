<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use Exception;

/**
 * Class UserApi
 * @package App\Services
 */
class UserApi
{
    protected $client;

    public function __construct(GuzzleHttpClient $client)
    {
        $this->client = $client;
    }

    public function mapTasks(array $allTasks)
    {
        try {
            $guzzleResponse = $this->client->get('https://gitlab.iterato.lt/snippets/3/raw');
            if ($guzzleResponse->getStatusCode() == 200) {
                $users = json_decode($guzzleResponse->getBody());
            }

        } catch (RequestException $e) {
            throw new Exception("RequestException : " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Exception : " . $e->getMessage());
        }

        foreach ($users->data as $user) {
            $user->tasks = array_filter($allTasks, function ($task) use ($user) {
                return $task['user_id'] == $user->id;
            });
        }

        return $users;
    }

    public function fetch()
    {
        $data = $this->client->get('http://api.com/api/items');
        $items = [];
        foreach ($data as $k => $v) {
            $item = [
                'name' => $v['name'],
                'description' => $v['data']['description'],
                'tags' => $v['data']['metadata']['tags']
            ];
            $items[] = $item;
        }
        return $items;
    }
}
