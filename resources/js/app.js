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

    const deviceStatusUrl = document.querySelector('meta[name="device-status-url"]')?.content;
    const deviceSection = document.getElementById('devices');

    if (deviceStatusUrl && deviceSection) {
        let isRefreshingDeviceStatus = false;

        const formatLastContact = (value) => {
            if (! value) {
                return 'Nunca';
            }

            return new Intl.DateTimeFormat('es-DO', {
                dateStyle: 'short',
                timeStyle: 'medium',
            }).format(new Date(value));
        };

        const refreshDeviceStatus = async () => {
            if (document.hidden || isRefreshingDeviceStatus) {
                return;
            }

            isRefreshingDeviceStatus = true;

            try {
                const response = await fetch(deviceStatusUrl, {
                    cache: 'no-store',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (! response.ok) {
                    return;
                }

                const payload = await response.json();
                document.querySelector('[data-device-summary-online]').textContent = payload.summary.online;
                document.querySelector('[data-device-summary-alerts]').textContent = payload.summary.alerts;

                payload.devices.forEach((device) => {
                    const card = deviceSection.querySelector(`[data-device-id="${device.id}"]`);
                    if (! card) {
                        return;
                    }

                    card.querySelector('[data-device-status]').className = `inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-extrabold ${device.badge_class}`;
                    card.querySelector('[data-device-status-dot]').className = `h-1.5 w-1.5 rounded-full ${device.dot_class}`;
                    card.querySelector('[data-device-status-label]').textContent = device.label;
                    card.querySelector('[data-device-last-seen]').textContent = formatLastContact(device.last_seen_at);
                    card.querySelector('[data-device-inventory]').textContent = `${device.user_count} / ${device.fingerprint_count}`;
                });

                const alert = document.getElementById('device-connection-alert');
                const alertMessage = alert.querySelector('[data-device-alert-message]');
                const alertCount = payload.summary.alerts;
                alert.classList.toggle('hidden', alertCount === 0);
                alert.classList.toggle('flex', alertCount > 0);
                alertMessage.textContent = alertCount === 1
                    ? 'Hay 1 lector con retraso o sin comunicación. Revisa alimentación, ONU/red e internet del centro.'
                    : `Hay ${alertCount} lectores con retraso o sin comunicación. Revisa alimentación, ONU/red e internet de cada centro.`;
            } finally {
                isRefreshingDeviceStatus = false;
            }
        };

        refreshDeviceStatus();
        window.setInterval(refreshDeviceStatus, 10000);
    }
});
