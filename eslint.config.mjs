import js from "@eslint/js";
import globals from "globals";
import tseslint from "typescript-eslint";
import pluginVue from "eslint-plugin-vue";
import json from "@eslint/json";
import markdown from "@eslint/markdown";
import css from "@eslint/css";
import tailwind from "eslint-plugin-tailwindcss";
import stylistic from "@stylistic/eslint-plugin";
import { defineConfig } from "eslint/config";
import { tailwind3 } from "tailwind-csstree";

export default defineConfig([
    // Base JS/TS/Vue configurations
    { files: ["**/*.{js,mjs,cjs,ts,mts,cts,vue}"], plugins: { js }, extends: ["js/recommended"] },
    {
        files: ["**/*.{js,mjs,cjs,ts,mts,cts,vue}"],
        languageOptions: {
            globals: {
                ...globals.browser,
                route: "readonly"
            }
        }
    },

    // General rules for all files
    {
        files: ["**/*.{js,mjs,cjs,ts,mts,cts,vue}"],
        rules: {
            // Core ESLint rules
            "no-console": "warn",
            "no-debugger": "warn",
            "eqeqeq": "error",
        }
    },

    // Stylistic rules
    {
        files: ["**/*.{js,mjs,cjs,ts,mts,cts,vue}"],
        plugins: { stylistic },
        rules: {
            "space-before-function-paren": [
                "error", {
                    anonymous: "never",
                    named: "never",
                    asyncArrow: "always"
                }
            ],
            "indent": ["warn", 4],
            "semi": ["warn", "always"],
            "no-trailing-spaces": "warn",
            "object-curly-spacing": ["warn", "always"],
            "max-len": ["warn", 120],
            "brace-style": ["warn", "1tbs", { allowSingleLine: true }],
            "arrow-parens": ["error", "as-needed"]
        }
    },

    // TypeScript configurations
    tseslint.configs.recommended,
    {
        files: ["**/*.{ts,mts,cts,vue}"],
        rules: {
            "@typescript-eslint/no-unused-expressions": "error",
            "@typescript-eslint/no-unused-vars": "warn",
            "@typescript-eslint/no-useless-constructor": "warn",
            "@typescript-eslint/no-explicit-any": "off",
            "@typescript-eslint/no-unsafe-return": "off",
            "@typescript-eslint/no-unsafe-assignment": "off",
            "@typescript-eslint/explicit-module-boundary-types": [
                "error",
                { allowArgumentsExplicitlyTypedAsAny: true }
            ],
            "@typescript-eslint/prefer-nullish-coalescing": "warn",
            "@typescript-eslint/prefer-optional-chain": "warn",
            "@typescript-eslint/ban-ts-comment": ["error", {
                minimumDescriptionLength: 3,
                "ts-check": false,
                "ts-expect-error": "allow-with-description",
                "ts-ignore": true,
                "ts-nocheck": true
            }],
            "@typescript-eslint/promise-function-async": "error",
            "@typescript-eslint/consistent-indexed-object-style": ["error", "record"],
            "@typescript-eslint/consistent-type-imports": [
                "error",
                { prefer: "type-imports", fixStyle: "separate-type-imports" }
            ],
            // ya'll not ready for this
            "@typescript-eslint/naming-convention": ["off",
                {
                    selector: "default",
                    format: ["camelCase"]
                },
                {
                    selector: "objectLiteralProperty",
                    format: ["camelCase", "PascalCase"]
                },
                {
                    selector: "typeLike",
                    format: ["PascalCase"]
                },
                {
                    selector: "import",
                    format: ["camelCase", "PascalCase"]
                },
                {
                    selector: "parameter",
                    format: null,
                    filter: {
                        regex: "^_.*",
                        match: true
                    }
                }
            ],
            "@typescript-eslint/no-non-null-assertion": "off",
            "@typescript-eslint/lines-between-class-members": "off"
        },
        languageOptions: {
            parserOptions: {
                project: "./tsconfig.json",
                extraFileExtensions: [".vue"]
            }
        }
    },

    // Vue configurations
    // ...pluginVue.configs["flat/recommended"],
    {
        extends: pluginVue.configs["flat/recommended"],
        files: ["**/*.vue"],
        languageOptions: {
            parserOptions: { parser: tseslint.parser }
        },
        rules: {
            "vue/html-indent": ["warn", 4],
            "vue/component-name-in-template-casing": ["warn", "PascalCase"],
            "vue/match-component-file-name": [
                "error", {
                    extensions: ["jsx", "vue"]
                }
            ],
            "vue/new-line-between-multi-line-property": "warn",
            "vue/max-attributes-per-line": [
                "warn", {
                    singleline: {
                        max: 3
                    },
                    multiline: {
                        max: 1
                    }
                }
            ],
            "vue/first-attribute-linebreak": ["warn", {
                singleline: "ignore",
                multiline: "beside"
            }],
            "vue/multi-word-component-names": "off",
            "vue/no-boolean-default": ["error", "default-false"],
            "vue/no-duplicate-attr-inheritance": "error",
            "vue/no-empty-component-block": "warn",
            "vue/no-multiple-objects-in-class": "error",
            "vue/no-potential-component-option-typo": [
                "error", {
                    presets: ["vue", "vue-router"]
                }
            ],
            "vue/no-reserved-component-names": [
                "error", {
                    disallowVueBuiltInComponents: true,
                    disallowVue3BuiltInComponents: true
                }
            ],
            "vue/no-template-target-blank": "error",
            "vue/no-unsupported-features": [
                "error", {
                    version: "^3.5.18"
                }
            ],
            "vue/no-useless-mustaches": "warn",
            "vue/no-useless-v-bind": "error",
            "vue/padding-line-between-blocks": "warn",
            "vue/require-name-property": "error",
            "vue/v-for-delimiter-style": "error",
            "vue/v-on-event-hyphenation": "error",
            "vue/eqeqeq": "error",
            "vue/no-extra-parens": "warn",
            "vue/html-closing-bracket-newline": [
                "error", {
                    singleline: "never",
                    multiline: "never"
                }
            ],
            "vue/script-setup-uses-vars": "off"
        }
    },

    // Tailwind CSS configurations
    {
        extends: tailwind.configs["flat/recommended"],
        files: ["**/*.{js,mjs,cjs,ts,mts,cts,vue}"],
        rules: {
            "tailwindcss/no-custom-classname": "off",
            "tailwindcss/migration-from-tailwind-2": "off"
        }
    },

    // JSON, Markdown, and CSS configurations
    { files: ["resources/js/**/*.json"], plugins: { json }, language: "json/json", extends: ["json/recommended"] },
    { files: ["**/*.md"], plugins: { markdown }, language: "markdown/commonmark", extends: ["markdown/recommended"] },
    { files: ["resources/**/*.css"], plugins: { css }, language: "css/css", extends: ["css/recommended"], languageOptions: { customSyntax: tailwind3 } },
]);
