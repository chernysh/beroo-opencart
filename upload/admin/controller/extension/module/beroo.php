<?php

/* OpenCart v. 2.3, 3.0 */

// raise time limit, if there are many products to export to Beroo
// set_time_limit(300);

// Fix for function array_column for PHP <=5.4
if (! function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if ( !array_key_exists($columnKey, $value)) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            }
            else {
                if ( !array_key_exists($indexKey, $value)) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if ( ! is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }
}

class ControllerExtensionModuleBeroo extends Controller
{
    private $error = array();
    private $token = 'token';
    private $berooLibrary;
	private $product_bind = 'id';

    public function __construct($registry)
    {
		parent::__construct($registry);
		$this->token = (defined('VERSION') && version_compare(VERSION,'3.0.0.0','>=')) ? 'user_token' : $this->token;
		if($this->config->get('module_beroo_status') == 1) {
			$this->load->library('beroo');
			$this->berooLibrary = new Beroo($this->config->get('module_beroo_domain'), $this->config->get('module_beroo_key'));
		}
		$this->product_bind = $this->config->get('module_beroo_product_bind') ? $this->config->get('module_beroo_product_bind') : 'id';	
		if(!$this->config->get('module_beroo_product_language')){
			$current_language_id = $this->config->get('config_language_id');
			$this->config->set('module_beroo_product_language', $current_language_id);
		}
		else{
			$this->config->set('config_language_id', $this->config->get('module_beroo_product_language'));
		}
		$this->load->model('catalog/attribute');
		$this->load->model('catalog/product');
		$this->load->language('extension/module/beroo');
        $this->load->model('catalog/category');
		$this->load->model('catalog/manufacturer');
		$this->load->model('localisation/length_class');
		$this->load->model('localisation/order_status');
		$this->load->model('localisation/language');
    }

    public function install()
    {
        $this->load->model('extension/module/beroo');
        if (version_compare(VERSION,'3.0.0.0','>=')) {
            $this->load->model('setting/extension');
        } else {
            $this->load->model('extension/extension');
        }
        $this->load->model('user/user_group');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/beroo');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/beroo');

        $this->model_extension_module_beroo->install();
    }

    public function uninstall()
    {
        $this->load->model('extension/module/beroo');
        $this->load->model('setting/setting');
        if (version_compare(VERSION,'3.0.0.0','>=')) {
            $this->load->model('setting/extension');
        } else {
            $this->load->model('extension/extension');
        }

        $this->model_extension_module_beroo->uninstall();
        if (version_compare(VERSION,'3.0.0.0','>=')) {
            $this->model_setting_extension->uninstall('beroo', $this->request->get['extension']);
        } else {
            $this->model_extension_extension->uninstall('beroo', $this->request->get['extension']);
        }
        $this->model_setting_setting->deleteSetting($this->request->get['module_beroo']);
    }

    public function index()
    {
        $this->document->setTitle('Інтеграція з Beroo');

        $this->load->model('setting/setting');
		
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            
			$post_data = $this->request->post;
			if(!isset($post_data['module_beroo_match_payment_methods'])){
				$post_data['module_beroo_match_payment_methods'] = [];
			}
			$post_data['module_beroo_match_payment_methods'] = $this->processDataArray($post_data['module_beroo_match_payment_methods']);
			
			if(!isset($post_data['module_beroo_match_shipping_methods'])){
				$post_data['module_beroo_match_shipping_methods'] = [];
			}
			$post_data['module_beroo_match_shipping_methods'] = $this->processDataArray($post_data['module_beroo_match_shipping_methods']);
			
			if(!isset($post_data['module_beroo_match_order_statuses'])){
				$post_data['module_beroo_match_order_statuses'] = [];
			}
			$post_data['module_beroo_match_order_statuses'] = $this->processDataArray($post_data['module_beroo_match_order_statuses']);
			
			$this->model_setting_setting->editSetting('module_beroo', $post_data);

            $this->session->data['success'] = 'Налаштування модуля успішно оновлено';
        }
		
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['domain'])) {
            $data['error_domain'] = $this->error['domain'];
        } else {
            $data['error_domain'] = '';
        }

        if (isset($this->error['key'])) {
            $data['error_key'] = $this->error['key'];
        } else {
            $data['error_key'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
        } else {
            $data['success'] = '';
        }
         
        $data['action'] = $this->url->link('extension/module/beroo', $this->token.'=' . $this->session->data[$this->token], true);
        
        if (version_compare(VERSION,'3.0.0.0','>=')) {
            $data['cancel'] = $this->url->link('marketplace/extension', $this->token.'=' . $this->session->data[$this->token] . '&type=module', true);
        } else {
            $data['cancel'] = $this->url->link('extension/extension', $this->token.'=' . $this->session->data[$this->token] . '&type=module', true);
        }
        $data['synchronize'] = $this->url->link('extension/module/beroo/sync', $this->token.'=' . $this->session->data[$this->token] . '&type=module', true);

        if (isset($this->request->post['module_beroo_domain'])) {
            $data['module_beroo_domain'] = $this->request->post['module_beroo_domain'];
        } else {
            $data['module_beroo_domain'] = $this->config->get('module_beroo_domain');
        }

        if (isset($this->request->post['module_beroo_key'])) {
            $data['module_beroo_key'] = $this->request->post['module_beroo_key'];
        } else {
            $data['module_beroo_key'] = $this->config->get('module_beroo_key');
        }

        if (isset($this->request->post['module_beroo_product_bind'])) {
            $data['module_beroo_product_bind'] = $this->request->post['module_beroo_product_bind'];
        } else {
            $data['module_beroo_product_bind'] = $this->config->get('module_beroo_product_bind') ? $this->config->get('module_beroo_product_bind') : "id";
        }

        if (isset($this->request->post['module_beroo_status'])) {
            $data['module_beroo_status'] = $this->request->post['module_beroo_status'];
        } else {
            $data['module_beroo_status'] = $this->config->get('module_beroo_status')===0 ? $this->config->get('module_beroo_status') : 1;
        }

        if (isset($this->request->post['module_beroo_feed'])) {
            $data['module_beroo_feed'] = $this->request->post['module_beroo_feed'];
        } else {
            $data['module_beroo_feed'] = $this->config->get('module_beroo_feed');
        }

        if (isset($this->request->post['module_beroo_cron'])) {
            $data['module_beroo_cron'] = $this->request->post['module_beroo_cron'];
        } else {
            $data['module_beroo_cron'] = $this->config->get('module_beroo_cron');
        }
		
        $data['module_beroo_import_stock_script'] = str_replace('admin/', '', $this->url->link('extension/module/beroo/update'));

		$data['module_beroo_set_order_status'] = str_replace('admin/', '', $this->url->link('extension/module/beroo/setOrderStatus','formKey='.$data['module_beroo_key']));

		$data['module_beroo_create_order'] = str_replace('admin/', '', $this->url->link('extension/module/beroo/createOrder','formKey='.$data['module_beroo_key']));
		
		$data['languages'] = $this->model_localisation_language->getLanguages();
        if (isset($this->request->post['module_beroo_product_language'])) {
			$this->config->set('module_beroo_product_language', $this->request->post['module_beroo_product_language']);
        }
		$data['module_beroo_product_language'] = $this->config->get('module_beroo_product_language');
		
		if($data['module_beroo_domain'] && $data['module_beroo_key']){
			$this->load->library('beroo');
			$this->berooLibrary = new Beroo($data['module_beroo_domain'], $data['module_beroo_key']);

			// match payment methods
			if (isset($this->request->post['module_beroo_match_payment_methods'])) {
				$data['match_payment_methods'] = $this->processDataArray($this->request->post['module_beroo_match_payment_methods']);
			} else {
				$data['match_payment_methods'] = $this->config->get('module_beroo_match_payment_methods');
			}
			$beroo_payment_methods = $this->berooLibrary->getPaymentMethods();
			if(isset($beroo_payment_methods['success']) && $beroo_payment_methods['success']){
				$beroo_payment_methods = $beroo_payment_methods['data'];
				$data['beroo_payment_methods_error'] = '';
			}
			else{
				$data['beroo_payment_methods_error'] = 'Не вдалося отримати список способів оплати Beroo. ';
				if(isset($beroo_payment_methods['message'])){
					$data['beroo_payment_methods_error'] .= $beroo_payment_methods['message'];
				}
				$beroo_payment_methods = [];
			}
			$payment_method_codes = $this->getInstalledExtensions('payment');
			$payment_methods = [];
			foreach($payment_method_codes as $payment_method_code){
				$this->load->language('extension/payment/'.$payment_method_code);
				$payment_method_name = $this->language->get('heading_title');
				$payment_methods[] = [
					'code' => $payment_method_code,
					'name' => $payment_method_name,
				];
			}
			$data['payment_methods'] = $payment_methods;
			$data['beroo_payment_methods'] = $beroo_payment_methods;

			// match shipping methods
			if (isset($this->request->post['module_beroo_match_shipping_methods'])) {
				$data['match_shipping_methods'] = $this->processDataArray($this->request->post['module_beroo_match_shipping_methods']);
			} else {
				$data['match_shipping_methods'] = $this->config->get('module_beroo_match_shipping_methods');
			}
			$beroo_shipping_methods = $this->berooLibrary->getDeliveryMethods();
			if(isset($beroo_shipping_methods['success']) && $beroo_shipping_methods['success']){
				$beroo_shipping_methods = $beroo_shipping_methods['data'];
				$data['beroo_shipping_methods_error'] = '';
			}
			else{
				$data['beroo_shipping_methods_error'] = 'Не вдалося отримати список способів доставки Beroo. ';
				if(isset($beroo_shipping_methods['message'])){
					$data['beroo_shipping_methods_error'] .= $beroo_shipping_methods['message'];
				}
				$beroo_shipping_methods = [];
			}
			$shipping_method_codes = $this->getInstalledExtensions('shipping');
			$shipping_methods = [];
			foreach($shipping_method_codes as $shipping_method_code){
				$this->load->language('extension/shipping/'.$shipping_method_code);
				$shipping_method_name = $this->language->get('heading_title');
				$shipping_methods[] = [
					'code' => $shipping_method_code,
					'name' => $shipping_method_name,
				];
			}
			$data['shipping_methods'] = $shipping_methods;
			$data['beroo_shipping_methods'] = $beroo_shipping_methods;
			
			// match statuses
			if (isset($this->request->post['module_beroo_match_order_statuses'])) {
				$data['match_order_statuses'] = $this->request->post['module_beroo_match_order_statuses'];
			} else {
				$data['match_order_statuses'] = $this->config->get('module_beroo_match_order_statuses');
			}
			$beroo_statuses = $this->berooLibrary->getStatuses();
			if(isset($beroo_statuses['success']) && $beroo_statuses['success']){
				$beroo_statuses = $beroo_statuses['data'];
				$data['beroo_statuses_error'] = '';
			}
			else{
				$data['beroo_statuses_error'] = 'Не вдалося отримати список статусів Beroo. ';
				if(isset($beroo_statuses['message'])){
					$data['beroo_statuses_error'] .= $beroo_statuses['message'];
				}
				$beroo_statuses = [];
			}
			$order_statuses = $this->model_localisation_order_status->getOrderStatuses();
			$data['order_statuses'] = $order_statuses;
			$data['beroo_statuses'] = $beroo_statuses;
			
		}
		
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/beroo', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/beroo')) {
            $this->error['warning'] = 'У вас немає доступу до цього модуля!';
        }

        if (!$this->request->post['module_beroo_domain']) {
            $this->error['domain'] = 'Обязательное поле!';
        }

        if (!$this->request->post['module_beroo_key']) {
            $this->error['key'] = 'Обязательное поле!';
        }

        return !$this->error;
    }
    
    public function sync()
    {
		$limit = 100;
        $offset = isset($_POST['offset']) ? $_POST['offset'] : 0;
		$time_start = time();
		$category_count = 0;
		if($offset){
			$sync_categories = 0;
		}
		else{
			$start = 0;
			$sync_categories = 1;
		}
			
		// category data
		if($sync_categories){
			$data = array();
			$categories = $this->model_catalog_category->getCategories();
			$category_count = count($categories);
			foreach($categories as $k => $category_info) {
				$desc = $this->model_catalog_category->getCategoryDescriptions($category_info['category_id']);
				$item = $desc[$this->config->get('module_beroo_product_language')];
				$name = $item['name'];
				$data[] = array(
				   'id' => $category_info['category_id'],
				   'name' => htmlspecialchars_decode($name),
				   'parentId' => $category_info['parent_id'],
				);
			}
			$this->berooLibrary->saveCategories($data);
			//second save categories for correct category nesting
			$this->berooLibrary->saveCategories($data);
		}
        
        // product data
        $data = array();
        $products = $this->model_catalog_product->getProducts(['start'=>$offset, 'limit'=>$limit, 'filter_status'=>1]);
		$product_count = count($products);
		$products_with_variations = 0;
        foreach ($products as $k => $product_info) {
            $data = array_merge($data, $this->createData($product_info));
        }
		$products_with_variations += count($data);
        if (!empty($data)) {
            $this->berooLibrary->saveProduct($data);
        }

        if ($this->berooLibrary->hasErrors()) {
            $this->session->data['error'] = implode('; ', (array)$this->berooLibrary->getErrors());
        } else {
            $this->session->data['success'] = 'Товари успішно синхронізовано!';
        }
		
		// Generate result json
		$time_finish = time();
		$execution_time = $time_finish - $time_start;
		$timeElapsed = isset($_POST['timeElapsed']) ? $_POST['timeElapsed'] : 0;
		$timeElapsed += $execution_time;
		if($product_count==0){
			$finish=1;
		}
		else{
			$finish=0;
		}
		$exported = $product_count+$offset;
		$variationCount = isset($_POST['variationCount']) ? $_POST['variationCount'] : 0;
		$variationCount += $products_with_variations;
		
		$result = [
			'product_count' => $product_count,
			'products_with_variations' => $products_with_variations,
			'variationCount' => $variationCount,
			'category_count' => $category_count,
			'execution_time' => $execution_time,
			'finish' => $finish,
			'timeElapsed' => $timeElapsed,
			'exported' => $exported,
			'errors' => $this->berooLibrary->hasErrors() ? $this->berooLibrary->getErrors() : [],
		];
		echo json_encode($result);
    }

	public function eventPreEditProduct($route, $vdata){
		if($this->config->get('module_beroo_status') != 1){
			return;
		}
		if(!isset($vdata[1])){
			return;
		}
		$product_info = $vdata[1];
		$product_info['product_id'] = $vdata[0];
		$product_info['id'] = $product_info['product_id'];
		$product_description = $product_info['product_description'];
		$product_description = array_shift($product_description);
		if(isset($product_description['name'])){
			$product_info['name'] = $product_description['name'];
		}

		$new_data = array($product_info);

		if(isset($product_info['product_option'])){
			$product_options = $product_info['product_option'];
			$this->sortOptions($product_options);
			foreach($product_options as $option){
				if(!empty($option['product_option_value'])) {
					$new_data = $this->generateOptionProduct($new_data, $option);
				}
			}
		}

		$product_data = array(
			'product_id' => $product_info['product_id'],
			'sku' => $product_info['sku'],
			'model' => $product_info['model'],
			'name' => $product_info['name'],
			'price' => $product_info['price'],
			'quantity' => $product_info['quantity'],
		);
		$product_data = $this->getVirtualProductData($product_data);
		$result = array();

		foreach($product_data as $k=>$item) {
			if(!in_array($item['id'], array_column($new_data, 'id'))) {
				$result[] = $item;
			}
		}
		if(!empty($result)) {
			$this->deleteProduct($result);
		}
	}
	
	public function eventEditProduct($route, $vdata, $product_id){
		if($this->config->get('module_beroo_status') != 1){
			return;
		}
		$product_id = $product_id ? $product_id : $vdata[0];
		$product_info = $this->model_catalog_product->getProduct($product_id);
		if($product_info['status'] == 0){
			$product_data = array(
				'product_id' => $product_id,
				'sku' => $product_info['sku'],
				'model' => $product_info['model'],
				'name' => $product_info['name'],
				'price' => $product_info['price'],
				'quantity' => $product_info['quantity'],
			);
			$product_data = $this->getVirtualProductData($product_data);
			$this->deleteProduct($product_data);
		}
		else{
			$data = $this->createData($product_info);
			$this->berooLibrary->saveProduct($data);
		}
	}

	public function eventDeleteProduct($route, $data){
		if($this->config->get('module_beroo_status') != 1){
			return;
		}
		$product_info = $this->model_catalog_product->getProduct($data[0]);
		$product_data = array(
			'product_id' => $product_info['product_id'],
			'sku' => $product_info['sku'],
			'model' => $product_info['model'],
			'name' => $product_info['name'],
			'price' => $product_info['price'],
			'quantity' => $product_info['quantity'],
		);
		$product_data = $this->getVirtualProductData($product_data);
		$this->deleteProduct($product_data);
	}
    
    private function createData($product_info) {

		// getProducts() (bulk "Sync" list) returns only summary columns — no
		// description/meta. Reload the full product so description + SEO reach
		// Beroo for both the Sync button and the on-edit event.
		if(!isset($product_info['meta_description']) || !isset($product_info['description'])){
			$full_info = $this->model_catalog_product->getProduct($product_info['product_id']);
			if($full_info){
				$product_info = array_merge($full_info, $product_info);
			}
		}
		// product category
        $category_id = '';
	    $category_name = '';
		$product_main_category_id = '';
        $product_cats = $this->model_catalog_product->getProductCategories($product_info['product_id']);
		if(method_exists('ModelCatalogProduct','getProductMainCategoryId')){
			$product_main_category_id = $this->model_catalog_product->getProductMainCategoryId($product_info['product_id']);
		}
        if($product_main_category_id){
 		   $category_id = $product_main_category_id;
        }
 	    elseif($product_cats){
		   $category_id = $product_cats[0];
        }
	    if($category_id){
		   $category_info = $this->model_catalog_category->getCategory($category_id);
           $category_name = htmlspecialchars_decode($category_info['name']);
	    }

        // product manufacturer
        $manufacturer_name = '';
        if ($product_info['manufacturer_id']) {
           $manufacturer = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id']);
           $manufacturer_name = $manufacturer['name'];
        }

        //generate virtual products by options
        $product_data = $this->getVirtualProductData($product_info);

		// product volume
		$product_length_class_id = $product_info['length_class_id'];
		$product_length_class_unit = '';
		$length_classes = $this->model_localisation_length_class->getLengthClasses();
		foreach($length_classes as $length_class){
			if($length_class['length_class_id']==$product_length_class_id){
				$product_length_class_unit = $length_class['unit'];
			}
		}
		$product_length_multiplier = 1;
		if($product_length_class_unit){
			if($product_length_class_unit == 'mm' || $product_length_class_unit == 'мм'){
				$product_length_multiplier= 0.001;
			}
			if($product_length_class_unit == 'cm' || $product_length_class_unit == 'см'){
				$product_length_multiplier= 0.01;
			}
		}
		$product_length_multiplier_cubic = pow($product_length_multiplier, 3);
		$product_volume = $product_info['length'] * $product_info['width'] * $product_info['height'] * $product_length_multiplier_cubic;
        

		// product specials
		$product_specials = $this->model_catalog_product->getProductSpecials($product_info['product_id']);
		$price_difference = 0;
		$discount = [];
		$date_now = strtotime(date("Y-m-d"));
		if(count($product_specials)==1){
			$product_special = $product_specials[0];
			$date_start = $product_special['date_start'];
			$date_end = $product_special['date_end'];
			$discount['value'] = $product_info['price'] - $product_special['price'];
			if($product_special['date_start'] != '0000-00-00'){
				$date_start = strtotime($product_special['date_start']);
				$discount['date_start'] = date("d.m.Y", $date_start);
			}
			if($product_special['date_end'] != '0000-00-00'){
				$date_end = strtotime($product_special['date_end']);
				$discount['date_end'] = date("d.m.Y", $date_end);
			}
		}
		else{
			foreach($product_specials as $product_special){
				$date_start = '';
				$date_end = '';
				if($product_special['date_start']!='0000-00-00'){
					$date_start = strtotime($product_special['date_start']);
				}
				if($product_special['date_end']!='0000-00-00'){
					$date_end = strtotime($product_special['date_end']);
				}
				if(!$date_start || $date_now >= $date_start){
					if(!$date_end || $date_now <= $date_end){
						$discount['value'] = $product_info['price'] - $product_special['price'];
						if($date_end){
							$discount['date_end'] = date("d.m.Y", $date_end);
						}
						if($date_start){
							$discount['date_start'] = date("d.m.Y", $date_start);
						}
					}
				}
			}
		}
		
		//site url, images
		if(isset($_SERVER['REQUEST_SCHEME'])){
			$request_scheme = $_SERVER['REQUEST_SCHEME'];
		}
		elseif(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==1){
			$request_scheme = 'https';
		}
		else{
			$request_scheme = 'http';
		}
		$site_url = $request_scheme.'://'.$_SERVER['HTTP_HOST'];
		$site_folder = preg_replace("/^([^\?]*)\/admin\/.*$/","$1",$_SERVER['REQUEST_URI']);
		$site_url .= $site_folder;
		
		$product_gallery_images = $this->model_catalog_product->getProductImages($product_info['product_id']);
		$product_images = array();
		$i=0;
		if($product_info['image']){
			$product_images[$i]['fullsize'] = $site_url.'/image/'.$product_info['image'];
			$i++;
		}
		foreach($product_gallery_images as $product_gallery_image){
			$product_images[$i]['fullsize'] = $site_url.'/image/'.$product_gallery_image['image'];
			$i++;
		}
		
		// product attributes
		$product_attributes = $this->model_catalog_product->getProductAttributes($product_info['product_id']);
		$product_attributes_beroo = [];
		$ai = 0;
		foreach($product_attributes as $product_attribute){
			$attribute_id = $product_attribute['attribute_id'];
			$attribute = $this->model_catalog_attribute->getAttribute($attribute_id);
			$attribute_description = '';
			foreach($product_attribute['product_attribute_description'] as $product_attribute_description){
				if(isset($product_attribute_description['text'])){
					$attribute_description = $product_attribute_description['text'];
				}
			};
			if($attribute_id && $attribute_description){
				$product_attributes_beroo[$ai]['name'] = htmlspecialchars_decode($attribute['name']);
				$product_attributes_beroo[$ai]['value'] = htmlspecialchars_decode($attribute_description);
				$ai++;
			}
		}
		
		// build result data array
		$data = array();
		$i = 0;
		foreach ($product_data as $od) {
			$id = $od['id'];
			if($this->product_bind == 'model'){
				$id = $od['model'];
			}
			if($this->product_bind == 'sku'){
				$id = $od['sku'];
			}
			$product_options = isset($od['options']) ? $od['options'] : [];
			$data[$i] = array(
				'id' => $id,
				'name' => htmlspecialchars_decode($od['name']),
				'sku' => htmlspecialchars_decode($product_info['sku']),
				//'sku' => htmlspecialchars_decode($product_info['model']),
				'manufacturer' => htmlspecialchars_decode($manufacturer_name),
				'costPerItem' => $od['price'],
				'discount' => $discount,
				'category' => [
					'id' => $category_id,
					'name' => htmlspecialchars_decode($category_name),
				],
				'description' => htmlspecialchars_decode($product_info['description']),
				'seo' => array(
					'title' => htmlspecialchars_decode(isset($product_info['meta_title']) ? $product_info['meta_title'] : ''),
					'description' => htmlspecialchars_decode(isset($product_info['meta_description']) ? $product_info['meta_description'] : ''),
					'keywords' => htmlspecialchars_decode(isset($product_info['meta_keyword']) ? $product_info['meta_keyword'] : ''),
				),
				'images' => $product_images,
				'params' => array_merge($product_attributes_beroo,$product_options),
				'url' => $site_url.'/index.php?route=product/product&product_id='.$product_info['product_id'],
			);
			if($product_info['weight']>0){
				$data[$i]['weight'] = $product_info['weight'];
			}
			if($product_volume){
				$data[$i]['volume'] = $product_volume;
			}
			/* передавати залишки на складі в Beroo
			if(substr_count($id,'_')<=1){
				$data[$i]['stockBalance'] = $od['quantity'];
			}
			 */
			
			$i++;
		}
		
		return $data;
	}
    
	private function getVirtualProductData($product_data) {
		$product_options = $this->model_catalog_product->getProductOptions($product_data['product_id']);
		$this->sortOptions($product_options);
		$product_data['id'] = $product_data['product_id'];
		$product_data = array($product_data);
		foreach ($product_options as $option) {
			if(!empty($option['product_option_value'])) {
				$product_data = $this->generateOptionProduct($product_data, $option);
			}
		}
		return $product_data;
	}
	
	private function generateOptionProduct($products, $option) {
		$result = array();

		foreach($products as $product){
			$id = (isset($product['id']) ? $product['id'] : $product['product_id']).'_'.$option['option_id'];
			$sku = $product['sku'].'_'.$option['option_id'];
			$model = $product['model'].'_'.$option['option_id'];
			
			foreach ($option['product_option_value'] as $k => $option_value) {
				$product_option_value = $this->model_catalog_product->getProductOptionValue($product['product_id'], $option_value['product_option_value_id']);
				$product_price = $product['price'];

				if(!isset($product_option_value['price'])){
					$product_option_value['price'] = 0;
				}
				if(!isset($product_option_value['name'])){
					$product_option_value['name'] = '';
				}

				if($option_value['price_prefix'] == "+"){
					$product_price = $product['price'] + $product_option_value['price'];
				}
				if($option_value['price_prefix'] == "-"){
					$product_price = $product['price'] - $product_option_value['price'];
				}
				if($option_value['price_prefix'] == "*"){
					$product_price = $product['price'] * $product_option_value['price'];
				}
				if($option_value['price_prefix'] == "/" && $product_option_value['price']!=0){
					$product_price = $product['price'] / $product_option_value['price'];
				}
				if($option_value['price_prefix'] == "="){
					$product_price = $product_option_value['price'];
				}

				if($option_value['price_prefix'] == "u"){
					// +%
					$product_price = $product['price'] * (1 + $product_option_value['price']);
				}
				if($option_value['price_prefix'] == "d"){
					// -%
					$product_price = $product['price'] * (1 - $product_option_value['price']);
				}
				
				$product_options = [];
				if(isset($product['options'])){
					$product_options = $product['options'];
				}
				$product_options[] = [
					'name' => $option['name'],
					'value' => $product_option_value['name'],
				];

				$result[] = array(
					'id' => $id.'-'.$option_value['option_value_id'],
					'sku' => $sku.'-'.$option_value['option_value_id'],
					'model' => $model.'-'.$option_value['option_value_id'],
					'product_id' => $product['product_id'],
					'name' => $product['name'].' '.$product_option_value['name'],
					'price' => $product_price,
					'options' => $product_options,
					'quantity' => $option_value['quantity'],
				);
				
			}
		}
		$result = !empty($result) ? $result : $products;

		return $result;
	}

	private function sortOptions(&$product_options){
		usort($product_options, function ($a, $b){
			if($a["option_id"] == $b["option_id"]){
				return 0;
			}
			return ($a["option_id"] < $b["option_id"]) ? -1 : 1;
		});
	}
	
	private function deleteProduct($products){
		$data = [];
		foreach($products as $product){
			if($product[$this->product_bind]){
				$data[] = [
					'id' => $product[$this->product_bind],
				];
			}
		}
		$this->berooLibrary->deleteProduct($data);
	}
	
	private function getInstalledExtensions($type){
		$extension_data = array();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "extension` WHERE `type` = '" . $this->db->escape($type) . "' ORDER BY `code`");

		foreach ($query->rows as $result) {
			$extension_data[] = $result['code'];
		}

		return $extension_data;
	}

	private function processDataArray($array){
		foreach($array as $key=>$value){
			if($value=='---' || $value=='-'){
				unset($array[$key]);
			}
			else{
				$array[$key] = htmlspecialchars_decode($value);
			}
		}
		return $array;
	}
	
}
