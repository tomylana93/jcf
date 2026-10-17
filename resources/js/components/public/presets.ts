import type {
    PublicPreset,
    PublicPresetPreferences,
    PublicSection,
} from '@/types';

export type PublicPresetDefinition = {
    label: string;
    hero: 'split' | 'editorial' | 'simple';
    articles: 'grid' | 'lead' | 'list';
    sections: 'columns' | 'ruled' | 'compact';
    width: string;
    order: PublicSection['type'][];
};

export const publicPresets: Record<PublicPreset, PublicPresetDefinition> = {
    corporate: {
        label: 'Corporate',
        hero: 'split',
        articles: 'grid',
        sections: 'columns',
        width: 'max-w-6xl',
        order: [
            'hero',
            'introduction',
            'services',
            'strengths',
            'articles',
            'contact',
        ],
    },
    editorial: {
        label: 'Editorial',
        hero: 'editorial',
        articles: 'lead',
        sections: 'ruled',
        width: 'max-w-6xl',
        order: [
            'hero',
            'introduction',
            'articles',
            'services',
            'strengths',
            'contact',
        ],
    },
    minimal: {
        label: 'Minimal',
        hero: 'simple',
        articles: 'list',
        sections: 'compact',
        width: 'max-w-4xl',
        order: [
            'hero',
            'introduction',
            'services',
            'strengths',
            'articles',
            'contact',
        ],
    },
};

export function composePublicSections(
    sections: PublicSection[],
    preset: PublicPresetDefinition,
    preferences: PublicPresetPreferences,
): PublicSection[] {
    const rank = (section: PublicSection): number => {
        const custom = preferences.sectionOrder.indexOf(section.id);
        return custom < 0
            ? preferences.sectionOrder.length +
                  preset.order.indexOf(section.type)
            : custom;
    };
    return sections
        .filter(
            (section) =>
                section.visible &&
                !preferences.hiddenSections.includes(section.id),
        )
        .toSorted((left, right) => rank(left) - rank(right));
}
