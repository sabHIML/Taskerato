## Taskerato
Tasks management application with simple UI and API. The application will manage tasks for users that are registered in third party application. Each task
can have subtasks and tasks without subtasks have points. That means parent task has a sum of points from subtasks. What is more, if all subtasks of a task are done, then
task also is done. If at least one subtask of a task marked as not done then it also becomes not done.

### Technologies :
* Laravel
* Vue.js

### Developed with :
* Node 12.1.0
* Yarn 1.17.0
* PHP 7.1.13

## Project setup

* clone from git
* cd to project directory
* `cp .env.example .env` then set mysql credential over there.  
* run `composer install`
* run `php artisan migrate`
* run `yarn`
* run `yarn run production`

#### Run project 
* Run `php artisan serve`
. Now visit in your browser.

## Required endpoints
#### Create task endpoint

#### POST /api/task
Success response code: 201

Request example:

    {
         "parent_id":1,
         "user_id":1,
         "title":"Task 1",
         "points":3,
         "is_done":0,
         "email":"john.doe@email.com"
    }

Response example:

    {
         "id":1,
         "parent_id":1,
         "user_id":1,
         "title":"Task 1",
         "points":3,
         "is_done":0,
         "created_at":"2020-01-01 00:00:00",
         "updated_at":"2020-01-01 00:00:00"
    }
#### Validations:
* parent_id: existing task id or null;
* user_id: required and existing user id;
* title: required;
* point: required integer where the minimum value is 1 and the maximum value is 10;
* is_done: required integer, 0 or 1;

#### Update task endpoint

#### PUT /api/task/{task_id}

Success response code: 201

Request example:

    {
         "parent_id":1,
         "user_id":1,
         "title":"Task 1",
         "points":10,
         "is_done":1,
         "email":"john.doe@email.com"
    }

Response example:

    {
         "id":1,
         "parent_id":1,
         "user_id":1,
         "title":"Task 1",
         "points":10,
         "is_done":1,
         "created_at":"2020-01-01 00:00:00",
         "updated_at":"2020-01-01 00:00:00"
    }
    
#### Validations:
* parent_id: existing task id or null;
* user_id: required and existing user id;
* title: required;
* point: required integer where the minimum value is 1 and the maximum value is 10;
* is_done: required integer, 0 or 1;

#### Errors handling
* If validations fail should return 400 status code and return validations messages;
* All other unexpected errors should return the 500 status code

#### Users management
User should be taken from : https://gitlab.iterato.lt/snippets/3/raw
User end point can be configure at `.env` file.

