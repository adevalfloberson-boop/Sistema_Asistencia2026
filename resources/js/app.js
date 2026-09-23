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

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-confirm]');

        if (form && ! window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });

    const section = new URLSearchParams(window.location.search).get('section');
    if (section) {
        window.requestAnimationFrame(() => document.getElementById(section)?.scrollIntoView());
    }

    const deviceConsoleState = {
        search: '',
        school: '',
        status: '',
        view: 'cards',
    };

    const applyDeviceConsoleState = () => {
        const consoleElement = document.querySelector('[data-device-console]');

        if (! consoleElement) {
            return;
        }

        const searchInput = consoleElement.querySelector('[data-device-search]');
        const schoolFilter = consoleElement.querySelector('[data-device-school-filter]');
        const statusFilter = consoleElement.querySelector('[data-device-status-filter]');
        const acceptedStatuses = deviceConsoleState.status.split(',').filter(Boolean);
        let visibleDevices = 0;

        if (searchInput) searchInput.value = deviceConsoleState.search;
        if (schoolFilter) schoolFilter.value = deviceConsoleState.school;
        if (statusFilter) statusFilter.value = deviceConsoleState.status;

        consoleElement.querySelectorAll('[data-device-card]').forEach((card) => {
            const matchesSearch = ! deviceConsoleState.search
                || card.dataset.deviceSearchable.includes(deviceConsoleState.search.toLocaleLowerCase());
            const matchesSchool = ! deviceConsoleState.school || card.dataset.deviceSchool === deviceConsoleState.school;
            const matchesStatus = acceptedStatuses.length === 0 || acceptedStatuses.includes(card.dataset.deviceState);
            const isVisible = matchesSearch && matchesSchool && matchesStatus;

            card.classList.toggle('hidden', ! isVisible);
            visibleDevices += isVisible ? 1 : 0;
        });

        consoleElement.querySelectorAll('[data-device-school-group]').forEach((group) => {
            const hasVisibleDevices = Boolean(group.querySelector('[data-device-card]:not(.hidden)'));
            group.classList.toggle('hidden', ! hasVisibleDevices);
        });

        consoleElement.querySelector('[data-device-empty]')?.classList.toggle('hidden', visibleDevices > 0);
        consoleElement.querySelectorAll('[data-device-grid]').forEach((grid) => {
            grid.classList.toggle('xl:grid-cols-2', deviceConsoleState.view === 'cards');
            grid.classList.toggle('2xl:grid-cols-3', deviceConsoleState.view === 'cards');
            grid.classList.toggle('grid-cols-1', deviceConsoleState.view === 'list');
        });
        consoleElement.querySelectorAll('[data-device-view]').forEach((button) => {
            const isActive = button.dataset.deviceView === deviceConsoleState.view;
            button.setAttribute('aria-pressed', String(isActive));
            button.classList.toggle('bg-white', isActive);
            button.classList.toggle('shadow-sm', isActive);
            button.classList.toggle('text-slate-800', isActive);
            button.classList.toggle('dark:bg-slate-800', isActive);
            button.classList.toggle('dark:text-white', isActive);
            button.classList.toggle('text-slate-500', ! isActive);
        });
    };

    document.addEventListener('click', (event) => {
        const openButton = event.target.closest('[data-open-dialog]');
        const closeButton = event.target.closest('[data-close-dialog]');
        const viewButton = event.target.closest('[data-device-view]');
        const copyButton = event.target.closest('[data-copy-value]');

        if (openButton) {
            document.getElementById(openButton.dataset.openDialog)?.showModal();
            openButton.closest('[data-device-actions]')?.removeAttribute('open');
        }

        if (closeButton) {
            closeButton.closest('dialog')?.close();
        }

        if (event.target.matches('dialog')) {
            event.target.close();
        }

        if (viewButton) {
            deviceConsoleState.view = viewButton.dataset.deviceView;
            applyDeviceConsoleState();
        }

        if (copyButton) {
            navigator.clipboard.writeText(copyButton.dataset.copyValue).then(() => {
                const originalLabel = copyButton.textContent;
                copyButton.textContent = 'Enlace copiado';
                window.setTimeout(() => { copyButton.textContent = originalLabel; }, 1800);
            });
        }
    });

    document.addEventListener('input', (event) => {
        if (event.target.matches('[data-device-search]')) {
            deviceConsoleState.search = event.target.value.trim().toLocaleLowerCase();
            applyDeviceConsoleState();
        }
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('[data-device-school-filter]')) {
            deviceConsoleState.school = event.target.value;
            applyDeviceConsoleState();
        }

        if (event.target.matches('[data-device-status-filter]')) {
            deviceConsoleState.status = event.target.value;
            applyDeviceConsoleState();
        }
    });

    applyDeviceConsoleState();

    const adminPanel = document.querySelector('[data-admin-async-panel]');

    if (! adminPanel) {
        return;
    }

    const syncLabel = document.querySelector('[data-admin-sync-label]');
    const syncDot = document.querySelector('[data-admin-sync-dot]');
    let isRefreshingAdminPanel = false;

    const updateSyncState = (state) => {
        if (! syncLabel || ! syncDot) {
            return;
        }

        const stateMeta = {
            active: ['Sincronización activa', 'bg-emerald-500'],
            refreshing: ['Actualizando…', 'bg-sky-500 animate-pulse'],
            paused: ['Pausada mientras editas', 'bg-amber-500'],
            error: ['Sin conexión', 'bg-rose-500'],
        };
        const [label, dotClasses] = stateMeta[state];

        syncLabel.textContent = label;
        syncDot.className = `h-2 w-2 rounded-full ${dotClasses}`;
    };

    const isAdministratorEditing = () => {
        const activeElement = document.activeElement;
        const hasFocusedField = activeElement
            && adminPanel.contains(activeElement)
            && activeElement.matches('input:not([type="hidden"]), select, textarea, [contenteditable="true"]');

        return hasFocusedField
            || Boolean(adminPanel.querySelector('details[data-device-actions][open], dialog[open]'));
    };

    const refreshAdminPanel = async () => {
        if (document.hidden || isRefreshingAdminPanel) {
            return;
        }

        if (isAdministratorEditing()) {
            updateSyncState('paused');

            return;
        }

        isRefreshingAdminPanel = true;
        updateSyncState('refreshing');

        try {
            const response = await fetch(window.location.href, {
                cache: 'no-store',
                credentials: 'same-origin',
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.redirected && new URL(response.url).pathname.endsWith('/login')) {
                window.location.assign(response.url);

                return;
            }

            if (! response.ok) {
                updateSyncState('error');

                return;
            }

            const refreshedDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
            const refreshedPanel = refreshedDocument.querySelector('[data-admin-async-panel]');

            if (! refreshedPanel) {
                updateSyncState('error');

                return;
            }

            adminPanel.replaceChildren(...refreshedPanel.childNodes);
            applyDeviceConsoleState();

            const currentSidebarSummary = document.querySelector('[data-admin-sidebar-summary]');
            const refreshedSidebarSummary = refreshedDocument.querySelector('[data-admin-sidebar-summary]');

            if (currentSidebarSummary && refreshedSidebarSummary) {
                currentSidebarSummary.replaceWith(refreshedSidebarSummary);
            }

            const currentTime = document.querySelector('[data-admin-current-time]');
            const refreshedTime = refreshedDocument.querySelector('[data-admin-current-time]');

            if (currentTime && refreshedTime) {
                currentTime.textContent = refreshedTime.textContent;
            }

            updateSyncState('active');
        } catch {
            updateSyncState('error');
        } finally {
            isRefreshingAdminPanel = false;
        }
    };

    updateSyncState('active');
    window.setInterval(refreshAdminPanel, 10000);
});
