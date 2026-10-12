IUPY - Catalogo dinamico con PHP + MySQL (XAMPP)

INSTALACION
1. Descomprimí la carpeta iupy_catalogo_db dentro de C:\xampp\htdocs\
2. Abrí XAMPP y encendé Apache y MySQL.
3. Entrá a http://localhost/phpmyadmin
4. Importá database.sql desde la pestaña Importar.
5. Copiá config/db.example.php como config/db.php y configurá la conexión local. La plantilla usa:
   servidor: localhost
   usuario: root
   contraseña: vacía
   base: iupy_catalogo
6. Abrí http://localhost/iupy_catalogo_db/catalogo.php
7. Administración: http://localhost/iupy_catalogo_db/admin.php

IMPORTANTE
- El catálogo es público y el administrador de productos pide usuario y clave. La configuración local se guarda en config/access.local.php, excluida de Git. No publiques las credenciales.
- config/db.php y los archivos *.local.php están excluidos de Git para proteger credenciales. No publiques la configuración local. En el hosting, crea una base y un usuario propios, y guarda sus datos en config/db.php. Activa HTTPS antes de compartir el acceso.
- La protección está preparada para Apache/cPanel. No publiques database.sql: el .htaccess del proyecto bloquea archivos SQL y respaldos.
- Las rutas de imágenes deben ser relativas a esta carpeta, por ejemplo: img/super.jpg
- Podés escribir varias imágenes, una por línea, al crear un producto.
- Tipo de catálogo determina dónde aparece: pelotero, salón, barra, animación, carpita, juego, souvenirs o extras/otros.
- Los peloteros incluyen filtros por edad recomendada, tamaño y tipo (agua, eléctrico/mecánico o tradicional).
- El precio masivo de administración se aplica sólo a peloteros por tamaño y tipo.

INTEGRACION DEL CATALOGO ANTERIOR
- La portada del proyecto abre catalogo.php desde index.php.
- catalogo.php reúne Peloteros, Extras, Barras, Carpitas, Juegos, Salón y Otros; permite buscar por nombre, categoría o descripción.
- database.sql incluye los productos cargados y las altas de ejemplo no se duplican al volver a importar el archivo.
- Se copiaron a img/ sólo las fotos elegidas para este catálogo; el resto de las imágenes originales permanece en la carpeta pagina-iupy del Escritorio.
- Las páginas carpitas.html, combos.html, salon.html y pelotero2.0.html estaban vacías. No se inventaron nombres ni precios para sus productos; se pueden cargar desde admin.php.

Consulta de disponibilidad externa:
- Cada producto muestra el selector de fecha. Para consultar, el producto debe tener un ID externo configurado en administración; los demás muestran que el código queda pendiente.
- Canchita (ID 12) y Canchita Box (ID 69) son productos distintos.
- Para habilitar la consulta, copiá config/fabricadefiestas.local.example.php como config/fabricadefiestas.local.php y completá el usuario de acceso. La contraseña puede quedar vacía si la cuenta no tiene; el archivo queda bloqueado desde la web por config/.htaccess.
- La respuesta compara las reservas de esa fecha con el stock del producto en el catálogo local. Mantené ese stock igual a la cantidad real del sistema de alquileres. Cuando la página remota informa un precio, también sincroniza el precio base de ese producto.
- El endpoint consultado requiere autenticación. Usá credenciales vigentes configuradas localmente; si las credenciales compartidas anteriormente siguen activas, cambialas antes de configurarlas.
- agus.html es una página personal y no forma parte del catálogo comercial.
- En las páginas originales el precio de Tejo aparece como $2.700 y $27.000. Se dejó $27.000, valor que figura en extras.html.


