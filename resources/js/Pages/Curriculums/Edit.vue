<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'

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

const filteredSpecializations = computed(() => {
    return props.specializations.filter(
        s => s.program_id == form.program_id
    )
})

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

    const program = props.programs.find(
        p => p.id == form.program_id
    )

    if (!program) return

    let code = program.code

    let name = program.name

    if (form.specialization_id) {

        const specialization = props.specializations.find(
            s => s.id == form.specialization_id
        )

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

function submit() {
    form.put(route('curriculums.update', props.curriculum.id))
}
</script>

<template>

<DashboardLayout>

<div class="max-w-3xl">

<h1 class="text-3xl font-bold mb-6">
    Edit Curriculum
</h1>

<form
    @submit.prevent="submit"
    class="bg-white rounded-lg shadow p-6 space-y-5"
>

<div>

<label class="block mb-2">
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

<p
    v-if="form.errors.program_id"
    class="text-red-500 text-sm mt-1"
>
{{ form.errors.program_id }}
</p>

</div>

<div>

<label class="block mb-2">
Specialization
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

</div>

<div class="grid grid-cols-2 gap-4">

<div>

<label class="block mb-2">
Effective Year
</label>

<input
    type="number"
    v-model="form.effective_year"
    class="w-full border rounded p-2"
/>

</div>

<div>

<label class="block mb-2">
Academic Year
</label>

<input
    readonly
    v-model="form.academic_year"
    class="w-full border rounded p-2 bg-gray-100"
/>

</div>

</div>

<div>

<label class="block mb-2">
Curriculum Code
</label>

<input
    readonly
    v-model="form.code"
    class="w-full border rounded p-2 bg-gray-100"
/>

</div>

<div>

<label class="block mb-2">
Curriculum Name
</label>

<input
    readonly
    v-model="form.name"
    class="w-full border rounded p-2 bg-gray-100"
/>

</div>

<div>

<label class="block mb-2">
Status
</label>

<select
    v-model="form.active"
    class="w-full border rounded p-2"
>
<option :value="true">Active</option>
<option :value="false">Inactive</option>
</select>

</div>

<div class="flex gap-3">

<button
    type="submit"
    :disabled="form.processing"
    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded"
>
{{ form.processing ? 'Updating...' : 'Update Curriculum' }}
</button>

<a
    :href="route('curriculums.index')"
    class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded"
>
Cancel
</a>

</div>

</form>

</div>

</DashboardLayout>

</template>