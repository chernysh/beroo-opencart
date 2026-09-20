<?php
/* OpenCart v.2.3, 3.0 */

class Beroo 
{
    public $prefix = 'https://'; //HTTP or HTTPS
    protected $product_handler = '/api/v1/opencart/products/';
    protected $category_handler = '/api/v1/opencart/categories/';
    protected $order_handler = '/api/v1/opencart/orders/';
    
    protected $errors = array();
   
	protected $last_response = array();

	private $_domain;
    private $_key;
    
    public function __construct($domain='', $key='')
	{
		$this->_domain = $domain;
		$this->_key = $key;
	}

    //OUT:	returns number of errors
	public function hasErrors()
	{
		return count($this->errors);
	}
	
	//OUT:	returns array of errors
	public function getErrors()
	{
		return $this->errors;
	}

	public function getResponse()
	{
		return $this->last_response;
	}

    public function addOrder($data)
    {
        $values = $data;

        $values['form'] = $this->_key;

        $url = $this->createUrl($this->order_handler);

        $this->execute($url, $values);
    }

	public function saveCategories($data)
    {
        
        $values = array(
            'form' => $this->_key,
            'action' => 'update',
            'category' => $data,
        );
        $url = $this->createUrl($this->category_handler);
        
        $this->execute($url, $values);
    }
    
	public function saveProduct($data)
    {
		$values = array(
            'form' => $this->_key,
            'action' => 'update',
            'product' => $data,
        );
        $url = $this->createUrl($this->product_handler);

        $this->execute($url, $values);
    }

    public function deleteProduct($data)
    {
		$values = array(
            'form' => $this->_key,
            'action' => 'delete',
            'product' => $data,
        );
        $url = $this->createUrl($this->product_handler);

        $this->execute($url, $values);

    }
	
	public function getPaymentMethods(){
		$url = $this->createUrl('/api/v1/opencart/payment-methods/');
		$response = $this->executeApi($url);
		return $response;
	}
	
	public function getDeliveryMethods(){
		$url = $this->createUrl('/api/v1/opencart/delivery-methods/');
		$response = $this->executeApi($url);
		return $response;
	}
	
	public function getStatuses(){
		$url = $this->createUrl('/api/v1/opencart/statuses/');
		$response = $this->executeApi($url);
		return $response;
	}
	
    protected function execute($actionUrl, $params = array())
	{
		$this->errors = array();

        $ch = curl_init($actionUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json", "Form-Api-Key:".$this->_key));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);			
        $response = curl_exec($ch);
        curl_close($ch);

        $this->last_response = json_decode($response);
        if ($this->last_response == 'error') {
            $this->errors = $this->last_response['message'];
        }

        return $this->last_response;		
	}
	
	private function executeApi($actionUrl, $params = array()){
		$headers = [
			'Content-Type: application/json',
			'Form-Api-Key: '.$this->_key
		];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $actionUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		$result = curl_exec($ch);
		$error = curl_error($ch);
		$result_decoded = json_decode($result,true);

		return $result_decoded;
	}

	private function createUrl($handler){
        $url = parse_url(trim($this->_domain));
		if(isset($url['host'])){
			$domain = $url['host'];
		}
		else{
			$domain = $url['path'];
		}
        return strtolower($this->prefix) . $domain. $handler;
    }
}