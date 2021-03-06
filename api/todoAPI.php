<?php
//require_once('api.php');
require_once('rb-mysql.php');
class todoAPI {
	public  $apiName = 'todo';
	function __construct(){
		header('Content-Type: application/json');
	  header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
							return 'get_action';
							break;
					case 'POST':
							return 'createAction';
							break;
					case 'PUT':
					if (stripos($_SERVER['REQUEST_URI'], 'all') === false){
							return 'updateAction';
					}
					else {
						  return 'updateAllAction';
					}
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
	public  function get_action(){
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
		$serverCode = 200;

		$newTodo = R::dispense('todos');
		$newTodo->title = $this->payload['title'];
		$newTodo->body = $this->payload['body'];
		$newTodo->deadline = $this->payload['deadline'];
		$newTodo->state = $this->payload['state'];
		$new_serial_id = R::getCell('select max(serial_id) FROM todos');
		if ($new_serial_id == null)
			$newTodo->serial_id = 1;
		else
			$newTodo->serial_id = $new_serial_id + 1;

		R::store($newTodo);

		$result = array(
			"success" => true,
			"newTask" => array(
				"id" => $newTodo->id,
				"body"=> $newTodo->body,
				"serial_id" => $newTodo->serial_id,
				"title" => $newTodo->title,
				"deadline" => $newTodo->deadline,
				"state" => $newTodo->state
			)
		);
		return $this->response($result, $serverCode);
	}
	public function updateAction (){
		$serverCode = 200;
		$updateTodo = R::load('todos', $this->payload['id']);
		if ($updateTodo){
			foreach ($this->payload['changes'] as $key => $value){
				$updateTodo->{$key} = $value;
			}
			R::store($updateTodo);
		}
		$result = array(
			"success" => false,
			'action' =>'updateAction'
		);
		return $this->response($result, $serverCode);
	}
	public function updateAllAction (){
		$serverCode = 200;
		$allTasks = R::getAll('select * from todos');
		$tasks = R::convertToBeans('todos', $allTasks);

		if ($tasks){
				foreach($tasks as $task){
					foreach ($this->payload['changes'] as $prop => $change) {
						$task->{$prop} = $change;
					}
					R::store($task);
				}
		} else {
			$serverCode = 500;
		}
		$result = array(
			"success" => true
		);
		return $this->response($result, $serverCode);

	}
	public function deleteAction (){
		$serverCode = 200;
		if (!empty($this->requestParams['id'])){
			$toDelete = R::load('todos', $this->requestParams['id']);
			// $all_data = R::exec('select * from todos');
			// foreach ($all_data as $row){
			// 	if ($row->serial_id > $toDelete->serial_id) $row->serial_id = $row->serial_id + 1;
			// 	else if ($row->serial_id < $toDelete->serial_id) $row->serial_id = $row->serial_id -1;
			// }
			// R::store($all_data);
			R::trash($toDelete);
		}
		$result = array(
			"success" => true
		);
		return $this->response($result, $serverCode);
	}
}
