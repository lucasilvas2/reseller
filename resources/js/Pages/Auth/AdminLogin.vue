<template>
    <div>
        <h1>Admin Login</h1>
        <form @submit.prevent="submit">
            <div>
                <label for="email">Email</label>
                <input id="email" v-model="form.email" type="email" required autofocus />
            </div>
            <div>
                <label for="password">Password</label>
                <input id="password" v-model="form.password" type="password" required />
            </div>
            <div>
                <button type="submit">Login</button>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    data() {
        return {
            form: {
                email: '',
                password: '',
                remember: false,
            },
        };
    },
    methods: {
        submit() {
            this.$inertia.post('/admin/login', this.form);
        },
    },
};
</script>
