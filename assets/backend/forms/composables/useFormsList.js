import { onMounted } from "vue";
import { usePaginatedFetch } from "@/shared/composables/http/backend/usePaginatedFetch.js";

export function useFormsList(listPath) {
    const {
        items: forms,
        loading,
        page,
        totalPages,
        total,
        load: fetchForms,
        goToPage,
    } = usePaginatedFetch(() => listPath);

    onMounted(fetchForms);

    return { forms, loading, page, totalPages, total, fetchForms, goToPage };
}
