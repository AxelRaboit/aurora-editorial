import { ref, watch } from "vue";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * Autocomplete search for related posts. Maintains the search state, the
 * remote results filtered against already-selected ids, and helpers to
 * add/remove selections. The selection is owned by the caller (form state).
 */
export function useRelatedSearch({
    searchPath = "/backend/posts/search",
    excludeId = null,
    getSelectedIds,
    addId,
    removeId,
}) {
    const query = ref("");
    const results = ref([]);
    const loading = ref(false);
    const open = ref(false);
    const selected = ref([]);
    const { request } = useRequest();

    watch(query, useDebounce(run, 200));

    async function run() {
        loading.value = true;
        try {
            const url = new URL(searchPath, window.location.origin);
            if (query.value) url.searchParams.set("q", query.value);
            if (excludeId) url.searchParams.set("excludeId", String(excludeId));
            const data = await request(url.toString(), null, HttpMethod.Get);
            if (data) {
                const selectedIds = getSelectedIds();
                results.value = (data.results ?? []).filter(
                    (result) => !selectedIds.includes(result.id),
                );
            }
        } catch {
            results.value = [];
        } finally {
            loading.value = false;
        }
    }

    function add(result) {
        if (getSelectedIds().includes(result.id)) return;
        addId(result.id);
        selected.value.push(result);
        query.value = "";
        results.value = results.value.filter((r) => r.id !== result.id);
    }

    function remove(id) {
        removeId(id);
        selected.value = selected.value.filter((r) => r.id !== id);
    }

    function setSelected(items) {
        selected.value = [...items];
    }

    return {
        query,
        results,
        loading,
        open,
        selected,
        add,
        remove,
        setSelected,
    };
}
