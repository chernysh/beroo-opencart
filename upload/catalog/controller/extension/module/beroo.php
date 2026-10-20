<?php

/* OpenCart 2.3, 3.0 */

class ControllerExtensionModuleBeroo extends Controller
{
	public function eventAddOrderHistory($route, $event_data) {

		if($this->config->get('module_beroo_status') != 1){
			return;
		}
		if(!isset($event_data[0])){
			return;
		}

		$this->load->model('account/order');
		$this->load->model('checkout/order');
		$this->load->model('catalog/product');

		$order_id = $event_data[0];

		if (count($this->model_account_order->getOrderHistories($order_id)) > 1) {
			return;
		}

		$order = $this->model_checkout_order->getOrder($order_id);
		$order_products = $this->model_account_order->getOrderProducts($order_id);
		$order_totals = $this->model_account_order->getOrderTotals($order_id);
		$order_custom_field = $order['custom_field'];

		// Order originated in Beroo (pushed here via createOrder): never echo it back.
		if(is_array($order_custom_field) && !empty($order_custom_field['beroo_external_id'])){
			return;
		}

		$data = array();
		$data['externalId'] = $order_id;
		$data['fName'] = htmlspecialchars_decode($order['firstname']);
		$data['lName'] = htmlspecialchars_decode($order['lastname']);
		$data['phone'] = $order['telephone'];
		$email = $order['email'];
		if(strpos($email,'localhost')!==false){
			$email = '';
		}
		$data['email'] = $email;
		$data['company'] = htmlspecialchars_decode($order['shipping_company']);
		$data['products'] = array();
		
		$product_bind = $this->config->get('module_beroo_product_bind') ? $this->config->get('module_beroo_product_bind') : 'id';
		
		foreach ($order_products as $product) {
			$options = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);
			$product_info = $this->model_catalog_product->getProduct($product['product_id']);

			$product_data = array(
				'id' => $product['product_id'],
				'model' => $product['model'],
				'sku' => $product_info['sku'],
				'name' => htmlspecialchars_decode($product['name']),
			);

			//generate virtual products by options
			$product_data = $this->getVirtualProductData($product_data, $options);

			$description = '';
			if($options){
				foreach($options as $option){
					if($option['product_option_value_id'] === '0'){
						$description .= htmlspecialchars_decode($option['name']).': '.htmlspecialchars_decode($option['value']).";\n";
					}
				}
			}
			$id = $product_data['id'];
			$name = htmlspecialchars_decode($product_data['name']);
			if($product_bind == 'model'){
				if($product_data['model']){
					$id = $product_data['model'];
				}
				else{
					$id = 'НЕ ВКАЗАНО МОДЕЛЬ';
					$description .= $name;
					$name = 'НЕ ВКАЗАНО МОДЕЛЬ';
				}
			}
			if($product_bind == 'sku'){
				if($product_data['sku']){
					$id = $product_data['sku'];
				}
				else{
					$id = 'НЕ ВКАЗАНО SKU';
					$description .= $name;
					$name = 'НЕ ВКАЗАНО SKU';
				}
			}
			$data['products'][] = array(
				'id' => $id,
				'name' => $name,
				'costPerItem' => $product['price'] * $order['currency_value'],
				'amount' => $product['quantity'],
				'description' => $description,
			);
		}

		foreach($order_totals as $order_total){
			if($order_total['code']=='coupon'){
				$coupon_discount = (int)($order_total['value']);
				$coupon_discount = -$coupon_discount;
				if($coupon_discount!=0){
					$coupon_title = htmlspecialchars_decode($order_total['title']);
					$data['products'][] = array(
						'id' => 'COUPON',
						'name' => 'КУПОН',
						'costPerItem' => '0',
						'discount' => $coupon_discount,
						'amount' => 1,
						'description' => $coupon_title,
					);
				}
			}
			if($order_total['code']=='voucher'){
				$voucher_discount = (int)($order_total['value']);
				$voucher_discount = -$voucher_discount;
				if($voucher_discount!=0){
					$voucher_title = htmlspecialchars_decode($order_total['title']);
					$data['products'][] = array(
						'id' => 'VOUCHER',
						'name' => 'ПОДАРУНКОВИЙ СЕРТИФІКАТ',
						'costPerItem' => '0',
						'discount' => $voucher_discount,
						'amount' => 1,
						'description' => $voucher_title,
					);
				}
			}
			if($order_total['code']=='shipping'){
				/*
				$shipping_price = (int)($order_total['value']);
				if($shipping_price!=0){
					$shipping_title = htmlspecialchars_decode($order_total['title']);
					$data['products'][] = array(
						'id' => 'DELIVERY',
						'name' => 'ДОСТАВКА',
						'costPerItem' => $shipping_price,
						'discount' => 0,
						'amount' => 1,
						'description' => $shipping_title,
					);
				}
				*/
			}
		}

		$shipping_method = trim(htmlspecialchars_decode($order['shipping_method']));
		$shipping_code = trim(htmlspecialchars_decode($order['shipping_code']));
		$match_shipping_methods = $this->config->get('module_beroo_match_shipping_methods');
		$shipping_code_trim = preg_replace('/^(.*)\..*$/','$1',$shipping_code);
		if(isset($match_shipping_methods[$shipping_code_trim])){
			$shipping_method = $match_shipping_methods[$shipping_code_trim];
		}
		
		$payment_method = trim(htmlspecialchars_decode($order['payment_method']));
		$payment_code = trim(htmlspecialchars_decode($order['payment_code']));
		$match_payment_methods = $this->config->get('module_beroo_match_payment_methods');
		if(isset($match_payment_methods[$payment_code])){
			$payment_method = $match_payment_methods[$payment_code];
		}
		
		$comment = trim(htmlspecialchars_decode($order['comment']));

		$shipping_country = trim(htmlspecialchars_decode($order['shipping_country']));
		$shipping_postcode = trim(htmlspecialchars_decode($order['shipping_postcode']));
		$shipping_zone = trim(htmlspecialchars_decode($order['shipping_zone']));
		$shipping_city = trim(htmlspecialchars_decode($order['shipping_city']));
		$shipping_address_1 = trim(htmlspecialchars_decode($order['shipping_address_1']));
		$shipping_address_2 = trim(htmlspecialchars_decode($order['shipping_address_2']));
		$shipping_address = $shipping_city;
		if($shipping_address_1){
			$shipping_address .= ', '.$shipping_address_1;
		}
		if($shipping_address_2){
			$shipping_address .= ', '.$shipping_address_2;
		}
		if($shipping_zone){
			$shipping_address = $shipping_zone.', '.$shipping_address;
		}

		$data['novaposhta']['ServiceType'] = 'Warehouse'; // можливі значення: Warehouse, Doors
		$data['novaposhta']['city'] = $shipping_city;
		$data['novaposhta']['WarehouseNumber'] = $shipping_address_1;
		//$data['novaposhta']['area'] = ''; // область українською мовою або Ref області в системі Нової Пошти
		//$data['novaposhta']['cityNameFormat'] = ''; // можливі значення: full (за замовчуванням), short
		//$data['novaposhta']['backwardDeliveryCargoType'] = ''; // можливі значення: None - без накладеного платежу, Money - з накладеним платежем

		$data['shipping_address'] = $shipping_address;

		$data['shipping_method'] = $shipping_method;
		$data['payment_method'] = $payment_method;
		$data['comment'] = $comment;
		
		// DEBUG
		/*
		$handle = fopen(dirname(__FILE__).'/beroo_log.txt', "a");
		$date = date('m/d/Y h:i:s a', time());
		ob_start();
		print($date.". ".$_SERVER['REMOTE_ADDR']."\n");

		print("ORDER:\n");
		print_r($order);

		$htmlStr = ob_get_contents()."\n";
		ob_end_clean(); 
		fwrite($handle,$htmlStr);		
		*/

		$data['prodex24page'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		$site = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		$site = preg_replace('/^www\./','',$site);
		$data['sajt'] = $site;
		
		// якщо задані utm-cookies, використати cookies. Інакше використати $order['custom_field']
		if(
			isset($_COOKIE["prodex24source_full"]) || 
			isset($_COOKIE["prodex24source"]) ||
			isset($_COOKIE["prodex24medium"]) ||
			isset($_COOKIE["prodex24campaign"]) ||
			isset($_COOKIE["prodex24content"]) ||
			isset($_COOKIE["prodex24term"])
		){
			$data['prodex24source_full'] = isset($_COOKIE['prodex24source_full']) ? $_COOKIE['prodex24source_full'] : '';
			$data['prodex24source'] = isset($_COOKIE['prodex24source']) ? $_COOKIE['prodex24source'] : '';
			$data['prodex24medium'] = isset($_COOKIE['prodex24medium']) ? $_COOKIE['prodex24medium'] : '';
			$data['prodex24campaign'] = isset($_COOKIE['prodex24campaign']) ? $_COOKIE['prodex24campaign'] : '';
			$data['prodex24content'] = isset($_COOKIE['prodex24content']) ? $_COOKIE['prodex24content'] : '';
			$data['prodex24term'] = isset($_COOKIE['prodex24term']) ? $_COOKIE['prodex24term'] : '';
		}
		else{
			$data['prodex24source_full'] = isset($order_custom_field['prodex24source_full']) ? $order_custom_field['prodex24source_full'] : '';
			$data['prodex24source'] = isset($order_custom_field['prodex24source']) ? $order_custom_field['prodex24source'] : '';
			$data['prodex24medium'] = isset($order_custom_field['prodex24medium']) ? $order_custom_field['prodex24medium'] : '';
			$data['prodex24campaign'] = isset($order_custom_field['prodex24campaign']) ? $order_custom_field['prodex24campaign'] : '';
			$data['prodex24content'] = isset($order_custom_field['prodex24content']) ? $order_custom_field['prodex24content'] : '';
			$data['prodex24term'] = isset($order_custom_field['prodex24term']) ? $order_custom_field['prodex24term'] : '';
		}

		$this->load->library('beroo');
		$beroo = new Beroo($this->config->get('module_beroo_domain'), $this->config->get('module_beroo_key'));

		$beroo->addOrder($data);
	}

	private function getVirtualProductData($product_data, $options) {
		$product_options = $this->model_catalog_product->getProductOptions($product_data['id']);
		$this->sortOptions($product_options);

		foreach ($product_options as $option) {
			if(count($option['product_option_value']) > 0) {
				$id = $product_data['id'].'_'.$option['option_id'];
				$model = $product_data['model'].'_'.$option['option_id'];
				$sku = $product_data['sku'].'_'.$option['option_id'];

				foreach ($option['product_option_value'] as $k => $option_value) {
					if (in_array($option_value['product_option_value_id'], array_column($options, 'product_option_value_id'))) {
						$product_data = array(
							'id' => $id.'-'.$option_value['option_value_id'],
							'model' => $model.'-'.$option_value['option_value_id'],
							'sku' => $sku.'-'.$option_value['option_value_id'],
							'product_id' => $product_data['id'],
							'name' => $product_data['name'].' '.$option_value['name'],
						);
					}
				}
			}
		}

		return $product_data;
	}

	public function update(){
		$time_start = time();
		$product_bind = $this->config->get('module_beroo_product_bind') ? $this->config->get('module_beroo_product_bind') : 'id';
		$feed = $this->config->get('module_beroo_feed');
		$xml = file_get_contents($feed);
		$xml = new SimpleXMLElement($xml);
		$offers = $xml->shop->offers;
		$time_finish_xml_import = time();
		$n = count($offers->offer);
		$updated = 0;
		$product_qty = [];
		$whereClauseProductsArray = [];
		for ($i = 0; $i < $n; $i++) {
			$sql = '';
			$opencart_product_ids = '';
			$product_ids_to_update = '';
			$offer = $offers->offer[ $i ];
			$id = (string)$offer['id'];
			$quantity = (int)$offer->quantity_in_stock;
			$ids = explode('_', $id);
			$product_id = (string)$ids[0];
			if(count($ids) == 1){
				if($product_bind=='id'){
					$sql = "SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . (int)$product_id . "' AND `quantity` != " . $quantity;
				}
				if($product_bind=='model'){
					$sql = "SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `model` = '" . $this->db->escape($product_id) . "' AND `quantity` != " . $quantity;
				}
				if($product_bind=='sku'){
					$sql = "SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `sku` = '" . $this->db->escape($product_id) . "' AND `quantity` != ". $quantity;
				}
				$result = $this->db->query($sql);
				if($result->num_rows){
					$product_ids_to_update = implode(', ', array_map('intval', array_column($result->rows,'product_id')));
					$sql = "UPDATE `" . DB_PREFIX . "product` SET `quantity` = '" . $quantity . "' WHERE `product_id` IN (" . $product_ids_to_update . ")";
					$this->db->query($sql);
					$updated++;
				}				
			}
			elseif(count($ids) == 2){
				$options = explode('-', $ids[1]);
				if(count($options) == 2){
					if($product_bind=='id'){
						$opencart_product_ids = (int)$product_id;
					}
					if($product_bind=='model'){
						$findProducts = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE `model` = '" . $this->db->escape($product_id) . "'");
						if($findProducts->num_rows){
							$opencart_product_ids = implode(', ', array_map('intval', array_column($findProducts->rows,'product_id')));
						}
					}
					if($product_bind=='sku'){
						$findProducts = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE `sku` = '" . $this->db->escape($product_id) . "'");
						if($findProducts->num_rows){
							$opencart_product_ids = implode(', ', array_map('intval', array_column($findProducts->rows,'product_id')));
						}
					}
					if($opencart_product_ids){
						$sql = "SELECT `product_id`,`option_id`,`option_value_id` FROM `" . DB_PREFIX . "product_option_value` WHERE 
						`product_id` IN(".$opencart_product_ids.") AND 
						`option_id` = '" . (int)$options[0] . "' AND 
						`option_value_id` = '" . (int)$options[1] . "' AND
						`quantity` != ". $quantity;
						$result = $this->db->query($sql);
						if($result->num_rows){
							$product_ids_to_update = implode(', ', array_map('intval', array_column($result->rows,'product_id')));
							$sql = "UPDATE `" . DB_PREFIX . "product_option_value` 
							SET `quantity` = '" . $quantity . "' 
							WHERE `product_id` IN(" . $product_ids_to_update . ") 
							AND `option_id` = '" . (int)$options[0] . "' 
							AND `option_value_id` = '" . (int)$options[1] . "'";
							$this->db->query($sql);
							$updated += $result->num_rows;
						}				
					}
				}
				if(!isset($product_qty[$product_id])){
					$product_qty[$product_id] = $quantity;
				}
				else{
					$product_qty[$product_id]+=$quantity;
				}
			}
		}
		
		// Update product quantity as a sum of options' quantity
		foreach($product_qty as $product_id => $quantity){
			$sql = '';
			if($product_bind=='id'){
				$sql = "SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . (int)$product_id . "' AND `quantity` != " . (int)$quantity;
			}
			if($product_bind=='model'){
				$sql = "SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `model` = '" . $this->db->escape($product_id) . "' AND `quantity` != " . (int)$quantity;
			}
			if($product_bind=='sku'){
				$sql = "SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `sku` = '" . $this->db->escape($product_id) . "' AND `quantity` != ". (int)$quantity;
			}
			$result = $this->db->query($sql);
			if($result->num_rows){
				$product_ids_to_update = implode(', ', array_map('intval', array_column($result->rows,'product_id')));
				$sql = "UPDATE `" . DB_PREFIX . "product` SET `quantity` = '" . (int)$quantity . "' WHERE `product_id` IN (" . $product_ids_to_update . ")";
				$this->db->query($sql);
			}				
		}
		
		$time_finish = time();
		echo 'Залишки успішно оновлено! <br>';
		echo 'Усього товарів у YML: ' . $n . '.<br>';
		echo 'Змінено залишки по товарах: ' . $updated . '.<br>';
		echo 'Час отримання YML: ' . ($time_finish_xml_import - $time_start) . '<br>';
		echo 'Час на оновлення залишків: ' . ($time_finish - $time_finish_xml_import) . '<br>';
		echo 'Загальний час: ' . ($time_finish - $time_start) . '<br>';
	}
	
	// Webhook key: X-Beroo-Key header, or legacy ?formKey= (what the Beroo backend sends today).
	private function requestKey() {
		if (isset($_SERVER['HTTP_X_BEROO_KEY'])) return $_SERVER['HTTP_X_BEROO_KEY'];
		return isset($_GET['formKey']) ? $_GET['formKey'] : '';
	}

	private function validKey() {
		$key = (string)$this->config->get('module_beroo_key');
		$given = $this->requestKey();
		return $key !== '' && is_string($given) && hash_equals($key, $given);
	}

	public function setOrderStatus(){
		if($this->requestKey() === ''){
			echo 'formKey не передано у веб-хуку.';
			die();
		};
		$module_beroo_key = $this->config->get('module_beroo_key');
		if(empty($module_beroo_key)){
			echo 'На сайті не вказано ключ форми в налаштуваннях модуля Beroo.';
			die();
		}
		if(!$this->validKey()){
			echo 'Ключ форми у веб-хуку не збігається з ключем форми на сайті в налаштуваннях модуля Beroo.';
			die();
		}
		
		$match_order_statuses = $this->config->get('module_beroo_match_order_statuses') ? $this->config->get('module_beroo_match_order_statuses') : [];
		if(empty($match_order_statuses)){
			echo 'Зіставлення статусів Beroo та OpenCart не задано.';
			die();
		};
		$json = file_get_contents('php://input');
		$json = json_decode($json, true);
		if(json_last_error() != JSON_ERROR_NONE){
			echo 'Отримано некоректний JSON.';
			die();
		}
		if(empty($json['data'])){
			echo 'json[data] не задано.';
			die();
		}
		$data = $json['data'];
		if(empty($data['externalId'])){
			echo 'externalId не задано.';
			die();
		}
		if(empty($data['statusId'])){
			echo 'statusId не задано.';
			die();
		}
		if(empty($match_order_statuses[$data['statusId']])){
			echo 'Не знайдено відповідність для статусу Beroo id='.$data['statusId'].'.';
			die();
		}
		$order_id = $data['externalId'];
		$order_status_id = $match_order_statuses[$data['statusId']];

		$this->load->model('checkout/order');
		
		$order = $this->model_checkout_order->getOrder($order_id);
		if(!$order){
			echo 'Замовлення з id='.$order_id.' не знайдено.';
			die();
		}
		
		$this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
		
		echo 'Статус в OpenCart успішно змінено на order_status_id='.$order_status_id.'.';
		
	}

	// beroo -> OC: create an order that originated in Beroo (formKey-guarded webhook).
	public function createOrder(){
		if($this->requestKey() === ''){
			echo 'formKey не передано у веб-хуку.'; die();
		}
		if(!$this->validKey()){
			echo 'Невірний formKey.'; die();
		}
		$match_order_statuses = $this->config->get('module_beroo_match_order_statuses') ? $this->config->get('module_beroo_match_order_statuses') : [];
		$json = json_decode(file_get_contents('php://input'), true);
		if(json_last_error() != JSON_ERROR_NONE || empty($json['data'])){
			echo 'Отримано некоректний JSON.'; die();
		}
		$data = $json['data'];
		if(empty($data['externalId'])){
			echo 'externalId не задано.'; die();
		}
		$external_id = (string)$data['externalId'];

		$this->load->model('checkout/order');
		$this->load->model('catalog/product');

		// idempotency: never create the same Beroo order twice (retry-safe)
		$existing = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE custom_field LIKE '%\"beroo_external_id\":\"" . $this->db->escape($external_id) . "\"%' LIMIT 1");
		if($existing->num_rows){
			$order_id = (int)$existing->row['order_id'];
			if(!empty($data['statusId']) && !empty($match_order_statuses[$data['statusId']])){
				$this->model_checkout_order->addOrderHistory($order_id, (int)$match_order_statuses[$data['statusId']]);
			}
			echo json_encode(array('success'=>true,'order_id'=>$order_id,'created'=>false));
			return;
		}

		$fName = isset($data['fName']) ? $data['fName'] : '';
		$lName = isset($data['lName']) ? $data['lName'] : '';
		$email = isset($data['email']) ? $data['email'] : '';
		$phone = isset($data['phone']) ? $data['phone'] : '';
		$comment = isset($data['comment']) ? $data['comment'] : '';
		$pay_method = isset($data['payment_method']) ? $data['payment_method'] : '';
		$ship_method = isset($data['shipping_method']) ? $data['shipping_method'] : '';
		$ship_addr = isset($data['shipping_address']) ? $data['shipping_address'] : '';
		$ship_city = isset($data['shipping_city']) ? $data['shipping_city'] : '';
		$ship_post = isset($data['shipping_postcode']) ? $data['shipping_postcode'] : '';
		$currency_code = !empty($data['currency']) ? $data['currency'] : $this->config->get('config_currency');
		$currency_value = !empty($data['currency_value']) ? (float)$data['currency_value'] : 1;

		$product_bind = $this->config->get('module_beroo_product_bind') ? $this->config->get('module_beroo_product_bind') : 'id';
		$products = array();
		$sub_total = 0;
		foreach((isset($data['products']) ? $data['products'] : array()) as $item){
			$ext = (string)$item['id'];
			if($product_bind == 'id'){
				$product_id = (int)$ext;
			} else {
				$col = ($product_bind == 'sku') ? 'sku' : 'model';
				$r = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product` WHERE `" . $col . "` = '" . $this->db->escape($ext) . "' LIMIT 1");
				$product_id = $r->num_rows ? (int)$r->row['product_id'] : 0;
			}
			$pinfo = $product_id ? $this->model_catalog_product->getProduct($product_id) : array();
			$price = (float)$item['price'];
			$qty = (int)$item['amount'];
			$sub_total += $price * $qty;
			$products[] = array(
				'product_id' => $product_id,
				'name'       => !empty($item['name']) ? $item['name'] : (isset($pinfo['name']) ? $pinfo['name'] : $ext),
				'model'      => isset($pinfo['model']) ? $pinfo['model'] : '',
				'option'     => array(),
				'download'   => array(),
				'quantity'   => $qty,
				'subtract'   => 0, // Beroo owns stock truth; it pushes quantities back to OC
				'price'      => $price,
				'total'      => $price * $qty,
				'tax'        => 0,
				'reward'     => 0,
			);
		}

		$shipping_total = isset($data['shipping_total']) ? (float)$data['shipping_total'] : 0;
		$grand_total = $sub_total + $shipping_total;
		$totals = array();
		$totals[] = array('code'=>'sub_total','title'=>'Sub-Total','value'=>$sub_total,'sort_order'=>1);
		if($shipping_total != 0){
			$totals[] = array('code'=>'shipping','title'=>$ship_method ? $ship_method : 'Shipping','value'=>$shipping_total,'sort_order'=>3);
		}
		$totals[] = array('code'=>'total','title'=>'Total','value'=>$grand_total,'sort_order'=>9);

		$addr = array(
			'firstname'=>$fName,'lastname'=>$lName,'company'=>'',
			'address_1'=>$ship_addr,'address_2'=>'',
			'city'=>$ship_city,'postcode'=>$ship_post,
			'zone'=>'','zone_id'=>0,'country'=>'','country_id'=>0,
			'address_format'=>'','custom_field'=>array(),
		);
		$order_data = array(
			'invoice_prefix'    => $this->config->get('config_invoice_prefix'),
			'store_id'          => 0,
			'store_name'        => $this->config->get('config_name'),
			'store_url'         => $this->config->get('config_url'),
			'customer_id'       => 0,
			'customer_group_id' => (int)($this->config->get('config_customer_group_id') ? $this->config->get('config_customer_group_id') : 1),
			'firstname'         => $fName,
			'lastname'          => $lName,
			'email'             => $email,
			'telephone'         => $phone,
			'custom_field'      => array('beroo_external_id'=>$external_id),
			'payment_method'    => $pay_method,
			'payment_code'      => 'beroo',
			'shipping_method'   => $ship_method,
			'shipping_code'     => 'beroo',
			'comment'           => $comment,
			'total'             => $grand_total,
			'affiliate_id'      => 0, 'commission'=>0, 'marketing_id'=>0, 'tracking'=>'',
			'language_id'       => (int)$this->config->get('config_language_id'),
			'currency_id'       => 0,
			'currency_code'     => $currency_code,
			'currency_value'    => $currency_value,
			'ip'=>'', 'forwarded_ip'=>'', 'user_agent'=>'', 'accept_language'=>'',
			'products'          => $products,
			'totals'            => $totals,
			'vouchers'          => array(),
		);
		foreach($addr as $k=>$v){ $order_data['payment_'.$k]=$v; $order_data['shipping_'.$k]=$v; }

		$order_id = (int)$this->model_checkout_order->addOrder($order_data);

		$status_id = 0;
		if(!empty($data['statusId']) && !empty($match_order_statuses[$data['statusId']])){
			$status_id = (int)$match_order_statuses[$data['statusId']];
		}
		if(!$status_id){
			$status_id = (int)($this->config->get('config_order_status_id') ? $this->config->get('config_order_status_id') : 1);
		}
		$this->model_checkout_order->addOrderHistory($order_id, $status_id);

		echo json_encode(array('success'=>true,'order_id'=>$order_id,'created'=>true));
	}
	
	// Save utm data to $order['custom_field']
	public function eventAddOrder($route = false, $order_info = false, $order_id = false) {
		if(isset($order_info[0]['custom_field'])){
			$data['custom_field'] = $order_info[0]['custom_field'];
		}
		else{
			$data['custom_field'] = [];
		}
		if(isset($_COOKIE["prodex24source_full"]) && strpos($_COOKIE["prodex24source_full"],'secure.wayforpay.com')===false){
			$data['custom_field']['prodex24source_full'] = $_COOKIE["prodex24source_full"];
		}
		if(isset($_COOKIE["prodex24source"])){
			$data['custom_field']['prodex24source'] = $_COOKIE["prodex24source"];
		}
		if(isset($_COOKIE["prodex24medium"])){
			$data['custom_field']['prodex24medium'] = $_COOKIE["prodex24medium"];
		}
		if(isset($_COOKIE["prodex24campaign"])){
			$data['custom_field']['prodex24campaign'] = $_COOKIE["prodex24campaign"];
		}
		if(isset($_COOKIE["prodex24content"])){
			$data['custom_field']['prodex24content'] = $_COOKIE["prodex24content"];
		}
		if(isset($_COOKIE["prodex24term"])){
			$data['custom_field']['prodex24term'] = $_COOKIE["prodex24term"];
		}
		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET custom_field = '" . $this->db->escape(json_encode($data['custom_field'])) . "' WHERE order_id = '" . (int)$order_id . "'");		
	}

	private function sortOptions(&$product_options){
		usort($product_options, function ($a, $b){
			if($a["option_id"] == $b["option_id"]){
				return 0;
			}
			return ($a["option_id"] < $b["option_id"]) ? -1 : 1;
		});
	}
	
}