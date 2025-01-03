module.exports = {
  env: {
    browser: true,
    es2021: true
  },
  extends: [
    'plugin:vue/vue3-essential',
    // later: 'plugin:vue/vue3-recommended',
    'standard-with-typescript'
  ],
  overrides: [
    {
      "files": ["./assets/tsconfig.json", "assets/vue/*.ts", "assets/vue/**/*.ts", "assets/vue/**/*.vue"]
    },
  ],
  // excludedFiles: "./assets/controllers/*.js",
  parser: "vue-eslint-parser",
  parserOptions: {
    ecmaVersion: 2020,
    sourceType: 'module',
    project: ["./assets/tsconfig.json"],
    parser: "@typescript-eslint/parser",
    extraFileExtensions: [".vue"]
  },
  plugins: [
    'vue',
    '@typescript-eslint'
  ],
  rules: {
    '@typescript-eslint/explicit-module-boundary-types': 'off',
    'no-array-constructor': 'off',
    'no-eval': 'off',
    'vue/no-mutating-props': 'off',
    'vue/no-reserved-component-names': 'off',
    'no-prototype-builtins': 'off',
    'no-cond-assign': 'off',
    'no-unused-expressions': 'off',
    'no-sequences': 'off',
  }
}