# YouTube Video Sync

Plugin de WordPress que sincroniza automáticamente videos de tu canal de YouTube con un Custom Post Type (CPT) "videos". Incluye soporte completo para Elementor Dynamic Tags.

## 📁 Estructura del Plugin

```
youtube-video-sync/
├── youtube-video-sync.php         # Archivo principal del plugin
├── README.md                       # Documentación
└── elementor-tags/                 # Dynamic Tags para Elementor
    ├── youtube-url-tag.php         # Tag para URL del video
    └── youtube-descripcion-tag.php # Tag para descripción
```

## Características

- ✅ **Sincronización automática diaria** a las 01:00 AM
- ✅ **Sincronización manual** desde el panel de administración
- ✅ **Prevención de duplicados** mediante verificación de ID de video
- ✅ **Importación completa** de metadatos:
  - Título del video
  - Descripción
  - Miniatura (thumbnail) de alta calidad
  - URL del video de YouTube
  - Categoría de YouTube mapeada a categorías de WordPress
- ✅ **Reporte detallado** de cada sincronización en el panel de administración
- ✅ **Custom Post Type "video"** creado automáticamente por el plugin
- ✅ **Taxonomía "ciudad"** basada en playlists de YouTube
- ✅ **Custom Fields registrados** y listos para usar
- ✅ **Integración con Elementor Dynamic Tags** para usar URL y descripción dinámicamente
- ✅ **Compatible con SCF** (Smart Custom Fields)

## Requisitos

- WordPress 5.0 o superior
- PHP 7.4 o superior
- API Key de YouTube Data API v3
- **Elementor (opcional)** - Para usar Dynamic Tags
- **Smart Custom Fields (opcional)** - Para gestión adicional de campos

## Instalación

1. Descarga el plugin completo (carpeta `youtube-video-sync`)
2. Sube la carpeta al directorio `/wp-content/plugins/` de tu WordPress
3. Activa el plugin desde el menú "Plugins" en WordPress
4. El plugin creará automáticamente:
   - Custom Post Type "video"
   - Taxonomía "ciudad"
   - Custom Fields necesarios
5. Configura el plugin (ver sección de Configuración)

## Configuración

### 1. Obtener tu API Key de YouTube

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Habilita **YouTube Data API v3**
4. Ve a "Credenciales" y crea una API Key
5. Copia tu API Key

### 2. Obtener el ID de tu canal

Puedes obtener tu Channel ID de varias formas:

**Método 1:** Desde tu canal de YouTube
- Ve a tu canal de YouTube
- El ID está en la URL: `youtube.com/channel/UCkP9R2e_9MyEOXLqwQstClg`
- El ID es: `UCkP9R2e_9MyEOXLqwQstClg`

**Método 2:** Desde YouTube Studio
- Ve a YouTube Studio → Configuración → Canal → Información avanzada

### 3. Configurar el plugin

Edita el archivo `youtube-video-sync.php` y modifica estas líneas:

```php
private $api_key = 'TU_API_KEY_AQUI'; // ⬅️ COLOCA TU API KEY DE YOUTUBE
private $channel_id = 'TU_CHANNEL_ID_AQUI'; // ⬅️ COLOCA TU CHANNEL ID
```

**Nota:** El plugin ya NO necesita configurar el `$post_type` ya que crea automáticamente el CPT "video".

### 4. Configurar mapeo de categorías (opcional)

Puedes mapear las categorías de YouTube a las categorías de WordPress:

```php
private $category_map = array(
    '1'  => 'baena',    // Categoría YouTube 1 → Categoría WP 'baena'
    '10' => 'cabra',    // Categoría YouTube 10 → Categoría WP 'cabra'
    '20' => 'cordoba'   // Categoría YouTube 20 → Categoría WP 'cordoba'
);
```

**IDs de categorías comunes de YouTube:**
- 1: Film & Animation
- 10: Music
- 20: Gaming
- 22: People & Blogs
- 24: Entertainment
- 25: News & Politics
- [Lista completa de categorías](https://developers.google.com/youtube/v3/docs/videoCategories/list)

## Uso

### Sincronización Automática

El plugin se ejecuta automáticamente todos los días a las 01:00 AM. No necesitas hacer nada.

### Sincronización Manual

1. Ve a **YouTube Sync** en el menú del panel de administración de WordPress
2. Haz clic en el botón **"Sincronizar Ahora"**
3. Espera a que termine el proceso
4. Revisa el reporte de sincronización

### Reporte de Sincronización

Después de cada sincronización, verás una tabla con:

- ✅ **Estado**: Si el video fue importado o ya existía
- **Título del video**
- **ID de YouTube**
- **ID del post en WordPress**
- **Fecha y hora** de la sincronización

## Campos Personalizados (SCF)

El plugin crea/actualiza los siguientes campos personalizados en cada post:

| Campo | Descripción | Tipo |
|-------|-------------|------|
| `_youtube_video_id` | ID único del video de YouTube | Meta personalizado |
| `youtube_url` | URL completa del video | Meta personalizado |
| `descripcion` | Descripción completa del video | Meta personalizado |
| `portada` | ID de la imagen destacada | Meta personalizado |

Todos los campos están registrados con `show_in_rest => true` para máxima compatibilidad.

## Integración con Elementor

El plugin incluye **Dynamic Tags personalizados** para usar los datos de YouTube directamente en Elementor.

### Dynamic Tags Disponibles

Una vez activado el plugin con Elementor, encontrarás un nuevo grupo llamado **"YouTube Video"** en los Dynamic Tags:

#### 1. YouTube URL
- **Uso**: Insertar la URL del video de YouTube
- **Categorías**: Text, URL, Post Meta
- **Ejemplo de uso**:
  - Widgets de Video de Elementor
  - Botones con enlace dinámico
  - Enlaces de texto

**Cómo usar:**
1. En cualquier campo de Elementor que soporte Dynamic Tags
2. Haz clic en el ícono de Dynamic Tags
3. Selecciona **YouTube Video → YouTube URL**

#### 2. YouTube Descripción
- **Uso**: Mostrar la descripción del video
- **Categorías**: Text, Post Meta
- **Opciones**:
  - **Texto completo**: Descripción completa
  - **Extracto**: Primeros 150 caracteres
  - **Corto**: Primeros 50 caracteres

**Cómo usar:**
1. En un widget de Texto o Heading de Elementor
2. Haz clic en el ícono de Dynamic Tags
3. Selecciona **YouTube Video → YouTube Descripción**
4. Configura el formato deseado

### Ejemplo de Plantilla en Elementor

**Caso de uso típico**: Crear una plantilla para mostrar videos

1. **Crear una Single Template** para el CPT "video"
2. **Agregar un Video Widget**:
   - URL: Dynamic Tag → YouTube Video → YouTube URL
3. **Agregar un Heading**:
   - Texto: Dynamic Tag → Post Title
4. **Agregar un Text Editor**:
   - Texto: Dynamic Tag → YouTube Video → YouTube Descripción (Extracto)
5. **Agregar Términos de Taxonomía**:
   - Dynamic Tag → Post Terms → ciudad

### Acceso Directo desde PHP

También puedes acceder a los campos directamente en tus plantillas:

```php
// Obtener URL del video
$youtube_url = get_post_meta(get_the_ID(), 'youtube_url', true);

// Obtener descripción
$descripcion = get_post_meta(get_the_ID(), 'descripcion', true);

// Obtener ID del video de YouTube
$video_id = get_post_meta(get_the_ID(), '_youtube_video_id', true);

// Mostrar video embebido
if ($youtube_url) {
    echo '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . $video_id . '" frameborder="0" allowfullscreen></iframe>';
}
```

## Funcionamiento Técnico

1. **Obtención de videos**: El plugin consulta la API de YouTube para obtener los últimos 50 videos del canal
2. **Verificación de duplicados**: Comprueba si el video ya existe en WordPress (por `_youtube_video_id`)
3. **Importación**: Si el video es nuevo:
   - Crea un post del tipo `video`
   - Importa título y descripción
   - Descarga y asigna la miniatura como imagen destacada
   - Guarda la URL del video y metadatos
   - Asigna la categoría correspondiente
4. **Reporte**: Guarda un reporte detallado de la sincronización

## Estructura del CPT recomendada

**Ya no es necesario** crear manualmente el Custom Post Type. El plugin lo crea automáticamente con esta configuración:

- **Slug**: `video`
- **Nombre**: Videos
- **Soporta**: Título, Editor, Imagen destacada, Campos personalizados
- **Archivo**: Sí (`/videos/`)
- **Taxonomías**: `category`, `ciudad`
- **REST API**: Habilitado
- **Ícono**: dashicons-video-alt3

Si deseas modificar la configuración del CPT, edita el método `register_post_type_and_taxonomy()` en el archivo principal del plugin.

## Preguntas Frecuentes

### ¿Necesito instalar otros plugins?

No. El plugin es completamente independiente y crea todo lo necesario:
- Custom Post Type "video"
- Taxonomía "ciudad"
- Custom Fields registrados

**Opcional**:
- **Elementor**: Para usar Dynamic Tags
- **Smart Custom Fields**: Para gestión visual adicional de campos

### ¿El plugin funciona sin Elementor?

Sí, el plugin funciona perfectamente sin Elementor. Los Dynamic Tags son una característica adicional opcional.

### ¿Cómo se asigna la taxonomía "ciudad"?

Automáticamente basándose en las playlists de YouTube donde está el video. Si un video está en una playlist llamada "Córdoba", se creará y asignará el término "Córdoba" en la taxonomía "ciudad".

### ¿Puedo cambiar la hora de sincronización automática?

Sí, modifica esta línea en el método `activate()`:

```php
wp_schedule_event(strtotime('tomorrow 03:00'), 'daily', 'youtube_sync_daily_event'); // 3:00 AM
```

### ¿Qué sucede si desactivo el plugin?

Al desactivar el plugin:
- Se eliminan las tareas programadas (cron)
- Se borra el último reporte de sincronización
- Los posts importados **NO** se eliminan

### ¿Cómo puedo importar más de 50 videos?

Actualmente el plugin importa los últimos 50 videos. Para importar más, necesitarías modificar el parámetro `maxResults` y agregar paginación con `nextPageToken`.

### ¿Puedo usar otro CPT en lugar de "video"?

Sí, solo cambia el valor de `$post_type` en el código.

### ¿Los videos se actualizan si cambio el título en YouTube?

No, el plugin solo importa videos nuevos. No actualiza videos existentes.

### ¿Dónde encuentro los Dynamic Tags en Elementor?

1. Abre el editor de Elementor
2. Selecciona cualquier widget que soporte Dynamic Tags (Video, Text, Heading, etc.)
3. Busca el ícono de Dynamic Tags (cilindro/base de datos)
4. En el menú desplegable, busca el grupo **"YouTube Video"**
5. Selecciona el Dynamic Tag que necesites

### ¿Puedo personalizar los Dynamic Tags?

Sí, puedes editar los archivos en la carpeta `elementor-tags/`:
- `youtube-url-tag.php` - Para personalizar el tag de URL
- `youtube-descripcion-tag.php` - Para personalizar el tag de descripción

## Solución de Problemas

### Los videos no se importan

1. Verifica que tu API Key sea correcta
2. Asegúrate de que la API de YouTube Data v3 esté habilitada
3. Comprueba que el Channel ID sea correcto
4. Revisa los logs de WordPress en `wp-content/debug.log`

### Las miniaturas no se descargan

Verifica que tu servidor tenga permisos de escritura en el directorio `wp-content/uploads/`

### El cron no se ejecuta

WordPress depende del tráfico del sitio para ejecutar cron. Considera usar un cron real del servidor o un plugin como WP Crontrol.

## Límites de la API de YouTube

- **10,000 unidades de cuota por día** (gratis)
- Obtener lista de videos: 1 unidad por video
- Obtener detalles de video: 1 unidad

Con el plan gratuito, puedes sincronizar fácilmente varios miles de videos al día.

## Changelog

### Versión 1.0
- Lanzamiento inicial
- Sincronización automática diaria
- Sincronización manual desde el admin
- Importación de metadatos y miniaturas
- Sistema de reportes
- Prevención de duplicados
- **Creación automática del CPT "video"**
- **Registro automático de taxonomía "ciudad"**
- **Asignación automática basada en playlists de YouTube**
- **Integración con Elementor Dynamic Tags**
- **Custom Fields registrados con REST API**
- **2 Dynamic Tags listos para usar** (URL y Descripción)

## Créditos

**Autor**: Eduardo Valenzuela  
**Versión**: 1.0  
**Licencia**: GPL v2 o posterior

## Licencia

Este plugin es software libre; puedes redistribuirlo y/o modificarlo bajo los términos de la Licencia Pública General GNU según publicada por la Free Software Foundation; ya sea la versión 2 de la Licencia, o (a tu elección) cualquier versión posterior.

## Soporte

Para reportar bugs o solicitar nuevas características, contacta al autor o crea un issue en el repositorio del proyecto.
