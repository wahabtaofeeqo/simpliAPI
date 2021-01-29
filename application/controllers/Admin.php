<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {

	
	public function index() {
		echo json_encode("Hello World");
	}

	public function login() {

		$this->load->model("HomeModel", "model");

		$response = array("error" => false);

		$postdata = $this->input->raw_input_stream;

		if (isset($postdata) && !empty($postdata)) {
			
			$postdata = json_decode($postdata);
			$data = $postdata->data;

			$email = $data->username;
			$password = $data->password;

			if ($email != null && $password != null) {
				$admin = $this->model->adminLogin($email, $password);

				if ($admin != null) {

					$response['error'] = FALSE;
					$response['data'] = $admin;
				}
				else {

					$response['error'] = TRUE;
					$response['errorMessage'] = "Username OR Password Not Correct";
				}
			}
			else {
				$response['error'] = TRUE;
				$response['errorMessage'] = "Required data not present";
			}
		}

		echo json_encode($response);
	}

	public function register() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$data = $this->input->raw_input_stream;

		if (isset($data) && !empty($data)) {

			$data = json_decode($data);
			$data = $data->data;

			$name = $data->name;
			$email = $data->email;
			$phone = $data->phone;
			$password = $data->password;
			$school = $data->school;
			$department = $data->department;
			$level = $data->level;

			if ($name != null && $email != null && $phone !=  null && $school != null && $department != null && $password != null && $level != null) {
				
				$user = array(
					'name' => $name,
					'faculty' => $school,
					'email' => $email,
					'phone' => $phone,
					'password' => $password,
					'department' => $department,
					'level' => $level);

				if ($this->homeModel->checkEmail($email)) {
					$response['error'] = TRUE;
					$response['errorMessage'] = "Email Already Exist";
				}
				elseif ($this->homeModel->checkPhone($phone)) {
					$response['error'] = TRUE;
					$response['errorMessage'] = "Phone Number Already Exist";
				}
				else {

					$id = $this->homeModel->addUser($user);

					$response['error'] = FALSE;
					$response['message'] = 'Your account has been created successfully';
				}
			}
			else {
				$response['error'] = TRUE;
				$response['errorMessage'] = "Required field is not available";
			}
		}
		else {

			$response['error'] = TRUE;
			$response['errorMessage'] = "Required Data is OR are missing";
		}

		echo json_encode($response);
	}

	public function applicants() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$data = array();
		$applicants = $this->homeModel->applicants();
		if ($applicants != null) {

			foreach ($applicants as $key => $value) {
				
				$points = 0;
				$grades = explode(",", $value->grades);

				foreach ($grades as $key => $grade) {
					if ($grade == 'a') {
						$points += 20;
					}
					else if ($grade == 'b') {
						$points += 15;
					}
					else if ($grade == 'c') {
						$points += 10;
					}
					else if ($grade == 'd') {
						$points += 5;
					}
				}

				$row = array();
				$row['firstname'] = $value->firstname;
				$row['lastname'] = $value->lastname;
				$row['phone'] = $value->phone;
				$row['department'] = $value->department;
				$row['jamb'] = $value->jamb;
				$row['points'] = $points;

				$data[] = $row;
			}

			$response['error'] = FALSE;
			$response['data'] = $data;
		}
		else {
			$response['error'] = TRUE;
			$response['errorMessage'] = "No Record Found";
		}

		echo json_encode($response);
	}


	public function admitted() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$data = array();
		$admitteds = $this->homeModel->admitted();
		if ($admitteds != null) {

			// foreach ($applicants as $key => $value) {
				
			// 	$points = 0;
			// 	$grades = explode(",", $value->grades);

			// 	foreach ($grades as $key => $grade) {
			// 		if ($grade == 'a') {
			// 			$points += 20;
			// 		}
			// 		else if ($grade == 'b') {
			// 			$points += 15;
			// 		}
			// 		else if ($grade == 'c') {
			// 			$points += 10;
			// 		}
			// 		else if ($grade == 'd') {
			// 			$points += 5;
			// 		}
			// 	}

			// 	$row = array();
			// 	$row['firstname'] = $value->firstname;
			// 	$row['lastname'] = $value->lastname;
			// 	$row['phone'] = $value->phone;
			// 	$row['department'] = $value->department;
			// 	$row['jamb'] = $value->jamb;
			// 	$row['points'] = $points;

			// 	$data[] = $row;
			// }

			$response['error'] = FALSE;
			$response['data'] = $admitteds;
		}
		else {
			$response['error'] = TRUE;
			$response['errorMessage'] = "No Record Found";
		}

		echo json_encode($response);
	}

	public function publish() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$post = $this->input->raw_input_stream;
		if (isset($post) && !empty($post)) {
			
			$post = json_decode($post);
			$data = $post->data;

			$department = $data->department;
			$points = $data->points;
			$jamb = $data->jamb;

			$res = $this->homeModel->publish($department, $points, $jamb);
			if ($res) {
				$response['error'] = FALSE;
				$response['message'] = "Admission has been publish for $department";
			}
			else {
				$response['error'] = TRUE;
				$response['errorMessage'] = "No Student match this criteria";
			}
		}
		else {
			$response['error'] = TRUE;
			$response['errorMessage'] = "Required data is OR are missing";
		}

		echo json_encode($response);
	}


	public function criteria() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$post = $this->input->raw_input_stream;
		if (isset($post) && !empty($post)) {
			
			$post = json_decode($post);
			$data = $post->data;

			$department = $data->department;
			$points = $data->points;
			$jamb = $data->jamb;

			$data = array(
				'department' => $department,
				'points' => $points,
				'jamb' => $jamb);

			$c = $this->homeModel->checkCriteria($department);
			if ($c != null) {
				$response['error'] = TRUE;
				$response['errorMessage'] = "Criteria has already been set for this department";
			}
			else {

				$this->homeModel->criteria($data);
				$response['error'] = FALSE;
				$response['message'] = "Admission criteria has been set for $department";
			}
		}
		else {
			$response['error'] = TRUE;
			$response['errorMessage'] = "Required data is OR are missing";
		}

		echo json_encode($response);
	}

	public function addAdmin() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$data = $this->input->raw_input_stream;

		if (isset($data) && !empty($data)) {

			$data = json_decode($data);
			$data = $data->data;

			$name = $data->name;
			$email = $data->email;
			$phone = $data->phone;
			$password = $data->password;

			if ($name != null && $email != null && $phone !=  null && $password != null) {
				
				$user = array(
					'name' => $name,
					'email' => $email,
					'phone' => $phone,
					'password' => $password);

				if ($this->homeModel->checkEmailAdmin($email)) {
					$response['error'] = TRUE;
					$response['errorMessage'] = "Email Already Exist";
				}
				elseif ($this->homeModel->checkPhoneAdmin($phone)) {
					$response['error'] = TRUE;
					$response['errorMessage'] = "Phone Number Already Exist";
				}
				else {

					$this->homeModel->addAdmin($user);

					$response['error'] = FALSE;
					$response['message'] = 'Your account has been created successfully';
				}
			}
			else {
				$response['error'] = TRUE;
				$response['errorMessage'] = "Required field is not available";
			}
		}
		else {

			$response['error'] = TRUE;
			$response['errorMessage'] = "Required Data is OR are missing";
		}

		echo json_encode($response);
	}

	public function admins() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$data = $this->homeModel->admins();
		if ($data != null) {
			$response['error'] = FALSE;
			$response['data']  = $data;
		}
		else {
			$response['error'] = TRUE;
			$response['errorMessage'] = "No Record Found";
		}

		echo json_encode($response);
	}
}