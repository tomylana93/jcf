// Avatar endpoint helpers (regenerate-safe).
//
// NOTE: the Account/ProfileAvatarController backend does not exist in this
// repo yet (see siblings jvst routes/account.php). These helpers intentionally
// live outside the wayfinder-generated folders so `wayfinder:generate` will
// never delete them. Once the backend exists, replace the bodies below with the
// generated actions and update ProfileAvatarForm accordingly.

type AvatarAction = {
    url: string;
    method: 'post' | 'patch' | 'delete';
};

function action(url: string, method: AvatarAction['method']): AvatarAction {
    return { url, method };
}

// TODO(jcf): implement Account/ProfileAvatarController backend, then replace
// these stubs with generated wayfinder actions.
export function uploadAvatar(): AvatarAction {
    return action('/account/profile/avatar/temporary', 'post');
}

export function updateAvatar(): AvatarAction {
    return action('/account/profile/avatar', 'patch');
}

export function discardAvatar(): AvatarAction {
    return action('/account/profile/avatar/temporary', 'delete');
}
