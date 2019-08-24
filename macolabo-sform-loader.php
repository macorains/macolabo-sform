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
    function _login($ini) {
        $url = $ini['baseurl'] . "signin";
        $data = [
            'email' => $ini['email'],
            'password' => $ini['password'],
            'group' => $ini['group']
        ];

        $res = apicall($url, $data, '');
        return $res['header']['X-Auth-Token'];
    }

    /**
     * load
     * parameters
     *   - auth_token : loginで取得したauthToken
     *   - request  : リクエストデータ
     *   - ini : 設定ファイル内容
     * return
     *   - response_data : レスポンス
     */
    function form_load($auth_token, $request, $ini) {
        $url = $ini['baseurl'] . "load";
        $data = [
            'formid' => $request['formid'],
            'receiverPath' => $request['receiverPath']
        ];

        $res = apicall($url, $data, $auth_token);

        $response_data = [
            'authToken' => $auth_token,
            'html' => $res['data']
        ];
        return json_encode($response_data);
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
        $url = $ini['baseurl'] . "validate";
        $data = [
            'formid' => $request['formid'],
            'receiverpath' => $request['receiverpath'],
            'postdata' => $request['postdata']
        ];

        $res = apicall($url, $data, $request['auth_token']);

        $response_data = [
            'html' => $res['data']
        ];
        return json_encode($response_data);

    }
    /**
     * confirm
     */
    function form_confirm($request, $ini) {
        $url = $ini['baseurl'] . "confirm";
        $data = [
            'formid' => $request['formid'],
            'cacheid' => $request['cacheid'],
            'receiverPath' => $request['receiverPath'],
            'postdata' => $request['postdata']
        ];

        $res = apicall($url, $data, $request['auth_token']);
        $response_data = [
            'html' => $res['data']
        ];
        return json_encode($response_data);
    }

    /**
     * save
     */
    function form_save($request, $ini) {
        $url = $ini['baseurl'] . "save";
        $data = [
            'formid' => $request['formid'],
            'cacheid' => $request['cacheid'],
            'receiverPath' => $request['receiverPath'],
        ];

        $res = apicall($url, $data, $request['auth_token']);
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

}