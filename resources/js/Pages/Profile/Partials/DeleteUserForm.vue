<script setup>
import { nextTick, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { EyeIcon, EyeSlashIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const confirmingUserDeletion = ref(false)
const passwordInput = ref(null)
const showPassword = ref(false)

const form = useForm({
    password: '',
})

function confirmUserDeletion() {
    confirmingUserDeletion.value = true
    nextTick(() => passwordInput.value?.focus())
}

function deleteUser() {
    form.delete('/profile', {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    })
}

function closeModal() {
    confirmingUserDeletion.value = false
    form.clearErrors()
    form.reset()
}

const inputClass = 'w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-4 py-2.5 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-muted)] transition-all duration-200 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/30'
const labelClass = 'mb-1.5 block text-sm font-medium text-[var(--text-secondary)]'
const errorClass = 'mt-1.5 text-xs text-rose-500'
</script>

<template>
    <section>
        <header class="mb-5">
            <h2 class="text-lg font-bold text-[var(--text-primary)]">
                Delete Account
            </h2>
            <p class="mt-1 text-sm text-[var(--text-secondary)]">
                Once your account is deleted, all of its data will be permanently
                removed. Please download any data you wish to keep before proceeding.
            </p>
        </header>

        <button
            type="button"
            @click="confirmUserDeletion"
            class="rounded-full bg-rose-500/10 px-6 py-2.5 text-sm font-semibold text-rose-600 transition-colors duration-150 hover:bg-rose-500/20 dark:text-rose-300"
        >
            Delete Account
        </button>

        <!-- Confirmation Modal -->
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            leave-active-class="transition-opacity duration-150 ease-in"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="confirmingUserDeletion"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="closeModal"
            >
                <div class="w-full max-w-md rounded-2xl border border-[var(--card-border)] bg-[var(--card-bg)] p-6 shadow-2xl">

                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-500/10">
                            <ExclamationTriangleIcon class="h-5 w-5 text-rose-600 dark:text-rose-300" />
                        </div>
                        <h3 class="text-lg font-bold text-[var(--text-primary)]">
                            Delete Account
                        </h3>
                    </div>

                    <p class="text-sm text-[var(--text-secondary)]">
                        Are you sure you want to delete your account? This action
                        cannot be undone. Enter your password to confirm.
                    </p>

                    <form @submit.prevent="deleteUser" class="mt-4">
                        <label :class="labelClass">Password</label>
                        <div class="relative">
                            <input
                                ref="passwordInput"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                :class="inputClass"
                                class="pr-11"
                                placeholder="Password"
                                autocomplete="current-password"
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-[var(--text-muted)] transition-colors duration-150 hover:text-[var(--text-primary)]"
                            >
                                <EyeSlashIcon v-if="showPassword" class="h-4 w-4" />
                                <EyeIcon v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" :class="errorClass">{{ form.errors.password }}</p>

                        <div class="mt-5 flex items-center justify-end gap-3">
                            <button
                                type="button"
                                @click="closeModal"
                                class="rounded-full border border-[var(--card-border)] px-6 py-2.5 text-sm font-semibold text-[var(--text-secondary)] transition-colors duration-150 hover:bg-[var(--page-bg)] hover:text-[var(--text-primary)]"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-full bg-rose-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-rose-600/20 transition-all duration-200 hover:scale-[1.02] hover:bg-rose-500 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:scale-100"
                            >
                                {{ form.processing ? 'Deleting...' : 'Delete Account' }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </Transition>

    </section>
</template>