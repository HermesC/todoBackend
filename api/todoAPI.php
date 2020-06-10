<?php
//require_once('api.php');
require_once('rb-mysql.php');
class todoAPI {
	public  $api_name = 'todo';
	function __construct(){
		header('Content-Type: application/json');
	  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
	  header('Access-Control-Allow-Headers: Content-Type');
		header('Access-Control-Allow-Credentials: true');
		$this->requestUri = explode('/', $_SERVER['REQUEST_URI']);
		$this->requestParams = $_REQUEST;
		$this->payload = json_decode(trim(file_get_contents('php://input')),1);

		//Определение метода запроса
		$this->method = $_SERVER['REQUEST_METHOD'];
		if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
				if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
						$this->method = 'DELETE';
				} else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
						$this->method = 'PUT';
				} else {
						throw new Exception("Unexpected Header");
				}
		}
		R::setup( 'mysql:host=127.0.0.1;dbname=storage','root', '');

		if ( !R::testConnection() )
		{
		        exit ('Нет соединения с базой данных');
		}
		 //else {
		// 	echo 'Есть соед с базой';
		// }
	}
	public function run (){
		// if(array_shift($this->requestUri) !== 'api' || array_shift($this->requestUri) !== $this->apiName){
	  //   throw new RuntimeException('API Not Found', 404);
	  // }
		$this->action = $this->getAction();
		//Если метод(действие) определен в дочернем классе API
		if (method_exists($this, $this->action)) {
				return $this->{$this->action}();
		} else {
				throw new RuntimeException('Invalid Method', 405);
		}
	}
	protected function getAction()
	{
			$method = $this->method;
			switch ($method) {
					case 'GET':
							return 'indexAction';
							break;
					case 'POST':
							return 'createAction';
							break;
					case 'PUT':
							return 'updateAction';
							break;
					case 'DELETE':
							return 'deleteAction';
							break;
					default:
							return null;
			}
	}
	function __destruct(){
		R::close();
	}
	public  function indexAction(){
		$serverCode = 200;
		if (empty($this->requestParams->ids)){
			$result = R::getAll('select * from todos');
		}else {
			$result = R::loadAll('todos', $this->requestParams);
		}
		if (!$result){
				$result = 'Data not found';
				$serverCode = 500;
			}
		return $this->response($result, $serverCode);
	}
	protected function response($data, $status = 500) {
			header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));
			return json_encode($data);
	}
	private function requestStatus($code) {
			$status = array(
					200 => 'OK',
					404 => 'Not Found',
					405 => 'Method Not Allowed',
					500 => 'Internal Server Error',
			);
			return ($status[$code])?$status[$code]:$status[500];
	}
	public function createAction (){
		$newTodo = R::dispense('todos');
		$newTodo->title = $this->payload['title'];
		$newTodo->body = $this->payload['body'];
		$newTodo->deadline = $this->payload['deadline'];
		$newTodo->state = $this->payload['state'];

		R::store($newTodo);
		return json_encode(array("id"=>$newTodo->id));
	}
	public function updateAction (){
		$updateTodo = R::load('todos', $this->payload['id']);
		if ($updateTodo){
			foreach ($this->payload['newTodo'] as $key => $value){
				$updateTodo->{$key} = $value;
			}
			R::store($updateTodo);
		}
	}
	public function deleteAction (){
		if (!empty($this->requestParams['ids'])){
			$toDelete = R::loadAll('todos', $this->requestParams['ids']);
			R::trashAll($toDelete);
		}
	}
}
