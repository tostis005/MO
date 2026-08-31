<?php
// Reverification after corrected Batch 02 deployment.
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;
$trp=$wpdb->prefix.'trp_dictionary_es_es_en_us';
$trp_exists=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$trp))===$trp;

$batches=[
 'BATCH01'=>[
  11058=>['title'=>'Carne picada extra','es'=>'Carne picada elaborada con 100% ternera y sin aditivos','en_title'=>'Extra Ground Beef','en'=>'Ground beef made with 100% beef and no added additives.'],
  11061=>['title'=>'Burger mixtas - sin gluten (2 uds)','es'=>'Hamburguesas elaboradas con una mezcla al 50% de carne de ternera y carne de cerdo de la zona','en_title'=>'Gluten-Free Beef & Pork Burgers','en'=>'Burgers made with an equal blend of beef and locally sourced pork.'],
  11064=>['title'=>'Filetes primera','es'=>'Filetes de ternera procedentes de piezas especialmente adecuadas para una cocción rápida','en_title'=>'First-Category Beef Steaks','en'=>'Beef steaks cut from tender pieces such as the knuckle or rump'],
  11073=>['title'=>'Magro o ragú de ternera','es'=>'Carne de ternera cortada a mano y pensada especialmente para preparaciones','en_title'=>'Diced Beef for Ragout','en'=>'Hand-cut beef designed for dishes where the meat has time to cook slowly'],
  11075=>['title'=>'Entraña de ternera','es'=>'La entraña es un corte fino','en_title'=>'Beef Skirt Steak – Entraña','en'=>'Entraña is a thin, flavourful cut taken from the inside of the rib area.'],
 ],
 'BATCH02'=>[
  11077=>['title'=>'Entrecot de lomo bajo','es'=>'Entrecot de ternera seleccionado del lomo bajo','en_title'=>'Beef Striploin Steak','en'=>'Beef steak selected from the striploin and presented boneless.'],
  11079=>['title'=>'Filetes aguja de ternera','es'=>'Filetes de aguja de ternera jugosos y sabrosos','en_title'=>'Beef Chuck Steaks','en'=>'Juicy, flavourful beef chuck steaks with intramuscular fat'],
  11082=>['title'=>'Chuletón de vaca vieja madurado','es'=>'Chuletón de lomo alto procedente de vacas seleccionadas','en_title'=>'Matured Old Cow Rib Steak','en'=>'Rib steak from the high loin of selected mature cows'],
  11087=>['title'=>'Solomillo de ternera','es'=>'Solomillo de ternera, una de las piezas más apreciadas por su ternura','en_title'=>'Beef Tenderloin','en'=>'Beef tenderloin, one of the most prized cuts for its tenderness'],
  11090=>['title'=>'Morcillo de ternera','es'=>'Morcillo de ternera procedente de la parte baja de la pata o jarrete','en_title'=>'Beef Shin','en'=>'Beef shin from the lower leg or shank.'],
 ],
];

$all_ok=true;
foreach($batches as $batch=>$items){
 echo "==== {$batch} VERIFY ====\n";
 $batch_ok=true;
 foreach($items as $id=>$spec){
  $p=get_post($id);
  $exists=$p&&$p->post_type==='product'&&$p->post_title===$spec['title'];
  $vendor=''; if($exists){$u=get_userdata((int)$p->post_author);$vendor=$u?$u->display_name:'';}
  $es_excerpt=$exists&&strpos((string)$p->post_excerpt,$spec['es'])!==false;
  $es_producer=$exists&&strpos((string)$p->post_content,'Tolecarnes es una ganadería familiar de Menasalbas')!==false;
  $es_faq=$exists&&strpos((string)$p->post_content,'<h2>Preguntas frecuentes</h2>')!==false;
  $en_title=$en_marker=$en_producer=false;
  if($trp_exists&&$exists){
   $en_title=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$trp} WHERE original=%s AND translated=%s AND status=2",$p->post_title,$spec['en_title']))>0;
   $en_marker=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$trp} WHERE translated LIKE %s AND status=2",'%'.$wpdb->esc_like($spec['en']).'%'))>0;
   $en_producer=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$trp} WHERE original=%s AND translated=%s AND status=2",'Sobre Tolecarnes','About Tolecarnes'))>0;
  }
  $ok=$exists&&stripos($vendor,'tolecarnes')!==false&&$es_excerpt&&$es_producer&&$es_faq&&$en_title&&$en_marker&&$en_producer;
  if(!$ok){$batch_ok=false;$all_ok=false;}
  echo "VERIFY {$batch} ID={$id} title=".($exists?$p->post_title:'MISSING')." vendor={$vendor} ES_EXCERPT=".($es_excerpt?'yes':'no')." ES_PRODUCER=".($es_producer?'yes':'no')." ES_FAQ=".($es_faq?'yes':'no')." EN_TITLE=".($en_title?'yes':'no')." EN_MARKER=".($en_marker?'yes':'no')." EN_PRODUCER=".($en_producer?'yes':'no')." STATUS=".($ok?'OK':'FAIL')."\n";
 }
 echo "{$batch}_OVERALL=".($batch_ok?'OK':'FAIL')."\n";
}
echo 'ALL_BATCHES_OVERALL='.($all_ok?'OK':'FAIL')."\n";
echo "DIAGNOSTIC_DONE\n";
