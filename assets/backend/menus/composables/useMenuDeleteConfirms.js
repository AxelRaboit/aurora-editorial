import { ref } from "vue";

export function useMenuDeleteConfirms(deleteMenu, deleteItem) {
    const confirmDeleteMenu = ref(null);

    async function submitDeleteMenu() {
        if (!confirmDeleteMenu.value) return;
        if (await deleteMenu(confirmDeleteMenu.value))
            confirmDeleteMenu.value = null;
    }

    const confirmDeleteItem = ref(null);

    async function submitDeleteItem() {
        if (!confirmDeleteItem.value) return;
        if (await deleteItem(confirmDeleteItem.value))
            confirmDeleteItem.value = null;
    }

    return {
        confirmDeleteMenu,
        submitDeleteMenu,
        confirmDeleteItem,
        submitDeleteItem,
    };
}
