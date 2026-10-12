import { LayoutGrid, Settings, Users, ScrollText } from '@lucide/vue';

import { dashboard } from '@/routes';
import { index as auditIndex } from '@/routes/audit';
import { index as masterDataIndex } from '@/routes/master-data';
import { index as settingsIndex } from '@/routes/settings';
import type {
    EditorLinkOption,
    NavGroup,
    NavItem,
    TranslationKey,
} from '@/types';

export function createMainNavigationItems(
    t: (key: TranslationKey) => string,
): NavItem[] {
    return [
        {
            title: t('navigation.link.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];
}

export function createAdminNavigationGroups(
    t: (key: TranslationKey) => string,
    canViewSettings = false,
    canViewUsers = false,
    _canViewUserPermissions = false,
    _canManageDocumentation = false,
    canViewAudit = false,
    _canViewSystem = false,
    _canManageBackups = false,
): NavGroup[] {
    if (!canViewSettings && !canViewUsers && !canViewAudit) {
        return [];
    }

    return [
        {
            title: t('navigation.group.admin'),
            items: [
                ...(canViewAudit
                    ? [
                          {
                              title: t('navigation.link.audit'),
                              href: auditIndex(),
                              icon: ScrollText,
                          },
                      ]
                    : []),
                ...(canViewUsers
                    ? [
                          {
                              title: t('navigation.link.master_data'),
                              href: masterDataIndex(),
                              icon: Users,
                          },
                      ]
                    : []),
                ...(canViewSettings
                    ? [
                          {
                              title: t('navigation.link.settings'),
                              href: settingsIndex(),
                              icon: Settings,
                          },
                      ]
                    : []),
            ],
        },
    ];
}

export function createFooterNavigationItems(
    _t: (key: TranslationKey) => string,
    _canViewDocumentation = false,
    _canManageDocumentation = false,
): NavItem[] {
    return [];
}

export function createNavigationGroups(
    t: (key: TranslationKey) => string,
    canViewSettings = false,
    canViewUsers = false,
    canViewUserPermissions = false,
    canViewDocumentation = false,
    canManageDocumentation = false,
    canViewAudit = false,
    canViewSystem = false,
    canManageBackups = false,
): NavGroup[] {
    return [
        {
            items: createMainNavigationItems(t),
        },
        ...createAdminNavigationGroups(
            t,
            canViewSettings,
            canViewUsers,
            canViewUserPermissions,
            canManageDocumentation,
            canViewAudit,
            canViewSystem,
            canManageBackups,
        ),
    ];
}

export function createEditorLinkOptions(
    t: (key: TranslationKey) => string,
    permissions: readonly string[],
): EditorLinkOption[] {
    return [
        ...createNavigationGroups(
            t,
            permissions.includes('settings.view'),
            permissions.includes('users.view'),
            permissions.includes('users.permissions.assign'),
            permissions.includes('documentation.view'),
            permissions.includes('documentation.manage'),
            permissions.includes('audit.view'),
            permissions.includes('system.view'),
            permissions.includes('backups.manage'),
        ).flatMap((group) => group.items),
        ...createFooterNavigationItems(
            t,
            permissions.includes('documentation.view'),
            permissions.includes('documentation.manage'),
        ),
    ]
        .filter((item) => !item.external)
        .map((item) => ({
            label: item.title,
            href: typeof item.href === 'string' ? item.href : item.href.url,
        }))
        .filter(
            (item) =>
                item.href !== dashboard.url() ||
                permissions.includes('dashboard.view'),
        );
}

export function flattenNavigationItems(items: NavItem[]): NavItem[] {
    return items.flatMap((item) => [
        item,
        ...flattenNavigationItems(item.children ?? []),
    ]);
}

// JCF fallback: simple navigation without i18n, for pages that do not use translations yet.
export function createSimpleNavigationItems(): NavItem[] {
    return [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];
}
