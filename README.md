# Sistema CRUD de Alumnos (PHP, Nginx & MySQL)

**Servidores 2 - Práctica 1 Unidad 2**  
Sistema CRUD con interfaz interactiva moderna (modales y notificaciones toast), arquitectura de API REST en PHP, servidor web Nginx con PHP-FPM y despliegue automatizado mediante Git hooks.

---

## 📖 Documentación Completa

Para consultar la **cronología de prompts utilizados**, la **arquitectura del sistema**, los **pasos detallados para levantar el servidor** y la **demostración de seguridad de privilegios**, consulta el archivo:

👉 **[DOCUMENTACION.md](DOCUMENTACION.md)**

---

## ⚡ Guía Rápida de Despliegue

1. Configurar la base de datos MySQL con el usuario `app_web` (con permisos `SELECT` e `INSERT`).
2. Configurar Nginx apuntando el `root` a `/var/www/crud` con `fastcgi_pass` hacia PHP-FPM.
3. Desplegar los cambios mediante Git:
   ```bash
   git push produccion main
   ```
4. Acceder en el navegador:
   ```text
   http://192.168.122.160/
   ```
