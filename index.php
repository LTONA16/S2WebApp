<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Alumnos | CRUD PHP & MySQL</title>
    <meta name="description" content="Sistema CRUD para gestión de alumnos con PHP-FPM, Nginx, MySQL, modales y notificaciones toast.">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Estilos de la aplicación -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <header class="header">
            <div class="brand">
                <div class="brand-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path>
                        <path d="M6 6h10"></path>
                        <path d="M6 10h10"></path>
                    </svg>
                </div>
                <div class="brand-info">
                    <h1>Portal de Alumnos</h1>
                    <p>Servidores 2 &bull; Práctica 1 Unidad 2</p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="badge-status">
                    <span class="status-dot"></span>
                    Servidor Activo (PHP-FPM)
                </span>
                <span id="totalCount" class="badge-id">Cargando...</span>
            </div>
        </header>

        <!-- Barra de Acciones y Búsqueda -->
        <section class="action-card">
            <div class="search-box">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="searchInput" class="search-input" placeholder="Buscar por nombre, carrera o ID..." autocomplete="off">
            </div>

            <button type="button" id="btnOpenCreate" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Nuevo Alumno
            </button>
        </section>

        <!-- Tabla de Registros -->
        <main class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 100px;">ID</th>
                            <th>Nombre Completo</th>
                            <th>Carrera</th>
                            <th style="width: 130px; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="alumnosTableBody">
                        <tr>
                            <td colspan="4" class="empty-state">
                                <p>Cargando registros...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- ==========================================================================
         Modal para Crear y Editar Alumno
         ========================================================================== -->
    <div id="alumnoModalOverlay" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="alumnoModalTitle">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span id="alumnoModalTitle">Nuevo Alumno</span>
                </div>
                <button type="button" id="btnCloseAlumnoModal" class="modal-close" aria-label="Cerrar modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <form id="alumnoForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="alumnoId" name="id">

                    <div class="form-group">
                        <label for="alumnoNombre" class="form-label">Nombre Completo *</label>
                        <input type="text" id="alumnoNombre" name="nombre" class="form-control" placeholder="Ej. Juan Pérez" required>
                        <div class="form-error-feedback">El nombre es requerido y debe tener al menos 3 caracteres.</div>
                    </div>

                    <div class="form-group">
                        <label for="alumnoCarrera" class="form-label">Carrera Universitaria *</label>
                        <input type="text" id="alumnoCarrera" name="carrera" class="form-control" placeholder="Ej. Licenciatura en Ciencias de la Computación" required>
                        <div class="form-error-feedback">La carrera es requerida y debe tener al menos 3 caracteres.</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btnCancelAlumnoModal" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" id="btnSaveAlumno" class="btn btn-primary">Guardar Alumno</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==========================================================================
         Modal de Confirmación para Eliminar
         ========================================================================== -->
    <div id="deleteModalOverlay" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title" style="color: var(--danger);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span id="deleteModalTitle">Confirmar Eliminación</span>
                </div>
                <button type="button" id="btnCloseDeleteModal" class="modal-close" aria-label="Cerrar modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                <div class="delete-icon-box">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </div>
                <p class="delete-text">
                    ¿Estás seguro de que deseas eliminar permanentemente al alumno?
                    <span id="deleteAlumnoName" class="delete-highlight">"..."</span>
                    Esta acción no se puede deshacer.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" id="btnCancelDeleteModal" class="btn btn-secondary">Cancelar</button>
                <button type="button" id="btnConfirmDelete" class="btn btn-danger">Sí, eliminar</button>
            </div>
        </div>
    </div>

    <!-- ==========================================================================
         Contenedor de Toasts
         ========================================================================== -->
    <div id="toastContainer" class="toast-container" aria-live="polite" aria-atomic="true"></div>

    <!-- Script Principal -->
    <script src="assets/js/app.js"></script>
</body>
</html>
