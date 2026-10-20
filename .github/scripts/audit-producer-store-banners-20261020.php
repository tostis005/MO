<?php
// Read-only production audit of public vendor banner settings.
$want = ['1957','hidalgo','tolecarnes','huerta','montjam','selectos','latitudes','chocolates','ikore','tropitop'];
foreach ( get_users(['role__in'=>['wcfm_vendor','vendor','seller'],'number'=>100]) as $user ) {
    $settings = get_user_meta($user->ID, 'wcfmmp_profile_settings', true);
    $name = is_array($settings) ? ($settings['store_name'] ?? $user->display_name) : $user->display_name;
    if (!array_filter($want, static fn($s) => str_contains(strtolower($name), $s))) continue;
    $banner = is_array($settings) ? ($settings['banner'] ?? null) : null;
    $list = is_array($settings) ? ($settings['list_banner'] ?? null) : null;
    $type = is_array($settings) ? ($settings['banner_type'] ?? null) : null;
    echo 'EMDO_STORE_BANNER=' . wp_json_encode(['id'=>$user->ID,'slug'=>$user->user_nicename,'name'=>$name,'banner'=>$banner,'list_banner'=>$list,'banner_type'=>$type], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
