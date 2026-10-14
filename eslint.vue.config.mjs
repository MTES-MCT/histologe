import pluginVue from 'eslint-plugin-vue'
export default [
    // Later we could use: "strongly-recommended", then "recommended"
    ...pluginVue.configs['flat/essential'],
    {
        rules: {
            "no-array-constructor": "off",
            "no-cond-assign": "off",
            "no-eval": "off",
            "no-prototype-builtins": "off",
            "no-sequences": "off",
            "no-unused-expressions": "off",
            "vue/no-mutating-props": "off",
            "vue/no-reserved-component-names": "off",
            "no-console": ["error", { "allow": ["warn", "error"] }]
        },
    },
]
