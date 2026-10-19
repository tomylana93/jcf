export function formatPageTitle(
    title: string,
    page: { props: Record<string, unknown> },
): string {
    if (page.props.website) {
        return title;
    }

    const general = page.props.general;

    const name =
        general &&
        typeof general === 'object' &&
        'site_name' in general &&
        typeof general.site_name === 'string'
            ? general.site_name
            : 'Jalara';

    const escapedName = name
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

    return title ? `${title} - ${escapedName}` : escapedName;
}
