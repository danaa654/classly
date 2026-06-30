<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { watch } from 'vue'

const props = defineProps({
    departments: Array,
})

const form = useForm({

    first_name: '',
    middle_name: '',
    last_name: '',
    suffix: '',

    gender: '',

    email: '',
    contact_number: '',

    department_id: '',

    employment_type: 'Full-Time',

    max_units: 24,

    teaching_qualification: 'Major',

    status: true,

})

watch(() => form.employment_type, (value) => {

    if (value === 'Full-Time') {
        form.max_units = 24
    }

})
</script>

<template>

<DashboardLayout>

<div class="max-w-5xl">

<h1 class="text-3xl font-bold mb-6">
Add Faculty
</h1>

<form
    @submit.prevent="form.post(route('faculty.store'))"
    class="bg-white rounded-lg shadow p-6 space-y-6"
>

<!-- PERSONAL -->

<div class="grid grid-cols-2 gap-4">

<div>

<label class="block mb-2">First Name</label>

<input
v-model="form.first_name"
class="w-full border rounded p-2"
/>

<p class="text-red-500 text-sm">
{{ form.errors.first_name }}
</p>

</div>

<div>

<label class="block mb-2">Middle Name</label>

<input
v-model="form.middle_name"
class="w-full border rounded p-2"
/>

</div>

<div>

<label class="block mb-2">Last Name</label>

<input
v-model="form.last_name"
class="w-full border rounded p-2"
/>

<p class="text-red-500 text-sm">
{{ form.errors.last_name }}
</p>

</div>

<div>

<label class="block mb-2">Suffix</label>

<input
v-model="form.suffix"
class="w-full border rounded p-2"
/>

</div>

</div>

<!-- GENDER -->

<div>

<label class="block mb-2">
Gender
</label>

<select
v-model="form.gender"
class="w-full border rounded p-2"
>

<option value="">
Select Gender
</option>

<option value="Male">
Male
</option>

<option value="Female">
Female
</option>

</select>

</div>

<!-- CONTACT -->

<div class="grid grid-cols-2 gap-4">

<div>

<label class="block mb-2">
Email
</label>

<input
v-model="form.email"
type="email"
class="w-full border rounded p-2"
/>

<p class="text-red-500 text-sm">
{{ form.errors.email }}
</p>

</div>

<div>

<label class="block mb-2">
Contact Number
</label>

<input
v-model="form.contact_number"
class="w-full border rounded p-2"
/>

</div>

</div>

<!-- HOME COLLEGE -->

<div>

<label class="block mb-2">
Home College
</label>

<select
v-model="form.department_id"
class="w-full border rounded p-2"
>

<option value="">
General Education Faculty
</option>

<option
v-for="department in departments"
:key="department.id"
:value="department.id"
>

{{ department.name }}

</option>

</select>

</div>

<!-- EMPLOYMENT -->

<div class="grid grid-cols-2 gap-4">

<div>

<label class="block mb-2">
Employment Type
</label>

<select
v-model="form.employment_type"
class="w-full border rounded p-2"
>

<option value="Full-Time">
Full-Time
</option>

<option value="Part-Time">
Part-Time
</option>

</select>

</div>

<div>

<label class="block mb-2">
Maximum Units
</label>

<input
v-model="form.max_units"
type="number"
min="1"
max="24"
:readonly="form.employment_type==='Full-Time'"
class="w-full border rounded p-2 bg-gray-50"
/>

</div>

</div>

<!-- QUALIFICATION -->

<div>

<label class="block mb-2">
Teaching Qualification
</label>

<select
v-model="form.teaching_qualification"
class="w-full border rounded p-2"
>

<option value="Major">
Major
</option>

<option value="Minor">
Minor
</option>

<option value="Both">
Both
</option>

</select>

</div>

<!-- STATUS -->

<div>

<label class="block mb-2">
Status
</label>

<select
v-model="form.status"
class="w-full border rounded p-2"
>

<option :value="true">
Active
</option>

<option :value="false">
Inactive
</option>

</select>

</div>

<div class="flex gap-3">

<button
type="submit"
:disabled="form.processing"
class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded"
>

Save Faculty

</button>

</div>

</form>

</div>

</DashboardLayout>

</template>