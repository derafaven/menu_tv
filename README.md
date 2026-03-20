Directorio de Enlaces TV

Sistema de directorios de enlaces con búsqueda integrada, panel de administración seguro y widgets de clima/fecha.
Estructura del Proyecto
 

tv/
├── index.php          # Página principal (visualización de enlaces)
├── admin.php          # Panel de administración (protegido)
├── css/
│   └── style.css      # Estilos (Dark Mode)
└── data/
    ├── config.json    # Configuración general
    └── data_*.json    # Archivos de datos por directorio 
text
 
  
 

## Configuración (config.json)

```json
{
    "registros_por_columna": 15,
    "ancho_columna": "280px",
    "tamano_texto": "14px",
    "ciudad_default": "Madrid",
    "codigo_pais": "ES",
    "clave_admin": "admin"
}
 
 
 

Campos nuevos: 

     ciudad_default, codigo_pais: Para la API del clima.
     clave_admin: Contraseña para acceder a admin.php.
     

Estructura de Datos (data_*.json) 
json
 
  
 
{
    "titulo": "Nombre del Directorio",
    "mostrar_busqueda": true,
    "mostrar_fecha_clima": true,
    "items": [
        {
            "nombre": "Nombre del enlace",
            "url": "https://ejemplo.com",
            "bus": "/search?q=" 
        }
    ]
}
 
 
 

Campos de control: 

     mostrar_busqueda (bool): Si es true, muestra la barra/UI de búsqueda. Si es false o no existe, la oculta.
     mostrar_fecha_clima (bool): Si es true, muestra el reloj y el widget de clima. Si es false, lo oculta.
     items: Lista de enlaces. El campo bus es opcional.
     

Lógica de Visualización y Funcionalidades 
1. Clima y Hora (Header) 

     Ubicación: Cabecera de index.php.
     Fecha/Hora: Reloj en tiempo real actualizado con JavaScript.
     Clima Actual: Usar API gratuita Open-Meteo (sin API Key). Obtener temperatura actual y estado.
     Pronóstico por Horas: Debajo del clima actual, mostrar un carrusel horizontal (scroll) con las próximas 12 horas. Cada hora debe mostrar Hora, Icono/Emoji y Temperatura.
     Dependencia: Solo cargar si mostrar_fecha_clima es true en el JSON cargado.
     

2. Búsqueda 

     Comportamiento: Controlado por mostrar_busqueda.
     Lógica de Enlaces:
         Sin parámetro b: Enlace a url.
         Con parámetro b:
             Si bus empieza con http: bus + valor_b.
             Si bus NO empieza con http: url + bus + valor_b.
             Si bus no existe: Redirigir solo a url.
             
         
     

3. Panel de Administración (admin.php) 

     Seguridad: Acceso protegido por contraseña.
         Al entrar, mostrar formulario de login.
         Verificar contra clave_admin en config.json.
         Usar sesiones PHP ($_SESSION) para mantener al usuario logueado.
         Botón de "Cerrar Sesión".
         
     Funciones:
         Listar, crear, editar y borrar archivos data_*.json.
         Editar config.json.
         Al editar/crear un JSON, permitir marcar los checks mostrar_busqueda y mostrar_fecha_clima.
         
     

Estilos (CSS) 

     Dark Mode obligatorio (Fondo oscuro #121212, texto claro).
     Estilo limpio y ligero.
     Columnas verticales para la lista de enlaces.
     Scroll horizontal suave para el pronóstico del clima.
     

Notas Técnicas 

     Sin Base de Datos SQL.
     PHP 8+.
     JavaScript Vainilla (Sin frameworks pesados).
     