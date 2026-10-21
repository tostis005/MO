# Auditoría y refuerzo SEO — café, mango, aguacate, cítricos y chocolate
**Fecha:** 21 de octubre de 2026  
**Web:** El Mercado de Origen  
**Repositorios/archivos:** tostis005/MO, GitHub Actions, WordPress producción.

## Resumen ejecutivo

Se ha revisado conjuntamente la arquitectura editorial y el catálogo real para los 24 artículos nuevos (48 versiones ES/EN), las 30 guías específicas de Chocolates Artesanos Isabel (60 versiones si están publicadas en ES/EN) y el conjunto de 111 productos comprables muestreados en las cinco familias. El objetivo es que los artículos puedan leerse y entenderse de forma independiente, que los enlaces internos sean pertinentes, y que las rutas de compra conduzcan a productos existentes y disponibles.

Se detectó una arquitectura insuficiente en café y frutas: el módulo global de clústeres no tenía registradas estas familias, 14 artículos de fruta estaban adscritos solo a «Frutas» y varias categorías de WooCommerce apenas tenían descripción. Chocolate contaba con un módulo específico de 30 guías, que se ha conservado.

**Cambios en producción:** cuatro nuevas subcategorías editoriales («Aguacates», «Mangos», «Cítricos», «Frutas tropicales») con 11 artículos clasificados también en ellas; siete lotes reales con mango añadidos a «Frutas tropicales» y ocho lotes reales con cítricos añadidos a «Frutas»; redacción descriptiva en cuatro categorías comerciales («Café de especialidad», «Aguacates», «Frutas», «Frutas tropicales»). Se desplegó un módulo bilingüe de enlaces recíprocos: guía -> guías relacionadas/productos con stock, producto -> guías adecuadas y categoría comercial -> guías. La guía adicional sobre cremas de cacao se conecta con las 30 guías del módulo de chocolate sin duplicar su lógica. El archivo instalado procede de \`mu-plugins/emdo-coffee-fruit-editorial-clusters-20261021.php\`.

La publicación de los artículos y los textos comerciales anteriores se preservaron; no se cambiaron precios, variaciones, impuestos, portes, stock ni autoría. Se conservaron metadatos de respaldo para relaciones taxonómicas y descripciones anteriores.

## Auditoría de calidad editorial

- **24 guías nuevas:** los manuscritos completos ES/EN contienen entre ~830–1.400 palabras en español y ~780–1.310 en inglés. Los títulos, introducciones, secciones y cierres muestran una secuencia natural: problema -> criterios relevantes -> casos prácticos -> decisión de compra o uso. Entre cuatro y siete secciones H2 por artículo, sin un patrón rígido de secciones idénticas. Las entradas pueden entenderse sin consultar otras guías; los enlaces sirven para ampliar o comprar, no para suplir una explicación ausente.
- **Café (9):** cubre compra en grano, molienda, especialidad, italiana, superautomática, arábica/robusta, torrefacto, espresso y tueste. Diferencia correctamente especies, procesos, aparatos y preferencias; mantener separadas las intenciones «grano o molido» y «qué café elegir para X».
- **Aguacate (4):** variedades, maduración, temporada y conservación. Son necesidades independientes, pero conectadas; conservar las guías separadas.
- **Mango (3):** tipos, maduración y temporada española. Títulos y secciones diferenciados, aunque estrechamente relacionados.
- **Cítricos (3):** mesa frente a zumo, variedades y campaña. El comprador debe distinguir las variedades descritas de las mezclas de cítricos realmente disponibles.
- **Frutas generales (4):** calendario, compra online, variedades tropicales y cajas. Separar la intención «cómo comprar fruta online» de «qué caja/cantidad pedir».
- **Chocolate:** 30 guías específicas con 823–1.188 palabras españolas, versiones inglesas, distintos asuntos comerciales y de uso; módulo ya existente que ofrece hasta cuatro productos relevantes y tres guías relacionadas. Una guía adicional (nº 10 del nuevo plan) trata las cremas de cacao.
- **Limitación metodológica:** se contrastaron todas las fuentes de los 24 manuscritos por estructura, títulos, extractos, enlaces y longitud, con lectura humana de sus aperturas, secciones y cierres y comprobaciones de contenido técnico; esto no equivale a una corrección literaria línea por línea de las 60 versiones de chocolate ni a una medición de ranking real. Priorizar corrección manual de tecnicismos o información delicada si surgen nuevas incidencias.

## Inventario comercial contrastado

| Familia | Productos publicados, comprables y en stock en el análisis | Cobertura inglesa nativa de productos |
|---|---:|---|
| Café de especialidad | 6 | 0/6 |
| Aguacate | 7 | 0/7 |
| Productos con mango | 8 | 0/8 |
| Productos con cítricos | 11 | 0/11 |
| Chocolates artesanos | 79 | 75/79 |

Todos los 111 productos de este grupo tienen descripción de más de 90 palabras y fotografía principal, según el recuento del auditor. El diagnóstico inicial encontró 105 fotos principales sin atributo alt guardado; se añadieron los **105 alt españoles** y **75 versiones alternativas inglesas** cuando existía un producto traducido, sin reemplazar fotografías. Se revisó que el texto alternativo describiera el producto y no añadiese palabras clave artificiales.

**Incidencia importante pendiente: 36 productos sin ficha inglesa nativa publicada (32 café/fruta y 4 chocolate).** La web puede traducir parte de su interfaz mediante TranslatePress, pero faltan metadatos de producto EN completos para que el clúster tienda/blog en inglés tenga rutas nativas y textos revisados. No publicar traducciones automáticas no comprobadas ni generar enlaces a rutas EN que no existan. Priorizar un proceso separado de traducción de producto que preserve atributos, variantes, alérgenos y textos legales.

**La categoría independiente «Naranjas» sigue vacía.** No la publicitamos desde las guías ni la usamos como destino del clúster. Se añadió una regla de \`noindex\` condicional mientras su recuento sea cero (se desactiva cuando tenga productos). Los productos de naranja/mandarina que existen son combinaciones en la categoría «Frutas»; no deben anunciarse como cajas monovarietales de zumo.

## Relacionado y SEO

**Nuevos vínculos rastreables** (en HTML normal con anclas y destinos de verdad, sin botones de JS):
- Artículo de café -> hasta dos artículos de café y, en español, hasta dos cafés disponibles -> categoría.
- Producto de café -> hasta tres guías de café -> enlaces desde el archivo de productos.
- Mango / aguacate / cítricos: guías -> fichas en stock + otras guías, y ficha -> guías relacionadas por nombre y categoría. No se mezclan artículos de arábica con caramelos de café ni recetas de cítricos con productos animales que contienen aroma de mandarina.
- Categorías de café, aguacates, frutas, frutas tropicales y cremas de cacao -> artículos clave. Las categorías del blog enlazan de vuelta a sus páginas de compra si hay existencias.
- Chocolate conserva su sistema de recomendaciones; la guía nueva sobre cremas se suma a la navegación pertinente y aparece desde las fichas de cremas.

**Control de stock:** el módulo consulta \`is_visible\`, \`is_purchasable\` e \`is_in_stock\` antes de recomendar un producto. **Inglés:** no se generan enlaces a fichas EN nativas inexistentes; permanecen las guías EN conectadas entre sí y la oferta por categorías que soporte la web.

## Verificaciones de producción

- Despliegue de arquitectura con QA público ES/EN: https://github.com/tostis005/MO/actions/runs/39551263840 (12 páginas representativas de artículos y categorías, además de portada, blog, tienda).
- Verificación independiente de enlaces recíprocos: https://github.com/tostis005/MO/actions/runs/39551917850 (**19 de 19 páginas HTTP 200 y relación real**), que incluyen 10 artículos ES/EN, 5 fichas comerciales y 4 categorías.
- Relleno de alt principal: https://github.com/tostis005/MO/actions/runs/39551702408 (111 fotografías analizadas, 105 faltantes corregidas).
- Lectura del inventario y traducciones: https://github.com/tostis005/MO/actions/runs/39551367522.
- Los 24 artículos también cuentan con SEO ES/EN del lote previo y comprobación \`hreflang\`, canonical, H1 y \`og:image\` en muestra auditada. No se afirma comprobación pública nueva de absolutamente todas las 108 posibles variantes editoriales.

## Mejoras pendientes por prioridad

**P1.** Traducir y revisar 36 fichas de producto (especialmente las 32 de café y frutas). Las fichas inglesas deben contar con datos reales de envío, stock, descripción, variantes, alérgenos y rutas comprobadas. Conviene coordinarlo con el flujo ya existente de traducciones de WooCommerce para evitar sobrescribir cambios de otras sesiones.

**P2.** Medir consultas, impresiones y clics en Search Console después de la indexación. La buena arquitectura no garantiza posiciones; sin métricas de consulta no se puede afirmar que no haya canibalización entre guías afines.

**P2.** Auditoría visual en móvil del recorte de imágenes y de los bloques de enlaces, además de un muestreo amplio de productos de chocolate; la revisión automatizada de HTML no sustituye inspección visual completa.

**P3.** Actualizar el clúster de naranja cuando existan cajas de naranjas reales en catálogo. El actual estado sin stock no justifica crear enlaces de compra hacia una categoría vacía.

## Resultado

El clúster español está ahora mucho mejor conectado: blog, guías específicas, tienda, categorías y fichas se recomiendan entre sí de forma relevante y rastreable. El clúster inglés editorial también está conectado, pero **la conversión hacia fichas traducidas no estará completa hasta publicar y comprobar las traducciones faltantes**. No conviene crear más decenas de artículos antes de cerrar ese hueco.
