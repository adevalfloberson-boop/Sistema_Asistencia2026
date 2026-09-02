const applyTheme = (theme) => {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.documentElement.style.colorScheme = theme;
};

const storedTheme = localStorage.getItem('school-panel-theme');
applyTheme(storedTheme ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
            localStorage.setItem('school-panel-theme', nextTheme);
            applyTheme(nextTheme);
        });
    });

    const section = new URLSearchParams(window.location.search).get('section');
    if (section) {
        window.requestAnimationFrame(() => document.getElementById(section)?.scrollIntoView());
    }

    if (section === 'enrollment') {
        let isRefreshingEnrollmentStatus = false;

        const refreshEnrollmentStatus = async () => {
            if (document.hidden || isRefreshingEnrollmentStatus) {
                return;
            }

            const enrollmentSection = document.getElementById('enrollment');
            const currentStatusPanel = enrollmentSection?.querySelector(':scope > article:nth-child(2)');
            if (!currentStatusPanel) {
                return;
            }

            isRefreshingEnrollmentStatus = true;

            try {
                const response = await fetch(window.location.href, {
                    cache: 'no-store',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'text/html',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const refreshedDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
                const refreshedStatusPanel = refreshedDocument
                    .getElementById('enrollment')
                    ?.querySelector(':scope > article:nth-child(2)');

                if (refreshedStatusPanel) {
                    currentStatusPanel.replaceWith(refreshedStatusPanel);
                }
            } finally {
                isRefreshingEnrollmentStatus = false;
            }
        };

        window.setInterval(refreshEnrollmentStatus, 4000);
    }
});
