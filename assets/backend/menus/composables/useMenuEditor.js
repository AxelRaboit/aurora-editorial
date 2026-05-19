import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

const replacePath = (template, id) => buildPath(template, { id });

function flattenItems(items, list = []) {
    for (const item of items) {
        list.push({
            id: item.id,
            parentId: item.parentId,
            position: item.position,
        });
        if (item.children?.length) flattenItems(item.children, list);
    }
    return list;
}

export function useMenuEditor(paths, initialMenus) {
    const { t } = useI18n();
    const { request } = useRequest();

    const menus = ref([...initialMenus]);
    const selectedMenu = ref(null);
    const loadingMenu = ref(false);

    async function selectMenu(menu) {
        if (!menu) {
            selectedMenu.value = null;
            return;
        }
        loadingMenu.value = true;
        try {
            const data = await request(
                replacePath(paths.show, menu.id),
                null,
                HttpMethod.Get,
            );
            if (data?.success) {
                selectedMenu.value = data.menu;
            } else if (data) {
                toast.error(t("shared.common.error"));
            }
        } finally {
            loadingMenu.value = false;
        }
    }

    async function refreshMenu() {
        if (!selectedMenu.value) return;
        await selectMenu(selectedMenu.value);
    }

    async function refreshList() {
        const data = await request(paths.list, null, HttpMethod.Get);
        if (data?.success) menus.value = data.menus;
    }

    async function updateMenu(menu, payload) {
        const data = await request(replacePath(paths.update, menu.id), payload);
        if (data?.success) {
            toast.success(t("shared.common.saved"));
            await refreshList();
            if (data.menu) await selectMenu(data.menu);
            return true;
        }
        if (data) toast.error(t(data.error ?? "common.error"));
        return false;
    }

    async function deleteMenu(menu) {
        const data = await request(replacePath(paths.delete, menu.id));
        if (data?.success) {
            toast.success(t("shared.common.deleted"));
            if (selectedMenu.value?.id === menu.id) selectedMenu.value = null;
            await refreshList();
            return true;
        }
        if (data) toast.error(t("shared.common.error"));
        return false;
    }

    async function reorderItems() {
        if (!selectedMenu.value) return;

        const reassign = (items, parentId) => {
            items.forEach((item, index) => {
                item.parentId = parentId;
                item.position = index;
                if (item.children?.length) reassign(item.children, item.id);
            });
        };
        reassign(selectedMenu.value.items, null);

        const payload = flattenItems(selectedMenu.value.items);
        const data = await request(
            replacePath(paths.itemReorder, selectedMenu.value.id),
            { items: payload },
        );
        if (data?.success) {
            selectedMenu.value = data.menu;
        } else {
            if (data) toast.error(t("shared.common.error"));
            await refreshMenu();
        }
    }

    async function saveItem(editingItem, payload) {
        if (!selectedMenu.value) return false;
        const url = editingItem
            ? replacePath(paths.itemUpdate, editingItem.id)
            : replacePath(paths.itemCreate, selectedMenu.value.id);
        const data = await request(url, payload);
        if (data?.success) {
            toast.success(t("shared.common.saved"));
            selectedMenu.value = data.menu;
            await refreshList();
            return true;
        }
        if (data) toast.error(t(data.error ?? "common.error"));
        return false;
    }

    async function deleteItem(item) {
        const data = await request(replacePath(paths.itemDelete, item.id));
        if (data?.success) {
            toast.success(t("shared.common.deleted"));
            selectedMenu.value = data.menu;
            return true;
        }
        if (data) toast.error(t("shared.common.error"));
        return false;
    }

    return {
        menus,
        selectedMenu,
        loadingMenu,
        selectMenu,
        refreshMenu,
        refreshList,
        updateMenu,
        deleteMenu,
        reorderItems,
        saveItem,
        deleteItem,
    };
}
