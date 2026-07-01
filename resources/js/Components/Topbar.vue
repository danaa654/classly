<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const emit = defineEmits(['toggle'])

const page = usePage()

const user = computed(() => page.props.auth?.user)

const role = computed(() => {
    return user.value?.roles?.length
        ? user.value.roles[0]
        : 'User'
})
</script>

<template>
    <div class="bg-white shadow px-5 py-4 flex justify-between items-center">

        <button
            class="text-2xl"
            @click="emit('toggle')"
        >
            ☰
        </button>

        <div class="flex items-center gap-4">

            <span class="font-medium">
                Welcome {{ role }}
            </span>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
            >
                Logout
            </Link>

        </div>

    </div>
</template>