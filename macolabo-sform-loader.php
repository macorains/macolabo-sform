<?php
/**
 * Sform実行用クラス
 */
class MacolaboSformLoader {

    private $options;

    private $common_error_login = '<div class="sform_wrapper">' . "Can't connect to SformAPI." . '</div>';

    public function __construct()
    {
        if(!function_exists('curl_init')){
          print('Error: php_curl not installed.');
          exit(1);
        }
        if(!function_exists('simplexml_load_string')){
          print('Error: php_xml not installed.');
          exit(1);
        }
        $this->options = get_option('msform_setting');
        wp_enqueue_script('jquery');

    }

    /**
     * login
     */
    function _login($info = null) {
        $url = (is_null($info) ? $this->options['api_url'] : $info['api_url'] ) . "/signin";
        $data = [
            'password' => is_null($info) ? $this->options['password'] : $info['password'],
            'username' => is_null($info) ? $this->options['group'] : $info['group']
        ];
        $result = $this->apicall_form($url, $data, '');

        $res = [
          'token' => array_key_exists('X-Auth-Token', $result['header']) ? $result['header']['X-Auth-Token'] : '',
          'message' => $result['data']
        ];
        return $res;
    }

    /**
     * initial_load
     * parameters
     *   - content : wordpressの本文部分
     *   - cache_id  : フォームキャッシュID（エラー時に使用）
     *   - ini : 設定ファイル内容
     * return
     *   - response_data : レスポンス
     */
    function form_initial_load($content, $cache_id) {
        $login = $this->_login();
        if($login['token'] != ''){
            $auth_token = $login['token'];
            $url = $this->options['api_url'] . "/load";
            $form_param = $this->get_form_param($content);

            $form_id = $form_param['form_id'];
            $data = [
                'hashed_form_id' => (string)$form_id,
                'receiver_path' => '',
                'cache_id' => $cache_id
            ];

            $res = $this->apicall($url, $data, $auth_token);
            $html = '<div class="sform_wrapper">';
            $html .= json_decode($res['data']) . $this->get_hidden_param((string)$form_id);
            $html .= '</div>';
            return preg_replace('/<msform>.*<\/msform>/', $html, str_replace("\n", '', $content));
        } else {
            $html = '<div class="sform_wrapper">';
            $html .= '<h4>' . $this->common_error_login . '</h4>';
            $html .= '<p>' . json_decode($res) . '</p>';
            $html .= '<p>' . $this->get_hidden_param((string)$form_id) . '</p>';
            $html .= '</div>';
            return preg_replace('/<msform>.*<\/msform>/', $html, str_replace("\n", '', $content));
        }
    }

    /**
     * load
     * parameters
     *   - form_id   : フォームID
     *   - cache_id  : フォームキャッシュID
     * return
     *   - response_data : レスポンス
    */
    function form_load($form_id, $cache_id) {
        $login = $this->_login();
        $auth_token = $login['token'];
        $url = $this->options['api_url'] . "/load";
        $data = [
            'hashed_form_id' => (string)$form_id,
            'receiver_path' => '',
            'cache_id' => $cache_id
        ];
        $res = $this->apicall($url, $data, $auth_token);
        return json_decode($res['data']) . $this->get_hidden_param((string)$form_id);
    }

    /**
     * validate
     * parameters
     *   - form_id  : フォームID
     *   - postdata : フォーム送信データ
     * return
     *   - result : バリデーション結果
     *   - validatekey : バリデーションチェックキー
     *   - html : バリデーションNGの場合に表示させるHTML
     */
    function form_validate($form_id, $postdata) {
        // Memo JWT認証の場合、セッションID毎にハッシュを作るので、load時とvalidate時でセッション違うため引き回しできない
        //      セッションごとに認証通す必要ある
        $login = $this->_login();

        $auth_token = $login['token'];
        $url = $this->options['api_url'] . "/validate";
        $data = [
          'hashed_form_id' => (string)$form_id,
          'receiver_path' => '',
          'postdata' => $postdata
        ];
        $res = $this->apicall($url, $data, $auth_token);
        return json_encode($res);
    }

    /**
     * confirm
     */
    function form_confirm($form_id, $postdata, $cache_id) {
        $login = $this->_login();
        $auth_token = $login['token'];
        $url = $this->options['api_url'] . "/confirm";
        $data = [
            'hashed_form_id' => $form_id,
            'cache_id' => $cache_id,
            'receiver_path' => '',
            'postdata' => $postdata
        ];

        $res = $this->apicall($url, $data, $auth_token);
        return json_encode($res);
    }

    /**
     * save
     */
    function form_save($form_id, $cache_id) {
        $login = $this->_login();
        $auth_token = $login['token'];
        $url = $this->options['api_url'] . "/save";
        $data = [
            'hashed_form_id' => $form_id,
            'cache_id' => $cache_id,
            'receiver_path' => '',
        ];

        $res = $this->apicall($url, $data, $auth_token);
        $response_data = [
            'html' => $res['data']
        ];
        return json_encode($response_data);
    }


    /**
     * apicall
     */
    function apicall($url, $data, $auth_token) {
        $header = array('Content-Type: application/json');
        if(!empty($auth_token)) {
            array_push($header, 'X-Auth-Token: ' . str_replace("\r","",$auth_token));
        }
        $_curl = curl_init();
        curl_setopt($_curl, CURLOPT_POST, TRUE);
        curl_setopt($_curl, CURLOPT_URL, $url);
        curl_setopt($_curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($_curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($_curl, CURLOPT_HEADER, true);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($_curl);

        $response_header_size = curl_getinfo($_curl, CURLINFO_HEADER_SIZE);
        $response_header_array = explode("\n", substr($res, 0, $response_header_size));
        $response_headers = [];
        $response_body = substr($res, $response_header_size);

        foreach($response_header_array as $header){
            $h = explode(":", $header);
            $response_headers[$h[0]] = empty($h[1])?"":$h[1];
        }

        $response_data = [
            'header' => $response_headers,
            'data' => $response_body
        ];
        curl_close($_curl);
        return $response_data;
    }

    /**
     * apicall_form
     */
    function apicall_form($url, $data, $auth_token) {
        $header = array('Content-Type: application/x-www-form-urlencoded');
        if(!empty($auth_token)) {
            array_push($header, 'X-Auth-Token: ' . str_replace("\r","",$auth_token));
        }
        $_curl = curl_init();
        curl_setopt($_curl, CURLOPT_POST, TRUE);
        curl_setopt($_curl, CURLOPT_URL, $url);
        curl_setopt($_curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($_curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($_curl, CURLOPT_HEADER, true);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($_curl);

        $response_header_size = curl_getinfo($_curl, CURLINFO_HEADER_SIZE);
        $response_header_array = explode("\n", substr($res, 0, $response_header_size));
        $response_headers = [];
        $response_body = substr($res, $response_header_size);

        foreach($response_header_array as $header){
            $h = explode(":", $header);
            $response_headers[$h[0]] = empty($h[1])?"":$h[1];
        }

        $response_data = [
            'header' => $response_headers,
            'data' => $response_body
        ];
        curl_close($_curl);
        return $response_data;
    }


    /**
     * get_form_param
     */
    function get_form_param($content) {
        preg_match('/<msform>.*<\/msform>/',str_replace("\n", '', $content), $params);
        $param_obj = simplexml_load_string($params[0]);
        return json_decode(json_encode($param_obj), TRUE);
    }

    function get_hidden_param($form_id) {
        $res = '<input type="hidden" id="sform_form_id" value="' . $form_id . '"/>';
        return $res;
    }
}
