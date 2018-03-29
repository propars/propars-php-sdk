<?php

include 'Client.php';







class ProparsObjectSet
{
    private $default_page_size = 30;
    private $api;
    private $query_params;
    private $_response;

    function __construct($api, $query_params = null)
    {

        $this->api = $api;
        $this->query_params = $query_params ? $query_params : array();
        $this->_response = null;
    }

    /*

    function __str__():
        return '<{} {}?{}>'.format($this->__class__.__name__, $this->api.resource, $this->get_query_string())
    */
    private function get_query_string()
    {
        # TODO urlencode
        return http_build_query($this->query_params);
    }

    private function __call_api(&$method, &$data = null, &$pk = null)
    {
        $endpoint = $this->api->resource;
        if ($pk)
            $endpoint .= $pk . '/';
        $endpoint .= '?' . $this->get_query_string();
        return $this->api->client->call_api($endpoint, $method, $data);
    }

    private function _api_get(&$pk = null)
    {
        return $this->__call_api($method = 'GET', $data = null, $pk);
    }

    public function response()
    {
        if (is_null($this->_response))
            $this->_response = $this->_api_get();
        return $this->_response;
    }

    public function get($pk)
    {
        return $this->_api_get($pk);
    }

    public function getByIndex($index){
        $_limit = in_array('limit', $this->query_params) ? $this->query_params['limit'] : $this->default_page_size;
        $_offset = in_array('offset', $this->query_params) ? $this->query_params['offset'] : 0;
        if($index <= ($_limit + $_offset)){
            return $this->results()[$index];
        } else {
            return $this->offset($index)->limit(1)->results()[0];
        }
    }

    public function update($data, $pk = null)
    {
        return $this->__call_api($method = 'PATCH', $data, $pk);
    }

    public function page_context()
    {
        return $this->response()['page_context'];
    }

    public function count()
    {
        return (int)$this->response()['count'];
    }

    public function results()
    {
        return $this->response()['results'];
    }

    public function filter(array $query_params)
    {

        $new_qp = array_replace($this->query_params, $query_params);
        return new ProparsObjectSet($api = $this->api, $query_params = $new_qp);
    }

    public function order_by()
    {
        $ordering = implode(',', func_get_args());
        return $this->filter(array('ordering' => $ordering));
    }

    public function limit($limit)
    {
        return $this->filter(array('limit' => $limit));
    }

    public function offset($offset)
    {
        return $this->filter(array('offset' => $offset));
    }


    public function page($page_num, $page_size = null)
    {
        $page_size = $page_size ? $page_size : $this->default_page_size;
        $page_num = $page_num < 1 ? 1 : $page_num;
        return $this->limit($page_size)->offset($page_size * ($page_num - 1));
    }

    public function iterate_pages($page_size = null){
        $pn = 0;
        while(true) {
            $pn += 1;
            $_page = $this->page($pn, $page_size);
            if (sizeof($_page->results()) > 0)
                yield $_page;
            else
                break;
        }
    }

}


class ProparsApiRoot{
    function __construct($client){
        $this->client = $client;
    }

    public function __get($key)
    {
        $resource = $key.'/';
        if($this instanceof ProparsResouce){
            $resource = $this->resource.$resource;
        }
        return new ProparsResouce($this->client, $resource);
    }

    static public function connect($username, $password)
    {
        return new ProparsApiRoot(Client::connect($username, $password));
    }

    static public function connectByToken($token)
    {
        return new ProparsApiRoot(new Client($token));
    }
}

class ProparsResouce extends ProparsApiRoot
{
    function __construct($client, $resource)
    {
        parent::__construct($client);
        $this->resource = $resource;
    }

    public function object_set()
    {
        return new ProparsObjectSet($api = $this);
    }


    public function all()
    {
        return $this->object_set();
    }

    public function filter(array $query_params)
    {
        return $this->object_set()->filter($query_params);
    }

    public function get($pk)
    {
        return $this->object_set()->get($pk);
    }

    public function create($data)
    {
        return $this->client->call_api($endpoint = $this->resource, $method = 'POST', $data);
    }

    public function update(&$pk, &$data)
    {
        return $this->object_set()->update($data, $pk);
    }

    public function help()
    {
        return $this->client->call_api($endpoint = $this->resource, $method = 'OPTIONS');
    }
}

