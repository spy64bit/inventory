<script setup lang="ts">
import { ref } from 'vue';
import { Form, Head } from "@inertiajs/vue3";
import Login from "@/actions/App/Http/Controllers/Login";

const email = ref('admin@example.com');
const password = ref('password');

function fillCredentials(role: 'admin' | 'manager' | 'staff') {
  email.value = `${role}@example.com`;
  password.value = 'password';
}
</script>

<template>

  <Head title="Login" />

  <div
    class="flex min-h-screen items-center justify-center bg-linear-to-br from-gray-50 to-gray-100 px-4 dark:from-gray-900 dark:to-gray-950">

    <div class="w-full max-w-md space-y-5">
      <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4 dark:border-indigo-900 dark:bg-indigo-950/40">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">
          Demo Accounts
        </p>
        <div class="space-y-1.5 text-sm">
          <div
            class="flex cursor-pointer justify-between rounded px-1 py-0.5 transition hover:bg-indigo-100 dark:hover:bg-indigo-900/50"
            @click="fillCredentials('admin')">
            <span class="text-gray-500 dark:text-gray-400">Admin</span>
            <span class="font-mono text-gray-700 dark:text-gray-300">admin@example.com / password</span>
          </div>
          <div
            class="flex cursor-pointer justify-between rounded px-1 py-0.5 transition hover:bg-indigo-100 dark:hover:bg-indigo-900/50"
            @click="fillCredentials('manager')">
            <span class="text-gray-500 dark:text-gray-400">Manager</span>
            <span class="font-mono text-gray-700 dark:text-gray-300">manager@example.com / password</span>
          </div>
          <div
            class="flex cursor-pointer justify-between rounded px-1 py-0.5 transition hover:bg-indigo-100 dark:hover:bg-indigo-900/50"
            @click="fillCredentials('staff')">
            <span class="text-gray-500 dark:text-gray-400">Staff</span>
            <span class="font-mono text-gray-700 dark:text-gray-300">staff@example.com / password</span>
          </div>
        </div>
      </div>

      <div id="loginForm"
        class="rounded-2xl bg-white p-8 shadow-xl ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
        <div class="mb-8 text-center">
          <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
            Welcome back
          </h1>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Sign in to your account to continue
          </p>
        </div>

        <Form v-bind="Login.form()" #default="{ errors, processing }">
          <div class="space-y-5">
            <div>
              <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Email address
              </label>
              <input id="email" v-model="email" type="email" name="email" autocomplete="email"
                placeholder="you@example.com" class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-indigo-400 
                dark:focus:ring-indigo-400/20" />
              <p v-if="errors.email" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                {{ errors.email }}
              </p>
            </div>

            <div>
              <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Password
              </label>
              <input id="password" v-model="password" type="password" name="password" autocomplete="current-password"
                placeholder="••••••••"
                class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/20" />
              <p v-if="errors.password" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                {{ errors.password }}
              </p>
            </div>

            <button type="submit" :disabled="processing"
              class="w-full cursor-pointer rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-800">
              {{ processing ? 'Signing in...' : 'Sign in' }}
            </button>
          </div>
        </Form>
      </div>
    </div>
  </div>
</template>