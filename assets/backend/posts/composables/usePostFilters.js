import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";

/**
 * Filter state + URL sync for the Posts list page.
 *
 * Owns the three filter refs (postTypes, terms, statuses), the
 * derived options (postTypeOptions, statusOptions, visibleTaxonomies)
 * and the mutation actions (each one syncs the URL and triggers
 * `performSearch()` so the SFC just wires events).
 *
 * @param {object} options
 * @param {Array}  options.parsedPostTypes
 * @param {Array}  options.parsedTaxonomies
 * @param {{postTypeIds?: number[], termIds?: number[], statuses?: string[]}} options.initial
 * @param {() => void} options.performSearch  - re-fetch the list after each change
 */
export function usePostFilters({
    parsedPostTypes,
    parsedTaxonomies,
    initial,
    performSearch,
}) {
    const { t } = useI18n();

    const selectedPostTypeIds = ref([...(initial.postTypeIds ?? [])]);
    const selectedTermIds = ref([...(initial.termIds ?? [])]);
    const selectedStatuses = ref([...(initial.statuses ?? [])]);

    const hasActiveFilters = computed(
        () =>
            selectedPostTypeIds.value.length > 0 ||
            selectedStatuses.value.length > 0 ||
            selectedTermIds.value.length > 0,
    );

    function syncFiltersToUrl() {
        const url = new URL(window.location.href);
        url.searchParams.delete("postTypeIds");
        selectedPostTypeIds.value.forEach((id) =>
            url.searchParams.append("postTypeIds", String(id)),
        );
        url.searchParams.delete("statuses");
        selectedStatuses.value.forEach((s) =>
            url.searchParams.append("statuses", s),
        );
        url.searchParams.delete("termIds");
        selectedTermIds.value.forEach((id) =>
            url.searchParams.append("termIds", String(id)),
        );
        history.replaceState(history.state, "", url);
    }

    function onPostTypeFilterChange(values) {
        selectedPostTypeIds.value = values ?? [];
        // Reset terms — they were filtered by the previous postType selection.
        selectedTermIds.value = [];
        syncFiltersToUrl();
        performSearch();
    }

    function onStatusFilterChange(values) {
        selectedStatuses.value = values ?? [];
        syncFiltersToUrl();
        performSearch();
    }

    function toggleTerm(id) {
        const index = selectedTermIds.value.indexOf(id);
        selectedTermIds.value =
            index === -1
                ? [...selectedTermIds.value, id]
                : selectedTermIds.value.filter((t) => t !== id);
        syncFiltersToUrl();
        performSearch();
    }

    function clearFilters() {
        selectedPostTypeIds.value = [];
        selectedStatuses.value = [];
        selectedTermIds.value = [];
        syncFiltersToUrl();
        performSearch();
    }

    const postTypeOptions = computed(() =>
        parsedPostTypes.map((pt) => ({ value: pt.id, label: pt.label })),
    );

    const statusOptions = computed(() => [
        { value: "draft", label: t("backend.posts.statusOptions.draft") },
        {
            value: "pending_review",
            label: t("backend.posts.statusOptions.pending_review"),
        },
        {
            value: "scheduled",
            label: t("backend.posts.statusOptions.scheduled"),
        },
        {
            value: "published",
            label: t("backend.posts.statusOptions.published"),
        },
        { value: "archived", label: t("backend.posts.statusOptions.archived") },
    ]);

    /**
     * Only show taxonomies whose post types are in the current filter.
     * No postType filter selected → show all taxonomies.
     */
    const visibleTaxonomies = computed(() => {
        if (!selectedPostTypeIds.value.length || !parsedTaxonomies.length) {
            return parsedTaxonomies;
        }
        const taxIds = new Set(
            parsedPostTypes
                .filter((pt) => selectedPostTypeIds.value.includes(pt.id))
                .flatMap((pt) => pt.taxonomyIds ?? []),
        );
        return parsedTaxonomies.filter((tax) => taxIds.has(tax.id));
    });

    return {
        selectedPostTypeIds,
        selectedTermIds,
        selectedStatuses,
        hasActiveFilters,
        postTypeOptions,
        statusOptions,
        visibleTaxonomies,
        onPostTypeFilterChange,
        onStatusFilterChange,
        toggleTerm,
        clearFilters,
        syncFiltersToUrl,
    };
}
