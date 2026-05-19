/**
 * Maps EditorJS block-tool types (as serialised) to the user-facing tool name keys.
 * Used to label blocks in template previews / lists.
 */
export const BLOCK_TYPE_TO_TOOL_NAME = Object.freeze({
    header: "heading",
    paragraph: "text",
    image: "image",
    list: "list",
    code: "code",
    callout: "callout",
    delimiter: "delimiter",
    twoColumn: "twoColumn",
    mediaText: "mediaText",
    embed: "embed",
    table: "table",
    quote: "quote",
    checklist: "checklist",
});

export const TEMPLATE_CATEGORIES = Object.freeze([
    "article",
    "marketing",
    "layout",
    "technique",
]);
