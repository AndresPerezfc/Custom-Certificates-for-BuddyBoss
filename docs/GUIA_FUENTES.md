# Guia de Fuentes para Certificados PDF

Esta guia explica como utilizar las diferentes fuentes y pesos tipograficos disponibles en el plugin Custom Certificates.

## Fuentes Disponibles

El plugin incluye las siguientes fuentes de Google Fonts:

| Fuente | Nombre CSS | Descripcion |
|--------|------------|-------------|
| DejaVu Sans | `dejavusans` | Fuente por defecto, amplio soporte de caracteres |
| Montserrat | `montserrat` | Moderna, geometrica, ideal para titulos |
| Open Sans | `opensans` | Legible, versatil, excelente para cuerpo de texto |
| Roboto | `roboto` | Equilibrada, profesional |
| Lato | `lato` | Elegante, semi-redondeada |
| Helvetica | `helvetica` | Clasica, profesional (fuente del sistema) |

---

## Seleccionar Fuente en la Plantilla

1. Ve a **Certificados > Plantillas**
2. Edita o crea una plantilla
3. En el panel lateral derecho, busca **Configuracion del Certificado**
4. Selecciona la fuente deseada en el dropdown "Fuente"
5. Guarda la plantilla

La fuente seleccionada sera la fuente base para todo el certificado.

---

## Pesos Tipograficos (Font Weight)

### Pesos Estandar (Funcionan con font-weight)

Los siguientes pesos funcionan normalmente con la propiedad `font-weight`:

| Peso | Valor CSS | Ejemplo |
|------|-----------|---------|
| Normal | `font-weight: normal;` o `font-weight: 400;` | Texto regular |
| Bold | `font-weight: bold;` o `font-weight: 700;` | **Texto en negrita** |

**Ejemplo:**
```html
<p style="font-family: montserrat; font-weight: normal;">Texto normal</p>
<p style="font-family: montserrat; font-weight: bold;">Texto en negrita</p>
```

### Pesos Extra (Black / ExtraBold - 900)

> **IMPORTANTE:** mPDF no soporta `font-weight: 900` directamente. Para usar pesos extra-gruesos, debes usar la familia de fuente Black/ExtraBold especifica.

#### Familias Black/ExtraBold Disponibles

| Fuente Base | Familia Black | Uso |
|-------------|---------------|-----|
| Montserrat | `montserratblack` | Peso 900 (Black) |
| Montserrat | `montserratextrabold` | Peso 800 (ExtraBold) |
| Open Sans | `opensansextrabold` | Peso 800 (ExtraBold) |
| Roboto | `robotoblack` | Peso 900 (Black) |
| Lato | `latoblack` | Peso 900 (Black) |

#### Forma Correcta vs Incorrecta

**INCORRECTO** (no funcionara):
```html
<h1 style="font-family: montserrat; font-weight: 900;">TITULO</h1>
```

**CORRECTO** (usar familia Black directamente):
```html
<h1 style="font-family: montserratblack;">TITULO</h1>
```

---

## Ejemplos Practicos

### Titulo Principal con Montserrat Black

```html
<div style="text-align: center;">
    <h1 style="font-family: montserratblack; font-size: 48px; color: #3fb6e8; margin: 0;">
        CERTIFICADO
    </h1>
</div>
```

### Subtitulo con Open Sans ExtraBold

```html
<h2 style="font-family: opensansextrabold; font-size: 24px; color: #333;">
    DE RECONOCIMIENTO
</h2>
```

### Combinacion de Pesos en un Certificado

```html
<!-- Titulo principal - Peso 900 -->
<div style="text-align: center; margin-bottom: 20px;">
    <h1 style="font-family: montserratblack; font-size: 42px; color: #2c3e50;">
        CERTIFICADO
    </h1>
</div>

<!-- Subtitulo - Peso 700 (Bold) -->
<div style="text-align: center; margin-bottom: 30px;">
    <h2 style="font-family: montserrat; font-weight: bold; font-size: 24px; color: #7f8c8d;">
        DE PARTICIPACION
    </h2>
</div>

<!-- Nombre del usuario - Peso 900 -->
<div style="text-align: center; margin: 40px 0;">
    <p style="font-family: montserratblack; font-size: 36px; color: #2980b9;">
        {NOMBRE_USUARIO}
    </p>
</div>

<!-- Texto descriptivo - Peso 400 (Normal) -->
<div style="text-align: center; margin: 20px 50px;">
    <p style="font-family: montserrat; font-size: 14px; line-height: 1.6; color: #555;">
        Por su destacada participacion en el evento Innovafest B10 5.0
        en la categoria {CATEGORIA} - {SUBCATEGORIA}
    </p>
</div>

<!-- Fecha - Peso 400 -->
<div style="text-align: center; margin-top: 40px;">
    <p style="font-family: montserrat; font-size: 12px; color: #777;">
        Emitido el {FECHA_EMISION}
    </p>
</div>
```

### Ejemplo con Roboto

```html
<!-- Usando diferentes pesos de Roboto -->
<h1 style="font-family: robotoblack; font-size: 40px;">DIPLOMA</h1>
<p style="font-family: roboto; font-weight: bold;">Texto en negrita</p>
<p style="font-family: roboto;">Texto normal</p>
```

### Ejemplo con Lato

```html
<!-- Usando diferentes pesos de Lato -->
<h1 style="font-family: latoblack; font-size: 38px;">RECONOCIMIENTO</h1>
<p style="font-family: lato; font-weight: bold;">Subtitulo importante</p>
<p style="font-family: lato;">Descripcion del certificado</p>
```

---

## Tabla de Referencia Rapida

| Quiero... | Usar |
|-----------|------|
| Montserrat normal | `font-family: montserrat;` |
| Montserrat bold | `font-family: montserrat; font-weight: bold;` |
| Montserrat black (900) | `font-family: montserratblack;` |
| Montserrat extrabold (800) | `font-family: montserratextrabold;` |
| Open Sans normal | `font-family: opensans;` |
| Open Sans bold | `font-family: opensans; font-weight: bold;` |
| Open Sans extrabold (800) | `font-family: opensansextrabold;` |
| Roboto normal | `font-family: roboto;` |
| Roboto bold | `font-family: roboto; font-weight: bold;` |
| Roboto black (900) | `font-family: robotoblack;` |
| Lato normal | `font-family: lato;` |
| Lato bold | `font-family: lato; font-weight: bold;` |
| Lato black (900) | `font-family: latoblack;` |

---

## Notas Importantes

1. **Nombres en minusculas:** Los nombres de fuentes en mPDF deben ir en minusculas (`montserrat`, no `Montserrat`).

2. **Sin comillas:** No uses comillas alrededor de los nombres de fuentes en el CSS inline para mPDF.

3. **Italicas:** Las variantes italicas estan disponibles usando `font-style: italic;` con las fuentes base.
   ```html
   <p style="font-family: montserrat; font-style: italic;">Texto en italica</p>
   ```

4. **Combinando estilos:** Puedes combinar bold e italic:
   ```html
   <p style="font-family: montserrat; font-weight: bold; font-style: italic;">Bold + Italic</p>
   ```

5. **Las fuentes Black no tienen italica separada:** Las variantes Black usan el mismo archivo para todas las combinaciones.

---

## Solucion de Problemas

### El texto no muestra la fuente correcta

- Verifica que el nombre de la fuente este en minusculas
- Asegurate de no usar comillas
- Confirma que la fuente este seleccionada en la configuracion de la plantilla

### font-weight: 900 no funciona

- Usa la familia de fuente Black directamente (ej: `montserratblack`)
- mPDF solo soporta `font-weight: normal` y `font-weight: bold` automaticamente

### Error "Not a TrueType font"

- Los archivos de fuente pueden estar corruptos
- Contacta al administrador para verificar los archivos en `/assets/fonts/`

---

## Archivos de Fuentes Incluidos

El plugin incluye los siguientes archivos TTF en `/assets/fonts/`:

```
Montserrat/
  - Montserrat-Regular.ttf
  - Montserrat-Bold.ttf
  - Montserrat-Italic.ttf
  - Montserrat-BoldItalic.ttf
  - Montserrat-Black.ttf
  - Montserrat-ExtraBold.ttf

OpenSans/
  - OpenSans-Regular.ttf
  - OpenSans-Bold.ttf
  - OpenSans-Italic.ttf
  - OpenSans-BoldItalic.ttf
  - OpenSans-ExtraBold.ttf
  - OpenSans-ExtraBoldItalic.ttf

Roboto/
  - Roboto-Regular.ttf
  - Roboto-Bold.ttf
  - Roboto-Italic.ttf
  - Roboto-BoldItalic.ttf
  - Roboto-Black.ttf
  - Roboto-BlackItalic.ttf

Lato/
  - Lato-Regular.ttf
  - Lato-Bold.ttf
  - Lato-Italic.ttf
  - Lato-BoldItalic.ttf
  - Lato-Black.ttf
  - Lato-BlackItalic.ttf
```
