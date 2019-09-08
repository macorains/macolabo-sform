<?php
/**
 * Sform実行用クラス
 */
class MacolaboSformLoader {

    private $options;

    public function __construct()
    {
        $this->options = get_option('msform_setting');
        wp_enqueue_script('jquery');

    }

    public function hello(){
        return 'hello!';
    }

    public function tag_replace($content){
        $html  = '<button id="submit-x" type="button">View Sitename</button>';
        return str_replace('<!-- msform -->',$html . $this->options['user_id'],$content);
    }

    /**
     * login
     */
    function _login() {
        $url = $this->options['api_url'] . "/signin";
        $data = [
            'email' => $this->options['user_id'],
            'password' => $this->options['password'],
            'group' => $this->options['group']
        ];
    
        $res = $this->apicall($url, $data, '');
        return $res['header']['X-Auth-Token'];
    }

    /**
     * load
     * parameters
     *   - auth_token : loginで取得したauthToken
     *   - content : wordpressの本文部分
     *   - cache_id  : フォームキャッシュID（エラー時に使用）
     *   - ini : 設定ファイル内容
     * return
     *   - response_data : レスポンス
     */
    function form_load($auth_token, $content, $cache_id) {
        $auth_token = empty($auth_token) ? $this->_login() : $auth_token;
        $url = $this->options['api_url'] . "/load";
        // TODO Validate失敗後のリロード時どうするか？
        $form_param = $this->get_form_param($content);

        foreach($form_param->form_id as $form_id)
        $data = [
            'formid' => (string)$form_id,
            'receiverPath' => '',
            'cacheid' => $cache_id
        ];

        $res = $this->apicall($url, $data, $auth_token);
        // TODO レスポンスHTML/JSの中でform_idとauth_tokenを保持させる必要あり？
        return preg_replace('/<msform>.*<\/msform>/', json_decode($res['data']), str_replace("\n", '', $content));
 
    }

    /**
     * validate
     * parameters
     *   - request  : リクエストデータ
     *   - ini : 設定ファイル内容
     * return
     *   - result : バリデーション結果
     *   - validatekey : バリデーションチェックキー
     *   - html : バリデーションNGの場合に表示させるHTML
     */
    function form_validate($request, $ini) {
        // TODO auth_tokenがnullの場合は403を返す
        $url = $this->options['api_url'] . "validate";
        $data = [
            'formid' => $request['formid'],
            'receiverpath' => $request['receiverpath'],
            'postdata' => $request['postdata']
        ];

        $res = $this->apicall($url, $data, $request['auth_token']);

        $response_data = [
            'html' => $res['data']
        ];
        return json_encode($response_data);

    }

    /**
     * confirm
     */
    function form_confirm($request, $ini) {
        // TODO auth_tokenがnullの場合は403を返す
        $url = $this->options['api_url'] . "confirm";
        $data = [
            'formid' => $request['formid'],
            'cacheid' => $request['cacheid'],
            'receiverPath' => $request['receiverPath'],
            'postdata' => $request['postdata']
        ];

        $res = $this->apicall($url, $data, $request['auth_token']);
        $response_data = [
            'html' => $res['data']
        ];
        return json_encode($response_data);
    }

    /**
     * save
     */
    function form_save($request, $ini) {
        // TODO auth_tokenがnullの場合は403を返す
        $url = $this->options['api_url'] . "save";
        $data = [
            'formid' => $request['formid'],
            'cacheid' => $request['cacheid'],
            'receiverPath' => $request['receiverPath'],
        ];

        $res = $this->apicall($url, $data, $request['auth_token']);
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
        return $param_obj;
    }
}