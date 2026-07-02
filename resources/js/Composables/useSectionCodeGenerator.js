/**
 * Client-side mirror of the generation rules in
 * app/Http/Controllers/SectionController.php and
 * app/Services/SectionCodeService.php.
 *
 * This ONLY drives the live "Generated Section Code" / "Section Name"
 * previews and frontend UX (disabling taken letters, etc.) on the
 * Create/Edit forms — the server always re-generates and validates the
 * real values on submit, so this never needs to be perfectly
 * authoritative, just consistent with the backend. Keep both in sync if
 * the generation rules ever change.
 */

// The only Program code (today) that requires a Specialization before a
// Section Code/Name can be generated. See SPECIALIZED_PROGRAM_CODE in
// SectionCodeService.php.
const SPECIALIZED_PROGRAM_CODE = 'BSCRIM'

// Five sections max per Program + Year Level (+ Specialization). See
// SectionController::ALLOWED_LETTERS.
export const SECTION_LETTERS = ['A', 'B', 'C', 'D', 'E']

export function requiresSpecialization(program) {
    return !!program && program.code?.toUpperCase() === SPECIALIZED_PROGRAM_CODE
}

/**
 * Build the Section Code preview string.
 *
 * Returns null when there isn't enough information yet (missing
 * Program, Year Level, Letter, or — for BSCRIM — Specialization).
 */
export function generateSectionCode({ program, specialization, yearLevel, letter }) {
    if (!program || !yearLevel || !letter) {
        return null
    }

    const upperLetter = letter.toString().toUpperCase()

    if (requiresSpecialization(program)) {
        if (!specialization || !specialization.code) {
            return null
        }

        return `${program.code.toUpperCase()}-${specialization.code.toUpperCase()}-${yearLevel}${upperLetter}`
    }

    return `${program.code.toUpperCase()}-${yearLevel}${upperLetter}`
}

/**
 * "Bachelor of Science in Information Technology" -> "BS Information Technology"
 * "Bachelor of Secondary Education" -> "BS Secondary Education"
 * "Bachelor of Science in Criminology" -> "BS Criminology"
 *
 * Generic on purpose — every program name in the system follows one of
 * these "Bachelor of ..." shapes, so a single regex strip covers all of
 * them without a per-program lookup table.
 */
function abbreviateProgramName(program) {
    if (!program?.name) {
        return program?.code ?? ''
    }

    return 'BS ' + program.name.replace(/^Bachelor of (Science in )?/i, '').trim()
}

/**
 * Clean, short display names for the four BSCRIM specializations, used
 * only for the Section Name preview (e.g. "BS Criminology (Lie
 * Detection) - 4A"). Deliberately not the same string as
 * specialization.name, which may carry a longer qualifier (e.g. "Lie
 * Detection (Polygraph)") — the Section Name examples call for the
 * short form specifically. Falls back to specialization.name for any
 * BSCRIM specialization added later that isn't in this list yet.
 */
const CRIM_SPECIALIZATION_SHORT_NAMES = {
    FI: 'Fingerprint Identification',
    FB: 'Firearms Identification',
    LD: 'Lie Detection',
    QD: 'Questioned Documents Examination',
}

function specializationDisplayName(specialization) {
    const short = CRIM_SPECIALIZATION_SHORT_NAMES[specialization?.code?.toUpperCase()]
    return short ?? specialization?.name ?? ''
}

/**
 * Build the Section Name preview string, e.g.:
 *   "BS Information Technology - 1A"
 *   "BS Criminology (Lie Detection) - 4A"
 *
 * Returns '' when there isn't enough information yet — callers should
 * treat that the same as generateSectionCode()'s null.
 */
export function generateSectionName({ program, specialization, yearLevel, letter }) {
    if (!program || !yearLevel || !letter) {
        return ''
    }

    const suffix = `${yearLevel}${letter.toString().toUpperCase()}`
    const base = abbreviateProgramName(program)

    if (requiresSpecialization(program)) {
        if (!specialization) {
            return ''
        }

        return `${base} (${specializationDisplayName(specialization)}) - ${suffix}`
    }

    return `${base} - ${suffix}`
}