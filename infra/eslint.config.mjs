import prettier from 'eslint-plugin-prettier'
import tsParser from '@typescript-eslint/parser'
import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    eslint.configs.recommended,
    tseslint.configs.recommended,
    {
        plugins: {
            prettier
        },

        languageOptions: {
            parser: tsParser,
            ecmaVersion: 2018,
            sourceType: 'module',

            parserOptions: {
                ecmaFeatures: {
                    jsx: true
                }
            }
        },

        rules: {
            curly: 'error',
            'no-magic-numbers': 'off',
            eqeqeq: 'error',
            'no-undef-init': 'error',
            'no-unneeded-ternary': 'error',
            'no-var': 'error',
            'prefer-promise-reject-errors': 'error',
            '@typescript-eslint/explicit-module-boundary-types': 'off'
        }
    }
)
