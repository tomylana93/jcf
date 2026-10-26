import {
    Activity,
    BookOpen,
    FilePenLine,
    Globe,
    LayoutGrid,
    MessagesSquare,
    ScrollText,
    Settings,
    ShieldCheck,
    Users,
} from '@lucide/vue';

import { dashboard } from '@/routes';
import { index as auditIndex } from '@/routes/audit';
import { index as chatIndex } from '@/routes/chat';
import { index as documentationIndex } from '@/routes/documentation';
import { index as manageDocumentationIndex } from '@/routes/documentation/manage';
import { index as masterDataIndex } from '@/routes/master-data';
import { index as permissionsIndex } from '@/routes/permissions';
import { index as settingsIndex } from '@/routes/settings';
import { index as systemIndex } from '@/routes/system';
import { index as websiteIndex } from '@/routes/website';
import type {
    EditorLinkOption,
    Features,
    NavGroup,
    NavItem,
    TranslationKey,
} from '@/types';

export type NavigationAccess = {
    canViewSettings: boolean;
    canViewUsers: boolean;
    canViewUserPermissions: boolean;
    canViewWebsite: boolean;
    canViewDocumentation: boolean;
    canManageDocumentation: boolean;
    canViewAudit: boolean;
    canViewSystem: boolean;
    canManageBackups: boolean;
    canUseChat: boolean;
};

export type NavigationOptions = {
    access: NavigationAccess;
    features: Features;
    chatUnreadCount?: number;
};

export function navigationAccess(
    permissions: readonly string[],
    superAdmin = false,
): NavigationAccess {
    return {
        canViewSettings: permissions.includes('settings.view'),
        canViewUsers: permissions.includes('users.view'),
        canViewUserPermissions:
            superAdmin && permissions.includes('users.permissions.assign'),
        canViewWebsite:
            permissions.includes('website.content.edit') ||
            permissions.includes('website.content.publish') ||
            permissions.includes('website.configure') ||
            permissions.includes('website.inbox.view'),
        canViewDocumentation: permissions.includes('documentation.view'),
        canManageDocumentation: permissions.includes('documentation.manage'),
        canViewAudit: permissions.includes('audit.view'),
        canViewSystem: permissions.includes('system.view'),
        canManageBackups: permissions.includes('backups.manage'),
        canUseChat: permissions.includes('chat.use'),
    };
}

export function createMainNavigationItems(
    t: (key: TranslationKey) => string,
    { access, features, chatUnreadCount = 0 }: NavigationOptions,
): NavItem[] {
    const canUseChat = access.canUseChat && features.chat.enabled;

    return [
        {
            title: t('navigation.link.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...(canUseChat
            ? [
                  {
                      title: t('chat.title'),
                      href: chatIndex(),
                      icon: MessagesSquare,
                      ...(chatUnreadCount > 0
                          ? { badge: chatUnreadCount }
                          : {}),
                  },
              ]
            : []),
    ];
}

export function createManagementNavigationGroups(
    t: (key: TranslationKey) => string,
    { access, features }: NavigationOptions,
): NavGroup[] {
    const canViewUsers = access.canViewUsers;
    const canManageBackups = access.canManageBackups && features.backup.enabled;
    const canViewUserPermissions = access.canViewUserPermissions;
    const canViewSettings = access.canViewSettings;
    const canManageDocumentation =
        access.canManageDocumentation && features.documentation.enabled;
    const canViewAudit = access.canViewAudit;
    const canViewSystem = access.canViewSystem;
    const canViewWebsite = access.canViewWebsite && features.website.enabled;

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
    { access, features }: NavigationOptions,
): NavItem[] {
    const canViewDocumentation =
        features.documentation.enabled &&
        (access.canViewDocumentation || access.canManageDocumentation);

    return canViewDocumentation
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
    options: NavigationOptions,
): NavGroup[] {
    return [
        {
            items: createMainNavigationItems(t, options),
        },
        ...createManagementNavigationGroups(t, options),
    ];
}

export function createEditorLinkOptions(
    t: (key: TranslationKey) => string,
    permissions: readonly string[],
    features: Features,
): EditorLinkOption[] {
    const options: NavigationOptions = {
        access: navigationAccess(permissions),
        features,
    };

    return [
        ...createNavigationGroups(t, options).flatMap((group) => group.items),
        ...createFooterNavigationItems(t, options),
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
