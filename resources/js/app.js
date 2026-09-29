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

    const initializeBiometricEnrollment = () => {
        const root = document.querySelector('[data-biometric-enrollment]');

        if (! root || root.dataset.initialized === 'true') {
            return;
        }

        root.dataset.initialized = 'true';
        const fingerNames = [
            'Pulgar derecho',
            'Índice derecho',
            'Medio derecho',
            'Anular derecho',
            'Meñique derecho',
            'Pulgar izquierdo',
            'Índice izquierdo',
            'Medio izquierdo',
            'Anular izquierdo',
            'Meñique izquierdo',
        ];
        let selectedPerson = null;
        let previewUrl = null;

        const setStep = (activeStep) => {
            const steps = ['person', 'biometry', 'capture'];
            const activeIndex = steps.indexOf(activeStep);

            root.querySelectorAll('[data-enrollment-step]').forEach((step) => {
                const stepIndex = steps.indexOf(step.dataset.enrollmentStep);
                const marker = step.querySelector('span');
                const label = step.querySelector('span:last-child');
                const isReached = stepIndex <= activeIndex;

                marker.classList.toggle('border-teal-600', isReached);
                marker.classList.toggle('bg-teal-600', isReached);
                marker.classList.toggle('text-white', isReached);
                marker.classList.toggle('border-stone-200', ! isReached);
                marker.classList.toggle('dark:border-slate-700', ! isReached);
                marker.classList.toggle('text-slate-400', ! isReached);
                label.classList.toggle('text-teal-600', isReached);
                label.classList.toggle('dark:text-teal-300', isReached);
                label.classList.toggle('text-slate-400', ! isReached);
            });
        };

        const faceStatusLabel = (status) => ({
            synced: 'Registrado y sincronizado',
            pending: 'Registrado · sincronización pendiente',
            failed: 'Registrado · error de sincronización',
        })[status] ?? 'No registrado';

        const selectPerson = (option, isRestoring = false) => {
            selectedPerson = {
                id: option.dataset.studentId,
                name: option.dataset.studentName,
                matricula: option.dataset.studentMatricula,
                course: option.dataset.studentCourse,
                biometricId: option.dataset.studentBiometricId,
                fingers: option.dataset.studentFingers.split(',').filter(Boolean).map(Number),
                faceStatus: option.dataset.studentFaceStatus,
            };

            root.querySelector('[data-person-search-panel]').classList.add('hidden');
            root.querySelector('[data-selected-person-panel]').classList.remove('hidden');
            root.querySelector('[data-biometry-choice-panel]').classList.remove('hidden');
            root.querySelector('[data-capture-panel]').classList.add('hidden');
            root.querySelector('[data-selected-person-name]').textContent = selectedPerson.name;
            root.querySelector('[data-selected-person-matricula]').textContent = selectedPerson.matricula;
            root.querySelector('[data-selected-person-course]').textContent = selectedPerson.course || 'Sin curso';
            root.querySelector('[data-selected-person-id]').textContent = selectedPerson.biometricId || 'sin asignar';
            root.querySelector('[data-selected-person-initials]').textContent = selectedPerson.name
                .split(/\s+/)
                .slice(0, 2)
                .map((part) => part.charAt(0))
                .join('')
                .toLocaleUpperCase();
            root.querySelector('[data-selected-person-face]').textContent = faceStatusLabel(selectedPerson.faceStatus);

            const fingers = root.querySelector('[data-selected-person-fingers]');
            fingers.replaceChildren();

            if (selectedPerson.fingers.length === 0) {
                const empty = document.createElement('span');
                empty.className = 'text-sm font-bold text-slate-500';
                empty.textContent = 'No hay huellas confirmadas';
                fingers.append(empty);
            } else {
                selectedPerson.fingers.forEach((index) => {
                    const badge = document.createElement('span');
                    badge.className = 'rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300';
                    badge.textContent = fingerNames[index] ?? `Dedo ${index}`;
                    fingers.append(badge);
                });
            }

            root.querySelectorAll('[data-biometric-student-select]').forEach((select) => {
                select.value = selectedPerson.id;
            });
            root.querySelectorAll('[data-form-person-name]').forEach((element) => {
                element.textContent = selectedPerson.name;
            });
            sessionStorage.setItem('biometric-enrollment-person', selectedPerson.id);

            if (! isRestoring) {
                sessionStorage.removeItem('biometric-enrollment-type');
            }

            setStep('biometry');
        };

        const showBiometricType = (type) => {
            if (! selectedPerson || ! ['fingerprint', 'face'].includes(type)) {
                return;
            }

            root.querySelector('[data-biometry-choice-panel]').classList.add('hidden');
            root.querySelector('[data-capture-panel]').classList.remove('hidden');
            root.querySelector('[data-fingerprint-enrollment-form]').classList.toggle('hidden', type !== 'fingerprint');
            root.querySelector('[data-face-enrollment-form]').classList.toggle('hidden', type !== 'face');
            root.querySelector('[data-capture-title]').textContent = type === 'fingerprint' ? 'Registro de huella' : 'Registro facial';
            sessionStorage.setItem('biometric-enrollment-type', type);
            setStep('capture');
        };

        root.querySelector('[data-biometric-person-search]').addEventListener('input', (event) => {
            const query = event.target.value.trim().toLocaleLowerCase();
            const results = root.querySelector('[data-biometric-person-results]');
            let visibleResults = 0;

            results.classList.toggle('hidden', query.length < 2);
            root.querySelectorAll('[data-biometric-person-option]').forEach((option) => {
                const isVisible = query.length >= 2
                    && visibleResults < 8
                    && option.dataset.studentSearch.includes(query);
                option.classList.toggle('hidden', ! isVisible);
                option.classList.toggle('flex', isVisible);
                visibleResults += isVisible ? 1 : 0;
            });
            root.querySelector('[data-biometric-person-empty]').classList.toggle('hidden', query.length < 2 || visibleResults > 0);
        });

        root.addEventListener('click', (event) => {
            const personOption = event.target.closest('[data-biometric-person-option]');
            const biometricChoice = event.target.closest('[data-biometric-choice]');

            if (personOption) {
                selectPerson(personOption);
            }

            if (event.target.closest('[data-change-biometric-person]')) {
                selectedPerson = null;
                sessionStorage.removeItem('biometric-enrollment-person');
                sessionStorage.removeItem('biometric-enrollment-type');
                root.querySelector('[data-selected-person-panel]').classList.add('hidden');
                root.querySelector('[data-biometry-choice-panel]').classList.add('hidden');
                root.querySelector('[data-capture-panel]').classList.add('hidden');
                root.querySelector('[data-person-search-panel]').classList.remove('hidden');
                root.querySelector('[data-biometric-person-search]').focus();
                setStep('person');
            }

            if (event.target.closest('[data-change-biometric-type]')) {
                sessionStorage.removeItem('biometric-enrollment-type');
                root.querySelector('[data-capture-panel]').classList.add('hidden');
                root.querySelector('[data-biometry-choice-panel]').classList.remove('hidden');
                setStep('biometry');
            }

            if (biometricChoice && selectedPerson) {
                const type = biometricChoice.dataset.biometricChoice;
                showBiometricType(type);
                root.querySelector('[data-capture-panel]').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        root.querySelector('[data-fingerprint-enrollment-form]').addEventListener('submit', (event) => {
            const fingerIndex = Number(root.querySelector('[data-finger-index]').value);

            if (selectedPerson?.fingers.includes(fingerIndex)
                && ! window.confirm(`Esta persona ya tiene registrada la huella ${fingerNames[fingerIndex]}. ¿Deseas continuar y reemplazarla?`)) {
                event.preventDefault();
            }
        });

        root.querySelector('[data-face-photo]').addEventListener('change', (event) => {
            const file = event.target.files?.[0];
            const panel = root.querySelector('[data-face-preview-panel]');
            const preview = root.querySelector('[data-face-preview]');

            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }

            if (! file) {
                panel.classList.add('hidden');

                return;
            }

            previewUrl = URL.createObjectURL(file);
            preview.src = previewUrl;
            panel.classList.remove('hidden');
        });

        const storedPersonId = sessionStorage.getItem('biometric-enrollment-person');
        const storedPersonOption = storedPersonId
            ? root.querySelector(`[data-biometric-person-option][data-student-id="${CSS.escape(storedPersonId)}"]`)
            : null;

        if (storedPersonOption) {
            selectPerson(storedPersonOption, true);
            showBiometricType(sessionStorage.getItem('biometric-enrollment-type'));
        } else {
            setStep('person');
        }
    };

    const initializeReportStudentSearch = () => {
        const container = document.querySelector('[data-report-student-search]');

        if (! container || container.dataset.initialized === 'true') {
            return;
        }

        container.dataset.initialized = 'true';
        const input = container.querySelector('[data-report-student-input]');
        const studentId = container.querySelector('[data-report-student-id]');
        const results = container.querySelector('[data-report-student-results]');
        const empty = container.querySelector('[data-report-student-empty]');

        input.addEventListener('input', () => {
            const query = input.value.trim().toLocaleLowerCase();
            let visibleResults = 0;

            studentId.value = '';
            results.classList.toggle('hidden', query.length < 2);
            container.querySelectorAll('[data-report-student-option]').forEach((option) => {
                const isVisible = query.length >= 2
                    && visibleResults < 8
                    && option.dataset.studentSearch.includes(query);
                option.classList.toggle('hidden', ! isVisible);
                visibleResults += isVisible ? 1 : 0;
            });
            empty.classList.toggle('hidden', query.length < 2 || visibleResults > 0);
        });

        container.addEventListener('click', (event) => {
            const option = event.target.closest('[data-report-student-option]');

            if (option) {
                studentId.value = option.dataset.studentId;
                input.value = option.dataset.studentName;
                results.classList.add('hidden');
            }

            if (event.target.closest('[data-clear-report-student]')) {
                studentId.value = '';
                input.value = '';
                input.focus();
            }
        });
    };

    const initializeOverviewRosterFilters = () => {
        document.querySelectorAll('[data-overview-roster]').forEach((container) => {
            if (container.dataset.initialized === 'true') {
                return;
            }

            container.dataset.initialized = 'true';
            const searchInput = container.querySelector('[data-roster-search]');
            const courseSelect = container.querySelector('[data-roster-course]');
            const emptyState = container.querySelector('[data-roster-empty]');

            const applyFilters = () => {
                const search = searchInput.value.trim().toLocaleLowerCase();
                const course = courseSelect.value;
                let visibleRows = 0;

                container.querySelectorAll('[data-roster-row]').forEach((row) => {
                    const isVisible = row.dataset.rosterSearchValue.includes(search)
                        && (course === '' || row.dataset.rosterCourseValue === course);
                    row.classList.toggle('hidden', ! isVisible);
                    visibleRows += isVisible ? 1 : 0;
                });

                emptyState.classList.toggle('hidden', visibleRows > 0);
            };

            searchInput.addEventListener('input', applyFilters);
            courseSelect.addEventListener('change', applyFilters);
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
    initializeBiometricEnrollment();
    initializeReportStudentSearch();
    initializeOverviewRosterFilters();

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
            initializeBiometricEnrollment();
            initializeReportStudentSearch();
            initializeOverviewRosterFilters();

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
