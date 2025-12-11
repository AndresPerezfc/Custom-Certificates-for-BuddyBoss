# ¿Dónde Está el Menú del Plugin?

## 📍 Ubicación del Menú

El menú del plugin debería aparecer en el **panel lateral izquierdo** de WordPress Admin con:

- **Nombre:** "Certificados"
- **Icono:** 🏆 (medalla/trofeo - `dashicons-awards`)
- **Posición:** Después de "Comentarios", antes de "Apariencia"

### Estructura del Menú:

```
WordPress Admin (Panel Izquierdo)
│
├── Escritorio
├── Actualizaciones
├── Entradas
├── Medios
├── Páginas
├── Comentarios
│
├── 🏆 Certificados  ← AQUÍ DEBERÍA ESTAR
│   ├── Plantillas de Certificados
│   ├── Añadir nueva
│   ├── Certificados Asignados
│   ├── Asignar Certificados
│   └── Configuración
│
├── Apariencia
├── Plugins
└── ...
```

---

## 🔍 ¿No Ves el Menú?

### Solución 1: Actualizar Enlaces Permanentes (MÁS COMÚN)

Esto es lo primero que debes hacer:

1. Ve a **Ajustes > Enlaces permanentes**
2. **NO cambies nada**
3. Simplemente haz clic en **Guardar cambios**
4. Recarga la página del admin (F5)
5. El menú "Certificados" debería aparecer ahora

**¿Por qué funciona?**
WordPress necesita actualizar sus rutas internas cuando se registran nuevos post types.

---

### Solución 2: Verificar Permisos de Usuario

El menú SOLO es visible para **Administradores**.

**Verifica:**
1. Ve a **Usuarios > Tu perfil**
2. Mira en "Rol": ¿Dice "Administrador"?
3. Si dice "Editor", "Autor", etc., NO verás el menú

**Solución:**
- Inicia sesión con una cuenta de Administrador
- O pide a un administrador que te cambie el rol

---

### Solución 3: Desactivar y Reactivar el Plugin

A veces WordPress necesita "reiniciar":

1. Ve a **Plugins**
2. Busca "Custom Certificates for BuddyBoss"
3. Click en **Desactivar**
4. Espera 2 segundos
5. Click en **Activar**
6. Ve a **Ajustes > Enlaces permanentes > Guardar cambios**
7. Recarga la página (F5)

---

### Solución 4: Limpiar Caché

Si usas un plugin de caché:

**WP Rocket:**
1. WP Rocket > Clear cache

**W3 Total Cache:**
1. Performance > Dashboard > Empty all caches

**WP Super Cache:**
1. Settings > WP Super Cache > Delete cache

Luego recarga el admin.

---

### Solución 5: Verificar que el Plugin Está Activo

1. Ve a **Plugins**
2. Busca "Custom Certificates for BuddyBoss"
3. Debería decir **"Desactivar"** (no "Activar")
4. Si dice "Activar", el plugin NO está activo

---

## 🛠️ Acceso Directo (Si el Menú No Aparece)

Puedes acceder directamente usando estas URLs:

### Ver Plantillas de Certificados:
```
https://tudominio.com/wp-admin/edit.php?post_type=bb_cert_template
```

### Añadir Nueva Plantilla:
```
https://tudominio.com/wp-admin/post-new.php?post_type=bb_cert_template
```

### Ver Certificados Asignados:
```
https://tudominio.com/wp-admin/edit.php?post_type=bb_cert_assigned
```

### Asignar Certificados:
```
https://tudominio.com/wp-admin/edit.php?post_type=bb_cert_template&page=assign-certificates
```

### Configuración:
```
https://tudominio.com/wp-admin/edit.php?post_type=bb_cert_template&page=cert-settings
```

### Ver Estado de Dependencias:
```
https://tudominio.com/wp-admin/admin.php?page=cert-install-dependencies
```

**Reemplaza `tudominio.com` con tu dominio real.**

---

## 🔧 Diagnóstico Técnico

Si aún no ves el menú después de todo lo anterior, verifica:

### 1. Verificar Errores de WordPress

Agregar a `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Luego revisar:
```
wp-content/debug.log
```

Buscar errores relacionados con "custom-certificates" o "bb_cert_template"

### 2. Verificar que el Post Type se Registró

En tu navegador, ve a:
```
https://tudominio.com/wp-admin/edit.php?post_type=bb_cert_template
```

**¿Qué debería pasar?**
- ✅ **Funciona:** Te muestra la lista de plantillas (aunque esté vacía)
- ❌ **Error 404 o "No tienes permisos":** El post type NO se registró

### 3. Verificar Conflictos con Otros Plugins

1. Desactiva TODOS los plugins excepto Custom Certificates
2. ¿Aparece el menú?
   - **SÍ:** Hay conflicto con otro plugin. Actívalos uno por uno para encontrar cuál
   - **NO:** El problema es del plugin o de WordPress

---

## 📸 Capturas de Referencia

### Así se ve el menú correctamente:

```
┌─────────────────────────────┐
│ 🏠 Escritorio               │
│ 🔄 Actualizaciones          │
│ 📝 Entradas                 │
│ 🖼️  Medios                  │
│ 📄 Páginas                  │
│ 💬 Comentarios              │
│                             │
│ 🏆 Certificados         ◀── AQUÍ
│    ├─ Plantillas           │
│    ├─ Añadir nueva         │
│    ├─ Cert. Asignados      │
│    ├─ Asignar Certificados │
│    └─ Configuración        │
│                             │
│ 🎨 Apariencia               │
│ 🔌 Plugins                  │
│ 👥 Usuarios                 │
└─────────────────────────────┘
```

---

## ✅ Checklist de Verificación

Marca lo que has hecho:

- [ ] Plugin está activado (Plugins > Custom Certificates > "Desactivar" visible)
- [ ] Eres Administrador (Usuarios > Tu perfil > Rol: Administrador)
- [ ] Enlaces permanentes guardados (Ajustes > Enlaces permanentes > Guardar)
- [ ] Caché limpiada (si usas plugin de caché)
- [ ] Página recargada (F5 o Ctrl+F5)
- [ ] Probado acceso directo: `/wp-admin/edit.php?post_type=bb_cert_template`

---

## 🆘 Si Nada Funciona

**Opción 1: Usar Enlaces Directos**

Guarda estos enlaces en marcadores:

- Plantillas: `wp-admin/edit.php?post_type=bb_cert_template`
- Añadir: `wp-admin/post-new.php?post_type=bb_cert_template`
- Asignar: `wp-admin/edit.php?post_type=bb_cert_template&page=assign-certificates`

**Opción 2: Reportar el Problema**

Proporciona:
1. ¿Eres administrador? (Sí/No)
2. ¿Al ir a `edit.php?post_type=bb_cert_template` qué pasa?
3. ¿Hay errores en wp-content/debug.log?
4. ¿Qué otros plugins tienes activos?
5. Versión de WordPress:
6. Versión de PHP:

---

## 🎯 Inicio Rápido (Cuando Veas el Menú)

Una vez que veas el menú "Certificados":

### 1. Crear Primera Plantilla
```
Certificados > Añadir nueva
├─ Título: "Certificado de Competencias Digitales"
├─ Imagen destacada: [Subir imagen de fondo]
├─ Configuración del Certificado:
│  ├─ Color de texto: #000000
│  ├─ Color de fondo: #ffffff
│  └─ Tamaño de fuente: 24
└─ Publicar
```

### 2. Asignar a un Usuario
```
Certificados > Asignar Certificados
├─ Plantilla: Seleccionar tu plantilla
├─ Usuarios: Buscar y seleccionar
├─ Descripción: (opcional)
└─ Asignar Certificado(s)
```

### 3. Ver en Perfil
```
Perfil del usuario > Certificados > Mis Certificados
└─ Descargar PDF
```

---

**Última actualización:** 2024-12-10
