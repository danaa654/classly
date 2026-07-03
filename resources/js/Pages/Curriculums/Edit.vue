<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Toast from '@/Components/Toast.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { computed, watch, ref } from 'vue'
import { useFlashToast } from '@/Composables/useFlashToast'

const props = defineProps({
    curriculum: Object,
    programs: Array,
    specializations: Array,
})

const form = useForm({
    program_id: props.curriculum.program_id,
    specialization_id: props.curriculum.specialization_id,
    code: props.curriculum.code,
    name: props.curriculum.name,
    academic_year: props.curriculum.academic_year,
    effective_year: props.curriculum.effective_year,
    active: props.curriculum.active,
})

const { toast } = useFlashToast()

const showConfirm = ref(false)

const filteredSpecializations = computed(() => {
    return props.specializations.filter(
        s => s.program_id == form.program_id
    )
})

const selectedProgram = computed(() =>
    props.programs.find(p => p.id == form.program_id) || null
)

const selectedSpecialization = computed(() =>
    filteredSpecializations.value.find(s => s.id == form.specialization_id) || null
)

watch(() => form.program_id, () => {
    form.specialization_id = ''
    generateFields()
})

watch(() => form.specialization_id, generateFields)

watch(() => form.effective_year, () => {
    if (form.effective_year) {
        form.academic_year =
            form.effective_year + '-' + (Number(form.effective_year) + 1)

        generateFields()
    }
})

function generateFields() {
    const program = selectedProgram.value

    if (!program) return

    let code = program.code
    let name = program.name

    if (form.specialization_id) {
        const specialization = selectedSpecialization.value

        if (specialization) {
            code += '-' + specialization.code
            name += ' - ' + specialization.name
        }
    }

    code += '-' + form.effective_year
    name += ' Curriculum ' + form.effective_year

    form.code = code.toUpperCase()
    form.name = name
}

function openConfirm() {
    showConfirm.value = true
}

function confirmSave() {
    showConfirm.value = false
    form.put(route('curriculums.update', props.curriculum.id))
}
</script>

<template>
    <DashboardLayout>

        <Toast :toast="toast" />

        <h1 class="text-3xl font-bold mb-6">
            Edit Curriculum
        </h1>

        <div class="bg-white rounded-lg shadow p-6 max-w-3xl">

            <form @submit.prevent="openConfirm">

                <!-- Program & Specialization -->
                <div class="mb-6">

                    <h2 class="text-lg font-semibold mb-3">
                        Program & Specialization
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block font-medium mb-1">
                                Program
                            </label>

                            <select
                                v-model="form.program_id"
                                class="w-full border rounded p-2"
                            >
                                <option value="">
                                    Select Program
                                </option>

                                <option
                                    v-for="program in programs"
                                    :key="program.id"
                                    :value="program.id"
                                >
                                    {{ program.department.abbreviation }} - {{ program.code }}
                                </option>

                            </select>

                            <p v-if="form.errors.program_id" class="text-red-500 text-sm mt-1">
                                {{ form.errors.program_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block font-medium mb-1">
                                Specialization (Optional)
                            </label>

                            <select
                                v-model="form.specialization_id"
                                class="w-full border rounded p-2"
                            >
                                <option value="">
                                    None
                                </option>

                                <option
                                    v-for="specialization in filteredSpecializations"
                                    :key="specialization.id"
                                    :value="specialization.id"
                                >
                                    {{ specialization.name }}
                                </option>

                            </select>

                            <p v-if="form.errors.specialization_id" class="text-red-500 text-sm mt-1">
                                {{ form.errors.specialization_id }}
                            </p>
                        </div>

                    </div>

                </div>

                <!-- Academic Period -->
                <div class="mb-6">

                    <h2 class="text-lg font-semibold mb-3">
                        Academic Period
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block font-medium mb-1">
                                Effective Year
                            </label>

                            <input
                                v-model.number="form.effective_year"
                                type="number"
                                min="2020"
                                max="2099"
                                class="w-full border rounded p-2"
                            >

                            <p v-if="form.errors.effective_year" class="text-red-500 text-sm mt-1">
                                {{ form.errors.effective_year }}
                            </p>
                        </div>

                        <div>
                            <label class="block font-medium mb-1">
                                Academic Year
                            </label>

                            <div class="w-full border rounded p-2 bg-gray-50 text-gray-700">
                                {{ form.academic_year || '—' }}
                            </div>

                            <p class="text-gray-500 text-xs mt-1">
                                Auto-generated from Effective Year
                            </p>
                        </div>

                    </div>

                </div>

                <!-- Generated Fields -->
                <div class="mb-6">

                    <h2 class="text-lg font-semibold mb-3">
                        Generated Information
                    </h2>

                    <div class="space-y-4">

                        <div>
                            <label class="block font-medium mb-1">
                                Curriculum Code
                            </label>

                            <div class="w-full border rounded p-2 bg-gray-50 font-mono text-gray-700">
                                {{ form.code || 'Complete fields above to generate' }}
                            </div>

                            <p class="text-gray-500 text-xs mt-1">
                                Auto-generated from Program, Specialization, and Effective Year
                            </p>
                        </div>

                        <div>
                            <label class="block font-medium mb-1">
                                Curriculum Name
                            </label>

                            <div class="w-full border rounded p-2 bg-gray-50 text-gray-700">
                                {{ form.name || 'Complete fields above to generate' }}
                            </div>

                            <p class="text-gray-500 text-xs mt-1">
                                Auto-generated from Program, Specialization, and Effective Year
                            </p>
                        </div>

                    </div>

                </div>

                <!-- Status -->
                <div class="mb-6">

                    <h2 class="text-lg font-semibold mb-3">
                        Status
                    </h2>

                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 border rounded px-3 py-2 cursor-pointer">
                            <input
                                v-model="form.active"
                                type="checkbox"
                            >
                            <span class="font-medium">Active</span>
                        </label>

                        <p class="text-gray-500 text-sm">
                            Inactive curriculums will not be available for new sections.
                        </p>
                    </div>

                    <p v-if="form.errors.active" class="text-red-500 text-sm mt-1">
                        {{ form.errors.active }}
                    </p>
                </div>

                <div class="flex justify-end gap-2">

                    <Link
                        :href="route('curriculums.index')"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2 rounded"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded disabled:opacity-50"
                    >
                        Update Curriculum
                    </button>

                </div>

            </form>

        </div>

        <!-- Review Confirmation Modal -->
        <div
            v-if="showConfirm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">

                <h3 class="text-lg font-semibold mb-1">
                    Review Curriculum
                </h3>

                <p class="text-gray-500 text-sm mb-4">
                    Please review the curriculum details before saving.
                </p>

                <dl class="space-y-2 text-sm mb-6">

                    <div class="flex justify-between border-b pb-2">
                        <dt class="text-gray-500">Curriculum Code</dt>
                        <dd class="font-mono font-medium">{{ form.code }}</dd>
                    </div>

                    <div class="flex justify-between border-b pb-2">
                        <dt class="text-gray-500">Curriculum Name</dt>
                        <dd class="font-medium">{{ form.name }}</dd>
                    </div>

                    <div class="flex justify-between border-b pb-2">
                        <dt class="text-gray-500">Program</dt>
                        <dd class="font-medium">{{ selectedProgram?.code }}</dd>
                    </div>

                    <div class="flex justify-between border-b pb-2">
                        <dt class="text-gray-500">Academic Year</dt>
                        <dd class="font-medium">{{ form.academic_year }}</dd>
                    </div>

                    <div class="flex justify-between border-b pb-2">
                        <dt class="text-gray-500">Effective Year</dt>
                        <dd class="font-medium">{{ form.effective_year }}</dd>
                    </div>

                    <div class="flex justify-between">
                        <dt class="text-gray-500">Status</dt>
                        <dd class="font-medium">{{ form.active ? 'Active' : 'Inactive' }}</dd>
                    </div>

                </dl>

                <div class="flex justify-end gap-2">

                    <button
                        type="button"
                        @click="showConfirm = false"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="confirmSave"
                        :disabled="form.processing"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded disabled:opacity-50"
                    >
                        Confirm Save
                    </button>

                </div>

            </div>
        </div>

    </DashboardLayout>
</template>