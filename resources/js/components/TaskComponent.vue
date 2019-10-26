<template>
    <div class="task-container">
        <p v-if="loading">Loading..</p>
        <p v-if="!loading && !users.length">List is empty!</p>
        <div class="user-box" v-for="user in users">

            <p>
                <span>{{user.first_name}} {{user.last_name}}</span>
                <span>({{user.totalDoneTaskPoint}}/{{user.totalTaskPoint}})</span>
            </p>

            <li v-for="task in user.tasks">
                <span>
                    (<span v-if="task.is_done == 0">X</span><span v-if="task.is_done == 1">V</span>)
                    {{task.title}}
                    ({{task.points}})
                </span>
                <ul v-for="sabTask in task.children">
                    <li>
                        <span>
                            (<span v-if="sabTask.is_done == 0">X</span><span v-if="sabTask.is_done == 1">V</span>)
                            {{sabTask.title}}
                            ({{sabTask.points}})
                        </span>
                        <ul v-for="sab2Task in sabTask.children">
                            <li>
                                <span>
                                    (<span v-if="sab2Task.is_done == 0">X</span><span
                                    v-if="sab2Task.is_done == 1">V</span>)
                                    {{sab2Task.title}}
                                    ({{sab2Task.points}})
                                </span>
                                <ul v-for="sab3Task in sab2Task.children">
                                    <li>
                                        <span>
                                            (<span v-if="sab3Task.is_done == 0">X</span><span
                                            v-if="sab3Task.is_done == 1">V</span>)
                                            {{sab3Task.title}}
                                            ({{sab3Task.points}})
                                        </span>
                                        <ul v-for="sab4Task in sab3Task.children">
                                            <li>
                                        <span>
                                            (<span v-if="sab4Task.is_done == 0">X</span><span
                                            v-if="sab4Task.is_done == 1">V</span>)
                                            {{sab4Task.title}}
                                            ({{sab4Task.points}})
                                        </span>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
        </div>
    </div>
</template>

<script>
    export default {
        data: function () {
            return {
                users: [],
                loading: true
            }
        },
        created() {
            this.fetchTaskList();
        },
        methods: {
            fetchTaskList() {
                window.axios.get('/api/tasks/').then(res => {
                    if (res.data.success != undefined && res.data.success == true) {
                        this.users = res.data.data;
                    }
                    this.loading = false;

                }).catch(e => {
                    this.loading = false;
                });
            }
        },
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>
