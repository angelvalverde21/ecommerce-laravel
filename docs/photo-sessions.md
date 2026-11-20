# Sesiones fotográficas

## Rendimiento de cargas

Los originales de hasta 16 MiB usan un PUT firmado directo a B2. Los archivos
mayores conservan multipart de 64 MiB y sus reintentos por parte. Las cargas
multipart ya iniciadas siguen funcionando por su upload_id. El servidor verifica
el tamaño y el identificador del original antes de marcarlo disponible.

La cola procesa como máximo dos archivos simultáneos. Las URLs de thumb, medium
y large se solicitan juntas en `assets/{id}/previews/batch`; las tres vistas se
envían en paralelo y se espera que terminen todas antes de confirmar o reintentar.
La confirmación realiza tres HEAD simultáneos, sin consultas previas de existencia.
PhotoSessionStorage reutiliza un cliente S3 dentro de la misma instancia/petición.

El progreso distingue preparación, envío del original, verificación, generación y
carga de vistas previas. La barra solo indica porcentaje durante el envío del
original; las verificaciones son indeterminadas. No se modifica su calidad ni se
pasan archivos por PHP. Ocultar el modal mantiene la cola activa.

En Apache sobre Windows, bootstrap/app.php desactiva el adaptador putenv de
dotenv antes de cargar la configuración. Las peticiones usan los valores locales
de $_ENV/$_SERVER; CLI y otros servidores conservan su comportamiento. Esto evita
la lectura intermitente de APP_KEY vacía al ejecutar solicitudes concurrentes.
No se regenera ni se cambia la clave de la aplicación. Se reprodujeron 2 errores
500 en 30 consultas anónimas concurrentes antes del ajuste; después, las 60
consultas de comprobación respondieron 401, el resultado esperado sin sesión.
Referencia: https://github.com/phpdotenv/phpdotenv#putenv-and-getenv

Si el original ya está confirmado y falla la preparación de vistas previas,
Reintentar conserva el mismo asset y omite el envío del original. Solo vuelve a
generar, subir y confirmar thumb, medium y large.

Ruta ERP: `/:store/dashboard/photo-sessions`. Acceso para master, CEO, admin y
marketing; cada operación valida la tienda. La sección permite crear y editar la
planificación y organizar la biblioteca sin subir los originales al hosting PHP.

## Campos disponibles

Nombre, estado, fecha, hora de inicio y fin, ubicación, colección/campaña,
responsable interno, fotógrafo/productora, modelos y equipo, objetivo/brief,
fecha prevista de entrega, presupuesto de referencia en PEN y observaciones.

Cada archivo conserva nombre original, tamaño, tipo foto/video, clasificación
original/editado/final, selección, usuario de carga, fecha de guardado, ETag y
referencias a B2. No se reemplazan pagos ni gastos del ERP.

Más adelante conviene relacionar productos/variantes por sus IDs, agregar una
lista de tomas y registrar autorizaciones de uso del material si el flujo lo necesita.

## Directorios de fotógrafos y modelos

Submenús bajo Sesiones fotográficas:
- `photo-sessions/photographers`: fotógrafos.
- `photo-sessions/models`: modelos.

Cada directorio permite listar, buscar, crear y editar fichas, además de filtrar
activos/inactivos. Campos: nombre profesional, teléfono/WhatsApp, correo, Instagram,
portafolio web, estudio/productora o agencia/representante, observaciones y estado.

Ambos usan `photo_contacts`, diferenciados por `kind` y separados por `store_id`.
No son cuentas de acceso al ERP. Cada sesión puede seleccionar un fotógrafo mediante
`photographer_contact_id` y varios modelos mediante `photo_session_models`.
La API valida tipo de ficha, tienda y modelos duplicados. Los textos anteriores
de fotógrafo y equipo se conservan; no se convierten automáticamente en contactos.
Marcar una ficha inactiva conserva sus relaciones y se indica en los selectores.

Se reutilizan componentes tipados `photo-contact-index`, `create` y `edit`, cada
uno con su página correspondiente. La categoría llega por los datos de las rutas
padre; las rutas de directorio preceden al ID dinámico de sesión para evitar cruces.

### Imágenes referenciales de modelos

La lista de modelos muestra un acceso Galería con el número de imágenes. Su ficha
incluye `imageindex` en modo cuadrícula, con el upload y el visor existentes.
El modo tabla sigue siendo el predeterminado para el resto de consumidores.

Las referencias se relacionan con PhotoContact mediante el morph `imageable` de la
tabla `images`; no se añade user_id ni otra tabla de imágenes. Solo admite fichas de
tipo model y valida permisos, tienda y propiedad al subir y eliminar.

Se admiten JPG, PNG y WebP, hasta 10 MB por imagen y 10 archivos por petición.
El upload actual del ERP guarda estas referencias en el disco público local bajo
`images/photo-models/{store}/{contact}`. Son distintas de los originales de sesiones
que utilizan la integración B2. Se conserva el archivo recibido como large y se
generan thumbnail (360 px) y medium (960 px) independientes. La galería carga
thumbnail y abre large en el lightbox. Al eliminar se retiran las tres versiones.
La carga encola nuevas selecciones sin volver a enviar el lote que ya está subiendo.

## Locaciones fotográficas

Submenú `photo-sessions/locations`, con páginas y componentes `photo-location-index`,
`create` y `edit`. Incluye búsqueda por nombre, dirección, distrito y ciudad, filtros
por tipo y estado, y número de sesiones asociadas. Tipos: Airbnb/alojamiento,
cafetería, estudio, hotel, casa/departamento, exteriores y otro.

Campos: nombre, tipo, dirección, distrito/zona, ciudad, persona de contacto,
teléfono, correo, enlace de referencia/anuncio/mapa, tarifa referencial en PEN,
modalidad por hora/día/sesión, notas de horarios/acceso/restricciones y estado.
La tarifa es informativa; no genera compras ni modifica el presupuesto de sesiones.

Se utiliza `photo_locations`, relacionada con `photo_sessions.photo_location_id`.
La tabla existente `locations` pertenece a inventarios: requiere almacén en su API,
tiene jerarquía de ubicaciones y está vinculada a conteos/existencias. Reutilizarla
para lugares de producción visual implicaría modificar esas reglas y filtros.
Este directorio mantiene independiente el concepto de locación fotográfica.

La selección valida `store_id`. No se eliminan las referencias históricas al
desactivar una locación. Los campos anteriores `photo_sessions.location` se
conservan como ubicación adicional/punto de encuentro; no se transforman datos
libres en registros del directorio mediante coincidencias de nombre.

### Dirección en addresses

La dirección ya no se guarda en las columnas `address`, `district` y `city` de
`photo_locations`: se migraron y retiraron. `PhotoLocation::address()` es una relación
polimórfica morphOne con la tabla compartida `addresses`.

El formulario sigue teniendo un solo botón Guardar. El endpoint de locaciones
recibe `address` como objeto con `primary`, `secondary`, `references`, `district_id`,
`city`, `url_maps`, `latitud` y `longitud`. Nombre, tipo, contacto, anuncio y tarifa
siguen en `photo_locations`. Se reutiliza InputDistrictIdComponent para elegir el
distrito del catálogo; escribir un texto nuevo invalida la selección anterior.

La creación/actualización de locación y dirección es transaccional. Al editar se
bloquea la fila de locación; los endpoints compartidos de direcciones usan el mismo
bloqueo y rechazan crear una segunda dirección. Se habilitó `addressable_type =
photo_location` mediante un adaptador con permisos del módulo y validación de
tienda y propietario. Omitir address conserva el registro; enviarlo como null lo
elimina. No se aceptan IDs ni propietarios dentro del objeto address.

La migración conserva calles de hasta 500 caracteres y ciudades existentes.
Vincula el distrito únicamente cuando el nombre exacto normalizado tiene una sola
coincidencia; nombres ambiguos o desconocidos quedan en referencias como
`Distrito/zona anterior: ...`. No se asignan distritos por aproximación. Las URLs
del mapa admiten hasta 1000 caracteres. No se cambiaron las direcciones de otros
tipos de propietarios ni sus reglas de múltiples direcciones.

### Responsable interno

Las opciones se obtienen únicamente de `employees` de la tienda, con empleado y
cuenta activos. La API responde `employees` y no la lista general de users. El
campo existente `responsible_id` conserva su referencia a users para no romper el
historial, pero solo acepta IDs que tengan una ficha activa en employees de la
misma tienda. Las opciones incluyen `employee_id` como identificación de la ficha.

El menú contiene Sesiones, Fotógrafos, Modelos y Locaciones. Crear una sesión se
mantiene en el botón del listado; se retiró el acceso lateral Nueva sesión.

## Estado de la conexión

Por decisión del usuario, los buckets son **públicos por ahora**:
- `erp-photo-originals`: originales de fotos y videos.
- `erp-photo-previews`: vistas previas thumb, medium y large.

El 18/11/2026 se crearon los buckets generales y se copiaron 1 original y 3 vistas
previas desde sorelle-photos/sorelle-thumbs mediante copia en B2. Se verificaron
los cuatro contenidos por SHA-256 y se conservaron metadatos, cifrado y CORS.
Se actualizaron el .env local y las referencias del archivo listo en MySQL. Las
descargas firmadas original/thumb/medium/large y las solicitudes previas PUT desde
localhost devolvieron 200. El manifiesto de reversión está en
storage/app/private/photo-bucket-migration-20261118095715.json.

Los buckets anteriores se conservan como respaldo y porque 3 registros uploading
todavía apuntan a sus multipart incompletos. No borrar esos buckets hasta completar
o cancelar dichas cargas y revisar cualquier referencia restante. Las cargas nuevas
usan los nombres generales. Al configurar el hosting se deben usar también estos
nombres en las variables del entorno de ese servidor.

Se eliminó el requisito de ACL privada. La configuración local apunta a esos
nombres. El 18/11/2026 se verificó el acceso a ambos buckets y se aplicaron sus reglas
CORS por la API S3 con autorización del usuario. Las solicitudes previas PUT desde
http://localhost:4200 responden 200 y un origen ajeno de prueba recibe 403. Las
lecturas posteriores confirman GET/HEAD/PUT, allowedHeaders *, ETag y caché de 3600 s.
Se guardó la configuración previa en storage/app/private/b2-photo-cors-before-*.json.
No se modificaron ACL ni se subieron archivos de prueba: queda verificar una carga
real y añadir el origen exacto de producción cuando corresponda.

La clave debe permitir las cargas multipart. Guardar exclusivamente en el `.env`
del backend (puede ser una clave multibucket con acceso a los dos buckets):

```dotenv
B2_PHOTO_BUCKET_NAME=erp-photo-originals
B2_PHOTO_PREVIEW_BUCKET_NAME=erp-photo-previews
B2_PHOTO_KEY_ID=id-de-la-clave-restringida
B2_PHOTO_APPLICATION_KEY=clave-restringida
# Se reutilizan B2_ENDPOINT y B2_REGION de la cuenta actual.
```

Después de editar variables: `php artisan config:clear` (o reconstruir config:cache
si el entorno usa configuración cacheada). Nunca enviar las claves al navegador.
El SDK oficial `aws/aws-sdk-php` está instalado; no se implementó firma criptográfica propia.

CORS ya está configurado en **ambos buckets** para el origen local. Regla S3 aplicada,
que debe ampliarse al dominio real al publicar:

```json
{
  "CORSRules": [{
    "ID": "erp-photo-local-upload",
    "AllowedOrigins": ["http://localhost:4200"],
    "AllowedMethods": ["GET", "HEAD", "PUT"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3600
  }]
}
```

El acceso público no autoriza escrituras anónimas ni sustituye CORS. Una vez conectado,
verificar un JPEG y un video de prueba: carga, descarga, vistas previas, cancelación,
reintento y hash del original descargado antes de comenzar la carga masiva real.

## Flujo de carga y calidad

1. El ERP registra los metadatos y solicita una carga multipart en B2.
2. El navegador envía partes de 64 MiB mediante URLs firmadas de 30 minutos. Los
   bytes viajan directamente a B2 mediante XHR, sin interceptores ni token del ERP.
3. Cada parte se puede reintentar hasta tres veces. Un reintento manual en la misma
   página continúa desde la parte pendiente; no hay reanudación persistente tras recargar.
4. El servidor consulta las partes, comprueba el orden y tamaño de cada una y el
   total, completa la carga y verifica tamaño e identificador del objeto con HEAD.
   Completar de nuevo un archivo guardado es idempotente. Cancelar y completar
   comparten un lock por archivo para no pisarse.
5. El original se conserva sin recomprimir, redimensionar ni transcodificar.
6. Para JPEG, PNG, WebP y AVIF de hasta 100 MiB, el navegador puede crear previews
   WebP independientes thumb (320), medium (960) y large (1920). Se guardan en B2
   en sorelle-thumbs con claves distintas. Un error de preview no elimina el original ya recibido.
7. RAW, HEIC, TIFF, fotos mayores y videos conservan su original y muestran un
   icono de archivo cuando el navegador no genera una vista previa. No existe
   transcodificación de video ni generador de previews RAW en el hosting.

Límite inicial: 100 GiB por archivo; configurable en `config/photo-sessions.php`.
La galería usa únicamente thumb; el lightbox existente abre large. El original
se descarga bajo demanda con una URL temporal y Content-Disposition attachment.
Se conservan URLs firmadas también mientras los buckets sean públicos. Al cambiar
`sorelle-photos` a privado en el futuro, el ERP seguirá firmando las descargas sin
necesitar migrar los objetos ni modificar sus IDs. Mientras el bucket sea público,
sus objetos también pueden leerse mediante sus URLs públicas.
No se guardan URLs firmadas en la base de datos; se generan al consultar la galería.
Si caducan, usar Actualizar. La selección de favoritos queda registrada en el ERP.

Al navegar con cargas activas se solicita confirmar la salida; al salir se detiene
la carga y se intenta cancelar el multipart pendiente. Si una desconexión impide la
cancelación, revisar periódicamente multipart incompletos en B2; configurar su
limpieza según el plazo de conservación acordado antes de usarlo en producción.

## Estructura y validación

- Tablas `photo_sessions` y `photo_session_assets`, migraciones aplicadas individualmente.
  Cada archivo guarda el bucket original y `preview_bucket`, para que cambiar la
  configuración futura no rompa los archivos anteriores. En registros antiguos
  sin preview_bucket se siguen buscando las vistas previas en el bucket original.
- Controllers separados para planificación y archivos; servicio `PhotoSessionStorage`
  para B2, sin guardar el contenido en PHP ni mezclarlo con el upload local de 10 MB.
- Frontend: páginas `photo-session-index-page`, `photo-session-create-page`,
  `photo-session-edit-page`; presentación `photo-session-index`, `create`, `edit`;
  biblioteca `photo-session-asset-index` y carga `photo-session-upload`.
- API y transporte multipart separados del formulario; vistas previas en una función
  independiente. El formulario de edición no se reinicia al cambiar el estado saving.
- La biblioteca abre la cola en photo-session-upload-dialog con el shell animado
  compartido photo-dialog. Ocultar la ventana conserva las subidas; completarlas
  refresca la biblioteca y cierra el modal si no hay errores. La navegación fuera
  de la sesión mantiene la confirmación existente y puede cancelar cargas.
- Backend: `php artisan test --filter=PhotoSessionTest` (aislamiento, validaciones,
  multipart, original intacto, idempotencia, buckets públicos y URLs firmadas separadas).
- Frontend: `npx ng test --configuration initial-import-module` incluye esta sección,
  la navegación de pedidos y los módulos de importación existentes.

Documentación oficial:
- https://www.backblaze.com/docs/cloud-storage-call-the-s3-compatible-api
- https://www.backblaze.com/docs/cloud-storage-cross-origin-resource-sharing-rules
- https://www.backblaze.com/apidocs/s3-create-multipart-upload

## Preparación y tareas de sesión

La migración 2026_11_18_020000 traslada las tablas anteriores a tasks y task_events,
conservando IDs, estados, versiones, responsables e historial. Task es genérico y
HasTasks proporciona la relación morphMany. taskable_type/taskable_id identifica
el propietario; photo_session es un alias explícito en el mapa de relaciones.
store_id mantiene el aislamiento de tienda. assigned_to referencia users y se valida contra employees activos de la
misma tienda, además del estado de la cuenta. No se crean cuentas nuevas.

Endpoints autenticados /photo-session-tasks: GET exige photo_session_id (no hay
consulta global sin propietario), POST (CEO/master), PUT /{id} (planificación, CEO/master),
PUT /{id}/status (CEO/master o empleado activo asignado), GET /{id}/events.
La consulta por sesión permite al equipo con acceso al módulo ver el conjunto;
el personal sin ese acceso recibe únicamente sus propias asignaciones. Todas las
operaciones exigen pertenencia a tienda y aíslan las tareas por la sesión.

Estados: pending, in_progress, blocked, completed. Bloquear requiere una nota;
completar registra completed_at/completed_by y reabrir los limpia. Vencida es una
condición calculada: fecha límite anterior al día actual en America/Lima y estado
distinto de completed. Los resúmenes abarcan toda la selección antes de filtrar por
estado y paginar. Los cambios se registran en una transacción con bloqueo y version;
una versión desactualizada produce 409 y no añade eventos ni sobrescribe datos.
No se eliminan tareas ni historial desde este flujo. No hay menú global de tareas.
El personal sin permisos administrativos ve únicamente las sesiones donde tiene
tareas y abre /photo-sessions/{id}/tasks. No obtiene datos de coordinación,
presupuesto, directorios ni biblioteca. Los cambios siguen limitados a sus tareas.

## Asignaciones múltiples y panel de sesión

La paleta de las seis secciones se define en _photo-section-themes.scss:
Resumen violeta, Tareas ámbar, Fotógrafos azul, Modelos rosa, Locaciones verde
y Biblioteca índigo.
Las pestañas y acciones usan las mismas variables. Las cabeceras de sección
reservan el fondo de color y los títulos de mayor tamaño. Las fichas internas
tienen cabeceras blancas, nombres en color neutro, bordes grises y acentos de
sección limitados a iconos y detalles. El área de fichas usa un fondo gris claro
para distinguir el contenedor de sus elementos; los cuerpos siguen blancos.
Los estados mantienen sus colores semánticos de avance, finalización y alerta.

La ficha organiza Resumen, Tareas, Fotógrafos, Modelos, Locaciones y Biblioteca
en pestañas con iconos, colores y contadores. Resumen se abre por defecto y
contiene el dashboard de preparación, los datos generales y el responsable único. Cada sección
muestra su tarjeta correspondiente. Los paneles se ocultan sin destruir los
componentes: cambiar de pestaña conserva los borradores, los filtros y las cargas.
La edición de datos generales aparece dentro de Resumen; el botón Editar datos
lleva a esa pestaña. La navegación admite flechas, Inicio y Fin con roles ARIA.

photo-session-dashboard usa el resumen global de tareas de la consulta existente:
el porcentaje es completed / total, sin sumar tareas en curso ni duplicar las
vencidas. Las alertas separan bloqueadas y vencidas; una tarea puede aparecer en
ambos grupos. Una sesión sin tareas muestra una invitación a organizar los
preparativos. La carga y los errores tienen estados visibles, sin inventar un
porcentaje cuando no hay datos. Cada actualización de tareas refresca el resumen.

Biblioteca conserva su componente montado en su propia pestaña. Su total global
se emite únicamente en consultas sin filtros y actualiza el contador de su pestaña.
Los accesos inferiores de recursos se retiraron para evitar duplicar las pestañas.
Los filtros no reducen ese total. Completar una carga refresca la
biblioteca y sus contadores sin recargar la sesión ni borrar borradores.

2026_11_18_010000 crea photo_session_photographers y photo_session_locations,
además del pivote photo_session_models existente. La migración incorpora los
enlaces singulares previos. Los campos singulares quedan como compatibilidad y
reflejan la primera relación. Los arrays photographer_ids, model_ids y location_ids
se validan por tipo y tienda. Las lecturas incluyen las fichas y direcciones.

PUT /photo-sessions/{id}/assignments recibe kind (photographer/model/location),
mode (attach/detach) e ids. Cada operación bloquea la sesión y cambia únicamente
la relación indicada; attach es idempotente y detach no elimina fichas. Actualizar
planificación sin arrays conserva las asignaciones actuales. Las tareas conservan
un controlador adaptador de sesión; futuros módulos pueden reutilizar Task/HasTasks
definiendo explícitamente sus propias políticas, sin aceptar clases arbitrarias.

La migración de tareas se comprobó con conteos y hashes de todas las columnas
de negocio e historial antes y después. La reversión se bloquea si perdería tareas
de otro tipo o asignaciones múltiples. Las migraciones se aplican individualmente.
