<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    phone: '',
    password: '',
    remember: false
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password')
    });
};
</script>

<template>
    <Head title="Login" />

    <main class="min-h-screen overflow-hidden bg-[#f6f3ec] text-[#1f2933]" dir="ltr">
        <div class="absolute inset-0 opacity-70">
            <div class="absolute -top-32 -left-24 h-96 w-96 rounded-full bg-emerald-200 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-[32rem] w-[32rem] rounded-full bg-amber-200 blur-3xl"></div>
        </div>

        <section class="relative grid min-h-screen place-items-center px-6 py-12">
            <div class="w-full max-w-md rounded-[2rem] border border-white/70 bg-white/85 p-8 shadow-2xl shadow-stone-300/60 backdrop-blur">
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-600 text-2xl font-black text-white shadow-lg shadow-emerald-900/20">
                        S
                    </div>
                    <h1 class="text-3xl font-black tracking-tight text-stone-950">Welcome to Admin</h1>
                    <p class="mt-2 text-sm text-stone-500">Sign in with your mobile number to continue.</p>
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <div>
                        <label for="phone" class="mb-2 block text-sm font-bold text-stone-700">Mobile</label>
                        <InputMask
                            id="phone"
                            v-model="form.phone"
                            mask="09999999999"
                            placeholder="09123456789"
                            class="w-full"
                            :invalid="Boolean(form.errors.phone)"
                        />
                        <small v-if="form.errors.phone" class="mt-2 block text-red-600">{{ form.errors.phone }}</small>
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-bold text-stone-700">Password</label>
                        <Password
                            id="password"
                            v-model="form.password"
                            placeholder="Password"
                            :toggleMask="true"
                            fluid
                            :feedback="false"
                            :invalid="Boolean(form.errors.password)"
                        />
                        <small v-if="form.errors.password" class="mt-2 block text-red-600">{{ form.errors.password }}</small>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-stone-600">
                            <Checkbox v-model="form.remember" binary />
                            Remember me
                        </label>
                    </div>

                    <Button label="Sign In" type="submit" class="w-full" :loading="form.processing" />
                </form>

                <div class="mt-6 rounded-2xl bg-stone-100 p-4 text-sm text-stone-600">
                    Demo admin: <b>09126860148</b> / <b>123456789</b>
                </div>
            </div>
        </section>
    </main>
</template>
