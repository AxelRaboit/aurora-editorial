<script setup>
import { useI18n } from "vue-i18n";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import MenuListPanel from "@editorial/backend/menus/MenuListPanel.vue";
import MenuEditorPanel from "@editorial/backend/menus/MenuEditorPanel.vue";
import MenuItemModal from "@editorial/backend/menus/MenuItemModal.vue";
import { useMenuEditor } from "@editorial/backend/menus/composables/useMenuEditor.js";
import { useMenuEditModal } from "@editorial/backend/menus/composables/useMenuEditModal.js";
import { useMenuDeleteConfirms } from "@editorial/backend/menus/composables/useMenuDeleteConfirms.js";
import { useMenuItemModal } from "@editorial/backend/menus/composables/useMenuItemModal.js";
import { Save, X, Trash2, Menu } from "lucide-vue-next";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    initialMenus: { type: Array, default: () => [] },
    locales: { type: Array, default: () => [] },
    targetTypes: { type: Array, default: () => [] },
    visibilities: { type: Array, default: () => [] },
    listPath: { type: String, required: true },
    createPath: { type: String, required: true },
    showPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    itemCreatePath: { type: String, required: true },
    itemUpdatePath: { type: String, required: true },
    itemDeletePath: { type: String, required: true },
    itemReorderPath: { type: String, required: true },
    pickerPostsPath: { type: String, required: true },
    pickerTermsPath: { type: String, required: true },
    pickerPostTypesPath: { type: String, required: true },
    pickerTaxonomiesPath: { type: String, required: true },
});

const paths = {
    list: props.listPath,
    show: props.showPath,
    update: props.updatePath,
    delete: props.deletePath,
    itemCreate: props.itemCreatePath,
    itemUpdate: props.itemUpdatePath,
    itemDelete: props.itemDeletePath,
    itemReorder: props.itemReorderPath,
};

const { menus, selectedMenu, selectMenu, updateMenu, deleteMenu, reorderItems, saveItem, deleteItem } =
    useMenuEditor(paths, props.initialMenus);

const { menuModal, menuForm, openEditMenu, submitMenu } = useMenuEditModal(updateMenu);
const { confirmDeleteMenu, submitDeleteMenu, confirmDeleteItem, submitDeleteItem } = useMenuDeleteConfirms(deleteMenu, deleteItem);
const { itemModal, openCreateItem, openEditItem, submitItem } = useMenuItemModal(saveItem);
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-4 min-h-[calc(100vh-8rem)]">
        <MenuListPanel
            :menus="menus"
            :selected-id="selectedMenu?.id ?? null"
            v-on:select="selectMenu"
        />

        <MenuEditorPanel
            :menu="selectedMenu"
            :target-types="targetTypes"
            v-on:edit-menu="openEditMenu"
            v-on:delete-menu="confirmDeleteMenu = $event"
            v-on:add-item="openCreateItem"
            v-on:edit-item="openEditItem"
            v-on:delete-item="confirmDeleteItem = $event"
            v-on:reorder-root="reorderItems"
        />

        <AppModal
            :show="menuModal.open"
            max-width="md"
            :title="t('backend.menus.edit_menu')"
            :icon="Menu"
            :closeable="false"
            v-on:close="menuModal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitMenu">
                <AppInput v-model="menuForm.name" :label="t('backend.menus.name')" required />
                <AppInput
                    v-model="menuForm.location"
                    :label="t('backend.menus.location')"
                    :placeholder="t('backend.menus.location_placeholder')"
                    :readonly="menuModal.editing?.protected"
                    required
                />
                <p v-if="menuModal.editing?.protected" class="text-xs text-amber-500 -mt-2">{{ t('backend.menus.location_locked_hint') }}</p>
                <AppInput v-model="menuForm.description" :label="t('backend.menus.description')" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="menuModal.open = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="menuModal.saving"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!confirmDeleteMenu"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="confirmDeleteMenu = null"
        >
            <p class="text-sm text-primary">{{ t("backend.menus.delete_confirm", { name: confirmDeleteMenu?.name ?? "" }) }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="confirmDeleteMenu = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="submitDeleteMenu"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!confirmDeleteItem"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="confirmDeleteItem = null"
        >
            <p class="text-sm text-primary">{{ t("backend.menus.delete_item_confirm") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="confirmDeleteItem = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="submitDeleteItem"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <MenuItemModal
            :show="itemModal.open"
            :editing="itemModal.editing"
            :target-types="targetTypes"
            :visibilities="visibilities"
            :locales="locales"
            :picker-posts-path="pickerPostsPath"
            :picker-terms-path="pickerTermsPath"
            :picker-post-types-path="pickerPostTypesPath"
            :picker-taxonomies-path="pickerTaxonomiesPath"
            v-on:close="itemModal.open = false"
            v-on:save="submitItem"
        />
    </div>
</template>
