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
        var that = this;
        var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
   
        // 入力フォームの「次へ」ボタンクリック時
        jQuery('#sform_button_confirm').on('click', function(){
            onClickConfirm(that);
        });
        // 入力フォームの「キャンセル」ボタンクリック時
        jQuery('#sform_button_cancel').on('click', function(){
            onClickCancel(that);
        });
        
        function onClickConfirm(that) {
            var tmpData = {};
            // フォーム入力内容でjson作る
            jQuery(".sform-col-form-text").each(function(index, elem){
                tmpData[this.name] = this.value;
            });
            jQuery(".sform-col-form-checkbox, .sform-col-form-radio").each(function(){
                var tmpChecked = [];
                jQuery("[name=sel_" + this.id + "]").each(function(){
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
                    var response_data = JSON.parse(JSON.parse(response).data);
                    if(Object.keys(response_data.validate_result).length === 0) {
                        // validate ok なら確認フォームを表示
                        jQuery.ajax({
                            type: 'POST',
                            url: ajaxurl,
                            data: {
                                'action' : 'msform_confirm_form',
                                'contentType' : 'application/json',
                                'data' :  tmpData,
                                'form_id' : jQuery("#sform_form_id").val(),
                                'cache_id' : response_data.cache_id
                            },
                            success: function( response ) {
                                var response_data = JSON.parse(JSON.parse(response).data);
                                jQuery("div.sform_wrapper").empty();
                                jQuery("div.sform_wrapper").append(response_data);
                                // 「送信」クリック時
                                jQuery('#sform_button_submit').on('click', function(){
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
                                            var response_data = JSON.parse(JSON.parse(response).html);
                                            jQuery("div.sform_wrapper").empty();
                                            jQuery("div.sform_wrapper").append(response_data);
                                                // 「完了」ボタンクリック時
                                                jQuery('#sform_button_finish').on('click', function(){
                                                location.href = jQuery("#complete_url").val();
                                            });

                                        }
                                    })
                                });
                                // 「戻る」クリック時
                                jQuery('#sform_button_back').on('click', function(){
                                    jQuery.ajax({
                                        type: 'POST',
                                        url: ajaxurl,
                                        data: {
                                            'action' : 'msform_load_form',
                                            'contentType' : 'application/json',
                                            'form_id' : jQuery("#hashed_id").val(),
                                            'cache_id' : jQuery("#cache_id").val()
                                        },
                                        success: function(response) {
                                            jQuery("div.sform_wrapper").empty();
                                            jQuery("div.sform_wrapper").append(response);
                                            // 入力フォームの「次へ」ボタンクリック時
                                            jQuery('#sform_button_confirm').on('click', function(){
                                                that.onClickConfirm(that);
                                            });
                                            // 入力フォームの「キャンセル」ボタンクリック時
                                            jQuery('#sform_button_cancel').on('click', function(){
                                                that.onClickCancel(that);
                                            });
                                        }
                                    })
                                });
                            },
                            error: function(a,b,c){
                                console.log(a);
                            }
                        });
                    } else {
                        // validate ng なら入力フォームにエラーメッセージを追加
                        Object.keys(response_data.validate_result).forEach(function(k){
                            jQuery('#sform-col-input-error-' + k).text(response_data.validate_result[k]);
                        })
                    }
                },
                error: function(a,b,c){
                    alert( 'error' );
                    //console.log(a);
                    //console.log(b);
                }
            });
        }

        function onClickCancel() {
            location.href = jQuery("#cancel_url").val();
        }
    //]]>
    </script>
<?php    
}
add_action( 'wp_footer', 'msform_js_footer' );

/**
 * フォーム読み込み（主にリロード）
 */
function msform_load_form(){
    $loader = new MacolaboSformLoader();
    $response = $loader->form_load($_POST['form_id'], $_POST['cache_id']);
    die($response);
}

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

/**
 * 疎通確認（ログインできるか）
 */
function msform_connection_check(){
  $loader = new MacolaboSformLoader();
  $data = $_POST['data'];
  $info = [
    'api_url' => $data['api_url'],
    'user_id' => $data['user_id'],
    'password' => $data['password'],
    'group' => $data['group']
  ];
  $response = json_encode($loader->_login($info));
  die($response);
}

add_action('wp_ajax_msform_load_form', 'msform_load_form');
add_action('wp_ajax_msform_validate_form', 'msform_validate_form');
add_action('wp_ajax_nopriv_msform_validate_form', 'msform_validate_form');
add_action('wp_ajax_msform_confirm_form', 'msform_confirm_form');
add_action('wp_ajax_nopriv_msform_confirm_form', 'msform_confirm_form');
add_action('wp_ajax_msform_save_form', 'msform_save_form');
add_action('wp_ajax_nopriv_msform_save_form', 'msform_save_form');
add_action('wp_ajax_msform_connection_check', 'msform_connection_check');

add_filter( 'the_content', function($content) {
    $loader = new MacolaboSformLoader();
    return $loader->form_initial_load($content, null);
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