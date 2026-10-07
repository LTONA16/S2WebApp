/**
 * CRUD Alumnos - Controlador Front-end (Vanilla JS)
 * Gestión de Modales, Toasts y Peticiones Asíncronas
 */

document.addEventListener('DOMContentLoaded', () => {
    // Estado local
    let alumnosList = [];
    let alumnoToDelete = null;

    // Elementos DOM
    const alumnosTableBody = document.getElementById('alumnosTableBody');
    const searchInput = document.getElementById('searchInput');
    const totalCountEl = document.getElementById('totalCount');
    const btnOpenCreate = document.getElementById('btnOpenCreate');

    // Modales
    const alumnoModalOverlay = document.getElementById('alumnoModalOverlay');
    const alumnoModalTitle = document.getElementById('alumnoModalTitle');
    const alumnoForm = document.getElementById('alumnoForm');
    const inputAlumnoId = document.getElementById('alumnoId');
    const inputNombre = document.getElementById('alumnoNombre');
    const inputCarrera = document.getElementById('alumnoCarrera');
    const btnSaveAlumno = document.getElementById('btnSaveAlumno');
    const btnCloseAlumnoModal = document.getElementById('btnCloseAlumnoModal');
    const btnCancelAlumnoModal = document.getElementById('btnCancelAlumnoModal');

    // Modal de confirmación de eliminación
    const deleteModalOverlay = document.getElementById('deleteModalOverlay');
    const deleteAlumnoName = document.getElementById('deleteAlumnoName');
    const btnConfirmDelete = document.getElementById('btnConfirmDelete');
    const btnCloseDeleteModal = document.getElementById('btnCloseDeleteModal');
    const btnCancelDeleteModal = document.getElementById('btnCancelDeleteModal');

    // Contenedor de Toasts
    const toastContainer = document.getElementById('toastContainer');

    /* ==========================================================================
       Sistema de Notificaciones (Toasts)
       ========================================================================== */
    const Toast = {
        show(message, type = 'info', title = null, duration = 3500) {
            if (!toastContainer) return;

            const titles = {
                success: '¡Éxito!',
                error: 'Error',
                warning: 'Atención',
                info: 'Información'
            };

            const icons = {
                success: `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>`,
                error: `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>`,
                warning: `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>`,
                info: `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>`
            };

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;

            const finalTitle = title || titles[type] || 'Aviso';

            toast.innerHTML = `
                <div class="toast-icon">${icons[type] || icons.info}</div>
                <div class="toast-content">
                    <div class="toast-title">${finalTitle}</div>
                    <div class="toast-message">${escapeHtml(message)}</div>
                </div>
                <button class="toast-close" type="button" aria-label="Cerrar">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
                <div class="toast-progress" style="animation-duration: ${duration}ms;"></div>
            `;

            toastContainer.appendChild(toast);

            const removeToast = () => {
                if (toast.classList.contains('hiding')) return;
                toast.classList.add('hiding');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            };

            const autoCloseTimer = setTimeout(removeToast, duration);

            toast.querySelector('.toast-close').addEventListener('click', () => {
                clearTimeout(autoCloseTimer);
                removeToast();
            });
        }
    };

    /* ==========================================================================
       Gestor de Modales
       ========================================================================== */
    function openModal(overlay) {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Cerrar modales con tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal(alumnoModalOverlay);
            closeModal(deleteModalOverlay);
        }
    });

    // Cerrar al hacer clic en el backdrop
    alumnoModalOverlay.addEventListener('click', (e) => {
        if (e.target === alumnoModalOverlay) closeModal(alumnoModalOverlay);
    });

    deleteModalOverlay.addEventListener('click', (e) => {
        if (e.target === deleteModalOverlay) closeModal(deleteModalOverlay);
    });

    btnCloseAlumnoModal.addEventListener('click', () => closeModal(alumnoModalOverlay));
    btnCancelAlumnoModal.addEventListener('click', () => closeModal(alumnoModalOverlay));
    btnCloseDeleteModal.addEventListener('click', () => closeModal(deleteModalOverlay));
    btnCancelDeleteModal.addEventListener('click', () => closeModal(deleteModalOverlay));

    /* ==========================================================================
       Carga y Renderizado de Datos
       ========================================================================== */
    async function loadAlumnos() {
        try {
            renderLoading();
            const response = await fetch('api/alumnos.php');
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'No se pudieron cargar los alumnos.');
            }

            alumnosList = result.data || [];
            renderTable(alumnosList);
            updateTotalCount(alumnosList.length);
        } catch (error) {
            console.error(error);
            renderError('Error al conectar con la API: ' + error.message);
            Toast.show('No se pudieron obtener los datos de la base de datos.', 'error');
        }
    }

    function renderLoading() {
        alumnosTableBody.innerHTML = `
            <tr>
                <td colspan="4" class="empty-state">
                    <p>Cargando registros de la base de datos...</p>
                </td>
            </tr>
        `;
    }

    function renderError(message) {
        alumnosTableBody.innerHTML = `
            <tr>
                <td colspan="4" class="empty-state">
                    <p style="color: var(--danger);">${escapeHtml(message)}</p>
                </td>
            </tr>
        `;
    }

    function renderTable(alumnos) {
        if (alumnos.length === 0) {
            alumnosTableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="8" y1="12" x2="16" y2="12"></line>
                        </svg>
                        <p>No se encontraron alumnos registrados.</p>
                    </td>
                </tr>
            `;
            return;
        }

        alumnosTableBody.innerHTML = alumnos.map(alumno => `
            <tr data-id="${alumno.id}">
                <td><span class="badge-id">#${alumno.id}</span></td>
                <td><strong>${escapeHtml(alumno.nombre)}</strong></td>
                <td>
                    <span class="badge-carrera">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                        </svg>
                        ${escapeHtml(alumno.carrera)}
                    </span>
                </td>
                <td>
                    <div class="table-actions">
                        <button class="btn btn-icon btn-edit" title="Editar alumno" onclick="window.app.editAlumno(${alumno.id})">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </button>
                        <button class="btn btn-icon btn-delete" title="Eliminar alumno" onclick="window.app.askDeleteAlumno(${alumno.id}, '${escapeAttr(alumno.nombre)}')">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function updateTotalCount(count) {
        if (totalCountEl) totalCountEl.textContent = `${count} ${count === 1 ? 'alumno' : 'alumnos'}`;
    }

    /* ==========================================================================
       Buscador en Tiempo Real
       ========================================================================== */
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        const filtered = alumnosList.filter(a => 
            a.nombre.toLowerCase().includes(query) || 
            a.carrera.toLowerCase().includes(query) ||
            String(a.id).includes(query)
        );
        renderTable(filtered);
    });

    /* ==========================================================================
       Creación y Edición
       ========================================================================== */
    btnOpenCreate.addEventListener('click', () => {
        alumnoForm.reset();
        inputAlumnoId.value = '';
        alumnoModalTitle.textContent = 'Nuevo Alumno';
        clearValidationErrors();
        openModal(alumnoModalOverlay);
        inputNombre.focus();
    });

    window.app = {
        editAlumno(id) {
            const alumno = alumnosList.find(a => a.id == id);
            if (!alumno) {
                Toast.show('No se encontró el alumno seleccionado.', 'warning');
                return;
            }

            clearValidationErrors();
            inputAlumnoId.value = alumno.id;
            inputNombre.value = alumno.nombre;
            inputCarrera.value = alumno.carrera;
            alumnoModalTitle.textContent = 'Editar Alumno';
            openModal(alumnoModalOverlay);
            inputNombre.focus();
        },

        askDeleteAlumno(id, nombre) {
            alumnoToDelete = { id, nombre };
            deleteAlumnoName.textContent = `"${nombre}"`;
            openModal(deleteModalOverlay);
        }
    };

    // Guardar (Submit Formulario)
    alumnoForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = inputAlumnoId.value.trim();
        const nombre = inputNombre.value.trim();
        const carrera = inputCarrera.value.trim();

        // Validaciones en cliente
        let hasErrors = false;
        clearValidationErrors();

        if (nombre.length < 3) {
            showInputError(inputNombre, 'El nombre debe tener al menos 3 caracteres.');
            hasErrors = true;
        }

        if (carrera.length < 3) {
            showInputError(inputCarrera, 'La carrera debe tener al menos 3 caracteres.');
            hasErrors = true;
        }

        if (hasErrors) {
            Toast.show('Por favor corrige los campos señalados.', 'warning', 'Validación');
            return;
        }

        const isEditing = Boolean(id);
        const payload = {
            id: isEditing ? Number(id) : undefined,
            nombre,
            carrera
        };

        btnSaveAlumno.disabled = true;
        btnSaveAlumno.textContent = 'Guardando...';

        try {
            const response = await fetch('api/alumnos.php', {
                method: isEditing ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Error al procesar la solicitud.');
            }

            Toast.show(result.message, 'success');
            closeModal(alumnoModalOverlay);
            await loadAlumnos();
        } catch (error) {
            Toast.show(error.message, 'error', 'Error al Guardar');
        } finally {
            btnSaveAlumno.disabled = false;
            btnSaveAlumno.textContent = 'Guardar Alumno';
        }
    });

    /* ==========================================================================
       Eliminación de Registro
       ========================================================================== */
    btnConfirmDelete.addEventListener('click', async () => {
        if (!alumnoToDelete) return;

        btnConfirmDelete.disabled = true;
        btnConfirmDelete.textContent = 'Eliminando...';

        try {
            const response = await fetch(`api/alumnos.php?id=${alumnoToDelete.id}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'No se pudo eliminar el registro.');
            }

            Toast.show(result.message, 'success', 'Eliminado');
            closeModal(deleteModalOverlay);
            alumnoToDelete = null;
            await loadAlumnos();
        } catch (error) {
            Toast.show(error.message, 'error', 'Error al Eliminar');
        } finally {
            btnConfirmDelete.disabled = false;
            btnConfirmDelete.textContent = 'Sí, eliminar';
        }
    });

    /* ==========================================================================
       Funciones Utilitarias
       ========================================================================== */
    function showInputError(inputEl, message) {
        inputEl.classList.add('is-invalid');
        const feedback = inputEl.nextElementSibling;
        if (feedback && feedback.classList.contains('form-error-feedback')) {
            feedback.textContent = message;
        }
    }

    function clearValidationErrors() {
        document.querySelectorAll('.form-control').forEach(el => {
            el.classList.remove('is-invalid');
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttr(str) {
        if (!str) return '';
        return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    // Carga inicial
    loadAlumnos();
});
