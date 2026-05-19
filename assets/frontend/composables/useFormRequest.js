import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * Lightweight HTTP composable for public form submission.
 * Unlike useRequest (admin), it does not show toasts — errors are returned
 * to the caller for inline display within the form.
 */
export function useFormRequest() {
    const { t } = useI18n();
    const submitting = ref(false);

    /**
     * @returns {{ success: boolean, errors?: Record<string, string> }|null}
     *   null when a network/unexpected error occurred
     */
    async function submit(url, payload) {
        submitting.value = true;
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            });
            return await response.json();
        } catch {
            return {
                success: false,
                errors: { _global: t("shared.form.error") },
            };
        } finally {
            submitting.value = false;
        }
    }

    return { submitting, submit };
}
