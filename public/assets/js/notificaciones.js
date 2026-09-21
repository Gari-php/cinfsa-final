// notificaciones-simple.js
class Notificaciones {
    constructor() {
        this.pusher = null;
        this.canal = null;
        this.isOpen = false;
        this.init();
    }

    init() {
        this.setupDOM();
        this.setupPusher();
        this.loadNotifications();
        this.setupAutoRefresh();
    }

    setupDOM() {
        const toggle = document.getElementById('notification-toggle');
        const dropdown = document.getElementById('notifications-dropdown');
        const markAllBtn = document.getElementById('mark-all-read');

        // Toggle dropdown
        toggle?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });

        // Marcar todas como leídas
        markAllBtn?.addEventListener('click', () => {
            this.markAllAsRead();
        });

        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.notifications-container')) {
                this.closeDropdown();
            }
        });
    }

    setupPusher() {
        if (!window.USUARIO_DATOS?.id || !window.Pusher) return;

        try {
            this.pusher = new Pusher('195cfd5239428846d64e', {
                cluster: 'us2',
                encrypted: true
            });

            // Canal personal del usuario
            this.canal = this.pusher.subscribe(`usuario-${window.USUARIO_DATOS.id}`);
            this.canal.bind('nueva-notificacion', (data) => {
                this.handleNewNotification(data);
            });

            // Canal de admins si es administrador
            if (window.USUARIO_DATOS.perfil === 3) {
                const canalAdmin = this.pusher.subscribe('admin-notifications');
                canalAdmin.bind('nueva-notificacion', (data) => {
                    this.handleNewNotification(data);
                });
            }

            console.log('✅ Notificaciones en tiempo real activas');
        } catch (error) {
            console.warn('⚠️ Error al conectar notificaciones tiempo real:', error);
        }
    }

    async loadNotifications() {
        try {
            const response = await fetch('/api/notificaciones/obtener?limite=10');
            const data = await response.json();

            if (data.ok) {
                this.renderNotifications(data.notificaciones);
                this.updateBadge(data.total_no_leidas);
            }
        } catch (error) {
            console.error('Error al cargar notificaciones:', error);
            this.showError();
        }
    }

    renderNotifications(notifications) {
        const container = document.getElementById('notifications-list');
        if (!container) return;

        if (!notifications || notifications.length === 0) {
            container.innerHTML = `
                <div class="no-notifications">
                    <i class="fa-solid fa-bell-slash"></i>
                    <p>No hay notificaciones</p>
                </div>
            `;
            return;
        }

        const html = notifications.map(notif => this.createNotificationHTML(notif)).join('');

        // Agregar botón "Ver Todas" al final
        const verTodasBtn = `
            <div class="ver-todas-container">
                <a href="/administrador/notificaciones" class="btn-ver-todas">
                    <i class="fa-solid fa-list"></i>
                    Ver Todas las Notificaciones
                </a>
            </div>
        `;

        container.innerHTML = html + verTodasBtn;

        // Agregar eventos de click para marcar como leídas
        container.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', () => {
                const id = item.dataset.id;
                const isUnread = item.classList.contains('unread');

                if (isUnread) {
                    this.markAsRead(id);
                }
            });
        });
    }

    createNotificationHTML(notif) {
        const iconMap = {
            'registro_usuario': 'fa-user-plus',
            'stock_bajo': 'fa-exclamation-triangle',
            'nueva_venta': 'fa-shopping-cart',
            'soporte': 'fa-headset',
            'contacto': 'fa-envelope',
            'sistema': 'fa-info-circle'
        };

        const iconClassMap = {
            'registro_usuario': 'icon-usuario',
            'stock_bajo': 'icon-stock',
            'nueva_venta': 'icon-venta',
            'soporte': 'icon-sistema',
            'contacto': 'icon-sistema',
            'sistema': 'icon-sistema'
        };

        const icon = iconMap[notif.tipo] || 'fa-bell';
        const iconClass = iconClassMap[notif.tipo] || 'icon-sistema';
        const timeAgo = this.timeAgo(notif.fecha_creacion);
        const isUnread = notif.leido == 0;

        return `
        <div class="notification-item ${isUnread ? 'unread' : ''}" data-id="${notif.id_notificacion}">
            <div class="notification-content">
                <div class="notification-icon ${iconClass}">
                    <i class="fa-solid ${icon}"></i>
                </div>
                <div class="notification-text">
                    <div class="notification-title">${this.escapeHtml(notif.titulo)}</div>
                    <div class="notification-desc">${this.formatearDescripcion(notif.descripcion)}</div>
                    <div class="notification-time">${timeAgo}</div>
                </div>
            </div>
        </div>
    `;
    }

    formatearDescripcion(texto) {
        const escapado = this.escapeHtml(texto);
        // Convierte rutas de adjuntos (/uploads/...) en un link clickeable
        return escapado.replace(/\/uploads\/\S+/g, (ruta) => {
            return `<a href="${ruta}" target="_blank" rel="noopener" class="notification-adjunto" onclick="event.stopPropagation()">📎 Ver adjunto</a>`;
        });
    }

    handleNewNotification(data) {
        console.log('📢 Nueva notificación:', data);

        // Actualizar contador inmediatamente
        this.updateBadgeCount(1);

        // Mostrar toast
        this.showToast(data);

        // Recargar notificaciones si el dropdown está abierto
        if (this.isOpen) {
            setTimeout(() => this.loadNotifications(), 1000);
        }

        // Sonido de notificación
        this.playNotificationSound();
    }

    showToast(data) {
        // Remover toast anterior si existe
        const existingToast = document.querySelector('.notification-toast');
        if (existingToast) existingToast.remove();

        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${this.escapeHtml(data.titulo)}</div>
                <div class="toast-message">${this.escapeHtml(data.descripcion)}</div>
            </div>
            <button class="toast-close">
                <i class="fa-solid fa-times"></i>
            </button>
        `;

        // Estilos del toast
        this.addToastStyles();

        document.body.appendChild(toast);

        // Cerrar toast
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.remove();
        });

        // Auto cerrar
        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    }

    addToastStyles() {
        if (document.getElementById('toast-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'toast-styles';
        styles.textContent = `
            .notification-toast {
                position: fixed;
                top: 120px;
                right: 20px;
                background: white;
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                padding: 15px;
                max-width: 350px;
                z-index: 10000;
                display: flex;
                align-items: flex-start;
                gap: 12px;
                animation: slideInToast 0.3s ease-out;
                border-left: 4px solid #ed850f;
            }
            .notification-toast.fade-out {
                animation: slideOutToast 0.3s ease-in;
            }
            @keyframes slideInToast {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutToast {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .toast-icon {
                width: 35px;
                height: 35px;
                background: #ed850f;
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .toast-content {
                flex: 1;
            }
            .toast-title {
                font-weight: bold;
                margin-bottom: 5px;
                color: #333;
                font-size: 14px !important;
            }
            .toast-message {
                color: #666;
                font-size: 13px !important;
                line-height: 1.4;
            }
            .toast-close {
                background: none;
                border: none;
                color: #999;
                cursor: pointer;
                padding: 5px;
                border-radius: 50%;
                width: 25px;
                height: 25px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .toast-close:hover {
                background: #f0f0f0;
                color: #333;
            }
        `;
        document.head.appendChild(styles);
    }

    playNotificationSound() {
        try {
            const audio = new Audio();
            audio.src = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmUZBTmS2O+8diMgGG22+t2QQAkQXK/r56rS0WLo+3YwlkgBJHeL';
            audio.volume = 0.3;
            audio.play().catch(() => { });
        } catch (error) {
            // Error silencioso
        }
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('/api/notificaciones/marcar-leida', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_notificacion: notificationId })
            });

            const data = await response.json();

            if (data.ok) {
                // Remover la notificación del dropdown 
                const item = document.querySelector(`[data-id="${notificationId}"]`);
                if (item) {
                    item.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(100%)';

                    setTimeout(() => {
                        item.remove();

                        // Si no quedan notificaciones, mostrar mensaje vacío
                        const container = document.getElementById('notifications-list');
                        const remainingNotifs = container.querySelectorAll('.notification-item');

                        if (remainingNotifs.length === 0) {
                            const verTodasContainer = container.querySelector('.ver-todas-container');
                            container.innerHTML = `
                                <div class="no-notifications">
                                    <i class="fa-solid fa-bell-slash"></i>
                                    <p>No hay notificaciones</p>
                                </div>
                            `;
                            if (verTodasContainer) {
                                container.appendChild(verTodasContainer);
                            }
                        }
                    }, 300);
                }

                this.updateBadge(data.nuevo_contador);
            }
        } catch (error) {
            console.error('Error al marcar como leída:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/api/notificaciones/marcar-todas-leidas', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const data = await response.json();

            if (data.ok) {
                // Limpiar todas las notificaciones del dropdown
                const container = document.getElementById('notifications-list');
                if (container) {
                    container.innerHTML = `
                        <div class="no-notifications">
                            <i class="fa-solid fa-bell-slash"></i>
                            <p>No hay notificaciones</p>
                        </div>
                        <div class="ver-todas-container">
                            <a href="/administrador/notificaciones" class="btn-ver-todas">
                                <i class="fa-solid fa-list"></i>
                                Ver Todas las Notificaciones
                            </a>
                        </div>
                    `;
                }

                this.updateBadge(0);
                this.showMessage('Todas las notificaciones marcadas como leídas');
            }
        } catch (error) {
            console.error('Error al marcar todas como leídas:', error);
        }
    }

    toggleDropdown() {
        const dropdown = document.getElementById('notifications-dropdown');
        if (!dropdown) return;

        this.isOpen = !this.isOpen;
        dropdown.classList.toggle('show', this.isOpen);

        if (this.isOpen) {
            this.loadNotifications();
        }
    }

    closeDropdown() {
        const dropdown = document.getElementById('notifications-dropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
            this.isOpen = false;
        }
    }

    updateBadge(count) {
        const badge = document.getElementById('notification-count');

        if (count > 0) {
            if (badge) {
                badge.textContent = count;
                badge.style.display = 'block';
            } else {
                // Crear badge si no existe
                const btn = document.getElementById('notification-toggle');
                if (btn) {
                    const newBadge = document.createElement('span');
                    newBadge.id = 'notification-count';
                    newBadge.className = 'notification-badge';
                    newBadge.textContent = count;
                    btn.appendChild(newBadge);
                }
            }
        } else {
            if (badge) {
                badge.style.display = 'none';
            }
        }
    }

    updateBadgeCount(increment) {
        const badge = document.getElementById('notification-count');
        let currentCount = 0;

        if (badge && badge.style.display !== 'none') {
            currentCount = parseInt(badge.textContent) || 0;
        }

        this.updateBadge(currentCount + increment);
    }

    showError() {
        const container = document.getElementById('notifications-list');
        if (container) {
            container.innerHTML = `
                <div class="loading-notifications">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    Error al cargar notificaciones
                </div>
            `;
        }
    }

    showMessage(message) {
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">Notificación</div>
                <div class="toast-message">${message}</div>
            </div>
        `;

        this.addToastStyles();
        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 300);
            }
        }, 3000);
    }

    setupAutoRefresh() {
        // Refrescar contador cada 30 segundos
        setInterval(() => {
            if (!this.isOpen) {
                this.updateNotificationCount();
            }
        }, 30000);
    }

    async updateNotificationCount() {
        try {
            const response = await fetch('/api/notificaciones/contar');
            const data = await response.json();

            if (data.ok) {
                this.updateBadge(data.contador);
            }
        } catch (error) {
            console.error('Error al actualizar contador:', error);
        }
    }

    timeAgo(dateString) {
        const now = new Date();
        const date = new Date(dateString);
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'Hace un momento';
        if (diffInSeconds < 3600) return `Hace ${Math.floor(diffInSeconds / 60)} min`;
        if (diffInSeconds < 86400) return `Hace ${Math.floor(diffInSeconds / 3600)} h`;
        return `Hace ${Math.floor(diffInSeconds / 86400)} días`;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    window.notificaciones = new Notificaciones();
    console.log('🔔 Sistema de notificaciones inicializado');
});

// API para uso desde otros scripts
window.NotificacionesAPI = {
    // Notificar nuevo usuario (llamar desde controlador de registro)
    notificarNuevoUsuario: function (nombreUsuario) {
        if (window.notificaciones) {
            const data = {
                titulo: 'Nuevo Usuario Registrado',
                descripcion: `Se registró: ${nombreUsuario}`,
                tipo: 'registro_usuario'
            };
            window.notificaciones.handleNewNotification(data);
        }
    },

    // Notificar stock bajo
    notificarStockBajo: function (producto, cantidad, cantina) {
        if (window.notificaciones) {
            const data = {
                titulo: 'Stock Bajo',
                descripcion: `${producto} en ${cantina}: solo ${cantidad} unidades`,
                tipo: 'stock_bajo'
            };
            window.notificaciones.handleNewNotification(data);
        }
    },

    // Notificar nueva venta
    notificarNuevaVenta: function (total, usuario) {
        if (window.notificaciones) {
            const data = {
                titulo: 'Nueva Venta',
                descripcion: `Venta de $${total} por ${usuario}`,
                tipo: 'nueva_venta'
            };
            window.notificaciones.handleNewNotification(data);
        }
    }
};