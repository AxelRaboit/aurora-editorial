import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { usePaginatedFetch } from "@/shared/composables/http/backend/usePaginatedFetch.js";

export function useFormSubmissions(
    submissionsPath,
    exportPath,
    selectedForm,
    activeLocale,
) {
    const submissionFields = ref([]);
    const viewingSubmission = ref(null);

    const {
        items: submissions,
        loading: submissionsLoading,
        page: submissionsPage,
        totalPages: submissionsTotalPages,
        total: submissionsTotal,
        load: fetchSubmissions,
        goToPage: goToSubmissionsPage,
        reset: resetSubmissions,
    } = usePaginatedFetch(
        () =>
            selectedForm.value
                ? buildPath(submissionsPath, { id: selectedForm.value.id })
                : null,
        () => ({}),
        (data) => {
            submissionFields.value = data.fields ?? [];
        },
    );

    function exportCsv() {
        const url = `${buildPath(exportPath, { id: selectedForm.value.id })}?locale=${activeLocale.value}`;
        window.location.href = url;
    }

    function submissionValue(submission, field) {
        const value = submission?.data?.[field.id];
        if (Array.isArray(value)) return value.join(", ");
        return value ?? "—";
    }

    function onTabChange(tab, activeTab) {
        activeTab.value = tab;
        if (tab === "submissions") resetSubmissions();
    }

    return {
        submissionFields,
        viewingSubmission,
        submissions,
        submissionsLoading,
        submissionsPage,
        submissionsTotalPages,
        submissionsTotal,
        fetchSubmissions,
        goToSubmissionsPage,
        resetSubmissions,
        exportCsv,
        submissionValue,
        onTabChange,
    };
}
