import { computed } from 'vue'

export const SEMESTERS = [
    { value: 1, label: '1st Semester' },
    { value: 2, label: '2nd Semester' },
    { value: 3, label: 'Summer' },
]

export const DAYS = [
    { key: 'monday', label: 'Monday' },
    { key: 'tuesday', label: 'Tuesday' },
    { key: 'wednesday', label: 'Wednesday' },
    { key: 'thursday', label: 'Thursday' },
    { key: 'friday', label: 'Friday' },
    { key: 'saturday', label: 'Saturday' },
    { key: 'sunday', label: 'Sunday' },
]

/**
 * Shared reactive logic for the Academic Term Create/Edit forms.
 *
 * IMPORTANT: Class Start / Class End are restricted to the selected
 * Academic Year ONLY — Jan 1 of Start Year through Dec 31 of
 * (Start Year + 1). There is deliberately no per-semester month
 * restriction here, because schools change their calendars. Server-side
 * validation mirrors this exactly in AcademicTerm::academicYearDateRange()
 * — keep both in sync if this assumption ever changes.
 */
export function useAcademicTermForm(form) {

    function parsedStartYear() {
        const raw = String(form.start_year ?? '')
        const year = parseInt(raw, 10)

        return raw.length === 4 && Number.isInteger(year) ? year : null
    }

    const academicYearPreview = computed(() => {
        const year = parsedStartYear()

        return year ? `${year}-${year + 1}` : null
    })

    const semesterLabel = computed(() => {
        return SEMESTERS.find(s => s.value === Number(form.semester))?.label ?? null
    })

    const dateRange = computed(() => {
        const year = parsedStartYear()

        if (! year) {
            return { min: null, max: null }
        }

        return {
            min: `${year}-01-01`,
            max: `${year + 1}-12-31`,
        }
    })

    const classDatesInvalid = computed(() => {
        return !!(
            form.class_start_date &&
            form.class_end_date &&
            form.class_end_date < form.class_start_date
        )
    })

    const schoolHoursInvalid = computed(() => {
        return !!(
            form.school_start_time &&
            form.school_end_time &&
            form.school_end_time <= form.school_start_time
        )
    })

    const lunchIncomplete = computed(() => {
        return (!!form.lunch_start_time && !form.lunch_end_time) ||
               (!form.lunch_start_time && !!form.lunch_end_time)
    })

    const lunchOrderInvalid = computed(() => {
        return !!(
            form.lunch_start_time &&
            form.lunch_end_time &&
            form.lunch_end_time <= form.lunch_start_time
        )
    })

    const lunchOutsideSchoolHours = computed(() => {
        if (! form.lunch_start_time || ! form.lunch_end_time || ! form.school_start_time || ! form.school_end_time) {
            return false
        }

        return form.lunch_start_time < form.school_start_time ||
               form.lunch_end_time > form.school_end_time
    })

    /**
     * Only a Published term may be set Active — mirrors the server-side
     * rule in AcademicTermRequest so the user sees the problem before
     * ever hitting submit.
     */
    const activeRequiresPublished = computed(() => {
        return !!form.active && form.status !== 'Published'
    })

    /**
     * Sanitizes the Start Year input (digits only, max 4 chars) and clears
     * Class Start / Class End if they fall outside the newly-typed year's
     * range — prevents stale dates from a previously-typed year silently
     * sticking around after Start Year changes.
     */
    function onStartYearInput(event) {
        const digitsOnly = event.target.value.replace(/\D/g, '').slice(0, 4)
        form.start_year = digitsOnly

        const year = digitsOnly.length === 4 ? parseInt(digitsOnly, 10) : null

        if (! year) {
            return
        }

        const min = `${year}-01-01`
        const max = `${year + 1}-12-31`

        if (form.class_start_date && (form.class_start_date < min || form.class_start_date > max)) {
            form.class_start_date = ''
        }

        if (form.class_end_date && (form.class_end_date < min || form.class_end_date > max)) {
            form.class_end_date = ''
        }
    }

    return {
        academicYearPreview,
        semesterLabel,
        dateRange,
        classDatesInvalid,
        schoolHoursInvalid,
        lunchIncomplete,
        lunchOrderInvalid,
        lunchOutsideSchoolHours,
        activeRequiresPublished,
        onStartYearInput,
    }
}