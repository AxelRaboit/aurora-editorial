const globals = require('globals');
const pluginVue = require('eslint-plugin-vue');
const prettierPlugin = require('eslint-plugin-prettier');

/** @type {import('eslint').Linter.FlatConfig[]} */
module.exports = [
    {
        // Mirrors aurora-core's config verbatim so both halves of the former
        // monorepo stay formatted identically. Indent width comes from
        // .editorconfig, which prettier reads — without that file it silently
        // falls back to its own 2-space default and reports every indented
        // line in the package as an error.
        ignores: ['node_modules/**', 'vendor/**', 'tools/**'],
    },

    // JS files — Prettier formatting
    {
        files: ['**/*.js'],
        plugins: { prettier: prettierPlugin },
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: globals.browser,
        },
        rules: {
            semi: 'error',
            'prefer-const': 'error',
            'prettier/prettier': 'error',
        },
    },

    // Vue files — Vue rules
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['**/*.vue'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: globals.browser,
        },
        rules: {
            semi: 'error',
            'prefer-const': 'error',
            'vue/multi-word-component-names': 'off',
            'vue/v-on-style': ['error', 'longform'],
            'vue/v-bind-style': ['error', 'shorthand'],
            'vue/html-indent': ['warn', 4],
            'vue/script-indent': ['warn', 4],
            'vue/max-attributes-per-line': ['warn', { singleline: 4, multiline: 1 }],
            'vue/singleline-html-element-content-newline': 'off',
            'vue/component-definition-name-casing': ['error', 'PascalCase'],
            'vue/require-prop-types': 'warn',
            'vue/require-default-prop': 'off',
            'vue/no-v-html': 'off',
            'vue/attributes-order': 'warn',
        },
    },
];
