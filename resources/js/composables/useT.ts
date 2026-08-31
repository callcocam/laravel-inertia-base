import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type TranslationTree = { [key: string]: string | TranslationTree };
type Replacements = Record<string, string | number>;

export function useT() {
    const page = usePage();

    const translations = computed<TranslationTree>(
        () => (page.props.translations as TranslationTree | undefined) ?? {},
    );

    const locale = computed<string>(
        () => (page.props.locale as string | undefined) ?? 'pt_BR',
    );

    function t(key: string, replacements?: Replacements): string {
        let node: string | TranslationTree | undefined = translations.value;

        for (const segment of key.split('.')) {
            if (typeof node !== 'object' || node === null) {
                node = undefined;
                break;
            }
            node = node[segment];
        }

        if (typeof node !== 'string') {
            return key;
        }

        if (!replacements) {
            return node;
        }

        return Object.entries(replacements).reduce(
            (message, [name, value]) =>
                message.replaceAll(`:${name}`, String(value)),
            node,
        );
    }

    return { t, locale };
}
