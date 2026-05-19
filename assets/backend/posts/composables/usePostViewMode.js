import { ref } from "vue";

const STORAGE_KEY = "aurora-posts-view-mode";

export function usePostViewMode() {
    const mode = ref(localStorage.getItem(STORAGE_KEY) ?? "compact");

    function setMode(value) {
        mode.value = value;
        localStorage.setItem(STORAGE_KEY, value);
    }

    return { mode, setMode };
}
