<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class ClickbankIPNSimulator {
    private $params;
    private $secret;
    private $uri;
    private $ver;
    private function prepare_params($v = "1.0") {
        if($v == "2.1") {
            $this->params = array(
                'ccustfullname' => null,
                'ccustfirstname' => null,
                'ccustlastname' => null,
                'ccuststate' => null,
                'ccustzip' => null,
                'ccustcc' => null,
                'ccustaddr1' => null,
                'ccustaddr2' => null,
                'ccustcity' => null,
                'ccustcounty' => null,
                'ccustshippingstate' => null,
                'ccustshippingzip' => null,
                'ccustshippingcountry' => null,
                'ccustemail' => null,
                'cproditem' => null,
                'cprodtitle' => null,
                'cprodtype' => null,
                'ctransaction' => null,
                'ctransaffiliate' => null,
                'caccountamount' => null,
                'corderamount' => null,
                'ctranspaymentmethod' => null,
                'ccurrency' => null,
                'ctranspublisher' => null,
                'ctransreceipt' => null,
                'ctransrole' => null,
                'cupsellreceipt' => null,
                'crebillamnt' => null,
                'cprocessedpayments' => null,
                'cfuturepayments' => null,
                'cnextpaymentdate' => null,
                'crebillstatus' => null,
                'ctid' => null,
                'cvendthru' => null,
                'cverify' => null,
                'ctranstime' => null,
            );
            return;
        }
        if($v == "1.0") {
            $this->params = array(
                'ccustname' => null,
                'ccuststate' => null,
                'ccustcc' => null,
                'ccustemail' => null,
                'cproditem' => null,
                'cprodtitle' => null,
                'cprodtype' => null,
                'ctransaction' => null,
                'ctransaffiliate' => null,
                'ctransamount' => null,
                'ctranspaymentmethod' => null,
                'ctranspublisher' => null,
                'ctransreceipt' => null,
                'cupsellreceipt' => null,
                'caffitid' => null,
                'cvendthru' => null,
                'cverify' => null,
                'ctranstime' => null
            );
            return;
        }
    }
    public function  __construct($secret = null, $uri = null, $v = "2.0") {
        $this->secret = $secret;
        $this->uri = $uri;
        $this->prepare_params($v);
    }
    private function calc_verify_code($data) {
        $secret_key = $this->secret;
        
        $fields = array();
        foreach($data as $k => $v) {
            if($k != 'cverify') {
                $fields[] = $k;
            }

        }
        sort($fields);
        $pop = "";
        foreach($fields as $i) {
            $pop = $pop . $data[$i] . "|";
        }
        $pop = $pop . $secret_key;
        
        $calced_verify = sha1(mb_convert_encoding($pop, "UTF-8"));
        $calced_verify = strtoupper(substr($calced_verify,0,8));
        return $calced_verify;

    }
    private function clean_data($data) {
        if(!is_array($data)) {
            return array();
        }
        foreach($data as $k => $v) {
            if(!array_key_exists($k, $this->params)) {
                unset($data[$key]);
            }
        }
        return $data;
    }
    public function simulate($order_id, $tx_type, $additional_data) {
        parse_str($additional_data, $more);
        $add_data = $this->clean_data($additional_data);
        $post_data = array_merge($this->params, $more);

        if(!empty($order_id)) {
            $post_data['ctransreceipt'] = $order_id;
        }

        //generate the cverify field
        $post_data['ctranstime'] = time();
        $post_data['ctransaction'] = strtoupper($tx_type);
        $post_data['cverify'] = $this->calc_verify_code($post_data);
        
        
        
        $tmp = array();
        foreach($post_data as $k => $v) {
            $tmp[] = sprintf("%s=%s", $k, urlencode($v));
        }
        return $this->request(implode('&', $tmp));
        
    }
    private function request($post_data) {
        $ch = curl_init();

        curl_setopt ($ch, CURLOPT_URL, $this->uri);
        curl_setopt ($ch, CURLOPT_TIMEOUT, 80);
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_VERBOSE, 1);
        $doc = curl_exec($ch);
        
        if(curl_error($ch)) {
            throw new Exception("Unable to access URI");
        }

        return $doc;
    }
}



function main($argc, $argv) {
    try {
        if($argc < 4) {
            throw new Exception("Wrong number of parameters");
        }

        list($tmp, $ver, $url, $secret, $order_id, $tx_type, $additional_params) = $argv;
        $cbs = new ClickbankIPNSimulator($secret, $url, $ver);


        $doc = $cbs->simulate($order_id, $tx_type, $additional_params);
        echo $doc;

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "== Fuck you!\n";
        echo "== This is how you do it\n";
        echo "== php -f ClicbankIPNSimulator.php 2.0 'http://mywebsite.com' MYSECRET ORDERID TXTYPE 'ctid=ctid'\n";
        echo "== TXTYPE can be SALE|BILL|RFND|CGBK|INSF|CANCEL-REBILL|UNCANCEL-REBILL|TEST\n";
    }
}


main($argc, $argv);