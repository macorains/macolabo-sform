<?php
/**
 * Sform設定画面用クラス
 */
class MacolaboSformSettingPage {
    private $options; 

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    /**
     * メニューを追加
     */
    public function add_plugin_page()
    {
        add_menu_page('Sform設定', 'Sform設定', 'manage_options', 'msform_setting', array($this, 'create_setting_page'));
    }

    /**
     * 設定ページの初期化
     */
    public function page_init()
    {
        register_setting('msform_setting', 'msform_setting', array($this, 'sanitize'));
        add_settings_section('msform_setting_section_id', '', '', 'msform_setting');
        add_settings_field('api_url', 'URL', array($this, 'api_url_callback'), 'msform_setting', 'msform_setting_section_id');
        add_settings_field('user_id', 'ユーザーID', array($this, 'user_id_callback'), 'msform_setting', 'msform_setting_section_id');
        add_settings_field('group', 'グループ', array($this, 'group_callback'), 'msform_setting', 'msform_setting_section_id');
        add_settings_field('password', 'パスワード', array($this, 'password_callback'), 'msform_setting', 'msform_setting_section_id');
    }

    /**
     * 設定ページのHTML出力
     */
    public function create_setting_page()
    {
        $this->options = get_option('msform_setting');
        ?>
        <div class="wrap">
            <h2>Sform設定</h2>
            <?php
            global $parent_file;
            if($parent_file != 'options-general.php'){
                require(ABSPATH . 'wp-admin/options-head.php');
            }
            ?>
            <form method="post" action="options.php">
            <?php
                settings_fields('msform_setting');
                do_settings_sections('msform_setting');
                $this->test_button();
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * 入力項目「URL」のHTML出力
     */
    public function api_url_callback()
    {
        $api_url = isset($this->options['api_url']) ? $this->options['api_url'] : '';
        ?>
        <input type="text" id="api_url" name="msform_setting[api_url]" value="<?php esc_attr_e($api_url)?>" />
        <?php
    }

    /**
     * 入力項目「ユーザーID」のHTML出力
     */
    public function user_id_callback()
    {
        $user_id = isset($this->options['user_id']) ? $this->options['user_id'] : '';
        ?>
        <input type="text" id="user_id" name="msform_setting[user_id]" value="<?php esc_attr_e($user_id)?>" />
        <?php
    }

    /**
     * 入力項目「グループ」のHTML出力
     */

    public function group_callback()
    {
        $group = isset($this->options['group']) ? $this->options['group'] : '';
        ?>
        <input type="text" id="group" name="msform_setting[group]" value="<?php esc_attr_e($group)?>" />
        <?php
    }

    /**
     * 入力項目「パスワード」のHTML出力
     */
    public function password_callback()
    {
        $password = isset($this->options['password']) ? $this->options['password'] : '';
        ?>
        <input type="password" id="password" name="msform_setting[password]" value="<?php esc_attr_e($password)?>" />
        <?php
    }

    public function test_button()
    {
      ?>
      <button type="button" class="button button-primary" id="connection_test">テスト</button>
      <span id="connection_test_result" style="margin-left:10px"></span>
      <script type="text/javascript">
        //<![CDATA[
        jQuery('#connection_test').on('click', function(){
          var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
          jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    'action' : 'msform_connection_check',
                    'contentType' : 'application/json',
                    'data' : {
                      api_url: jQuery("#api_url").val(),
                      user_id: jQuery("#user_id").val(),
                      password: jQuery("#password").val(),
                      group: jQuery("#group").val()
                    }
                },
                success: function( response ){
                  const data = JSON.parse(response);
                  if(data.token.length === 0) {
                    if(data.message.length === 0) {
                      jQuery("#connection_test_result").text('結果: 接続失敗');
                    } else {
                      jQuery("#connection_test_result").text('結果: ログイン失敗');
                    }
                  } else {
                    jQuery("#connection_test_result").text('結果: ログイン成功');
                  }
                },
                error: function(a,b,c){
                  jQuery("#connection_test_result").text('結果: 接続失敗');
                }
          });
        });
        //]]>
      </script>
      <?php
    }

    public function sanitize($input)
    {
        $this->options = get_option('msform_setting');
        $new_input = array();

        if(isset($input['api_url']) && trim($input['api_url']) !== ''){
            $new_input['api_url'] = sanitize_text_field($input['api_url']);
        } else {
            add_settings_error('msform_setting', 'api_url', 'URLを入力してください');
            $new_input['api_url']= isset($this->options['api_url']) ? $this->options['api_url'] : '';
        }

        if(isset($input['user_id']) && trim($input['user_id']) !== ''){
            $new_input['user_id'] = sanitize_text_field($input['user_id']);
        } else {
            add_settings_error('msform_setting', 'user_id', 'ユーザーIDを入力してください');
            $new_input['user_id']= isset($this->options['user_id']) ? $this->options['user_id'] : '';
        }

        if(isset($input['group']) && trim($input['group']) !== ''){
            $new_input['group'] = sanitize_text_field($input['group']);
        } else {
            add_settings_error('msform_setting', 'group', 'グループを入力してください');
            $new_input['group']= isset($this->options['group']) ? $this->options['group'] : '';
        }

        if(isset($input['password']) && trim($input['password']) !== ''){
            $new_input['password'] = sanitize_text_field($input['password']);
        } else {
            add_settings_error('msform_setting', 'password', 'パスワードを入力してください');
            $new_input['password']= isset($this->options['password']) ? $this->options['password'] : '';
        }

        return $new_input;
    }
}
