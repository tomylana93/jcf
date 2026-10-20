import {
    Activity,
    BookOpen,
    FilePenLine,
    Globe,
    LayoutGrid,
    ScrollText,
    Settings,
    ShieldCheck,
    Users,
} from '@lucide/vue';

import { dashboard } from '@/routes';
import { index as auditIndex } from '@/routes/audit';
import { index as documentationIndex } from '@/routes/documentation';
import { index as manageDocumentationIndex } from '@/routes/documentation/manage';
import { index as masterDataIndex } from '@/routes/master-data';
import { index as permissionsIndex } from '@/routes/permissions';
import { index as settingsIndex } from '@/routes/settings';
import { index as systemIndex } from '@/routes/system';
import { index as websiteIndex } from '@/routes/website';
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

export function createManagementNavigationGroups(
    t: (key: TranslationKey) => string,
    canViewSettings = false,
    canViewUsers = false,
    canViewUserPermissions = false,
    canViewWebsite = false,
    canManageDocumentation = false,
    canViewAudit = false,
    canViewSystem = false,
    canManageBackups = false,
): NavGroup[] {
    if (
        !canViewSettings &&
        !canViewUsers &&
        !canViewUserPermissions &&
        !canViewWebsite &&
        !canManageDocumentation &&
        !canViewAudit &&
        !canViewSystem &&
        !canManageBackups
    ) {
        return [];
    }

    return [
        {
            title: t('navigation.group.admin'),
            items: [
                ...(canViewUsers || canManageBackups
                    ? [
                          {
                              title: t('navigation.link.master_data'),
                              href: masterDataIndex(),
                              icon: Users,
                          },
                      ]
                    : []),
                ...(canViewUserPermissions
                    ? [
                          {
                              title: t('navigation.link.permission'),
                              href: permissionsIndex(),
                              icon: ShieldCheck,
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
                ...(canManageDocumentation
                    ? [
                          {
                              title: t('navigation.link.manage_documentation'),
                              href: manageDocumentationIndex(),
                              icon: FilePenLine,
                          },
                      ]
                    : []),
                ...(canViewAudit
                    ? [
                          {
                              title: t('navigation.link.audit'),
                              href: auditIndex(),
                              icon: ScrollText,
                          },
                      ]
                    : []),
                ...(canViewSystem
                    ? [
                          {
                              title: t('navigation.link.system'),
                              href: systemIndex(),
                              icon: Activity,
                          },
                      ]
                    : []),
            ],
        },
        ...(canViewWebsite
            ? [
                  {
                      title: t('navigation.link.website'),
                      items: [
                          {
                              title: t('navigation.link.website'),
                              href: websiteIndex(),
                              icon: Globe,
                          },
                      ],
                  },
              ]
            : []),
    ].filter((group) => group.items.length > 0);
}

export function createFooterNavigationItems(
    t: (key: TranslationKey) => string,
    canViewDocumentation = false,
    canManageDocumentation = false,
): NavItem[] {
    return canViewDocumentation || canManageDocumentation
        ? [
              {
                  title: t('navigation.link.documentation'),
                  href: documentationIndex(),
                  icon: BookOpen,
              },
          ]
        : [];
}

export function createNavigationGroups(
    t: (key: TranslationKey) => string,
    canViewSettings = false,
    canViewUsers = false,
    canViewUserPermissions = false,
    canViewWebsite = false,
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
        ...createManagementNavigationGroups(
            t,
            canViewSettings,
            canViewUsers,
            canViewUserPermissions,
            canViewWebsite,
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
            permissions.includes('website.content.edit') ||
                permissions.includes('website.content.publish') ||
                permissions.includes('website.configure') ||
                permissions.includes('website.inbox.view'),
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
        }));
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
