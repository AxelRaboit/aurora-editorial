import { computed } from "vue";

/**
 * Tree helpers for the menu editor panel.
 *
 * Currently exposes `itemCount(menuRef)` — total descendants of the menu
 * (recursive). Kept as a composable rather than a util so future
 * tree-related helpers (selection, expand state, etc.) have a natural
 * home next to the panel.
 */
export function useMenuTree(menuRef) {
    const itemCount = computed(() => {
        const items = menuRef.value?.items;
        if (!items) return 0;
        return countDescendants(items);
    });

    function countDescendants(items) {
        return items.reduce(
            (acc, item) =>
                acc +
                1 +
                (item.children?.length ? countDescendants(item.children) : 0),
            0,
        );
    }

    return { itemCount };
}
