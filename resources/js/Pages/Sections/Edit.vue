<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import {
    requiresSpecialization,
    generateSectionCode,
    generateSectionName,
    SECTION_LETTERS,
} from '@/Composables/useSectionCodeGenerator'

const props = defineProps({
    section: Object,
    programs: Array,
    // { "programId_specializationId_yearLevel": ["A","B",...] }
    // Already excludes this section itself — see SectionController::edit().
    usedLetters: Object,
})

const form = useForm({
    program_id: props.section.curriculum?.program?.id ?? '',
    specialization_id: props.section.curriculum?.specialization?.id ?? '',
    year_level: props.section.year_level ?? '',
    section_letter: props.section.section_letter ?? '',
    section_name: props.section.section_name,
    capacity: props.section.capacity,
    status: props.section.status,
})

// True for legacy sections created before this refactor, whose
// section_code didn't match the "<digits><letter>" pattern the backfill
// migration looks for. year_level/section_letter are left blank for
// these — the user just needs to pick both once, same as a new section,
// and saving will normalize the code going forward.
const needsNormalization = !props.section.year_level || !props.section.section_letter

const selectedProgram = computed(() =>
    props.programs.find(program => program.id === form.program_id) ?? null
)

const isBscrim = computed(() => requiresSpecialization(selectedProgram.value))

const specializationOptions = computed(() =>
    selectedProgram.value?.specializations ?? []
)

const selectedSpecialization = computed(() =>
    specializationOptions.value.find(spec => spec.id === form.specialization_id) ?? null
)

const yearLevelOptions = computed(() => {
    const years = selectedProgram.value?.years ?? 0
    return Array.from({ length: years }, (_, i) => i + 1)
})

const scopeReady = computed(() =>
    !!selectedProgram.value
    && !!form.year_level
    && (!isBscrim.value || !!form.specialization_id)
)

const scopeKey = computed(() =>
    `${form.program_id}_${form.specialization_id || 'null'}_${form.year_level}`
)

const usedLettersForScope = computed(() =>
    scopeReady.value ? (props.usedLetters[scopeKey.value] ?? []) : []
)

const availableLetters = computed(() =>
    SECTION_LETTERS.filter(letter => !usedLettersForScope.value.includes(letter))
)

const scopeFull = computed(() =>
    scopeReady.value && availableLetters.value.length === 0
)

const generatedCode = computed(() => generateSectionCode({
    program: selectedProgram.value,
    specialization: selectedSpecialization.value,
    yearLevel: form.year_level,
    letter: form.section_letter,
}))

const generatedName = computed(() => generateSectionName({
    program: selectedProgram.value,
    specialization: selectedSpecialization.value,
    yearLevel: form.year_level,
    letter: form.section_letter,
}))

// Starts "touched" only if the stored name already differs from what
// auto-generation would produce right now — i.e. an intentionally
// custom name is left alone, but a name that already matches the
// pattern stays in sync as the user keeps editing.
let nameTouched = !!generatedName.value && form.section_name !== generatedName.value

// Reset fields that no longer apply whenever Program changes.
watch(() => form.program_id, () => {
    form.specialization_id = ''

    if (form.year_level && form.year_level > yearLevelOptions.value.length) {
        form.year_level = ''
    }
})

// Auto-select the next available letter whenever the scope changes —
// keeps the current letter if it's still valid for the new scope,
// otherwise picks the first free one (or clears it if the scope is full).
watch([() => form.program_id, () => form.specialization_id, () => form.year_level], () => {
    if (!scopeReady.value) {
        return
    }

    if (form.section_letter && availableLetters.value.includes(form.section_letter)) {
        return
    }

    form.section_letter = availableLetters.value[0] ?? ''
})

// Keep Section Name in sync with the generated preview until the user
// types something of their own into it.
watch(generatedName, (value) => {
    if (!nameTouched && value) {
        form.section_name = value
    }
})

function onSectionNameInput() {
    nameTouched = true
}

function useAutoName() {
    nameTouched = false
    form.section_name = generatedName.value
}

function clampCapacity() {
    if (form.capacity === '' || form.capacity === null) {
        return
    }

    const value = Number(form.capacity)

    if (Number.isNaN(value)) {
        return
    }

    form.capacity = Math.min(45, Math.max(20, value))
}

function submit() {
    if (scopeFull.value) {
        return
    }

    form.put(route('sections.update', props.section.id))
}
</script>

<template>
    <DashboardLayout>

        <h1 class="text-3xl font-bold mb-6">
            Edit Section
        </h1>

        <div class="bg-white rounded-lg shadow p-6 max-w-2xl">

            <p
                v-if="needsNormalization"
                class="mb-4 text-sm bg-amber-50 border border-amber-200 text-amber-800 rounded p-3"
            >
                This section was created before automatic Section Codes.
                Please select its Year Level and Section Letter below —
                saving will generate a proper code for it.
            </p>

            <form @submit.prevent="submit">

                <div class="mb-4">
                    <label class="block font-medium mb-1">
                        Program
                    </label>

                    <select
                        v-model="form.program_id"
                        class="w-full border rounded p-2"
                    >
                        <option value="" disabled>
                            Select a program
                        </option>
                        <option
                            v-for="program in programs"
                            :key="program.id"
                            :value="program.id"
                        >
                            {{ program.code }} - {{ program.name }}
                        </option>
                    </select>

                    <p v-if="form.errors.program_id" class="text-red-500 text-sm mt-1">
                        {{ form.errors.program_id }}
                    </p>
                </div>

                <div v-if="isBscrim" class="mb-4">
                    <label class="block font-medium mb-1">
                        Specialization
                    </label>

                    <select
                        v-model="form.specialization_id"
                        class="w-full border rounded p-2"
                    >
                        <option value="" disabled>
                            Select a specialization
                        </option>
                        <option
                            v-for="specialization in specializationOptions"
                            :key="specialization.id"
                            :value="specialization.id"
                        >
                            {{ specialization.code }} - {{ specialization.name }}
                        </option>
                    </select>

                    <p v-if="form.errors.specialization_id" class="text-red-500 text-sm mt-1">
                        {{ form.errors.specialization_id }}
                    </p>
                </div>

                <div class="mb-4 grid grid-cols-2 gap-4">

                    <div>
                        <label class="block font-medium mb-1">
                            Year Level
                        </label>

                        <select
                            v-model="form.year_level"
                            :disabled="!selectedProgram"
                            class="w-full border rounded p-2 disabled:bg-gray-100"
                        >
                            <option value="" disabled>
                                Select year level
                            </option>
                            <option
                                v-for="year in yearLevelOptions"
                                :key="year"
                                :value="year"
                            >
                                Year {{ year }}
                            </option>
                        </select>

                        <p v-if="form.errors.year_level" class="text-red-500 text-sm mt-1">
                            {{ form.errors.year_level }}
                        </p>
                    </div>

                    <div>
                        <label class="block font-medium mb-1">
                            Section Letter
                        </label>

                        <select
                            v-model="form.section_letter"
                            :disabled="!scopeReady"
                            class="w-full border rounded p-2 disabled:bg-gray-100"
                        >
                            <option value="" disabled>
                                Select letter
                            </option>
                            <option
                                v-for="letter in SECTION_LETTERS"
                                :key="letter"
                                :value="letter"
                                :disabled="usedLettersForScope.includes(letter)"
                            >
                                {{ letter }}{{ usedLettersForScope.includes(letter) ? ' (Taken)' : '' }}
                            </option>
                        </select>

                        <p v-if="form.errors.section_letter" class="text-red-500 text-sm mt-1">
                            {{ form.errors.section_letter }}
                        </p>
                    </div>

                </div>

                <p
                    v-if="scopeFull"
                    class="mb-4 text-sm bg-amber-50 border border-amber-200 text-amber-800 rounded p-3"
                >
                    All available sections (A–E) have already been created for this year level.
                </p>

                <div class="mb-4">
                    <label class="block font-medium mb-1">
                        Generated Section Code
                    </label>

                    <div class="w-full border rounded p-2 bg-gray-50 font-mono text-gray-700">
                        {{ generatedCode ?? section.section_code }}
                    </div>
                </div>

                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block font-medium">
                            Section Name
                        </label>

                        <button
                            v-if="generatedName"
                            type="button"
                            @click="useAutoName"
                            class="text-sm text-blue-600 hover:underline"
                        >
                            Use auto-generated name
                        </button>
                    </div>

                    <input
                        v-model="form.section_name"
                        @input="onSectionNameInput"
                        type="text"
                        placeholder="e.g. BS Information Technology - 1A"
                        class="w-full border rounded p-2"
                    >

                    <p v-if="form.errors.section_name" class="text-red-500 text-sm mt-1">
                        {{ form.errors.section_name }}
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1">
                        Capacity
                    </label>

                    <input
                        v-model="form.capacity"
                        @blur="clampCapacity"
                        type="number"
                        min="20"
                        max="45"
                        class="w-full border rounded p-2"
                    >

                    <p class="text-gray-500 text-sm mt-1">
                        Must be between 20 and 45 students.
                    </p>

                    <p v-if="form.errors.capacity" class="text-red-500 text-sm mt-1">
                        {{ form.errors.capacity }}
                    </p>
                </div>

                <div class="mb-6">
                    <label class="block font-medium mb-1">
                        Status
                    </label>

                    <select
                        v-model="form.status"
                        class="w-full border rounded p-2"
                    >
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>

                    <p v-if="form.errors.status" class="text-red-500 text-sm mt-1">
                        {{ form.errors.status }}
                    </p>
                </div>

                <div class="flex justify-end gap-2">

                    <Link
                        :href="route('sections.index')"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2 rounded"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing || scopeFull"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-2 rounded disabled:opacity-50"
                    >
                        Update Section
                    </button>

                </div>

            </form>

        </div>

    </DashboardLayout>
</template>