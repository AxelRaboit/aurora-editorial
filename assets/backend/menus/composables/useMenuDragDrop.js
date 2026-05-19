import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

const DATA_TYPE = "application/x-aurora-menu-item";

/**
 * Native HTML5 drag-drop for the menu items tree.
 *
 * Mirrors `useTaxonomyTree` / `useNoteDragDrop`: each row is natively
 * `draggable`, drop targets read the dragged id from `dataTransfer`,
 * and the tree is mutated in place before the parent's `reorderItems`
 * is called to persist the whole new layout.
 *
 * @param {object} options
 * @param {import('vue').Ref} options.menuRef     - the selected menu
 *   whose `.items` tree we mutate.
 * @param {() => Promise<void>} options.persist   - parent's reorderItems
 *   callback (flattens the tree + POSTs).
 */
export function useMenuDragDrop({ menuRef, persist }) {
    const { t } = useI18n();

    const draggingId = ref(null);
    const dragOverId = ref(null);
    const rootDragOver = ref(false);

    function onItemDragStart(item, event) {
        if (!event.dataTransfer) return;
        draggingId.value = item.id;
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData(DATA_TYPE, String(item.id));
    }

    function onItemDragEnd() {
        draggingId.value = null;
        dragOverId.value = null;
        rootDragOver.value = false;
    }

    function onItemDragOver(item, event) {
        if (!event.dataTransfer?.types.includes(DATA_TYPE)) return;
        if (item.id === draggingId.value) return;
        // Block drops on own descendants client-side.
        if (draggingId.value !== null) {
            const dragged = findNode(menuRef.value?.items, draggingId.value);
            if (dragged && collectDescendantIds(dragged).has(item.id)) return;
        }
        event.preventDefault();
        event.stopPropagation();
        event.dataTransfer.dropEffect = "move";
        dragOverId.value = item.id;
        rootDragOver.value = false;
    }

    function onItemDragLeave(item, event) {
        const related = event.relatedTarget;
        if (related && event.currentTarget.contains(related)) return;
        if (dragOverId.value === item.id) dragOverId.value = null;
    }

    function onRootDragOver(event) {
        if (!event.dataTransfer?.types.includes(DATA_TYPE)) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = "move";
        rootDragOver.value = true;
        dragOverId.value = null;
    }

    function onRootDragLeave(event) {
        const related = event.relatedTarget;
        if (related && event.currentTarget.contains(related)) return;
        rootDragOver.value = false;
    }

    async function onDropOnItem(target, event) {
        event.preventDefault();
        event.stopPropagation();
        const dragId = Number(event.dataTransfer.getData(DATA_TYPE));
        dragOverId.value = null;
        draggingId.value = null;
        if (!dragId || dragId === target.id) return;
        if (!moveItemUnderParent(dragId, target.id)) return;
        await persist();
    }

    async function onDropOnRoot(event) {
        event.preventDefault();
        const dragId = Number(event.dataTransfer.getData(DATA_TYPE));
        rootDragOver.value = false;
        draggingId.value = null;
        if (!dragId) return;
        if (!moveItemUnderParent(dragId, null)) return;
        await persist();
    }

    /**
     * Reparent `nodeId` under `newParentId` (null = root). Appends at the
     * end of the target's children. Returns false on cycle / unknown id.
     */
    function moveItemUnderParent(nodeId, newParentId) {
        if (!menuRef.value?.items) return false;
        if (nodeId === newParentId) return false;
        const node = findNode(menuRef.value.items, nodeId);
        if (!node) return false;
        if (
            newParentId !== null &&
            collectDescendantIds(node).has(newParentId)
        ) {
            toast.error(t("backend.menus.errors.cycle"));
            return false;
        }
        removeNode(menuRef.value.items, nodeId);
        if (newParentId === null) {
            menuRef.value.items.push(node);
        } else {
            const parent = findNode(menuRef.value.items, newParentId);
            if (!parent) {
                menuRef.value.items.push(node);
                return false;
            }
            parent.children = parent.children ?? [];
            parent.children.push(node);
        }
        return true;
    }

    function findNode(items, id) {
        if (!items) return null;
        for (const item of items) {
            if (item.id === id) return item;
            const inChild = findNode(item.children, id);
            if (inChild) return inChild;
        }
        return null;
    }

    function collectDescendantIds(node) {
        const ids = new Set();
        ids.add(node.id);
        for (const child of node.children ?? []) {
            collectDescendantIds(child).forEach((id) => ids.add(id));
        }
        return ids;
    }

    function removeNode(items, id) {
        const idx = items.findIndex((i) => i.id === id);
        if (idx !== -1) {
            items.splice(idx, 1);
            return true;
        }
        for (const item of items) {
            if (item.children && removeNode(item.children, id)) return true;
        }
        return false;
    }

    return {
        draggingId,
        dragOverId,
        rootDragOver,
        onItemDragStart,
        onItemDragEnd,
        onItemDragOver,
        onItemDragLeave,
        onRootDragOver,
        onRootDragLeave,
        onDropOnItem,
        onDropOnRoot,
    };
}
