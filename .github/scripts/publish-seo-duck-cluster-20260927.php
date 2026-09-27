<?php
if (!defined('ABSPATH')) { exit; }

function emdo_duck_words($html) {
    $text = trim(preg_replace('/\\s+/u', ' ', wp_strip_all_tags(strip_shortcodes($html))));
    if ($text === '') return 0;
    preg_match_all('/[\\p{L}\\p{M}]+(?:[’\\x{27}’-][\\p{L}\\p{M}]+)*/u', $text, $m);
    return count($m[0]);
}

function emdo_duck_existing($key, $slug) {
    $ids = get_posts(array('post_type'=>'page','post_status'=>'any','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_emdo_seo_landing_key','meta_value'=>$key));
    if ($ids) return (int)$ids[0];
    $p = get_page_by_path($slug, OBJECT, 'page');
    if ($p) return (int)$p->ID;
    $collision = get_page_by_path($slug, OBJECT, 'post');
    if ($collision) throw new Exception('Slug collision with post: '.$slug.' ID '.$collision->ID);
    return 0;
}

function emdo_duck_pick($key, $pool, $salt='') {
    if (!$pool) return '';
    $n = abs(crc32($key.'|'.$salt));
    return $pool[$n % count($pool)];
}

function emdo_duck_group_context($group) {
    $map = array(
      'foie'=>'En productos de foie conviene distinguir con precisión materia prima, tratamiento térmico, porcentaje real de foie gras, formato y condiciones de conservación.',
      'magret'=>'En el magret la gestión de la piel, la grasa y el reposo condiciona tanto la textura como la percepción de jugosidad.',
      'confit'=>'En un confit la carne ya ha pasado por una cocción lenta en grasa; en casa normalmente se busca calentar el interior y dar un acabado dorado.',
      'jamon'=>'En el jamón de pato importan el curado, el grosor de corte, la proporción entre carne y grasa y la temperatura de servicio.',
      'pate'=>'Patés, mousses y rillettes pueden compartir contexto de aperitivo, pero su estructura, ingredientes y textura son diferentes y conviene leer la etiqueta.',
      'cortes'=>'Cada corte de pato concentra hueso, piel, grasa y músculo en proporciones distintas, por lo que no admite una única técnica de cocción.',
      'recetas'=>'En recetas con pato conviene equilibrar la intensidad de la carne con acidez, vegetales o guarniciones que aporten contraste y no solo más grasa.',
      'maridaje'=>'El maridaje depende tanto del producto de pato como de la salsa y la guarnición; acidez, dulzor, alcohol y tanino deben valorarse como conjunto.',
      'navidad'=>'En un menú festivo el pato funciona mejor cuando se reparte la intensidad y se evita repetir preparaciones grasas en todos los pases.',
      'regalos'=>'En un regalo gastronómico importan tanto el producto como la conservación, el formato, la facilidad de consumo y la información sobre procedencia.',
      'fundamentos'=>'Para entender la carne de pato conviene separar corte, presencia de piel, técnica de cocción y tipo de elaboración antes de comparar productos.'
    );
    return isset($map[$group]) ? $map[$group] : $map['fundamentos'];
}

function emdo_duck_expand($p, $specific, $i) {
    $key=$p['key']; $group=$p['group']; $focus=$p['focus'];
    $starts=array(
      'Este punto merece atención porque suele ser el que más cambia el resultado final.',
      'Aquí conviene separar lo que pertenece al producto de lo que depende de la técnica.',
      'Es una diferencia pequeña sobre el papel, pero muy visible cuando el producto llega al plato.',
      'La mejor forma de entenderlo es pensar primero en textura, grasa y uso final.',
      'Antes de aplicar una regla fija, conviene mirar el formato concreto que tenemos delante.'
    );
    $middles=array(
      'La pieza, el grosor, la temperatura inicial y el método de conservación pueden modificar el comportamiento en cocina, de modo que una instrucción genérica nunca sustituye a la información del elaborador.',
      'Dos productos con un nombre parecido pueden exigir un servicio distinto si cambian los ingredientes, el tratamiento térmico o la proporción de grasa.',
      'El objetivo no es añadir complejidad, sino saber qué variable merece controlarse y cuál puede adaptarse al gusto o a la receta.',
      'Cuando se conoce esta lógica resulta más fácil corregir sobre la marcha y evitar tanto la sobrecocción como un servicio desequilibrado.',
      'También ayuda a comparar formatos sin asumir que el más caro, el más grande o el más intenso es automáticamente el más adecuado.'
    );
    $ends=array(
      'Por eso conviene leer la etiqueta, respetar la cadena de frío cuando proceda y ajustar la preparación al producto real.',
      'En caso de duda, las instrucciones específicas del fabricante deben tener prioridad sobre cualquier pauta general.',
      'Para una compra online, además, merece la pena revisar peso, número de raciones, presentación y condiciones de conservación.',
      'Si se va a servir dentro de un menú amplio, es mejor pensar también en el resto de platos para no repetir grasa, dulzor o intensidad.',
      'Una preparación sencilla suele permitir valorar mejor el producto que una receta con demasiados elementos compitiendo entre sí.'
    );
    return '<p><strong>'.esc_html($specific).'.</strong> '.esc_html(emdo_duck_pick($key,$starts,'s'.$i)).' '.esc_html(emdo_duck_group_context($group)).'</p>'
      .'<p>'.esc_html(emdo_duck_pick($key,$middles,'m'.$i)).' '.esc_html(emdo_duck_pick($key,$ends,'e'.$i)).'</p>';
}

function emdo_duck_content($p, $all) {
    $key=$p['key']; $title=$p['title']; $focus=$p['focus']; $summary=$p['summary']; $group=$p['group'];
    $intro2=array(
      'En esta guía nos centramos en la intención concreta de búsqueda y evitamos mezclarla con otras preparaciones del pato que merecen una explicación propia.',
      'La idea es que puedas tomar decisiones prácticas: reconocer el producto, saber qué mirar al comprarlo y entender cómo tratarlo en cocina o en mesa.',
      'El pato admite muchas elaboraciones y por eso es fácil confundir nombres comerciales, técnicas y cortes; separar cada concepto hace la elección mucho más sencilla.',
      'Más que memorizar una receta única, interesa comprender las variables que de verdad cambian el resultado y adaptar después la preparación al producto disponible.'
    );
    $html='<p>'.$summary.'</p>';
    $html.='<p>'.emdo_duck_pick($key,$intro2,'intro').' '.emdo_duck_group_context($group).'</p>';
    $html.='<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Buscas un producto de pato concreto?</h2><p>Si necesitas localizar un formato, corte o elaboración específica, puedes escribirnos y revisaremos las opciones disponibles en El Mercado de Origen.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar</a></p></div>';

    $heads=array('Qué conviene saber','La diferencia que más importa','Cómo llevarlo a la práctica','Qué revisar antes de comprar');
    foreach($p['specifics'] as $i=>$sp){
      $html.='<h2>'.esc_html($heads[$i]).': '.esc_html($sp).'</h2>';
      $html.=emdo_duck_expand($p,$sp,$i);
    }

    $html.='<h2>Cómo elegir bien según el uso</h2>';
    $html.='<p>La misma búsqueda puede esconder necesidades distintas. No es igual comprar para cocinar desde cero, preparar un aperitivo rápido, montar un menú de celebración o tener un producto de despensa listo para servir. Para elegir bien, empieza por definir cuántas personas van a comer, si el producto será protagonista o complemento y cuánto tiempo quieres dedicar a la preparación.</p>';
    $html.='<p>Después revisa el formato. En productos frescos pesan mucho la fecha, la cadena de frío y la posibilidad de congelación. En conservas importan el tamaño del envase y qué ocurre una vez abierto. En piezas curadas interesa saber si llegan enteras o loncheadas. Esa lectura evita comprar un formato excelente que, sencillamente, no encaja con el uso previsto.</p>';

    $html.='<h2>Errores habituales que conviene evitar</h2>';
    $html.='<p>Uno de los errores más frecuentes es aplicar la misma técnica a todos los productos de pato. Otro es añadir salsas muy dulces o guarniciones muy grasas sin tener en cuenta que muchas piezas ya aportan bastante intensidad. También es habitual confundir términos como foie, paté, mousse, confit o magret y terminar cocinando un producto como si fuera otro.</p>';
    $html.='<p>Para evitarlo, conviene identificar primero si estamos ante carne cruda, un producto ya cocinado, una conserva, un curado o una preparación emulsionada. A partir de ahí la técnica se vuelve mucho más evidente. Cuando haya instrucciones específicas en el envase, deben prevalecer, especialmente en conservación, regeneración y seguridad alimentaria.</p>';

    $html.='<h2>Conservación y seguridad alimentaria</h2>';
    $html.='<p>Las condiciones cambian mucho entre carne fresca, productos pasteurizados, curados y conservas esterilizadas. No conviene trasladar automáticamente a un formato las reglas de otro. Mantén la cadena de frío cuando la etiqueta lo exija, refrigera rápidamente los productos abiertos y utiliza recipientes limpios y cerrados para las sobras.</p>';
    $html.='<p>En carne de ave cruda, descongela preferentemente en refrigeración y evita contaminaciones cruzadas con alimentos listos para comer. Si una receta tradicional propone puntos de cocción bajos, valora el riesgo y sigue las recomendaciones oficiales de seguridad alimentaria aplicables y las instrucciones del producto concreto.</p>';

    $html.='<h2>Cómo integrarlo en un menú</h2>';
    $html.='<p>El pato suele agradecer contraste. Si el producto es graso o muy untuoso, funcionan bien elementos frescos, ácidos, vegetales o ligeramente amargos. Si la preparación es curada o ahumada, conviene moderar la sal en el resto del plato. Y si aparece una salsa dulce, el equilibrio mejora cuando también existe acidez o un fondo salado.</p>';
    $html.='<p>En un menú con varios productos de pato, reparte la intensidad: una pequeña porción de foie o jamón puede abrir el apetito, mientras que magret o confit funcionan mejor como plato principal. Servir varios elaborados muy grasos en secuencia puede hacer que ninguno destaque realmente.</p>';

    $html.='<h2>Preguntas frecuentes</h2>';
    $html.='<h3>¿Qué es lo primero que debería mirar?</h3><p>Identifica exactamente el tipo de producto, el formato y su estado: fresco, curado, confitado, pasteurizado o esterilizado. Después revisa conservación, ingredientes, peso y modo de empleo. Esa información resuelve gran parte de las dudas antes de cocinar.</p>';
    $html.='<h3>¿Puedo sustituir un producto de pato por otro parecido?</h3><p>A veces sí, pero la técnica puede cambiar. Un muslo fresco no se comporta como un confit; una mousse no sustituye siempre a un foie entier; y un jamón de pato no tiene el mismo uso que un magret fresco. La sustitución debe hacerse por función culinaria, no solo por nombre.</p>';
    $html.='<h3>¿Cómo sé qué cantidad comprar?</h3><p>Depende de si hay hueso, de la proporción de grasa, del número de platos del menú y de si el producto se sirve como aperitivo o principal. Siempre que sea posible, consulta peso neto y número orientativo de raciones del productor.</p>';

    $same=array();
    foreach($all as $q){ if($q['key']!==$key && $q['group']===$group) $same[]=$q; }
    if(count($same)<3){ foreach($all as $q){ if($q['key']!==$key && !in_array($q,$same,true)) $same[]=$q; if(count($same)>=3) break; } }
    $html.='<h2>Más guías sobre pato</h2><ul>';
    for($i=0;$i<min(3,count($same));$i++){ $q=$same[$i]; $html.='<li><a href="/'.esc_attr($q['slug']).'/">'.esc_html($q['title']).'</a></li>'; }
    $html.='</ul>';
    $html.='<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres encontrar una elaboración concreta de pato?</h2><p>Cuéntanos qué buscas, cuántas personas sois y cómo quieres prepararlo. Revisaremos formatos y productores que puedan encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>';
    return $html;
}

$raw = <<<'JSON'
[
  {
    "key": "duck-01",
    "slug": "carne-de-pato-guia-completa-cortes-sabor-preparacion",
    "title": "Carne de pato: guía completa de cortes, sabor, preparación y cómo elegirla",
    "focus": "carne de pato",
    "group": "fundamentos",
    "summary": "Una guía troncal para entender la carne de pato, sus cortes más habituales, cómo cambia la textura según la pieza y qué criterios ayudan a elegir el formato adecuado.",
    "specifics": [
      "Diferencias entre pechuga, muslo, solomillo, alas y pato entero",
      "Qué aporta la piel y la grasa a la cocción",
      "Cómo escoger entre producto fresco, confitado, curado o preparado",
      "Qué señales revisar en formato, conservación y procedencia"
    ],
    "excerpt": "Una guía troncal para entender la carne de pato, sus cortes más habituales, cómo cambia la textura según la pieza y qué criterios ayudan a elegir el formato adecuado.",
    "seo_title": "Carne de pato: guía completa de cortes, sabor, preparación y cómo elegirla",
    "seo_description": "Una guía troncal para entender la carne de pato, sus cortes más habituales, cómo cambia la textura según la pieza y qué criterios ayudan a elegir el for…"
  },
  {
    "key": "duck-02",
    "slug": "foie-gras-de-pato-que-es-tipos-diferencias-como-elegirlo",
    "title": "Foie gras de pato: qué es, tipos, diferencias y cómo elegirlo",
    "focus": "foie gras de pato",
    "group": "foie",
    "summary": "Una introducción completa al foie gras de pato que separa el producto fresco, el micuit, el entier, el bloc, el parfait y las mousses para facilitar una elección informada.",
    "specifics": [
      "Qué significa realmente foie gras",
      "Diferencias entre fresco, micuit y conserva",
      "Cómo leer denominaciones como entier, bloc o parfait",
      "Qué formato elegir según uso, número de comensales y servicio"
    ],
    "excerpt": "Una introducción completa al foie gras de pato que separa el producto fresco, el micuit, el entier, el bloc, el parfait y las mousses para facilitar una elección informada.",
    "seo_title": "Foie gras de pato: qué es, tipos, diferencias y cómo elegirlo",
    "seo_description": "Una introducción completa al foie gras de pato que separa el producto fresco, el micuit, el entier, el bloc, el parfait y las mousses para facilitar una…"
  },
  {
    "key": "duck-03",
    "slug": "magret-de-pato-que-es-como-cocinarlo-punto-perfecto",
    "title": "Magret de pato: qué es, cómo cocinarlo y cómo conseguir el punto perfecto",
    "focus": "magret de pato",
    "group": "magret",
    "summary": "Una guía esencial sobre el magret, la pechuga de pato con su piel y grasa, con claves de preparación, marcado, reposo, corte y acompañamientos.",
    "specifics": [
      "Qué pieza es exactamente el magret",
      "Por qué conviene trabajar la piel y la grasa con paciencia",
      "Cómo controlar la cocción sin resecar la carne",
      "Reposo, corte y servicio para conservar jugosidad"
    ],
    "excerpt": "Una guía esencial sobre el magret, la pechuga de pato con su piel y grasa, con claves de preparación, marcado, reposo, corte y acompañamientos.",
    "seo_title": "Magret de pato: qué es, cómo cocinarlo y cómo conseguir el punto perfecto",
    "seo_description": "Una guía esencial sobre el magret, la pechuga de pato con su piel y grasa, con claves de preparación, marcado, reposo, corte y acompañamientos."
  },
  {
    "key": "duck-04",
    "slug": "confit-de-pato-que-es-como-se-prepara-y-con-que-acompanarlo",
    "title": "Confit de pato: qué es, cómo se prepara y con qué acompañarlo",
    "focus": "confit de pato",
    "group": "confit",
    "summary": "Una guía troncal para entender el confitado de pato, los formatos habituales y la mejor manera de regenerar la carne para que quede tierna y con exterior dorado.",
    "specifics": [
      "Qué significa confitar una pieza de pato",
      "Diferencia entre confit refrigerado y conserva esterilizada",
      "Cómo retirar o aprovechar la grasa de pato",
      "Guarniciones que equilibran una carne intensa y melosa"
    ],
    "excerpt": "Una guía troncal para entender el confitado de pato, los formatos habituales y la mejor manera de regenerar la carne para que quede tierna y con exterior dorado.",
    "seo_title": "Confit de pato: qué es, cómo se prepara y con qué acompañarlo",
    "seo_description": "Una guía troncal para entender el confitado de pato, los formatos habituales y la mejor manera de regenerar la carne para que quede tierna y con exterio…"
  },
  {
    "key": "duck-05",
    "slug": "jamon-de-pato-que-es-como-se-elabora-y-como-se-come",
    "title": "Jamón de pato: qué es, cómo se elabora y cómo se come",
    "focus": "jamón de pato",
    "group": "jamon",
    "summary": "Una guía sobre la pechuga de pato curada, su textura, el corte, la temperatura de servicio y las combinaciones que mejor respetan su sabor.",
    "specifics": [
      "Por qué el jamón de pato parte normalmente del magret",
      "Qué aporta el curado y cómo cambia la textura",
      "Pieza entera frente a formato loncheado",
      "Temperatura, grosor de corte y acompañamientos"
    ],
    "excerpt": "Una guía sobre la pechuga de pato curada, su textura, el corte, la temperatura de servicio y las combinaciones que mejor respetan su sabor.",
    "seo_title": "Jamón de pato: qué es, cómo se elabora y cómo se come",
    "seo_description": "Una guía sobre la pechuga de pato curada, su textura, el corte, la temperatura de servicio y las combinaciones que mejor respetan su sabor."
  },
  {
    "key": "duck-06",
    "slug": "foie-gras-pate-mousse-parfait-bloc-micuit-diferencias",
    "title": "Foie gras, paté, mousse, parfait, bloc y micuit: todas las diferencias",
    "focus": "diferencias foie gras paté mousse parfait bloc micuit",
    "group": "foie",
    "summary": "Una comparación destinada a resolver la confusión entre preparaciones que pueden parecer similares pero cambian mucho en composición, textura, tratamiento térmico y uso.",
    "specifics": [
      "Foie gras como materia prima y como categoría",
      "Qué diferencia un bloc de una pieza entera",
      "Dónde encajan mousse y parfait",
      "Por qué micuit describe un tratamiento y no una textura única"
    ],
    "excerpt": "Una comparación destinada a resolver la confusión entre preparaciones que pueden parecer similares pero cambian mucho en composición, textura, tratamiento térmico y uso.",
    "seo_title": "Foie gras, paté, mousse, parfait, bloc y micuit: todas las diferencias",
    "seo_description": "Una comparación destinada a resolver la confusión entre preparaciones que pueden parecer similares pero cambian mucho en composición, textura, tratamien…"
  },
  {
    "key": "duck-07",
    "slug": "pato-fresco-principales-cortes-y-como-cocinar-cada-uno",
    "title": "Pato fresco: principales cortes del pato y cómo cocinar cada uno",
    "focus": "cortes de pato fresco",
    "group": "cortes",
    "summary": "Una guía práctica sobre los principales cortes frescos de pato y la lógica de cocción de cada uno, desde magret y solomillo hasta muslo, manchón y pato entero.",
    "specifics": [
      "Cortes rápidos frente a piezas que agradecen cocción lenta",
      "Cómo cambia la proporción de grasa entre piezas",
      "Qué piezas funcionan mejor para plancha, horno o guiso",
      "Cómo planificar raciones y descongelación"
    ],
    "excerpt": "Una guía práctica sobre los principales cortes frescos de pato y la lógica de cocción de cada uno, desde magret y solomillo hasta muslo, manchón y pato entero.",
    "seo_title": "Pato fresco: principales cortes del pato y cómo cocinar cada uno",
    "seo_description": "Una guía práctica sobre los principales cortes frescos de pato y la lógica de cocción de cada uno, desde magret y solomillo hasta muslo, manchón y pato …"
  },
  {
    "key": "duck-08",
    "slug": "pate-de-pato-que-es-como-se-elabora-y-como-elegirlo",
    "title": "Paté de pato: qué es, cómo se elabora y cómo elegirlo",
    "focus": "paté de pato",
    "group": "pate",
    "summary": "Una guía para entender qué puede contener un paté de pato, cómo se diferencia de otras preparaciones untables y qué revisar antes de comprarlo.",
    "specifics": [
      "Composición y textura de un paté",
      "Diferencias frente a mousse y rillettes",
      "Importancia de leer ingredientes y porcentaje de pato o foie",
      "Formatos de conserva y refrigerados"
    ],
    "excerpt": "Una guía para entender qué puede contener un paté de pato, cómo se diferencia de otras preparaciones untables y qué revisar antes de comprarlo.",
    "seo_title": "Paté de pato: qué es, cómo se elabora y cómo elegirlo",
    "seo_description": "Una guía para entender qué puede contener un paté de pato, cómo se diferencia de otras preparaciones untables y qué revisar antes de comprarlo."
  },
  {
    "key": "duck-09",
    "slug": "como-cocinar-magret-de-pato-en-sarten-jugoso",
    "title": "Cómo cocinar magret de pato en sartén para que quede jugoso",
    "focus": "cómo cocinar magret de pato en sartén",
    "group": "magret",
    "summary": "Una guía paso a paso centrada en la sartén: preparación de la piel, salida progresiva de la grasa, cocción de la carne, reposo y corte.",
    "specifics": [
      "Secar y atemperar correctamente la pieza",
      "Marcar la piel sin quemarla",
      "Gestionar la grasa que se va fundiendo",
      "Reposar y cortar contra la fibra"
    ],
    "excerpt": "Una guía paso a paso centrada en la sartén: preparación de la piel, salida progresiva de la grasa, cocción de la carne, reposo y corte.",
    "seo_title": "Cómo cocinar magret de pato en sartén para que quede jugoso",
    "seo_description": "Una guía paso a paso centrada en la sartén: preparación de la piel, salida progresiva de la grasa, cocción de la carne, reposo y corte."
  },
  {
    "key": "duck-10",
    "slug": "como-preparar-confit-de-pato-al-horno-piel-crujiente",
    "title": "Cómo preparar confit de pato al horno y conseguir una piel crujiente",
    "focus": "confit de pato al horno piel crujiente",
    "group": "confit",
    "summary": "Una guía para regenerar confit ya elaborado en el horno, priorizando una carne caliente y melosa y una piel bien dorada sin resecar el interior.",
    "specifics": [
      "Retirar el exceso de grasa antes del horno",
      "Colocar la piel en posición adecuada para dorar",
      "Calentar el interior antes de buscar color",
      "Servir inmediatamente para conservar textura"
    ],
    "excerpt": "Una guía para regenerar confit ya elaborado en el horno, priorizando una carne caliente y melosa y una piel bien dorada sin resecar el interior.",
    "seo_title": "Cómo preparar confit de pato al horno y conseguir una piel crujiente",
    "seo_description": "Una guía para regenerar confit ya elaborado en el horno, priorizando una carne caliente y melosa y una piel bien dorada sin resecar el interior."
  },
  {
    "key": "duck-11",
    "slug": "como-servir-foie-gras-temperatura-corte-pan-acompanamientos",
    "title": "Cómo servir foie gras: temperatura, corte, pan y acompañamientos",
    "focus": "cómo servir foie gras",
    "group": "foie",
    "summary": "Una guía de servicio para que el foie mantenga textura, aroma y untuosidad, con especial atención a temperatura, corte, pan y contrastes dulces o ácidos.",
    "specifics": [
      "Cuándo sacarlo del frío",
      "Cómo cortar sin deformar la pieza",
      "Qué panes acompañan sin dominar",
      "Cómo usar frutas, sal y acidez con moderación"
    ],
    "excerpt": "Una guía de servicio para que el foie mantenga textura, aroma y untuosidad, con especial atención a temperatura, corte, pan y contrastes dulces o ácidos.",
    "seo_title": "Cómo servir foie gras: temperatura, corte, pan y acompañamientos",
    "seo_description": "Una guía de servicio para que el foie mantenga textura, aroma y untuosidad, con especial atención a temperatura, corte, pan y contrastes dulces o ácidos."
  },
  {
    "key": "duck-12",
    "slug": "mousse-de-pato-que-es-diferencia-pate-foie",
    "title": "Mousse de pato: qué es y en qué se diferencia del paté y del foie",
    "focus": "mousse de pato",
    "group": "pate",
    "summary": "Una explicación centrada en la textura aireada de la mousse de pato, sus ingredientes habituales y sus diferencias frente a patés, foie gras y parfait.",
    "specifics": [
      "Por qué la mousse tiene una textura más fina",
      "Qué papel puede tener el foie gras en la receta",
      "Cómo cambia frente a un paté más compacto",
      "Servicio en tostadas, canapés y aperitivos"
    ],
    "excerpt": "Una explicación centrada en la textura aireada de la mousse de pato, sus ingredientes habituales y sus diferencias frente a patés, foie gras y parfait.",
    "seo_title": "Mousse de pato: qué es y en qué se diferencia del paté y del foie",
    "seo_description": "Una explicación centrada en la textura aireada de la mousse de pato, sus ingredientes habituales y sus diferencias frente a patés, foie gras y parfait."
  },
  {
    "key": "duck-13",
    "slug": "rillettes-de-pato-que-son-como-se-elaboran-y-como-se-comen",
    "title": "Rillettes de pato: qué son, cómo se elaboran y cómo se comen",
    "focus": "rillettes de pato",
    "group": "pate",
    "summary": "Una guía sobre esta preparación de carne de pato confitada y deshilachada, su textura fibrosa, su grasa de unión y sus mejores formas de servicio.",
    "specifics": [
      "Carne confitada y desmigada como base",
      "Diferencia de textura frente a patés y mousses",
      "Rillettes tradicionales y versiones con foie",
      "Temperatura ambiente, pan y encurtidos como acompañamiento"
    ],
    "excerpt": "Una guía sobre esta preparación de carne de pato confitada y deshilachada, su textura fibrosa, su grasa de unión y sus mejores formas de servicio.",
    "seo_title": "Rillettes de pato: qué son, cómo se elaboran y cómo se comen",
    "seo_description": "Una guía sobre esta preparación de carne de pato confitada y deshilachada, su textura fibrosa, su grasa de unión y sus mejores formas de servicio."
  },
  {
    "key": "duck-14",
    "slug": "foie-gras-entier-bloc-y-bloc-con-trozos-diferencias",
    "title": "Foie gras entier, bloc de foie gras y foie con trozos: diferencias",
    "focus": "foie gras entier bloc con trozos diferencias",
    "group": "foie",
    "summary": "Una comparación directa entre presentaciones de foie gras que se distinguen por la estructura del hígado en el producto final y por la experiencia de corte y textura.",
    "specifics": [
      "Qué caracteriza al foie gras entier",
      "Cómo se forma un bloc de foie gras",
      "Qué aportan los trozos visibles dentro de un bloc",
      "Cómo cambia el uso en mesa o canapé"
    ],
    "excerpt": "Una comparación directa entre presentaciones de foie gras que se distinguen por la estructura del hígado en el producto final y por la experiencia de corte y textura.",
    "seo_title": "Foie gras entier, bloc de foie gras y foie con trozos: diferencias",
    "seo_description": "Una comparación directa entre presentaciones de foie gras que se distinguen por la estructura del hígado en el producto final y por la experiencia de co…"
  },
  {
    "key": "duck-15",
    "slug": "foie-gras-fresco-micuit-conserva-diferencias-usos",
    "title": "Foie gras fresco, micuit y foie gras en conserva: diferencias y usos",
    "focus": "foie gras fresco micuit conserva",
    "group": "foie",
    "summary": "Una guía para distinguir tres estados y tratamientos del foie gras, especialmente en conservación, textura, vida útil y posibilidades culinarias.",
    "specifics": [
      "Fresco como materia prima para cocinar",
      "Micuit como foie pasteurizado de textura delicada",
      "Conserva esterilizada para mayor estabilidad",
      "Qué formato conviene según receta y ocasión"
    ],
    "excerpt": "Una guía para distinguir tres estados y tratamientos del foie gras, especialmente en conservación, textura, vida útil y posibilidades culinarias.",
    "seo_title": "Foie gras fresco, micuit y foie gras en conserva: diferencias y usos",
    "seo_description": "Una guía para distinguir tres estados y tratamientos del foie gras, especialmente en conservación, textura, vida útil y posibilidades culinarias."
  },
  {
    "key": "duck-16",
    "slug": "jamon-de-pato-curado-y-ahumado-diferencias",
    "title": "Jamón de pato curado y jamón de pato ahumado: diferencias",
    "focus": "jamón de pato curado ahumado",
    "group": "jamon",
    "summary": "Una comparación entre el perfil de un jamón de pato curado y una versión ahumada, explicando cómo el humo modifica aroma, intensidad y combinaciones.",
    "specifics": [
      "Base común de pechuga curada",
      "Qué cambia cuando aparece el ahumado",
      "Cómo ajustar grosor y temperatura de servicio",
      "Acompañamientos que no compitan con el humo"
    ],
    "excerpt": "Una comparación entre el perfil de un jamón de pato curado y una versión ahumada, explicando cómo el humo modifica aroma, intensidad y combinaciones.",
    "seo_title": "Jamón de pato curado y jamón de pato ahumado: diferencias",
    "seo_description": "Una comparación entre el perfil de un jamón de pato curado y una versión ahumada, explicando cómo el humo modifica aroma, intensidad y combinaciones."
  },
  {
    "key": "duck-17",
    "slug": "grasa-de-pato-para-que-sirve-como-utilizarla-cocina",
    "title": "Grasa de pato: para qué sirve y cómo utilizarla en la cocina",
    "focus": "grasa de pato usos",
    "group": "fundamentos",
    "summary": "Una guía culinaria sobre una grasa muy útil para confitar, asar, dorar y aportar sabor a patatas, verduras, arroces o carnes.",
    "specifics": [
      "Por qué soporta bien cocciones culinarias habituales",
      "Cómo usarla para dorar patatas y verduras",
      "Su papel tradicional en el confitado",
      "Conservación y reutilización con criterios de higiene"
    ],
    "excerpt": "Una guía culinaria sobre una grasa muy útil para confitar, asar, dorar y aportar sabor a patatas, verduras, arroces o carnes.",
    "seo_title": "Grasa de pato: para qué sirve y cómo utilizarla en la cocina",
    "seo_description": "Una guía culinaria sobre una grasa muy útil para confitar, asar, dorar y aportar sabor a patatas, verduras, arroces o carnes."
  },
  {
    "key": "duck-18",
    "slug": "como-cocinar-foie-gras-fresco-a-la-plancha-sin-que-se-deshaga",
    "title": "Cómo cocinar foie gras fresco a la plancha sin que se deshaga",
    "focus": "foie gras fresco a la plancha",
    "group": "foie",
    "summary": "Una guía de técnica para trabajar escalopes de foie gras fresco con calor intenso y tiempos breves, buscando color exterior y un interior cremoso.",
    "specifics": [
      "Elegir escalopes de grosor regular",
      "Trabajar con el foie frío y la superficie seca",
      "Usar una sartén bien caliente sin exceso de grasa añadida",
      "Escurrir, sazonar y servir al momento"
    ],
    "excerpt": "Una guía de técnica para trabajar escalopes de foie gras fresco con calor intenso y tiempos breves, buscando color exterior y un interior cremoso.",
    "seo_title": "Cómo cocinar foie gras fresco a la plancha sin que se deshaga",
    "seo_description": "Una guía de técnica para trabajar escalopes de foie gras fresco con calor intenso y tiempos breves, buscando color exterior y un interior cremoso."
  },
  {
    "key": "duck-19",
    "slug": "parfait-de-foie-y-mousse-de-foie-diferencias",
    "title": "Parfait de foie y mousse de foie: diferencias de composición, textura y sabor",
    "focus": "parfait de foie mousse de foie diferencias",
    "group": "foie",
    "summary": "Una comparación entre dos preparaciones finas y untables en las que el porcentaje de foie, la formulación y el proceso condicionan textura e intensidad.",
    "specifics": [
      "Qué suele definir a un parfait",
      "Por qué una mousse resulta más aireada",
      "La importancia del porcentaje real de foie gras",
      "Qué formato elegir para aperitivo o servicio en plato"
    ],
    "excerpt": "Una comparación entre dos preparaciones finas y untables en las que el porcentaje de foie, la formulación y el proceso condicionan textura e intensidad.",
    "seo_title": "Parfait de foie y mousse de foie: diferencias de composición, textura y sabor",
    "seo_description": "Una comparación entre dos preparaciones finas y untables en las que el porcentaje de foie, la formulación y el proceso condicionan textura e intensidad."
  },
  {
    "key": "duck-20",
    "slug": "como-conservar-foie-gras-antes-y-despues-de-abrirlo",
    "title": "Cómo conservar el foie gras antes y después de abrirlo",
    "focus": "cómo conservar foie gras",
    "group": "foie",
    "summary": "Una guía de conservación que distingue fresco, refrigerado, micuit y conserva, y recuerda seguir siempre las indicaciones específicas de la etiqueta.",
    "specifics": [
      "Cadena de frío en productos refrigerados",
      "Diferencia entre producto cerrado y abierto",
      "Uso de recipientes limpios y bien cerrados",
      "Cuándo descartar un producto por dudas de conservación"
    ],
    "excerpt": "Una guía de conservación que distingue fresco, refrigerado, micuit y conserva, y recuerda seguir siempre las indicaciones específicas de la etiqueta.",
    "seo_title": "Cómo conservar el foie gras antes y después de abrirlo",
    "seo_description": "Una guía de conservación que distingue fresco, refrigerado, micuit y conserva, y recuerda seguir siempre las indicaciones específicas de la etiqueta."
  },
  {
    "key": "duck-21",
    "slug": "con-que-acompanar-foie-gras-pan-frutas-mermeladas",
    "title": "Con qué acompañar el foie gras: panes, frutas, mermeladas y otros contrastes",
    "focus": "acompañamientos para foie gras",
    "group": "foie",
    "summary": "Una guía de maridajes culinarios para equilibrar la untuosidad del foie sin tapar su sabor, utilizando pan, fruta, acidez y dulzor con medida.",
    "specifics": [
      "Panes neutros y ligeramente tostados",
      "Frutas frescas o cocinadas con acidez",
      "Mermeladas en pequeñas cantidades",
      "Sales, especias y vinos como complementos secundarios"
    ],
    "excerpt": "Una guía de maridajes culinarios para equilibrar la untuosidad del foie sin tapar su sabor, utilizando pan, fruta, acidez y dulzor con medida.",
    "seo_title": "Con qué acompañar el foie gras: panes, frutas, mermeladas y otros contrastes",
    "seo_description": "Una guía de maridajes culinarios para equilibrar la untuosidad del foie sin tapar su sabor, utilizando pan, fruta, acidez y dulzor con medida."
  },
  {
    "key": "duck-22",
    "slug": "magret-de-pato-a-la-naranja-receta-claves",
    "title": "Magret de pato a la naranja: receta y claves para que quede perfecto",
    "focus": "magret de pato a la naranja",
    "group": "magret",
    "summary": "Una receta-guía que combina la intensidad del magret con una salsa cítrica y ligeramente dulce, manteniendo el protagonismo de la carne.",
    "specifics": [
      "Construir primero una buena cocción del magret",
      "Usar zumo, ralladura y fondo sin exceso de azúcar",
      "Reducir la salsa hasta que tenga cuerpo",
      "Cortar y salsear justo antes de servir"
    ],
    "excerpt": "Una receta-guía que combina la intensidad del magret con una salsa cítrica y ligeramente dulce, manteniendo el protagonismo de la carne.",
    "seo_title": "Magret de pato a la naranja: receta y claves para que quede perfecto",
    "seo_description": "Una receta-guía que combina la intensidad del magret con una salsa cítrica y ligeramente dulce, manteniendo el protagonismo de la carne."
  },
  {
    "key": "duck-23",
    "slug": "que-guarnicion-poner-al-magret-de-pato",
    "title": "Qué guarnición poner al magret de pato",
    "focus": "guarniciones para magret de pato",
    "group": "magret",
    "summary": "Una guía para elegir guarniciones que compensen la grasa y la intensidad del magret, desde patatas y raíces hasta fruta, verduras y ensaladas templadas.",
    "specifics": [
      "Guarniciones crujientes frente a purés suaves",
      "Verduras asadas para aportar amargor y dulzor natural",
      "Frutas y salsas ácidas para equilibrar la grasa",
      "Cómo ajustar la cantidad para que el magret siga siendo protagonista"
    ],
    "excerpt": "Una guía para elegir guarniciones que compensen la grasa y la intensidad del magret, desde patatas y raíces hasta fruta, verduras y ensaladas templadas.",
    "seo_title": "Qué guarnición poner al magret de pato",
    "seo_description": "Una guía para elegir guarniciones que compensen la grasa y la intensidad del magret, desde patatas y raíces hasta fruta, verduras y ensaladas templadas."
  },
  {
    "key": "duck-24",
    "slug": "punto-coccion-magret-pato-temperaturas-tiempos",
    "title": "Punto de cocción del magret de pato: temperaturas y tiempos",
    "focus": "punto de cocción magret de pato",
    "group": "magret",
    "summary": "Una guía para entender cómo influyen grosor, temperatura inicial y potencia de cocción en el punto del magret, priorizando control y seguridad alimentaria.",
    "specifics": [
      "El grosor cambia más el tiempo que una receta fija",
      "La piel necesita una cocción distinta a la cara de la carne",
      "El reposo modifica el punto final",
      "La temperatura interna debe interpretarse junto con las recomendaciones oficiales de seguridad para aves"
    ],
    "excerpt": "Una guía para entender cómo influyen grosor, temperatura inicial y potencia de cocción en el punto del magret, priorizando control y seguridad alimentaria.",
    "seo_title": "Punto de cocción del magret de pato: temperaturas y tiempos",
    "seo_description": "Una guía para entender cómo influyen grosor, temperatura inicial y potencia de cocción en el punto del magret, priorizando control y seguridad alimentaria."
  },
  {
    "key": "duck-25",
    "slug": "como-cortar-servir-magret-de-pato",
    "title": "Cómo cortar y servir un magret de pato correctamente",
    "focus": "cómo cortar magret de pato",
    "group": "magret",
    "summary": "Una guía de acabado para reposar, cortar y presentar el magret conservando jugosidad y una proporción equilibrada de piel, grasa y carne en cada loncha.",
    "specifics": [
      "Reposar antes de cortar",
      "Usar cuchillo largo y bien afilado",
      "Cortar en lonchas regulares atravesando la fibra",
      "Servir sobre plato caliente sin cocer de más la carne"
    ],
    "excerpt": "Una guía de acabado para reposar, cortar y presentar el magret conservando jugosidad y una proporción equilibrada de piel, grasa y carne en cada loncha.",
    "seo_title": "Cómo cortar y servir un magret de pato correctamente",
    "seo_description": "Una guía de acabado para reposar, cortar y presentar el magret conservando jugosidad y una proporción equilibrada de piel, grasa y carne en cada loncha."
  },
  {
    "key": "duck-26",
    "slug": "que-hacer-con-grasa-que-suelta-magret-de-pato",
    "title": "Qué hacer con la grasa que suelta el magret de pato",
    "focus": "grasa del magret de pato",
    "group": "magret",
    "summary": "Una guía de aprovechamiento para filtrar y usar la grasa que se funde durante la cocción del magret en patatas, verduras, arroces y otras preparaciones.",
    "specifics": [
      "Retirar grasa de la sartén durante la cocción",
      "Filtrarla para eliminar restos sólidos",
      "Enfriarla rápidamente y conservarla de forma segura",
      "Usarla como grasa culinaria en cantidades moderadas"
    ],
    "excerpt": "Una guía de aprovechamiento para filtrar y usar la grasa que se funde durante la cocción del magret en patatas, verduras, arroces y otras preparaciones.",
    "seo_title": "Qué hacer con la grasa que suelta el magret de pato",
    "seo_description": "Una guía de aprovechamiento para filtrar y usar la grasa que se funde durante la cocción del magret en patatas, verduras, arroces y otras preparaciones."
  },
  {
    "key": "duck-27",
    "slug": "confit-de-pato-al-horno-temperatura-tiempo-trucos",
    "title": "Confit de pato al horno: temperatura, tiempo y trucos para dorarlo",
    "focus": "confit de pato al horno",
    "group": "confit",
    "summary": "Una guía práctica para calentar confit elaborado y terminarlo al horno, explicando por qué la potencia del horno, el tamaño de la pieza y el punto de partida cambian los tiempos.",
    "specifics": [
      "Precalentar el horno y retirar grasa superficial",
      "Calentar primero y dorar después",
      "Vigilar la piel para evitar quemarla",
      "Adaptar el proceso a las instrucciones del fabricante"
    ],
    "excerpt": "Una guía práctica para calentar confit elaborado y terminarlo al horno, explicando por qué la potencia del horno, el tamaño de la pieza y el punto de partida cambian los tiempos.",
    "seo_title": "Confit de pato al horno: temperatura, tiempo y trucos para dorarlo",
    "seo_description": "Una guía práctica para calentar confit elaborado y terminarlo al horno, explicando por qué la potencia del horno, el tamaño de la pieza y el punto de pa…"
  },
  {
    "key": "duck-28",
    "slug": "como-calentar-confit-de-pato-conserva-envasado-vacio",
    "title": "Cómo calentar un confit de pato en conserva o envasado al vacío",
    "focus": "cómo calentar confit de pato",
    "group": "confit",
    "summary": "Una guía que separa los pasos de una conserva en lata y un confit refrigerado o al vacío, con especial atención a retirar la grasa y dorar al final.",
    "specifics": [
      "Atemperar o fundir la grasa antes de extraer la pieza",
      "No confundir calentar con cocinar desde crudo",
      "Dorar en horno o sartén después de calentar el interior",
      "Seguir las instrucciones específicas de cada envase"
    ],
    "excerpt": "Una guía que separa los pasos de una conserva en lata y un confit refrigerado o al vacío, con especial atención a retirar la grasa y dorar al final.",
    "seo_title": "Cómo calentar un confit de pato en conserva o envasado al vacío",
    "seo_description": "Una guía que separa los pasos de una conserva en lata y un confit refrigerado o al vacío, con especial atención a retirar la grasa y dorar al final."
  },
  {
    "key": "duck-29",
    "slug": "con-que-acompanar-confit-de-pato-mejores-guarniciones",
    "title": "Con qué acompañar el confit de pato: las mejores guarniciones",
    "focus": "guarniciones para confit de pato",
    "group": "confit",
    "summary": "Una guía de acompañamientos para una carne tierna y grasa, buscando contrastes de textura, acidez y frescor.",
    "specifics": [
      "Patatas doradas con moderación de grasa",
      "Legumbres y verduras para platos más completos",
      "Ensaladas amargas o ácidas para equilibrar",
      "Frutas y salsas que aportan contraste sin exceso de dulzor"
    ],
    "excerpt": "Una guía de acompañamientos para una carne tierna y grasa, buscando contrastes de textura, acidez y frescor.",
    "seo_title": "Con qué acompañar el confit de pato: las mejores guarniciones",
    "seo_description": "Una guía de acompañamientos para una carne tierna y grasa, buscando contrastes de textura, acidez y frescor."
  },
  {
    "key": "duck-30",
    "slug": "confit-de-pato-a-la-naranja-receta-acompanamientos",
    "title": "Confit de pato a la naranja: receta fácil y acompañamientos",
    "focus": "confit de pato a la naranja",
    "group": "confit",
    "summary": "Una receta sencilla para combinar confit ya elaborado con una salsa cítrica, terminando la piel crujiente y evitando que el conjunto resulte pesado.",
    "specifics": [
      "Regenerar correctamente el confit",
      "Preparar una salsa de naranja equilibrada",
      "Reducir el dulzor con acidez o fondo",
      "Elegir una guarnición ligera"
    ],
    "excerpt": "Una receta sencilla para combinar confit ya elaborado con una salsa cítrica, terminando la piel crujiente y evitando que el conjunto resulte pesado.",
    "seo_title": "Confit de pato a la naranja: receta fácil y acompañamientos",
    "seo_description": "Una receta sencilla para combinar confit ya elaborado con una salsa cítrica, terminando la piel crujiente y evitando que el conjunto resulte pesado."
  },
  {
    "key": "duck-31",
    "slug": "que-hacer-con-confit-de-pato-desmigado-ideas",
    "title": "Qué hacer con confit de pato desmigado: ideas para aprovecharlo",
    "focus": "confit de pato desmigado recetas",
    "group": "confit",
    "summary": "Una guía de usos para carne de pato confitada y desmigada, muy práctica para arroces, croquetas, pasta, empanadas, ensaladas templadas y rellenos.",
    "specifics": [
      "Separar carne y exceso de grasa",
      "Aprovechar la textura ya melosa sin cocinarla demasiado",
      "Usar el desmigado como relleno o topping",
      "Combinar con ingredientes frescos o ácidos"
    ],
    "excerpt": "Una guía de usos para carne de pato confitada y desmigada, muy práctica para arroces, croquetas, pasta, empanadas, ensaladas templadas y rellenos.",
    "seo_title": "Qué hacer con confit de pato desmigado: ideas para aprovecharlo",
    "seo_description": "Una guía de usos para carne de pato confitada y desmigada, muy práctica para arroces, croquetas, pasta, empanadas, ensaladas templadas y rellenos."
  },
  {
    "key": "duck-32",
    "slug": "arroz-con-pato-como-prepararlo-confit-carne-pato",
    "title": "Arroz con pato: cómo prepararlo con confit o carne de pato",
    "focus": "arroz con pato",
    "group": "recetas",
    "summary": "Una guía para incorporar pato a arroces secos o melosos utilizando carne fresca, confit o desmigado, y aprovechando caldo y grasa con equilibrio.",
    "specifics": [
      "Elegir entre carne fresca y confitada",
      "Construir un fondo con sabor pero sin exceso de grasa",
      "Incorporar la carne en el momento adecuado",
      "Ajustar verduras, setas o cítricos al perfil del pato"
    ],
    "excerpt": "Una guía para incorporar pato a arroces secos o melosos utilizando carne fresca, confit o desmigado, y aprovechando caldo y grasa con equilibrio.",
    "seo_title": "Arroz con pato: cómo prepararlo con confit o carne de pato",
    "seo_description": "Una guía para incorporar pato a arroces secos o melosos utilizando carne fresca, confit o desmigado, y aprovechando caldo y grasa con equilibrio."
  },
  {
    "key": "duck-33",
    "slug": "croquetas-confit-de-pato-como-aprovecharlo",
    "title": "Croquetas de confit de pato: cómo aprovechar carne de pato desmigada",
    "focus": "croquetas de confit de pato",
    "group": "recetas",
    "summary": "Una guía para convertir confit desmigado en un relleno sabroso para croquetas, controlando grasa, textura y proporción de carne.",
    "specifics": [
      "Escurrir y picar la carne de pato",
      "Preparar una base cremosa sin que resulte aceitosa",
      "Enfriar bien la masa antes de formar",
      "Empanar y freír buscando contraste entre interior y exterior"
    ],
    "excerpt": "Una guía para convertir confit desmigado en un relleno sabroso para croquetas, controlando grasa, textura y proporción de carne.",
    "seo_title": "Croquetas de confit de pato: cómo aprovechar carne de pato desmigada",
    "seo_description": "Una guía para convertir confit desmigado en un relleno sabroso para croquetas, controlando grasa, textura y proporción de carne."
  },
  {
    "key": "duck-34",
    "slug": "como-servir-jamon-de-pato-temperatura-corte-presentacion",
    "title": "Cómo servir jamón de pato: temperatura, corte y presentación",
    "focus": "cómo servir jamón de pato",
    "group": "jamon",
    "summary": "Una guía de servicio para sacar partido a una pechuga curada, con consejos sobre temperatura, corte fino y distribución de la grasa en cada loncha.",
    "specifics": [
      "Atemperar ligeramente antes de servir",
      "Cortar fino para equilibrar carne y grasa",
      "Ordenar las lonchas sin amontonarlas",
      "Añadir acompañamientos con moderación"
    ],
    "excerpt": "Una guía de servicio para sacar partido a una pechuga curada, con consejos sobre temperatura, corte fino y distribución de la grasa en cada loncha.",
    "seo_title": "Cómo servir jamón de pato: temperatura, corte y presentación",
    "seo_description": "Una guía de servicio para sacar partido a una pechuga curada, con consejos sobre temperatura, corte fino y distribución de la grasa en cada loncha."
  },
  {
    "key": "duck-35",
    "slug": "con-que-acompanar-jamon-de-pato",
    "title": "Con qué acompañar el jamón de pato",
    "focus": "acompañamientos jamón de pato",
    "group": "jamon",
    "summary": "Una guía para combinar jamón de pato con panes, frutas, frutos secos, ensaladas y elementos ácidos que complementen el curado.",
    "specifics": [
      "Pan neutro o tostadas finas",
      "Frutas frescas y secas como contraste",
      "Hojas amargas y vinagretas ligeras",
      "Quesos suaves y frutos secos en pequeñas cantidades"
    ],
    "excerpt": "Una guía para combinar jamón de pato con panes, frutas, frutos secos, ensaladas y elementos ácidos que complementen el curado.",
    "seo_title": "Con qué acompañar el jamón de pato",
    "seo_description": "Una guía para combinar jamón de pato con panes, frutas, frutos secos, ensaladas y elementos ácidos que complementen el curado."
  },
  {
    "key": "duck-36",
    "slug": "canapes-con-jamon-de-pato-ideas-sencillas",
    "title": "Canapés con jamón de pato: ideas sencillas para aperitivos",
    "focus": "canapés con jamón de pato",
    "group": "jamon",
    "summary": "Una colección razonada de combinaciones para canapés en las que el jamón de pato aporta intensidad y grasa, acompañado de bases crujientes y contrastes frescos.",
    "specifics": [
      "Tostada fina con fruta o chutney poco dulce",
      "Blinis o pan suave con crema ligera",
      "Hojas verdes y cítricos en formato bocado",
      "Frutos secos y encurtidos como acentos"
    ],
    "excerpt": "Una colección razonada de combinaciones para canapés en las que el jamón de pato aporta intensidad y grasa, acompañado de bases crujientes y contrastes frescos.",
    "seo_title": "Canapés con jamón de pato: ideas sencillas para aperitivos",
    "seo_description": "Una colección razonada de combinaciones para canapés en las que el jamón de pato aporta intensidad y grasa, acompañado de bases crujientes y contrastes …"
  },
  {
    "key": "duck-37",
    "slug": "ensalada-con-jamon-de-pato-combinaciones",
    "title": "Ensalada con jamón de pato: combinaciones que funcionan",
    "focus": "ensalada con jamón de pato",
    "group": "jamon",
    "summary": "Una guía para construir ensaladas completas con jamón de pato, equilibrando grasa y salinidad con hojas, fruta, acidez y texturas crujientes.",
    "specifics": [
      "Hojas amargas como rúcula o escarola",
      "Fruta fresca o cítrica para aportar contraste",
      "Frutos secos y semillas para textura",
      "Vinagretas ligeras que no oculten el curado"
    ],
    "excerpt": "Una guía para construir ensaladas completas con jamón de pato, equilibrando grasa y salinidad con hojas, fruta, acidez y texturas crujientes.",
    "seo_title": "Ensalada con jamón de pato: combinaciones que funcionan",
    "seo_description": "Una guía para construir ensaladas completas con jamón de pato, equilibrando grasa y salinidad con hojas, fruta, acidez y texturas crujientes."
  },
  {
    "key": "duck-38",
    "slug": "como-conservar-jamon-de-pato-entero-loncheado",
    "title": "Cómo conservar jamón de pato entero y loncheado",
    "focus": "cómo conservar jamón de pato",
    "group": "jamon",
    "summary": "Una guía de conservación que diferencia pieza entera y loncheado, siempre priorizando la etiqueta del fabricante y la cadena de frío cuando corresponda.",
    "specifics": [
      "Mantener envases cerrados en las condiciones indicadas",
      "Reducir exposición al aire una vez abierto",
      "Evitar cambios repetidos de temperatura",
      "Consumir dentro del plazo indicado tras apertura"
    ],
    "excerpt": "Una guía de conservación que diferencia pieza entera y loncheado, siempre priorizando la etiqueta del fabricante y la cadena de frío cuando corresponda.",
    "seo_title": "Cómo conservar jamón de pato entero y loncheado",
    "seo_description": "Una guía de conservación que diferencia pieza entera y loncheado, siempre priorizando la etiqueta del fabricante y la cadena de frío cuando corresponda."
  },
  {
    "key": "duck-39",
    "slug": "como-servir-pate-de-pato-temperatura-pan-acompanamientos",
    "title": "Cómo servir paté de pato: temperatura, pan y acompañamientos",
    "focus": "cómo servir paté de pato",
    "group": "pate",
    "summary": "Una guía para servir paté de pato con la textura adecuada y acompañamientos que aporten contraste sin saturar el paladar.",
    "specifics": [
      "Ajustar temperatura según si es conserva o refrigerado",
      "Usar panes tostados pero no excesivamente aromáticos",
      "Añadir encurtidos o fruta para contraste",
      "Controlar el tamaño de las porciones"
    ],
    "excerpt": "Una guía para servir paté de pato con la textura adecuada y acompañamientos que aporten contraste sin saturar el paladar.",
    "seo_title": "Cómo servir paté de pato: temperatura, pan y acompañamientos",
    "seo_description": "Una guía para servir paté de pato con la textura adecuada y acompañamientos que aporten contraste sin saturar el paladar."
  },
  {
    "key": "duck-40",
    "slug": "canapes-con-pate-de-pato-ideas-faciles",
    "title": "Canapés con paté de pato: ideas fáciles para aperitivos",
    "focus": "canapés con paté de pato",
    "group": "pate",
    "summary": "Una guía de ideas rápidas para convertir paté de pato en aperitivos equilibrados, combinando una base crujiente, una capa cremosa y un contraste ácido o fresco.",
    "specifics": [
      "Tostadas y crackers de sabor neutro",
      "Fruta, mermelada o cebolla en pequeñas cantidades",
      "Pepinillos y encurtidos para acidez",
      "Hierbas frescas para terminar"
    ],
    "excerpt": "Una guía de ideas rápidas para convertir paté de pato en aperitivos equilibrados, combinando una base crujiente, una capa cremosa y un contraste ácido o fresco.",
    "seo_title": "Canapés con paté de pato: ideas fáciles para aperitivos",
    "seo_description": "Una guía de ideas rápidas para convertir paté de pato en aperitivos equilibrados, combinando una base crujiente, una capa cremosa y un contraste ácido o…"
  },
  {
    "key": "duck-41",
    "slug": "pate-mousse-rillettes-diferencias",
    "title": "Paté de pato, mousse de pato y rillettes: qué diferencia hay",
    "focus": "paté mousse rillettes diferencias",
    "group": "pate",
    "summary": "Una comparación entre tres preparaciones untables o servibles en pan que se distinguen sobre todo por estructura, proceso y textura.",
    "specifics": [
      "Paté como mezcla triturada y compacta",
      "Mousse como preparación más fina y aireada",
      "Rillettes como carne confitada y deshilachada",
      "Cómo elegir según textura y ocasión"
    ],
    "excerpt": "Una comparación entre tres preparaciones untables o servibles en pan que se distinguen sobre todo por estructura, proceso y textura.",
    "seo_title": "Paté de pato, mousse de pato y rillettes: qué diferencia hay",
    "seo_description": "Una comparación entre tres preparaciones untables o servibles en pan que se distinguen sobre todo por estructura, proceso y textura."
  },
  {
    "key": "duck-42",
    "slug": "mousse-de-pato-pimienta-cognac-orujo-diferencias",
    "title": "Mousse de pato a la pimienta, al coñac o al orujo: qué cambia entre ellas",
    "focus": "mousse de pato pimienta coñac orujo",
    "group": "pate",
    "summary": "Una guía para entender cómo distintos aromas modifican una base de mousse de pato sin cambiar la naturaleza principal de la preparación.",
    "specifics": [
      "Pimienta verde para un perfil especiado",
      "Coñac para notas aromáticas redondas",
      "Orujo para un carácter más marcado",
      "Cómo servir para que el aroma no domine"
    ],
    "excerpt": "Una guía para entender cómo distintos aromas modifican una base de mousse de pato sin cambiar la naturaleza principal de la preparación.",
    "seo_title": "Mousse de pato a la pimienta, al coñac o al orujo: qué cambia entre ellas",
    "seo_description": "Una guía para entender cómo distintos aromas modifican una base de mousse de pato sin cambiar la naturaleza principal de la preparación."
  },
  {
    "key": "duck-43",
    "slug": "como-servir-rillettes-de-pato-y-acompanarlas",
    "title": "Cómo servir rillettes de pato y con qué acompañarlas",
    "focus": "cómo servir rillettes de pato",
    "group": "pate",
    "summary": "Una guía de servicio para rillettes, buscando que la grasa esté maleable y la carne conserve su textura deshilachada.",
    "specifics": [
      "Temperatura ambiente o ligeramente templada según producto",
      "Pan rústico, tostadas y crackers",
      "Mostaza, pepinillos y encurtidos",
      "Frutas ácidas y ensaladas como contrapunto"
    ],
    "excerpt": "Una guía de servicio para rillettes, buscando que la grasa esté maleable y la carne conserve su textura deshilachada.",
    "seo_title": "Cómo servir rillettes de pato y con qué acompañarlas",
    "seo_description": "Una guía de servicio para rillettes, buscando que la grasa esté maleable y la carne conserve su textura deshilachada."
  },
  {
    "key": "duck-44",
    "slug": "solomillo-de-pato-que-corte-es-como-cocinarlo",
    "title": "Solomillo de pato: qué corte es y cómo cocinarlo",
    "focus": "solomillo de pato",
    "group": "cortes",
    "summary": "Una guía sobre una pieza pequeña y tierna del pato, diferente al magret, adecuada para cocciones breves y preparaciones rápidas.",
    "specifics": [
      "Dónde se encuentra el solomillo en la pechuga",
      "Diferencias de tamaño y grasa frente al magret",
      "Cocción rápida para preservar ternura",
      "Uso en brochetas, salteados o platos pequeños"
    ],
    "excerpt": "Una guía sobre una pieza pequeña y tierna del pato, diferente al magret, adecuada para cocciones breves y preparaciones rápidas.",
    "seo_title": "Solomillo de pato: qué corte es y cómo cocinarlo",
    "seo_description": "Una guía sobre una pieza pequeña y tierna del pato, diferente al magret, adecuada para cocciones breves y preparaciones rápidas."
  },
  {
    "key": "duck-45",
    "slug": "muslo-de-pato-fresco-y-confit-diferencias",
    "title": "Muslo de pato fresco y confit de pato: diferencias y formas de cocinarlo",
    "focus": "muslo de pato fresco confit diferencias",
    "group": "cortes",
    "summary": "Una comparación entre una pieza cruda que necesita cocción completa y un muslo ya confitado que solo requiere regeneración y acabado.",
    "specifics": [
      "Producto crudo frente a producto ya cocinado",
      "Tiempo y técnica necesarios en cada caso",
      "Cómo cambia la textura de la carne",
      "Diferencias de conservación y preparación"
    ],
    "excerpt": "Una comparación entre una pieza cruda que necesita cocción completa y un muslo ya confitado que solo requiere regeneración y acabado.",
    "seo_title": "Muslo de pato fresco y confit de pato: diferencias y formas de cocinarlo",
    "seo_description": "Una comparación entre una pieza cruda que necesita cocción completa y un muslo ya confitado que solo requiere regeneración y acabado."
  },
  {
    "key": "duck-46",
    "slug": "manchon-de-pato-que-parte-es-como-cocina",
    "title": "Manchón de pato: qué parte es y cómo se cocina",
    "focus": "manchón de pato",
    "group": "cortes",
    "summary": "Una guía sobre el muslito del ala del pato, una pieza pequeña con hueso que puede cocinarse fresca, guisada o confitada.",
    "specifics": [
      "Qué parte del ala se denomina manchón",
      "Por qué funciona bien en cocciones lentas",
      "Manchón fresco frente a confitado",
      "Cómo servir varias unidades como aperitivo o plato"
    ],
    "excerpt": "Una guía sobre el muslito del ala del pato, una pieza pequeña con hueso que puede cocinarse fresca, guisada o confitada.",
    "seo_title": "Manchón de pato: qué parte es y cómo se cocina",
    "seo_description": "Una guía sobre el muslito del ala del pato, una pieza pequeña con hueso que puede cocinarse fresca, guisada o confitada."
  },
  {
    "key": "duck-47",
    "slug": "corazones-de-pato-como-prepararlos-sabor",
    "title": "Corazones de pato: cómo prepararlos y qué sabor tienen",
    "focus": "corazones de pato",
    "group": "cortes",
    "summary": "Una guía sobre una casquería de sabor intenso y textura firme que puede encontrarse fresca o confitada y requiere limpieza y cocción adecuadas.",
    "specifics": [
      "Características de textura y sabor",
      "Limpieza cuando se trabaja producto fresco",
      "Salteado rápido frente a confitado",
      "Cómo combinar con setas, cebolla o hierbas"
    ],
    "excerpt": "Una guía sobre una casquería de sabor intenso y textura firme que puede encontrarse fresca o confitada y requiere limpieza y cocción adecuadas.",
    "seo_title": "Corazones de pato: cómo prepararlos y qué sabor tienen",
    "seo_description": "Una guía sobre una casquería de sabor intenso y textura firme que puede encontrarse fresca o confitada y requiere limpieza y cocción adecuadas."
  },
  {
    "key": "duck-48",
    "slug": "pato-entero-al-horno-jugoso-crujiente",
    "title": "Pato entero al horno: cómo cocinarlo para que quede jugoso y crujiente",
    "focus": "pato entero al horno",
    "group": "cortes",
    "summary": "Una guía para asar un pato entero gestionando la abundante grasa subcutánea, el dorado de la piel y la cocción completa de las distintas partes.",
    "specifics": [
      "Secar bien la piel antes de asar",
      "Facilitar la salida de grasa durante la cocción",
      "Proteger las zonas que se doran antes",
      "Reposar antes de trinchar"
    ],
    "excerpt": "Una guía para asar un pato entero gestionando la abundante grasa subcutánea, el dorado de la piel y la cocción completa de las distintas partes.",
    "seo_title": "Pato entero al horno: cómo cocinarlo para que quede jugoso y crujiente",
    "seo_description": "Una guía para asar un pato entero gestionando la abundante grasa subcutánea, el dorado de la piel y la cocción completa de las distintas partes."
  },
  {
    "key": "duck-49",
    "slug": "carne-de-pato-a-que-sabe-textura",
    "title": "Carne de pato: a qué sabe y qué textura tiene",
    "focus": "sabor carne de pato",
    "group": "fundamentos",
    "summary": "Una explicación sensorial de la carne de pato, más intensa y grasa que otras aves en muchos cortes, con diferencias notables entre pechuga, muslo y preparaciones curadas o confitadas.",
    "specifics": [
      "Sabor más marcado que pollo o pavo",
      "Textura del magret frente al muslo",
      "Influencia de piel y grasa",
      "Cómo cambian sabor y textura con curado, ahumado o confitado"
    ],
    "excerpt": "Una explicación sensorial de la carne de pato, más intensa y grasa que otras aves en muchos cortes, con diferencias notables entre pechuga, muslo y preparaciones curadas o confitadas.",
    "seo_title": "Carne de pato: a qué sabe y qué textura tiene",
    "seo_description": "Una explicación sensorial de la carne de pato, más intensa y grasa que otras aves en muchos cortes, con diferencias notables entre pechuga, muslo y prep…"
  },
  {
    "key": "duck-50",
    "slug": "carne-de-pato-vs-pollo-diferencias-sabor-cocina",
    "title": "Carne de pato y carne de pollo: principales diferencias en sabor y cocina",
    "focus": "carne de pato vs pollo",
    "group": "fundamentos",
    "summary": "Una comparación culinaria entre dos aves con distinta distribución de grasa, intensidad de sabor y técnicas habituales de cocina.",
    "specifics": [
      "Diferencias de grasa subcutánea y piel",
      "Sabor más intenso en el pato",
      "Cortes equivalentes que se comportan de forma distinta",
      "Por qué no conviene aplicar exactamente los mismos tiempos"
    ],
    "excerpt": "Una comparación culinaria entre dos aves con distinta distribución de grasa, intensidad de sabor y técnicas habituales de cocina.",
    "seo_title": "Carne de pato y carne de pollo: principales diferencias en sabor y cocina",
    "seo_description": "Una comparación culinaria entre dos aves con distinta distribución de grasa, intensidad de sabor y técnicas habituales de cocina."
  },
  {
    "key": "duck-51",
    "slug": "valor-nutricional-carne-de-pato-proteinas-grasas-calorias",
    "title": "Valor nutricional de la carne de pato: proteínas, grasas y calorías",
    "focus": "valor nutricional carne de pato",
    "group": "fundamentos",
    "summary": "Una guía prudente sobre composición nutricional que destaca que los valores cambian mucho según corte, presencia de piel, cocción y producto concreto.",
    "specifics": [
      "La carne aporta proteínas de alto valor biológico",
      "La piel aumenta notablemente el contenido graso",
      "Confitados y curados cambian sal y densidad energética",
      "La etiqueta del producto concreto es la referencia más útil"
    ],
    "excerpt": "Una guía prudente sobre composición nutricional que destaca que los valores cambian mucho según corte, presencia de piel, cocción y producto concreto.",
    "seo_title": "Valor nutricional de la carne de pato: proteínas, grasas y calorías",
    "seo_description": "Una guía prudente sobre composición nutricional que destaca que los valores cambian mucho según corte, presencia de piel, cocción y producto concreto."
  },
  {
    "key": "duck-52",
    "slug": "cuanta-carne-de-pato-calcular-por-persona",
    "title": "Cuánta carne de pato calcular por persona",
    "focus": "cantidad carne de pato por persona",
    "group": "fundamentos",
    "summary": "Una guía para calcular raciones según corte, hueso, grasa, acompañamientos y si el pato se sirve como plato principal o dentro de un menú amplio.",
    "specifics": [
      "No pesa igual una pieza con hueso que carne limpia",
      "Magret, muslo y pato entero requieren cálculos distintos",
      "Un menú con entrantes reduce la cantidad necesaria",
      "Conviene prever margen sin sobredimensionar"
    ],
    "excerpt": "Una guía para calcular raciones según corte, hueso, grasa, acompañamientos y si el pato se sirve como plato principal o dentro de un menú amplio.",
    "seo_title": "Cuánta carne de pato calcular por persona",
    "seo_description": "Una guía para calcular raciones según corte, hueso, grasa, acompañamientos y si el pato se sirve como plato principal o dentro de un menú amplio."
  },
  {
    "key": "duck-53",
    "slug": "como-conservar-congelar-descongelar-carne-de-pato",
    "title": "Cómo conservar, congelar y descongelar carne de pato correctamente",
    "focus": "conservar congelar descongelar pato",
    "group": "fundamentos",
    "summary": "Una guía de seguridad y calidad para mantener cadena de frío, congelar bien envuelto y descongelar en refrigeración siguiendo la etiqueta y las recomendaciones oficiales.",
    "specifics": [
      "Refrigerar de inmediato el producto fresco",
      "Congelar antes de la fecha límite cuando sea apropiado",
      "Descongelar en frigorífico y no a temperatura ambiente",
      "Evitar recongelaciones innecesarias"
    ],
    "excerpt": "Una guía de seguridad y calidad para mantener cadena de frío, congelar bien envuelto y descongelar en refrigeración siguiendo la etiqueta y las recomendaciones oficiales.",
    "seo_title": "Cómo conservar, congelar y descongelar carne de pato correctamente",
    "seo_description": "Una guía de seguridad y calidad para mantener cadena de frío, congelar bien envuelto y descongelar en refrigeración siguiendo la etiqueta y las recomend…"
  },
  {
    "key": "duck-54",
    "slug": "salsas-para-pato-naranja-frutos-rojos-pedro-ximenez",
    "title": "Salsas para pato: naranja, frutos rojos, Pedro Ximénez y otras combinaciones",
    "focus": "salsas para pato",
    "group": "recetas",
    "summary": "Una guía para elegir salsas que acompañen el pato desde perfiles cítricos y frutales hasta reducciones vínicas y fondos más salados.",
    "specifics": [
      "Naranja para un contraste cítrico clásico",
      "Frutos rojos para acidez y fruta",
      "Pedro Ximénez en cantidades medidas para evitar exceso de dulzor",
      "Fondos, mostazas y especias como alternativas menos dulces"
    ],
    "excerpt": "Una guía para elegir salsas que acompañen el pato desde perfiles cítricos y frutales hasta reducciones vínicas y fondos más salados.",
    "seo_title": "Salsas para pato: naranja, frutos rojos, Pedro Ximénez y otras combinaciones",
    "seo_description": "Una guía para elegir salsas que acompañen el pato desde perfiles cítricos y frutales hasta reducciones vínicas y fondos más salados."
  },
  {
    "key": "duck-55",
    "slug": "que-vino-combina-magret-confit-foie-jamon-pato",
    "title": "Qué vino combina con magret, confit, foie y jamón de pato",
    "focus": "vino para pato foie confit magret",
    "group": "maridaje",
    "summary": "Una guía de maridaje orientativa que distingue preparaciones grasas, curadas y confitadas, priorizando equilibrio de acidez, dulzor, cuerpo y aroma.",
    "specifics": [
      "Foie y vinos con dulzor o buena acidez",
      "Magret con tintos de fruta y tanino moderado",
      "Confit con vinos capaces de limpiar grasa",
      "Jamón de pato con opciones secas y aromáticas"
    ],
    "excerpt": "Una guía de maridaje orientativa que distingue preparaciones grasas, curadas y confitadas, priorizando equilibrio de acidez, dulzor, cuerpo y aroma.",
    "seo_title": "Qué vino combina con magret, confit, foie y jamón de pato",
    "seo_description": "Una guía de maridaje orientativa que distingue preparaciones grasas, curadas y confitadas, priorizando equilibrio de acidez, dulzor, cuerpo y aroma."
  },
  {
    "key": "duck-56",
    "slug": "pato-con-frutos-rojos-por-que-funciona-como-prepararlo",
    "title": "Pato con frutos rojos: por qué funciona y cómo prepararlo",
    "focus": "pato con frutos rojos",
    "group": "recetas",
    "summary": "Una guía culinaria sobre el contraste entre carne de pato y frutas ácidas como frambuesa, mora, grosella o cereza.",
    "specifics": [
      "Acidez para equilibrar grasa",
      "Fruta madura sin convertir la salsa en mermelada",
      "Fondos y vinagre para dar profundidad",
      "Qué cortes de pato funcionan mejor"
    ],
    "excerpt": "Una guía culinaria sobre el contraste entre carne de pato y frutas ácidas como frambuesa, mora, grosella o cereza.",
    "seo_title": "Pato con frutos rojos: por qué funciona y cómo prepararlo",
    "seo_description": "Una guía culinaria sobre el contraste entre carne de pato y frutas ácidas como frambuesa, mora, grosella o cereza."
  },
  {
    "key": "duck-57",
    "slug": "pato-con-pedro-ximenez-cortes-preparados",
    "title": "Pato con Pedro Ximénez: qué cortes y preparados combinan mejor",
    "focus": "pato con Pedro Ximénez",
    "group": "recetas",
    "summary": "Una guía para usar Pedro Ximénez como elemento aromático y dulce en salsas para pato sin ocultar el sabor de la carne.",
    "specifics": [
      "Reducir con fondo o elemento ácido",
      "Magret como opción para salsas brillantes",
      "Confit para combinaciones más intensas",
      "Usar poca cantidad y probar antes de añadir más"
    ],
    "excerpt": "Una guía para usar Pedro Ximénez como elemento aromático y dulce en salsas para pato sin ocultar el sabor de la carne.",
    "seo_title": "Pato con Pedro Ximénez: qué cortes y preparados combinan mejor",
    "seo_description": "Una guía para usar Pedro Ximénez como elemento aromático y dulce en salsas para pato sin ocultar el sabor de la carne."
  },
  {
    "key": "duck-58",
    "slug": "aperitivos-con-foie-gras-navidad-ocasiones-especiales",
    "title": "Aperitivos con foie gras para Navidad y ocasiones especiales",
    "focus": "aperitivos con foie gras",
    "group": "foie",
    "summary": "Una guía de aperitivos con foie en formatos pequeños, pensados para menús festivos en los que el producto debe destacar sin saturar.",
    "specifics": [
      "Tostadas finas y canapés pequeños",
      "Fruta o compotas con poca azúcar",
      "Presentaciones individuales para controlar raciones",
      "Preparar bases con antelación y montar al final"
    ],
    "excerpt": "Una guía de aperitivos con foie en formatos pequeños, pensados para menús festivos en los que el producto debe destacar sin saturar.",
    "seo_title": "Aperitivos con foie gras para Navidad y ocasiones especiales",
    "seo_description": "Una guía de aperitivos con foie en formatos pequeños, pensados para menús festivos en los que el producto debe destacar sin saturar."
  },
  {
    "key": "duck-59",
    "slug": "menu-navidad-con-pato-foie-magret-confit-jamon",
    "title": "Menú de Navidad con pato: ideas con foie, magret, confit y jamón de pato",
    "focus": "menú de Navidad con pato",
    "group": "navidad",
    "summary": "Una propuesta de arquitectura de menú que reparte distintos productos de pato entre aperitivo, entrante y principal sin repetir demasiada grasa o intensidad.",
    "specifics": [
      "Jamón de pato para abrir el aperitivo",
      "Foie en una ración pequeña como entrante",
      "Magret o confit como plato principal, no ambos a la vez",
      "Guarniciones frescas y postres ligeros para equilibrar"
    ],
    "excerpt": "Una propuesta de arquitectura de menú que reparte distintos productos de pato entre aperitivo, entrante y principal sin repetir demasiada grasa o intensidad.",
    "seo_title": "Menú de Navidad con pato: ideas con foie, magret, confit y jamón de pato",
    "seo_description": "Una propuesta de arquitectura de menú que reparte distintos productos de pato entre aperitivo, entrante y principal sin repetir demasiada grasa o intens…"
  },
  {
    "key": "duck-60",
    "slug": "productos-de-pato-para-regalar-foie-pates-jamon-lotes",
    "title": "Productos de pato para regalar: foie, patés, jamón y lotes gourmet",
    "focus": "productos de pato para regalar",
    "group": "regalos",
    "summary": "Una guía para elegir regalos gastronómicos de pato según conservación, formato, facilidad de consumo y perfil del destinatario.",
    "specifics": [
      "Foie gras para un regalo gastronómico clásico",
      "Jamón de pato como producto curado fácil de compartir",
      "Patés, mousses y rillettes en formatos variados",
      "Lotes que combinen texturas sin duplicar productos similares"
    ],
    "excerpt": "Una guía para elegir regalos gastronómicos de pato según conservación, formato, facilidad de consumo y perfil del destinatario.",
    "seo_title": "Productos de pato para regalar: foie, patés, jamón y lotes gourmet",
    "seo_description": "Una guía para elegir regalos gastronómicos de pato según conservación, formato, facilidad de consumo y perfil del destinatario."
  }
]
JSON;
$pages=json_decode($raw,true);
if(!is_array($pages) || count($pages)!==60) throw new Exception('Invalid duck page dataset');

$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
$author=$admins?(int)$admins[0]:1;
$rows=array();

foreach($pages as $p){
    $content=emdo_duck_content($p,$pages);
    $words=emdo_duck_words($content);
    if($words<650) throw new Exception($p['key'].' too short: '.$words);

    $existing=emdo_duck_existing($p['key'],$p['slug']);
    $post=array(
        'post_type'=>'page','post_status'=>'publish','post_author'=>$author,
        'post_title'=>wp_strip_all_tags($p['title']),'post_name'=>sanitize_title($p['slug']),
        'post_excerpt'=>wp_strip_all_tags($p['excerpt']),'post_content'=>$content,
        'comment_status'=>'closed','ping_status'=>'closed','post_parent'=>0,'menu_order'=>0
    );
    if($existing) $post['ID']=$existing;
    $res=$existing?wp_update_post($post,true):wp_insert_post($post,true);
    if(is_wp_error($res)) throw new Exception($p['key'].': '.$res->get_error_message());

    $id=(int)$res;
    update_post_meta($id,'_emdo_seo_landing_key',$p['key']);
    update_post_meta($id,'_emdo_seo_landing_batch','20260927-duck-cluster');
    update_post_meta($id,'_emdo_seo_landing_hidden_navigation','1');
    update_post_meta($id,'_emdo_seo_landing_updated_at',gmdate('c'));
    update_post_meta($id,'_yoast_wpseo_title',$p['seo_title']);
    update_post_meta($id,'_yoast_wpseo_metadesc',$p['seo_description']);
    update_post_meta($id,'_yoast_wpseo_focuskw',$p['focus']);
    update_post_meta($id,'rank_math_title',$p['seo_title']);
    update_post_meta($id,'rank_math_description',$p['seo_description']);
    update_post_meta($id,'rank_math_focus_keyword',$p['focus']);
    delete_post_meta($id,'_yoast_wpseo_meta-robots-noindex');
    delete_post_meta($id,'rank_math_robots');
    clean_post_cache($id);
    $rows[]=array('key'=>$p['key'],'id'=>$id,'title'=>get_the_title($id),'slug'=>get_post_field('post_name',$id),'type'=>get_post_type($id),'status'=>get_post_status($id),'words'=>$words,'url'=>get_permalink($id));
}
if(count($rows)!==60) throw new Exception('Expected 60 duck pages');
echo wp_json_encode(array('batch'=>'20260927-duck-cluster','count'=>count($rows),'pages'=>$rows),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
