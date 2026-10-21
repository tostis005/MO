# Roadmap de SEO y catálogo — El Mercado de Origen
Fecha de corte: 2026-10-21 (UTC)
Repositorio: tostis005/MO
Regla: solo correcciones acotadas a artículos/productos nuevos y, excepcionalmente, correcciones inglesas. Sin cambios masivos de URLs, canonicals o SEO de productos antiguos españoles.

## Definiciones de avance
- **Revisado**: leído en inventario de WordPress o auditado contra contenido real; no implica modificado.
- **Corregido**: modificado en WordPress y comprobado con resultado de GitHub Actions.
- **Ya conforme**: no requiere escritura.
- **Pendiente**: exige corrección, validación o no existe evidencia de ejecución.

## Estado por tarea
| Tarea | Universo de trabajo | Revisado | Corregido en esta intervención | Pendiente | Prueba |
|---|---:|---:|---:|---:|---|
| 1. Elegibilidad Isabel (solo físico) | 106 referencias fuente | 106 | 2 vales excluidos + 6 personalizables puestos en borrador | 6 personalizables para decidir implementación simplificada | actions/runs/39570329402 |
| 1a. Servicios Isabel | 3 servicios presenciales dentro de las 106 | 3 | 0; los 3 ya estaban excluidos | 0 | actions/runs/39570329402 |
| 1b. Kits físicos Isabel | 2 fichas dentro de las 106 | 2 | 2 introducciones corregidas en otra tarea | 0; conservar | actions/runs/39569646917 |
| 2. Redacción SEO nuevos productos | 111 fichas vendibles muestreadas (café, fruta, chocolate) en auditoría anterior | 111 auditoría técnica histórica | 2 textos de kit | Resto de incidencias, pendientes de contrastar con últimas importaciones | docs/AUDITORIA-CLUSTERS-CAFES-FRUTAS-CHOCOLATE-20261021.md |
| 3. Artículos y enlazado | 24 guías nuevas + 30 guías chocolate | 54 inventariadas; 24 análisis de estructura completos | 0 en este lote (los enlaces se habían reparado previamente) | Comprobar cambios de catálogo en enlaces y revisión editorial de muestras | docs/AUDITORIA-ARTICULOS-24-20261020.md |
| 4. Fichas inglesas | 36 faltantes nativas en la auditoría anterior del subconjunto | 36 detectadas | 0 en este lote | 36 sujetas a revalidación (pueden existir traducciones recientes) | docs/AUDITORIA-CLUSTERS-CAFES-FRUTAS-CHOCOLATE-20261021.md |
| 5. SEO técnico multilingüe | 48 rutas ES/EN nuevas y muestras del resto inglés | 8 QA pública anterior y controles previos | 0 en este lote | QA extendido sin despliegue SEO global | docs/AUDITORIA-ARTICULOS-24-20261020.md |
| 6. Categorías recientes | Café y cremas de cacao ES (2) | 2 | 2 ajustes locales de render SEO desplegados | Inspección pública final de duplicidad | actions/runs/39569430266 |

## Política de producto físico (Isabel)
- Mantener el kit físico "Haz tu propio chocolate Bean to Bar": ingredientes y útiles enviados en caja, sin selector requerido.
- Excluir vales de 20 y 50 EUR (2 fichas), por representar crédito del productor y no un bien físico.
- No publicar visitas (2) ni talleres (1), previamente excluidos del importador.
- Mantener en borrador seis referencias personalizables físicas sin un formulario local completo: Chocomensaje, lote regalo n.º 1, piruleta personalizada, tarjeta de chocolate personalizada y dos cajas de minitabletas. Las dos cajas tienen subida de imagen/diseño en origen; no incorporar ese flujo sin un proceso de pedido, diseño y cumplimiento verificado.
- No cambiar precios, stock, variantes, imágenes, impuestos ni portes. Fuente de inventario: audit de 106 registros realizado en producción.

## Pruebas y persistencia
- Auditoría y supresión focalizadas: https://github.com/tostis005/MO/actions/runs/39570329402 (éxito, 106 inspeccionados, 11 objetivos: dos vales, seis personalizables, tres servicios ya excluidos, dos kits respetados).
- Kits: https://github.com/tostis005/MO/actions/runs/39569646917 (éxito).
- Depuración visual de descripciones de categorías: https://github.com/tostis005/MO/actions/runs/39569430266 (instalación y QA pública correctas; el trabajo falló solo al registrar el informe en GitHub).
- Los registros excluidos en supplier-sync conservan su estado en actualizaciones futuras. El importador limita productos marcados como pendiente de personalización. El antiguo script publicador masivo de Isabel incorpora un tope de seguridad para no ejecutar otra vez sobre el catálogo ya existente.
- Despliegue del importador: https://github.com/tostis005/MO/actions/runs/39570522722 (éxito).
- Verificación del publicador posterior: https://github.com/tostis005/MO/actions/runs/39570641272 (éxito, `ISABEL_ONE_TIME_PUBLISHER_SKIPPED_LIVE_CATALOG=95`), confirmando que no se republicaron los ocho artículos ocultados.

## Próximo lote lógico
1. Verificar despliegue de protección contra republicación de Isabel.
2. Revisar qué personalizaciones permiten datos sencillos capturados por nuestro checkout y cuáles requieren diseño/archivo/validación externa.
3. Revalidar el inventario nuevo de Tropitop y cafés contra servicios o productos que no se envían.
4. Volver a medir cobertura inglesa nativa después de las traducciones más recientes.
5. Cerrar enlaces y metadatos solo donde falte una corrección demostrable.


## Avance adicional — intervención SEO del 21/10/2026 (continuación)

**Tropitop (187 fichas origen vinculadas):** 177 descripciones revisadas/corregidas y 50 asignaciones de categoría aplicadas en el primer pase; las cuatro últimas alertas de primera persona y suscripciones del productor se han resuelto en el código de sincronización y auditorías recientes reflejan `residual_flags: 0`. Las tres cajas de temporada admiten pedido único en la web de origen, por lo que conservamos el producto físico pero no trasladamos instrucciones de suscripción, cobros automáticos, WhatsApp ni plazos comerciales del proveedor. **Queda por cerrar** la deriva de una categoría: la ficha Tropitop `20413` (Pack melones premium) recibía incorrectamente «Jamones y paletas» (term 149) por un proceso de guardado ajeno a la taxonomía de fruta. Se ha intervenido exclusivamente en el mapeo y en la exclusión Tropitop de la cola de atributos de jamones. Pendiente de QA independiente para darlo por terminado.

**Inglés real, inventario de productores nuevos:** auditoría en WordPress https://github.com/tostis005/MO/actions/runs/39575262831: Chocolates Isabel 4 faltantes de 78 comprables; Tropitop 102 de 102; Latitudes 9 de 109. Los seis productos Ikore Kofi, gestionados fuera del sincronizador de proveedores, se han identificado por SKU y traducido manualmente al inglés (6/6, metadatos nativos y respaldo) con verificación https://github.com/tostis005/MO/actions/runs/39576330863. Por tanto quedan **115 fichas inglesas nativas confirmadas pendientes en esas tres familias** (4+102+9) más cualquier otro producto no incluido en esta muestra. No asumir que traducciones automáticas o traducciones de interfaz equivalen a fichas inglesas completas.

| Línea | Revisadas | Correcciones de esta continuación | Estado |
|---|---:|---:|---|
| Tropitop: inventario editorial/taxonomía | 187 | 4 alertas finales resueltas; 1 clasificación incorrecta en tratamiento | QA de una categoría pendiente |
| Ikore Kofi: versiones EN | 6 | 6 publicadas y verificadas | Completado |
| Chocolates Isabel: EN | 78 comprables | 0 | 4 pendientes |
| Tropitop: EN | 102 comprables | 0 | 102 pendientes |
| Latitudes: EN | 109 comprables | 0 | 9 pendientes |

**Disciplina de cambios:** los cambios de descripción y taxonomía no alteran precios, variantes, pesos, stock, URLs canónicas ni los títulos SEO de otros productores. Las descripciones inglesas de café se han añadido únicamente a sus seis productos, sin cambios a contenido español.
