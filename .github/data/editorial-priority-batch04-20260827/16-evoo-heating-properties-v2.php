<?php
$a=require __DIR__.'/16-evoo-heating-properties.php';
$extra='<h2>Does lower heat preserve more of the oil?</h2><p>As a general principle, shorter exposure and lower temperatures reduce thermal stress, although cooking technique matters. A gentle stew, a quick sauté and a prolonged frying session are not equivalent treatments. This is also why adding a small amount of fresh EVOO at the end of a cooked dish can be useful: the cooked portion contributes during preparation while the finishing portion preserves more of the original aroma and phenolic profile.</p>';
$a['en_content']=str_replace('<h2>The conclusion: EVOO changes, but it does not lose everything</h2>',$extra.'<h2>The conclusion: EVOO changes, but it does not lose everything</h2>',$a['en_content']);
return $a;
