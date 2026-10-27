<?php
/**
 * Plugin Name: EMDO - Musée packs in admin review
 * Description: Protects the four unapproved Musée packs; three approved packs are exported to Mentta only, with public exclusion enforced independently.
 * Version: 1.1.0
 */
if (!defined('ABSPATH')) exit;
/* Owner approved 21814, 21818 and 21820 for Mentta on 2026-10-27.
   Only the four NOT approved are blocked from the Mentta endpoint. */
function emdo_musee_review_ids() {
    return array(21816,21822,21824,21826);
}
function emdo_musee_approved_mentta_ids() {
    return array(21814,21818,21820);
}
function emdo_musee_review_preview_allowed() {
    if (is_admin() || !is_user_logged_in() || !current_user_can('manage_options')) return false;
    if (!isset($_GET['preview']) || !wp_validate_boolean(wp_unslash($_GET['preview']))) return false;
    $id=absint($_GET['p'] ?? $_GET['preview_id'] ?? 0);
    return in_array($id,emdo_musee_review_ids(),true)
        && current_user_can('edit_post',$id)
        && get_post_meta($id,'_emdo_mentta_pack_key',true)!=='';
}
/* The existing exclusive-products guard can call this function on any request.
   Only a logged-in WP administrator viewing a specific protected preview gets the exception. */
add_filter('woocommerce_product_is_visible',static function($visible,$id){
    if (in_array((int)$id,emdo_musee_review_ids(),true) && function_exists('emdo_montjam_mentta_product_export_request')
        && emdo_montjam_mentta_product_export_request()) return false;
    return $visible;
},PHP_INT_MAX,2);
/* Defence in depth: even a manually selected ID cannot be fetched from Mentta's product REST endpoint. */
add_filter('rest_pre_dispatch',static function($value,$server,$request){
    if (!($request instanceof WP_REST_Request) || rtrim($request->get_route(),'/')!=='/mentta_marketplace/products') return $value;
    $id=absint($request->get_param('product_id'));
    if (in_array($id,emdo_musee_review_ids(),true))
        return new WP_Error('emdo_musee_review_pending','Pack pendiente de revisión; exportación a Mentta detenida.',array('status'=>404));
    return $value;
},9,3);
/* Strip only the four remaining unapproved IDs from paginated Mentta exports. */
add_filter('rest_post_dispatch',static function($response,$server,$request) {
    if(!($response instanceof WP_REST_Response) || !($request instanceof WP_REST_Request)
        || rtrim($request->get_route(),'/')!=='/mentta_marketplace/products') return $response;
    $value=$response->get_data();
    $filter=static function($items) {
        return array_values(array_filter($items,static function($item) {
            $id=is_array($item)?($item['Id']??$item['id']??0):(is_object($item)?($item->Id??$item->id??0):0);
            return !in_array((int)$id,emdo_musee_review_ids(),true);
        }));
    };
    if(is_object($value) && isset($value->data) && is_array($value->data)) {
        $value->data=$filter($value->data); $response->set_data($value);
    } elseif(is_array($value)) {
        if(isset($value['data']) && is_array($value['data'])) $value['data']=$filter($value['data']);
        elseif(array_is_list($value))$value=$filter($value);
        $response->set_data($value);
    }
    return $response;
},PHP_INT_MAX,3);
