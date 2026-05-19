import { reactive } from "vue";

export function useMenuItemModal(saveItem) {
    const itemModal = reactive({ open: false, editing: null, saving: false });

    function openCreateItem() {
        itemModal.editing = null;
        itemModal.open = true;
    }

    function openEditItem(item) {
        itemModal.editing = item;
        itemModal.open = true;
    }

    async function submitItem(payload) {
        itemModal.saving = true;
        try {
            const ok = await saveItem(itemModal.editing, payload);
            if (ok) itemModal.open = false;
        } finally {
            itemModal.saving = false;
        }
    }

    return { itemModal, openCreateItem, openEditItem, submitItem };
}
