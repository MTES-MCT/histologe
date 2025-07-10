import js from '@eslint/js';
import prettier from 'eslint-config-prettier';

export default [
  js.configs.recommended,
  prettier,
  {
    languageOptions: {
        parserOptions: {
            parser: 'espree',
        },
    },
    rules: {
        'indent': ['error', 2],
        'semi': ['error', 'always'],
        'quotes': ['error', 'single'],
        'no-unused-vars': 'warn',
        'no-undef': 'off',
        'no-prototype-builtins': 'off'
    },
  }
];
