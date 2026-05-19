import { ref, computed } from "vue";

export function usePostTypeSelect(initialPostTypes) {
    const postTypes = ref([...initialPostTypes]);
    const selectedId = ref(postTypes.value[0]?.id ?? null);
    const selected = computed(
        () => postTypes.value.find((pt) => pt.id === selectedId.value) ?? null,
    );

    function replacePostType(fresh) {
        const idx = postTypes.value.findIndex((pt) => pt.id === fresh.id);
        if (idx === -1) postTypes.value.push(fresh);
        else postTypes.value[idx] = fresh;
    }

    return { postTypes, selectedId, selected, replacePostType };
}
