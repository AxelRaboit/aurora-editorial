import { reactive } from "vue";

export function useMenuEditModal(updateMenu) {
    const menuModal = reactive({ open: false, editing: null, saving: false });
    const menuForm = reactive({ name: "", location: "", description: "" });

    function openEditMenu(menu) {
        menuModal.editing = menu;
        menuForm.name = menu.name;
        menuForm.location = menu.location;
        menuForm.description = menu.description ?? "";
        menuModal.open = true;
    }

    async function submitMenu() {
        if (!menuModal.editing) return;
        menuModal.saving = true;
        try {
            const ok = await updateMenu(menuModal.editing, {
                name: menuForm.name,
                location: menuForm.location,
                description: menuForm.description || null,
            });
            if (ok) menuModal.open = false;
        } finally {
            menuModal.saving = false;
        }
    }

    return { menuModal, menuForm, openEditMenu, submitMenu };
}
