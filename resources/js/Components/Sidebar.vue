<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useAppShell } from '@/Composables/useAppShell'

const { sidebarPinned, mobileOpen, closeMobile } = useAppShell()

const page = usePage()
const user = computed(() => page.props.auth?.user)
const role = computed(() => user.value?.roles?.[0] ?? '')

function hasRole(...roles) {
    return roles.includes(role.value)
}

function initials(name) {
    if (!name) return ''
    return name.trim().split(/\s+/).map((p) => p[0]).slice(0, 2).join('').toUpperCase()
}

// Hover state, tracked in JS so the group-toggle click handler can know
// whether the sidebar is currently "visible" the same way the CSS
// :hover / .is-expanded rules do.
const isHovering = ref(false)
const isVisible = computed(() => sidebarPinned.value || isHovering.value)

/* ── Nav config ─────────────────────────────────────────────────
   type: 'link' → single item. type: 'group' → collapsible section.
   roles: null means visible to everyone.
────────────────────────────────────────────────────────────────── */
const navConfig = [
    { type: 'link', label: 'Dashboard', route: 'dashboard', icon: '📊', roles: null },
    {
        type: 'group',
        id: 'academic',
        label: 'Academic Structure',
        icon: '🏛️',
        roles: ['Admin', 'Registrar'],
        children: [
            { label: 'Academic Terms', route: 'academic-terms.index', icon: '🗓️' },
            { label: 'Colleges', route: 'departments.index', icon: '🏫' },
            { label: 'Programs', route: 'programs.index', icon: '🎓' },
            { label: 'Specializations', route: 'specializations.index', icon: '🧩' },
            { label: 'Curriculum', route: 'curriculums.index', icon: '📘' },
            { label: 'Curriculum Items', route: 'curriculum-items.index', icon: '📄' },
            { label: 'Sections', route: 'sections.index', icon: '🧮' },
        ],
    },
    {
        type: 'group',
        id: 'faculty',
        label: 'Faculty & Scheduling',
        icon: '🧑‍🏫',
        roles: ['Admin', 'Registrar', 'Dean', 'Assistant Dean', 'OIC'],
        children: [
            { label: 'Faculty', route: 'faculty.index', icon: '👨‍🏫' },
            // Kept from the original menu — not in the requested list, but removing it
            // would hide an existing page. Delete this line if it's no longer needed.
            { label: 'Subjects', route: 'subjects.index', icon: '📚' },
            { label: 'Faculty Subjects', route: 'faculty-subjects.index', icon: '🔗' },
            { label: 'Rooms', route: 'rooms.index', icon: '🏢' },
            { label: 'Teaching Assignments', route: 'teaching-assignments.index', icon: '📋' },
            { label: 'Schedule', href: '#', icon: '🗓️', soon: true },
        ],
    },
    {
        type: 'group',
        id: 'system',
        label: 'System',
        icon: '⚙️',
        roles: ['Admin'],
        children: [
            { label: 'Users', route: 'users.index', icon: '👥' },
            // TODO: swap in the real route name once the Settings page exists —
            // safeRoute() below falls back to '#' instead of throwing if it's missing.
            { label: 'Settings', route: 'settings.index', icon: '🛠️' },
        ],
    },
]

const visibleNav = computed(() =>
    navConfig.filter((item) => !item.roles || hasRole(...item.roles))
)

// Ziggy's route() throws on an unknown route name — guard so one bad/placeholder
// route (e.g. Settings, above) can't break the whole sidebar.
function safeRoute(name) {
    try {
        return route(name)
    } catch (e) {
        return '#'
    }
}

function itemHref(item) {
    return item.route ? safeRoute(item.route) : (item.href ?? '#')
}

function isCurrent(item) {
    if (!item.route) return false
    try {
        return route().current(item.route)
    } catch (e) {
        return false
    }
}

function groupActive(group) {
    return group.children.some((c) => isCurrent(c))
}

// Open whichever group contains the active route by default.
const openGroups = reactive(
    Object.fromEntries(
        navConfig.filter((i) => i.type === 'group').map((g) => [g.id, groupActive(g)])
    )
)

// Accordion: only one group open at a time.
function closeOtherGroups(exceptId) {
    Object.keys(openGroups).forEach((id) => {
        if (id !== exceptId) openGroups[id] = false
    })
}

function onGroupClick(group) {
    if (isVisible.value) {
        const next = !openGroups[group.id]
        closeOtherGroups(group.id)
        openGroups[group.id] = next
    } else {
        // Sidebar is collapsed (rail mode) — jump straight to the first child
        // instead of expanding in place.
        const first = group.children.find((c) => c.route)
        if (first) router.visit(itemHref(first))
    }
}

function onNavClick() {
    if (mobileOpen.value) closeMobile()
}
</script>

<template>
    <aside
        id="app-sidebar"
        :class="{ 'is-expanded': sidebarPinned, 'mobile-open': mobileOpen }"
        @mouseenter="isHovering = true"
        @mouseleave="isHovering = false"
    >
        <!-- Logo -->
        <div class="flex items-center h-16 px-4 border-b shrink-0" style="border-color: var(--sidebar-border)">
            <div class="flex items-center gap-3 w-full overflow-hidden">
                <img
                    src="/logo.png"
                    alt="Classly"
                    class="w-8 h-8 shrink-0 rounded-lg object-cover"
                />
                <div class="logo-wordmark flex flex-col leading-none brand-font">
                    <span class="text-white font-extrabold text-[16px] tracking-tight uppercase">
                        Classly<span class="text-blue-500">.</span>
                    </span>
                    <span class="text-indigo-200/80 font-bold text-[7px] uppercase tracking-[0.15em] mt-0.5">
                        Your Friendly Class Scheduler
                    </span>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto custom-scrollbar px-2 py-3 space-y-0.5">
            <template v-for="item in visibleNav" :key="item.id ?? item.route">

                <!-- Single link -->
                <div v-if="item.type === 'link'" class="nav-link-wrap relative">
                    <Link
                        :href="itemHref(item)"
                        class="relative flex items-center gap-3 px-2 py-2.5 rounded-xl transition-all duration-150 group"
                        :class="isCurrent(item)
                            ? 'nav-link-active'
                            : 'text-slate-100/80 hover:text-white hover:bg-white/10 border border-transparent'"
                        @click="onNavClick"
                    >
                        <span v-if="isCurrent(item)" class="active-pip"></span>
                        <span class="nav-item-icon text-base transition-transform duration-150 group-hover:scale-110">{{ item.icon }}</span>
                        <span class="nav-label">{{ item.label }}</span>
                    </Link>
                    <span class="nav-tooltip">{{ item.label }}</span>
                </div>

                <!-- Collapsible group -->
                <div v-else class="nav-group">
                    <p class="nav-section-heading px-2 pb-1 pt-3 text-[10px] font-black text-indigo-200/60 uppercase tracking-[0.18em]">
                        {{ item.label }}
                    </p>

                    <div class="nav-link-wrap relative">
                        <div
                            class="relative flex items-center gap-3 px-2 py-2.5 rounded-xl transition-all duration-150 group cursor-pointer"
                            :class="groupActive(item)
                                ? 'nav-link-active'
                                : 'text-slate-100/80 hover:text-white hover:bg-white/10 border border-transparent'"
                            @click="onGroupClick(item)"
                        >
                            <span v-if="groupActive(item)" class="active-pip"></span>
                            <span class="nav-item-icon text-base transition-transform duration-150 group-hover:scale-110">{{ item.icon }}</span>
                            <span class="nav-label flex-1">{{ item.label }}</span>
                            <span class="nav-chevron ml-auto" :class="{ 'is-open': openGroups[item.id] }">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </div>
                        <span class="nav-tooltip">{{ item.label }}</span>
                    </div>

                    <!-- Submenu: height comes from content via CSS grid, never clipped -->
                    <div class="nav-submenu" :class="{ 'is-open': openGroups[item.id] }">
                        <div class="nav-submenu-inner">
                            <div class="ml-6 pl-3 border-l space-y-0.5" style="border-color: rgba(59, 130, 246, 0.2)">
                                <component
                                    :is="child.route ? Link : 'span'"
                                    v-for="child in item.children"
                                    :key="child.label"
                                    :href="child.route ? itemHref(child) : undefined"
                                    class="flex items-center gap-2 px-2 py-2 rounded-lg text-[13px] font-semibold transition-all duration-150"
                                    :class="[
                                        isCurrent(child) ? 'text-blue-200 bg-blue-500/20' : 'text-indigo-100/70 hover:text-white hover:bg-white/10',
                                        child.soon ? 'opacity-50 cursor-not-allowed pointer-events-none' : '',
                                    ]"
                                    @click="onNavClick"
                                >
                                    <span class="text-sm">{{ child.icon }}</span>
                                    <span class="nav-label flex-1" style="font-size: 13px">{{ child.label }}</span>
                                    <span v-if="child.soon" class="nav-label text-[10px] font-black uppercase tracking-widest text-indigo-200/50">Soon</span>
                                </component>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </nav>

        <!-- Bottom: user + logout -->
        <div class="shrink-0 border-t p-2 space-y-1" style="border-color: var(--sidebar-border)">
            <div class="nav-link-wrap relative flex items-center gap-3 px-2 py-2.5">
                <div class="relative shrink-0">
                    <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white font-black text-[10px]">
                        {{ initials(user?.name) }}
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 w-2 h-2 rounded-full bg-emerald-500 border border-[var(--sidebar-bg)]"></span>
                </div>
                <div class="sidebar-user-text flex flex-col leading-none overflow-hidden">
                    <span class="text-[12px] font-bold text-white truncate">{{ user?.name }}</span>
                    <span class="text-[10px] font-semibold text-indigo-200/80 uppercase tracking-widest mt-0.5">{{ role }}</span>
                </div>
            </div>

            <div class="nav-link-wrap relative">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full flex items-center gap-3 px-2 py-2.5 rounded-xl transition-all duration-150 group text-rose-300/80 hover:text-rose-300 hover:bg-rose-500/10 border border-transparent"
                >
                    <span class="nav-item-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </span>
                    <span class="nav-label text-rose-300/90 group-hover:text-rose-200">Sign Out</span>
                </Link>
                <span class="nav-tooltip">Sign Out</span>
            </div>
        </div>
    </aside>

    <!-- Mobile overlay -->
    <div id="sidebar-overlay" :class="{ active: mobileOpen }" @click="closeMobile"></div>
</template>