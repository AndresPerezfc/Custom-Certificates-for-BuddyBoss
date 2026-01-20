# Inicio Rápido - Custom Certificates

Esta guía te ayudará a tener tu primer certificado funcionando en **menos de 5 minutos**.

## Paso 1: Instalar Dependencias (1 minuto)

```bash
cd wp-content/plugins/custom-certificates
composer install --no-dev
```

## Paso 2: Activar el Plugin (30 segundos)

1. Ve a **Plugins** en WordPress
2. Busca "Custom Certificates for BuddyBoss"
3. Haz clic en **Activar**

## Paso 3: Crear una Plantilla (2 minutos)

1. Ve a **Certificados > Añadir nueva**
2. **Título**: "Mi Primer Certificado"
3. **Imagen destacada**: Sube una imagen de fondo (opcional pero recomendado)
   - Tamaño ideal: 297mm x 210mm (A4 horizontal)
   - Formato: JPG o PNG
4. **Configuración**:
   - Color de texto: Negro (#000000)
   - Color de fondo: Blanco (#ffffff)
   - Tamaño de fuente: 24px
5. Haz clic en **Publicar**

## Paso 4: Asignar a un Usuario (1 minuto)

1. Ve a **Certificados > Asignar Certificados**
2. **Plantilla**: Selecciona "Mi Primer Certificado"
3. **Usuarios**: Busca tu usuario (escribe tu nombre o email)
4. **Descripción** (opcional): "Por completar el tutorial de certificados"
5. Haz clic en **Asignar Certificado(s)**

## Paso 5: Ver y Descargar (30 segundos)

1. Ve a tu **Perfil** en BuddyBoss
2. Haz clic en la pestaña **Certificados**
3. Verás tu certificado listado
4. Haz clic en **Descargar PDF**
5. ¡Tu certificado se descargará! 🎉

---

## Casos de Uso Comunes

### Caso 1: Asignar el Mismo Certificado a Varios Usuarios

```
Certificados > Asignar Certificados
→ Selecciona plantilla
→ Busca y selecciona múltiples usuarios (Ctrl+Click)
→ Asignar
```

### Caso 2: Crear Diferentes Tipos de Certificados

```
1. Certificados > Añadir nueva → "Competencias Digitales"
2. Certificados > Añadir nueva → "Liderazgo"
3. Certificados > Añadir nueva → "Innovación"
```

Luego asigna según corresponda.

### Caso 3: Ver Todos los Certificados Asignados

```
Certificados > Certificados Asignados
```

Aquí puedes:
- Ver todos los certificados
- Filtrar por usuario
- Descargar PDFs
- Eliminar asignaciones

### Caso 4: Activar Notificaciones por Email

```
Certificados > Configuración
→ ✅ Habilitar Notificaciones
→ Personalizar mensaje
→ Guardar
```

Ahora los usuarios recibirán un email cuando reciban un certificado.

---

## Personalización Básica

### Cambiar Colores del Certificado

```
Editar plantilla → Configuración del Certificado
→ Color de Texto: #1a1a1a
→ Color de Fondo: #f5f5f5
→ Actualizar
```

### Usar una Imagen de Fondo

```
Editar plantilla → Imagen destacada
→ Establecer imagen destacada
→ Subir imagen (297mm x 210mm recomendado)
→ Actualizar
```

### Personalizar el Mensaje del Certificado

Edita el archivo:
```
wp-content/plugins/custom-certificates/public/templates/certificate-pdf.php
```

O mejor aún, cópialo a tu tema:
```
wp-content/themes/tu-tema/custom-certificates/certificate-pdf.php
```

---

## Funciones Útiles para Desarrolladores

### Asignar certificado programáticamente:

```php
$certificate_id = custom_cert_assign_certificate(
    123, // User ID
    456, // Template ID
    array('description' => 'Por completar el curso')
);
```

### Verificar si un usuario tiene un certificado:

```php
if (custom_cert_user_has_certificate(123, 456)) {
    echo 'El usuario tiene el certificado';
}
```

### Obtener certificados de un usuario:

```php
$certificates = custom_cert_get_user_certificates(123);
foreach ($certificates as $cert) {
    echo $cert->post_title;
}
```

### Obtener URL de descarga:

```php
$url = custom_cert_get_download_url($certificate_id);
echo '<a href="' . $url . '">Descargar</a>';
```

---

## Solución Rápida de Problemas

| Problema | Solución Rápida |
|----------|----------------|
| "mPDF no instalado" | `composer install --no-dev` |
| Pestaña no aparece | Configuración > Enlaces permanentes > Guardar |
| PDF no descarga | Verificar carpeta `vendor` existe |
| No veo certificados | Verificar que esté publicado, no borrador |

---

## Siguientes Pasos

✅ **Ya tienes tu primer certificado funcionando**

Ahora puedes:

1. **Crear más plantillas** para diferentes tipos de certificados
2. **Personalizar el diseño** del PDF
3. **Asignar certificados masivamente** a tus usuarios
4. **Configurar notificaciones** personalizadas
5. **Leer la documentación completa** en README.md

---

## Recursos Adicionales

- 📖 [README.md](README.md) - Documentación completa
- 🔧 [INSTALACION.md](INSTALACION.md) - Guía detallada de instalación
- 📋 [PLAN-CERTIFICADOS-PERSONALIZADOS.md](PLAN-CERTIFICADOS-PERSONALIZADOS.md) - Plan de desarrollo
- 📝 [CHANGELOG.md](CHANGELOG.md) - Historial de cambios

---

**¿Problemas?** Revisa los logs de WordPress en `/wp-content/debug.log`

**¿Sugerencias?** Contacta con el desarrollador

**¡Disfruta asignando certificados!** 🎓✨
