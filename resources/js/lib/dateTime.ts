export function formatDateTime(
    isoUtc: string,
    locale: string,
    timeZone: string,
): string {
    return new Intl.DateTimeFormat(locale, {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone,
    }).format(new Date(isoUtc));
}
