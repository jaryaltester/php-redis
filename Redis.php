<?php

class Redis {

	private $ci;

	protected $redis;
	
	protected $site_id;
	
	protected $user_id;

	protected $prefix = '';

    /**
     * Create Redis connection.
     */
    function __construct($site_id, $user_id)
    {
		$this->ci =& get_instance();
		$this->_check_connection();

		$this->site_id = $site_id;
		$this->user_id = $user_id;
		$this->prefix = 'user_'.$this->site_id.'_'.$this->user_id.'_';
    }

    public function set_prefix($prefix){
    	$this->prefix = $prefix;
    }

    public function get_prefix(){
    	return $this->prefix;
    }

    public function cache_exist(){
    	$this->_check_connection();
    	if(!$this->prefix){
			echo 'For cache checking, prefix must be required.';
			exit;
		}
    	$key = $this->prefix.'cache_exist';
    	if($this->redis->get($key)){
    		return true;
    	}
    	return false;
    }

    public function key_exist($permission_key, $prefix = TRUE){
	  	$key = $prefix ? $this->prefix.trim($permission_key) : trim($permission_key);
	  	if(!$this->redis->get($key)){
    		return false;
    	}
    	return true;
	}

	public function set_redis_value($key, $value = 1, $prefix = TRUE){
		$key = trim($key);
		$value = is_string($value) ? trim($value) : $value;
		if(empty($key)){
			return false;
		}
		if($prefix){
			$this->set_cache_exist();
			$this->redis->set($this->prefix.$key, $value);
		}else{
			$this->redis->set($key, $value);
		}
		return $this;
    	
	}

	public function get_key_value($permission_key, $prefix = TRUE){
		$key = $prefix ? $this->prefix.$permission_key : $permission_key;
	  	if($this->redis->get($key)){
    		return $this->redis->get($key);
    	}
    	return false;
	}

	public function delete_key($key, $prefix = FALSE){
		$key = $prefix ? $this->prefix.$key : $key;
		$this->redis->del($key);
	}

	public function get_all_keys_by_prefix($prefix = FALSE){
		$prefix = $prefix ? $prefix : $this->prefix;

		if(!$prefix){
			return false;
		}

		return $this->redis->keys($prefix.'*');
			
	}

	protected function set_value_by_array(array $key_value_array, $prefix = TRUE){
		foreach($key_value_array as $key => $value){
			if(is_string($key)){
    			$this->set_redis_value($key, $value, $prefix);
			}elseif(is_integer($key)){
				$this->set_redis_value($value, 1, $prefix);
			}
		}
		return true;
	}

	protected function delete_all_keys_by_prefix(){
		$keys = $this->get_all_keys_by_prefix();
		if(!empty($keys)){
			$this->redis->del($keys);
		}
		
	}

	protected function set_cache_exist(){
    	if(!$this->cache_exist()){
			$this->redis->set($this->prefix.'cache_exist', 1);
		}
		return $this;
    }

	private function _check_connection(){
		if(!$this->ci){
			$this->ci = &get_instance();
		}
		$config = $this->ci->config->item('redis_credential');
		try {
		    $this->redis = new Predis\Client((array)$config);
		    return true;
		}
		catch (Exception $e) {
		    $this->redis = null;
		    return $e->getMessage();
		}
	}
}
