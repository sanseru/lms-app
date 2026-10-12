import AppLayout from '@/Layouts/AppLayout.vue';
import FocusLayout from '@/Layouts/FocusLayout.vue';

/**
 * Lesson pages: students who are learning get the full-screen focus layout,
 * teachers, admins and parents keep the normal app layout with the sidebar.
 */
export default function lessonLayout(h, page) {
    return h(page.props?.can?.learn ? FocusLayout : AppLayout, () => page);
}
