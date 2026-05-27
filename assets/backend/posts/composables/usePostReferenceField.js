import { computed, ref, watch } from "vue";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { PostFieldType } from "@editorial/shared/enums/postFieldType.js";

/**
 * Manages a Reference-type custom field on a Post: autocomplete search,
 * server-side resolution of preselected IDs, and add/remove helpers.
 * Supports single or multiple selection based on field.options.multiple.
 */
export function usePostReferenceField({
    field,
    modelValue,
    update,
    searchPath = "/backend/editorial/posts/search",
}) {
    const isReference = computed(
        () => field.value.type === PostFieldType.Reference,
    );
    const isMultiple = computed(() => field.value.options?.multiple === true);

    const referenceIds = computed(() => {
        if (!isReference.value) return [];
        if (isMultiple.value)
            return Array.isArray(modelValue.value)
                ? modelValue.value.map(Number)
                : [];
        return modelValue.value ? [Number(modelValue.value)] : [];
    });

    const resolved = ref([]);
    const search = ref("");
    const results = ref([]);
    const open = ref(false);

    const { loading, request: searchRequest } = useRequest();
    const { request: resolveRequest } = useRequest();

    async function runSearch() {
        const url = new URL(searchPath, window.location.origin);
        if (search.value) url.searchParams.set("q", search.value);
        if (field.value.options?.postTypeId) {
            url.searchParams.set(
                "postTypeId",
                String(field.value.options.postTypeId),
            );
        }
        const data = await searchRequest(url.toString(), null, {
            method: HttpMethod.Get,
            noGuard: true,
        });
        if (!data) {
            results.value = [];
            return;
        }
        const exclude = new Set(referenceIds.value);
        results.value = (data.results ?? []).filter(
            (result) => !exclude.has(result.id),
        );
    }

    async function resolveMissingIds() {
        if (!isReference.value) return;
        const alreadyResolved = new Set(
            resolved.value.map((entry) => entry.id),
        );
        const missing = referenceIds.value.filter(
            (id) => !alreadyResolved.has(id),
        );
        if (missing.length === 0) {
            resolved.value = resolved.value.filter((entry) =>
                referenceIds.value.includes(entry.id),
            );
            return;
        }
        const url = new URL(searchPath, window.location.origin);
        url.searchParams.set("ids", missing.join(","));
        const data = await resolveRequest(url.toString(), null, {
            method: HttpMethod.Get,
            noGuard: true,
        });
        if (data) {
            for (const result of data.results ?? []) {
                if (!alreadyResolved.has(result.id))
                    resolved.value.push(result);
            }
        }
        resolved.value = resolved.value.filter((entry) =>
            referenceIds.value.includes(entry.id),
        );
    }

    function addReference(result) {
        if (referenceIds.value.includes(result.id)) return;
        resolved.value.push(result);
        if (isMultiple.value) {
            update([...referenceIds.value, result.id]);
        } else {
            update(result.id);
            open.value = false;
        }
        search.value = "";
    }

    function removeReference(id) {
        resolved.value = resolved.value.filter((entry) => entry.id !== id);
        if (isMultiple.value) {
            update(referenceIds.value.filter((existing) => existing !== id));
        } else {
            update(null);
        }
    }

    watch(search, useDebounce(runSearch, 200));
    watch(modelValue, resolveMissingIds, { immediate: true });

    return {
        isReference,
        isMultiple,
        resolved,
        search,
        results,
        open,
        loading,
        runSearch,
        addReference,
        removeReference,
    };
}
