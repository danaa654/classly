<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import NavItem from '@/Components/Sidebar/NavItem.vue'
import NavGroup from '@/Components/Sidebar/NavGroup.vue'
import NavSectionLabel from '@/Components/Sidebar/NavSectionLabel.vue'

const page = usePage()

const user = computed(() => page.props.auth?.user)
const role = computed(() => user.value?.roles?.[0] ?? '')

function hasRole(...roles) {
    return roles.includes(role.value)
}

// Same role groupings as the original sidebar, unchanged.
const canRegistrar = computed(() => hasRole('Admin', 'Registrar'))
const canResourceRoles = computed(() =>
    hasRole('Admin', 'Registrar', 'Dean', 'Assistant Dean', 'OIC')
)
const isAdmin = computed(() => hasRole('Admin'))
</script>

<template>
    <div class="w-64 bg-slate-900 text-white min-h-screen flex flex-col">
        <!-- Logo -->
        <div class="p-5 border-b border-slate-700">
            <h1 class="text-3xl font-bold">CLASSLY</h1>
            <p class="text-sm text-slate-400 mt-2">{{ role }}</p>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 py-2 overflow-y-auto">
            <!-- Dashboard -->
            <div class="pt-2">
                <NavItem label="Dashboard" routeName="dashboard">
                    <template #icon>
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1.5" />
                            <rect x="14" y="3" width="7" height="7" rx="1.5" />
                            <rect x="3" y="14" width="7" height="7" rx="1.5" />
                            <rect x="14" y="14" width="7" height="7" rx="1.5" />
                        </svg>
                    </template>
                </NavItem>
            </div>

            <!-- ACADEMIC STRUCTURE -->
            <template v-if="canRegistrar">
                <NavSectionLabel label="Academic Structure" />

                <!--
                    Colleges: the parent label itself links to departments.index
                    (via parentRoute) AND the chevron independently expands the
                    sub-list. No "Colleges" child here, per spec — the parent
                    IS the Colleges link.
                -->
                <NavGroup
                    label="Colleges"
                    parent-route="departments.index"
                    :children="[
                        { label: 'Programs', routeName: 'programs.index' },
                        { label: 'Specializations', routeName: 'specializations.index' },
                    ]"
                >
                    <template #icon>
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="3" width="16" height="18" rx="1" />
                            <line x1="8" y1="7" x2="8" y2="7.01" />
                            <line x1="12" y1="7" x2="12" y2="7.01" />
                            <line x1="16" y1="7" x2="16" y2="7.01" />
                            <line x1="8" y1="11" x2="8" y2="11.01" />
                            <line x1="12" y1="11" x2="12" y2="11.01" />
                            <line x1="16" y1="11" x2="16" y2="11.01" />
                            <line x1="9" y1="21" x2="9" y2="15" />
                            <line x1="15" y1="21" x2="15" y2="15" />
                        </svg>
                    </template>
                </NavGroup>

                <!--
                    Curriculum: pure toggle (no parentRoute — there's no
                    standalone "Curriculum" landing page). "Subjects" isn't
                    in the requested hierarchy but is a real existing route
                    (subjects.index); nested here rather than dropped so no
                    working page disappears from navigation. Move freely if
                    you'd rather it live elsewhere.
                -->
                <NavGroup
                    label="Curriculum"
                    :children="[
                        { label: 'Curricula', routeName: 'curriculums.index' },
                        { label: 'Curriculum Items', routeName: 'curriculum-items.index' },
                        { label: 'Subjects', routeName: 'subjects.index' },
                    ]"
                >
                    <template #icon>
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 5.5C4 4.67 4.67 4 5.5 4H12v16H5.5A1.5 1.5 0 014 18.5v-13z" />
                            <path d="M20 5.5c0-.83-.67-1.5-1.5-1.5H12v16h6.5a1.5 1.5 0 001.5-1.5v-13z" />
                        </svg>
                    </template>
                </NavGroup>
            </template>

            <!-- RESOURCES -->
            <template v-if="canResourceRoles">
                <NavSectionLabel label="Resources" />

                <!--
                    Faculty: intentionally lists "Faculty" as its own child
                    alongside "Qualifications", per spec.
                -->
                <NavGroup
                    label="Faculty"
                    :children="[
                        { label: 'Faculty', routeName: 'faculty.index' },
                        { label: 'Qualifications', routeName: 'faculty-subjects.index' },
                    ]"
                >
                    <template #icon>
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="8" r="3" />
                            <path d="M3 20c0-3.5 2.7-6 6-6s6 2.5 6 6" />
                            <circle cx="17" cy="9" r="2.5" />
                            <path d="M15.5 14.2c2.4.4 4.5 2.4 4.5 5.8" />
                        </svg>
                    </template>
                </NavGroup>

                <NavGroup
                    label="Facilities"
                    :children="[
                        { label: 'Rooms', routeName: 'rooms.index' },
                    ]"
                >
                    <template #icon>
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 21V9l8-5 8 5v12" />
                            <path d="M9 21v-7h6v7" />
                        </svg>
                    </template>
                </NavGroup>
            </template>

            <!-- OPERATIONS -->
            <template v-if="canRegistrar || canResourceRoles">
                <NavSectionLabel label="Operations" />

                <NavItem v-if="canRegistrar" label="Sections" routeName="sections.index">
                    <template #icon>
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="4" rx="1" />
                            <rect x="3" y="10" width="18" height="4" rx="1" />
                            <rect x="3" y="16" width="18" height="4" rx="1" />
                        </svg>
                    </template>
                </NavItem>

                <!--
                    Schedule: left as a disabled placeholder, matching the
                    uploaded source. If schedule.index (or similar) already
                    exists as a working route in your app, swap
                    `href="#" disabled` for `routeName="your.route.name"`
                    to make it a real link.
                -->
                <NavItem v-if="canResourceRoles" label="Schedule" href="#" disabled>
                    <template #icon>
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="17" rx="2" />
                            <line x1="3" y1="9" x2="21" y2="9" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                        </svg>
                    </template>
                </NavItem>
            </template>

            <!-- SYSTEM -->
            <template v-if="isAdmin">
                <NavSectionLabel label="System" />

                <NavItem label="Users" routeName="users.index">
                    <template #icon>
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="8" r="3" />
                            <path d="M3 20c0-3.5 2.7-6 6-6s6 2.5 6 6" />
                            <circle cx="17" cy="9" r="2.5" />
                            <path d="M15.5 14.2c2.4.4 4.5 2.4 4.5 5.8" />
                        </svg>
                    </template>
                </NavItem>

                <NavItem label="Settings" href="#" disabled>
                    <template #icon>
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3" />
                            <path d="M19.4 15a1.7 1.7 0 00.34 1.87l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.7 1.7 0 00-1.87-.34 1.7 1.7 0 00-1.04 1.56V21a2 2 0 11-4 0v-.09A1.7 1.7 0 009 19.35a1.7 1.7 0 00-1.87.34l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.7 1.7 0 004.65 15a1.7 1.7 0 00-1.56-1.04H3a2 2 0 110-4h.09A1.7 1.7 0 004.65 9a1.7 1.7 0 00-.34-1.87l-.06-.06a2 2 0 112.83-2.83l.06.06A1.7 1.7 0 009 4.65a1.7 1.7 0 001.04-1.56V3a2 2 0 114 0v.09A1.7 1.7 0 0015 4.65a1.7 1.7 0 001.87-.34l.06-.06a2 2 0 112.83 2.83l-.06.06A1.7 1.7 0 0019.35 9a1.7 1.7 0 001.56 1.04H21a2 2 0 110 4h-.09a1.7 1.7 0 00-1.56 1.04z" />
                        </svg>
                    </template>
                </NavItem>
            </template>
        </nav>
    </div>
</template>