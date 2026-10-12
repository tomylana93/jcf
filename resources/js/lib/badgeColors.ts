export const badgeColorClasses: Record<string, string> = {
    red: 'border-transparent bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-100',
    orange: 'border-transparent bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-100',
    amber: 'border-transparent bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-100',
    yellow: 'border-transparent bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-100',
    lime: 'border-transparent bg-lime-100 text-lime-800 dark:bg-lime-900/40 dark:text-lime-100',
    green: 'border-transparent bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-100',
    emerald:
        'border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-100',
    teal: 'border-transparent bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-100',
    cyan: 'border-transparent bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-100',
    sky: 'border-transparent bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-100',
    blue: 'border-transparent bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-100',
    indigo: 'border-transparent bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-100',
    violet: 'border-transparent bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-100',
    purple: 'border-transparent bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-100',
    fuchsia:
        'border-transparent bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-900/40 dark:text-fuchsia-100',
    pink: 'border-transparent bg-pink-100 text-pink-800 dark:bg-pink-900/40 dark:text-pink-100',
    rose: 'border-transparent bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-100',
    slate: 'border-transparent bg-slate-100 text-slate-800 dark:bg-slate-900/40 dark:text-slate-100',
    gray: 'border-transparent bg-gray-100 text-gray-800 dark:bg-gray-900/40 dark:text-gray-100',
    zinc: 'border-transparent bg-zinc-100 text-zinc-800 dark:bg-zinc-900/40 dark:text-zinc-100',
    neutral:
        'border-transparent bg-neutral-100 text-neutral-800 dark:bg-neutral-900/40 dark:text-neutral-100',
    stone: 'border-transparent bg-stone-100 text-stone-800 dark:bg-stone-900/40 dark:text-stone-100',
    mauve: 'border-transparent bg-mauve-100 text-mauve-800 dark:bg-mauve-900/40 dark:text-mauve-100',
    olive: 'border-transparent bg-olive-100 text-olive-800 dark:bg-olive-900/40 dark:text-olive-100',
    mist: 'border-transparent bg-mist-100 text-mist-800 dark:bg-mist-900/40 dark:text-mist-100',
    taupe: 'border-transparent bg-taupe-100 text-taupe-800 dark:bg-taupe-900/40 dark:text-taupe-100',
};

export function getBadgeColorClasses(color: string = 'neutral'): string {
    return Object.hasOwn(badgeColorClasses, color)
        ? badgeColorClasses[color]
        : badgeColorClasses.neutral;
}
