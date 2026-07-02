import { ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Shared toast state, driven either by the server (session flash data
 * relayed through Inertia) or manually via show() for instant
 * client-side feedback (e.g. a pre-flight check before a request is even
 * sent).
 *
 * REQUIRES: HandleInertiaRequests must share a 'flash' prop, e.g.
 *
 *   'flash' => [
 *       'success' => fn () => $request->session()->get('success'),
 *       'warning' => fn () => $request->session()->get('warning'),
 *       'error'   => fn () => $request->session()->get('error'),
 *   ],
 *
 * Add that to app/Http/Middleware/HandleInertiaRequests.php's share()
 * method if it isn't there yet — otherwise controller flash() calls will
 * silently never reach the browser.
 */
export function useFlashToast() {
    const page = usePage()
    const toast = ref(null)
    let timer = null

    function show(message, type = 'success') {
        if (! message) {
            return
        }

        toast.value = { message, type }

        clearTimeout(timer)
        timer = setTimeout(() => {
            toast.value = null
        }, 4500)
    }

    watch(
        () => page.props.flash,
        (flash) => {
            if (! flash) {
                return
            }

            if (flash.success) show(flash.success, 'success')
            else if (flash.warning) show(flash.warning, 'warning')
            else if (flash.error) show(flash.error, 'error')
        },
        { immediate: true, deep: true }
    )

    return { toast, show }
}