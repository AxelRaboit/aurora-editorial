import { ref, reactive, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import {
    buildTree,
    flattenTreeForReorder,
    collectDescendantIds,
    findNodeInTree,
} from "@/shared/composables/tree/useHierarchicalTree.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

const DATA_TYPE = "application/x-aurora-taxonomy-term";

export function useTaxonomyTree(
    selected,
    flatTerms,
    termReorderPath,
    locales,
    activeLocale,
    replaceTaxonomy,
    termName,
) {
    const { t } = useI18n();
    const { request } = useRequest();

    const tree = ref([]);

    watch(
        () => selected.value?.id,
        () => {
            tree.value = buildTree(flatTerms.value);
        },
        { immediate: true },
    );
    watch(
        flatTerms,
        (terms) => {
            tree.value = buildTree(terms);
        },
        { deep: true },
    );

    // ── Native HTML5 drag-drop ────────────────────────────────────────────────
    // Same approach as useMediaDragDrop / useNoteDragDrop (markdown notes):
    // drop ON a term row → becomes child of that term (hierarchical), or
    // sibling before that term (flat). Drop on the root surface → root.
    // VueDraggable's cross-list drops were unreliable in this tree.
    const draggingId = ref(null);
    const dragOverId = ref(null);
    const rootDragOver = ref(false);

    function onTermDragStart(node, event) {
        if (!event.dataTransfer) return;
        draggingId.value = node.id;
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData(DATA_TYPE, String(node.id));
    }

    function onTermDragEnd() {
        draggingId.value = null;
        dragOverId.value = null;
        rootDragOver.value = false;
    }

    function onTermDragOver(node, event) {
        if (!event.dataTransfer?.types.includes(DATA_TYPE)) return;
        if (node.id === draggingId.value) return;
        // Reject drops on own descendants up-front (UX hint; server also rejects).
        if (selected.value?.hierarchical && draggingId.value !== null) {
            const dragged = findNodeInTree(tree.value, draggingId.value);
            if (dragged && collectDescendantIds(dragged).has(node.id)) return;
        }
        event.preventDefault();
        event.stopPropagation();
        event.dataTransfer.dropEffect = "move";
        dragOverId.value = node.id;
        rootDragOver.value = false;
    }

    function onTermDragLeave(node, event) {
        const related = event.relatedTarget;
        if (related && event.currentTarget.contains(related)) return;
        if (dragOverId.value === node.id) dragOverId.value = null;
    }

    function onRootDragOver(event) {
        if (!event.dataTransfer?.types.includes(DATA_TYPE)) return;
        if (!selected.value?.hierarchical) return; // flat taxonomy: no "root drop" semantics
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

    async function onDropOnTerm(target, event) {
        event.preventDefault();
        event.stopPropagation();
        const dragId = Number(event.dataTransfer.getData(DATA_TYPE));
        dragOverId.value = null;
        draggingId.value = null;
        if (!dragId || dragId === target.id) return;

        if (selected.value?.hierarchical) {
            // Hierarchical: dragged becomes child of target (appended at end).
            if (!moveNodeUnderParent(dragId, target.id)) return;
        } else {
            // Flat: dragged inserted before the target (sibling reorder).
            if (!reorderSiblingBefore(dragId, target.id)) return;
        }
        await persistTreeOrder();
    }

    async function onDropOnRoot(event) {
        event.preventDefault();
        const dragId = Number(event.dataTransfer.getData(DATA_TYPE));
        rootDragOver.value = false;
        draggingId.value = null;
        if (!dragId) return;
        if (!selected.value?.hierarchical) return;
        if (!moveNodeUnderParent(dragId, null)) return;
        await persistTreeOrder();
    }

    /**
     * Mutate `tree.value` to reparent `nodeId` under `newParentId` (or root
     * when null). Appends at the end of the target parent's children.
     * Returns false if the move would create a cycle or the node isn't found.
     */
    function moveNodeUnderParent(nodeId, newParentId) {
        if (nodeId === newParentId) return false;
        const node = findNodeInTree(tree.value, nodeId);
        if (!node) return false;
        if (
            newParentId !== null &&
            collectDescendantIds(node).has(newParentId)
        ) {
            return false; // can't drop under own descendant
        }
        removeNodeFromTree(tree.value, nodeId);
        if (newParentId === null) {
            tree.value.push(node);
        } else {
            const parent = findNodeInTree(tree.value, newParentId);
            if (!parent) {
                // Parent vanished — push back to root rather than losing the node.
                tree.value.push(node);
                return false;
            }
            parent.children = parent.children ?? [];
            parent.children.push(node);
        }
        return true;
    }

    /**
     * Mutate `tree.value` to place `nodeId` immediately before `targetId`
     * within the same parent (flat sibling reorder). Returns false on noop.
     */
    function reorderSiblingBefore(nodeId, targetId) {
        const nodeIdx = tree.value.findIndex((n) => n.id === nodeId);
        const targetIdx = tree.value.findIndex((n) => n.id === targetId);
        if (nodeIdx === -1 || targetIdx === -1) return false;
        const [node] = tree.value.splice(nodeIdx, 1);
        const insertAt = tree.value.findIndex((n) => n.id === targetId); // refresh after splice
        tree.value.splice(insertAt, 0, node);
        return true;
    }

    /** Recursive removal helper — strips the node from wherever it lives. */
    function removeNodeFromTree(nodes, id) {
        const idx = nodes.findIndex((n) => n.id === id);
        if (idx !== -1) {
            nodes.splice(idx, 1);
            return true;
        }
        for (const node of nodes) {
            if (node.children && removeNodeFromTree(node.children, id))
                return true;
        }
        return false;
    }

    async function persistTreeOrder() {
        if (!selected.value) return;
        const entries = flattenTreeForReorder(tree.value);
        const data = await request(
            buildPath(termReorderPath, { id: selected.value.id }),
            { entries },
        );
        if (!data) return;
        if (!data.success) {
            toast.error(data.error ?? t("shared.common.error"));
            // Resync from server to revert any optimistic mutation that the
            // server rejected (eg. a cycle detected via concurrent edit).
            tree.value = buildTree(flatTerms.value);
        } else {
            replaceTaxonomy(data.taxonomy);
        }
    }

    const collapsed = reactive(new Set());
    function toggleCollapsed(id) {
        if (collapsed.has(id)) collapsed.delete(id);
        else collapsed.add(id);
    }

    const flatTermsForParentSelect = computed(() => {
        if (!selected.value?.hierarchical) return [];
        const list = [];
        const walk = (nodes, depth) =>
            nodes.forEach((node) => {
                list.push({
                    id: node.id,
                    label: `${"— ".repeat(depth)}${termName(node, activeLocale.value)}`,
                    descendants: collectDescendantIds(node),
                });
                if (node.children) walk(node.children, depth + 1);
            });
        walk(tree.value, 0);
        return list;
    });

    /**
     * Parent-select options for a given term being edited (or null when
     * creating). When editing, the term and its descendants are stripped
     * out so the user can't move a node under itself or its own subtree.
     */
    function parentOptionsForTerm(term) {
        if (!selected.value?.hierarchical) return [];
        const forbidden = term
            ? collectDescendantIds(findNodeInTree(tree.value, term.id) ?? term)
            : new Set();
        return flatTermsForParentSelect.value.filter(
            (opt) => !forbidden.has(opt.id),
        );
    }

    return {
        tree,
        draggingId,
        dragOverId,
        rootDragOver,
        collapsed,
        toggleCollapsed,
        onTermDragStart,
        onTermDragEnd,
        onTermDragOver,
        onTermDragLeave,
        onRootDragOver,
        onRootDragLeave,
        onDropOnTerm,
        onDropOnRoot,
        flatTermsForParentSelect,
        parentOptionsForTerm,
        collectDescendantIds,
        findNodeInTree,
    };
}
