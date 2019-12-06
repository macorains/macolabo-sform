<?php
/*
Plugin Name: Sform Plugin
Plugin URI:
Description: Sformによるフォーム表示
Version: 0.0.2
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
    wp_enqueue_script('macolabo-sform', plugins_url( 'macolabo-sform' ) . '/macolabo-sform.js');
    wp_print_scripts( array( 'jquery' ));
    ?>
    <script type="text/javascript">
    //<![CDATA[
        var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
   
        // 入力フォームの「次へ」ボタンクリック時
        jQuery('#sform_button_confirm').on('click', function(){
            var tmpData = {};

            // フォーム入力内容でjson作る
            jQuery(".sform-col-form-text").each(function(){
                tmpData[this.id] = this.value;
            });
            jQuery(".sform-col-form-checkbox, .sform-col-form-radio").each(function(){
                var tmpChecked = [];
                $("[name=sel_" + this.id + "]").each(function(){
                    if(this.checked) tmpChecked.push(this.value);
                })
                tmpData[this.id] = tmpChecked.join();
            });

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    'action' : 'msform_validate_form',
                    'contentType' : 'application/json',
                    'data' : tmpData,
                    'form_id' : jQuery("#sform_form_id").val()
                },
                success: function( response ){
                    // validate ok なら確認フォームを表示
                    var response_data = JSON.parse(JSON.parse(response).data);
                    jQuery.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            'action' : 'msform_confirm_form',
                            'contentType' : 'application/json',
                            'data' : tmpData,
                            'form_id' : jQuery("#sform_form_id").val(),
                            'cache_id' : response_data.id
                        },
                        success: function( response ) {
                            console.log(response);
                            var response_data = JSON.parse(JSON.parse(response).data);
                            jQuery("div.sform_wrapper").empty();
                            jQuery("div.sform_wrapper").append(response_data);
                            jQuery('#sform_button_submit').on('click', function(){
                                alert('submit!');
                                jQuery.ajax({
                                    type: 'POST',
                                    url: ajaxurl,
                                    data: {
                                        'action' : 'msform_save_form',
                                        'contentType' : 'application/json',
                                        'data' : tmpData,
                                        'form_id' : jQuery("#hashed_id").val(),
                                        'cache_id' : jQuery("#cache_id").val()
                                    },
                                    success: function(response) {
                                        console.log(response);
                                        var response_data = JSON.parse(JSON.parse(response).html);
                                        jQuery("div.sform_wrapper").empty();
                                        jQuery("div.sform_wrapper").append(response_data);
                                    }
                                })
                            });
                        },
                        error: function(a,b,c){
                            console.log(a);
                        }
                    });
                    // validate ng なら入力フォームにエラーメッセージを追加
                },
                error: function(a,b,c){
                    alert( 'error' );
                    //console.log(a);
                    //console.log(b);
                }
            });

            // 入力フォームの「送信」ボタンクリック時
            jQuery('#sform_button_submit').on('click', function(){
                alert('!!!');
            });
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
    $loader = new MacolaboSformLoader();
    $response = $loader->form_validate($_POST['form_id'], $_POST['data']);
    die($response);
}

/**
 * 確認ページ取得
 */
function msform_confirm_form(){
    $loader = new MacolaboSformLoader();
    $response = $loader->form_confirm($_POST['form_id'], $_POST['data'], $_POST['cache_id']);
    die($response);
}

/**
 * フォーム保存
 */
function msform_save_form(){
    $loader = new MacolaboSformLoader();
    $response = $loader->form_save($_POST['form_id'], $_POST['cache_id']);
    die($response);

}

add_action('wp_ajax_msform_validate_form', 'msform_validate_form');
add_action('wp_ajax_nopriv_msform_validate_form', 'msform_validate_form');
add_action('wp_ajax_msform_confirm_form', 'msform_confirm_form');
add_action('wp_ajax_nopriv_msform_confirm_form', 'msform_confirm_form');
add_action('wp_ajax_msform_save_form', 'msform_save_form');
add_action('wp_ajax_nopriv_msform_save_form', 'msform_save_form');

add_filter( 'the_content', function($content) {
    $loader = new MacolaboSformLoader();
    return $loader->form_load(null, $content, null);
} );


if(is_admin()){
    $msform_setting_page = new MacolaboSformSettingPage();
}


if(!function_exists('_log')){
    function _log($message) {
      if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
          error_log(print_r($message, true));
        } else {
          error_log($message);
        }
      }
    }
}
?>