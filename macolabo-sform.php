<?php
/*
Plugin Name: Sform Plugin
Plugin URI:
Description: Sformによるフォーム表示
Version: 0.0.1
Author: Macorains
Author URI:
License: GPL2
*/
?>
<?php
/*  Copyright 2019 Macorains (email : mac.rainshrine@gmail.com)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
     published by the Free Software Foundation.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
require(dirname(__FILE__) . "/macolabo-sform-setting.php");
require(dirname(__FILE__) . "/macolabo-sform-loader.php");

function msform_hello() {
    $loader = new MacolaboSformLoader();
    return $loader->hello();
}

// Sform JS
function msform_js_footer(){
    wp_print_scripts( array( 'jquery' ));
    ?>
    <script type="text/javascript">
    //<![CDATA[
        var ajaxurl = '<?php echo admin_url( 'admin-ajax.php'); ?>';
        jQuery( '#submit-x' ).on( 'click', function(){
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    'action' : 'view_sitename'
                },
                success: function( response ){
                    alert( response );
                },
                error: function(){
                    alert( 'error' );
                }
            });
            return false;
        });
    //]]>
    </script>
<?php    
}
add_action( 'wp_footer', 'msform_js_footer' );

/**
 * フォームバリデーション
 */
function msform_validate_form(){

}

/**
 * 確認ページ取得
 */
function msform_confirm_form(){

}

/**
 * フォーム保存
 */
function msform_save_form(){

}

add_action('wp_ajax_msform_validate_form', 'msform_validate_form');
add_action('wp_ajax_nopriv_msform_validate_form', 'msform_validate_form');
add_action('wp_ajax_msform_confirm_form', 'msform_confirm_form');
add_action('wp_ajax_nopriv_msform_confirm_form', 'msform_confirm_form');
add_action('wp_ajax_msform_save_form', 'msform_save_form');
add_action('wp_ajax_nopriv_msform_save_form', 'msform_save_form');

/*
function view_sitename(){
    echo get_bloginfo( 'name' );
    echo ('!!!');
    die();
}
add_action( 'wp_ajax_view_sitename', 'view_sitename' );
add_action( 'wp_ajax_nopriv_view_sitename', 'view_sitename' );
// ajax test
*/

add_filter( 'the_content', function($content) {
    $loader = new MacolaboSformLoader();
    //return 'おおお';
	return $loader->tag_replace($content);
} );


if(is_admin()){
    $msform_setting_page = new MacolaboSformSettingPage();
}


?>