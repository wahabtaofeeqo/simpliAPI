<?php defined('BASEPATH') OR exit('No direct script access allowed');

require 'Twilio/autoload.php';
use Twilio\Rest\Client;

class Home extends CI_Controller {

	
	public function index() {

		
		$sid = 'ACd24be7024653a5433dfa993046f4b421';
		$token = 'ee515181851506b0ff555009ce191506';
		$client = new Client($sid, $token);

		$client->messages->create(
		    '+919284865249',
		    array(
		        'from' => '+12058398691',
		        'body' => 'Hey Jenny! Good luck on the bar exam!',
		        'mediaUrl' => 'http://farm2.static.flickr.com/1075/1404618563_3ed9a44a3a.jpg',
		    )
		);
	}

	public function sendCode() {

		$this->load->model('homeModel');

		$post = $this->input->raw_input_stream;
		$data = json_decode($post);

		$number = trim($data->number);

		$phone  = "+234".$number;
		$sid = 'ACd24be7024653a5433dfa993046f4b421';
		$token = 'ee515181851506b0ff555009ce191506';
		$client = new Client($sid, $token);

		$code = mt_rand(1000, 6000);

		// $client->messages->create(
		//     $phone,
		//     array(
		//         'from' => '+12058398691',
		//         'body' => "Hey Aditya, from Taofeeq. OTP code is: $code",
		//     )
		// );

		$this->homeModel->setVerify(array('phone' => $number, 'code' => 1234));
		echo json_encode("done");
	}


	public function verifyCode() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$post = $this->input->raw_input_stream;
		$post = json_decode($post);


		$data = $post->data;

		$code = $data->code;
		$phone = $data->number;

		$res = $this->homeModel->getVerify($phone, $code);
		
		if ($res) {
			$response['error'] = FALSE;
			$response['message'] = "Verification passed";
			$response['data'] = $res;
		}
		else {
			$response['error'] = TRUE;
			$response['errorMessage'] = "Verification failed";
		}

		$this->homeModel->deleteVerify($phone, $code);
		echo json_encode($response);
	}


	public function getServices() {

		$this->load->model('homeModel');
		$response = array("error" => false);

		$postdata = $this->input->raw_input_stream;

		if (isset($postdata) && !empty($postdata)) {
			
			$postdata = json_decode($postdata);
			$location = $postdata->location;

			if ($location != null) {
				
				$result = $this->homeModel->getServices($location);
				if ($result != null) {


					$response['data'] = $this->processResult($result);
					$response['error'] = FALSE;
				}
				else {
					$response['error'] = TRUE;
					$response['errorMessage'] = "No Services Found in $location";
				}
			}
			else {

				$response['error'] = TRUE;
				$response['errorMessage'] = "Location is not accessible. Please try again";
			}
		}
		else {
			$response['error'] = TRUE;
			$response['errorMessage'] = "Empty Data passed. Please try again.";
		}

		echo json_encode($response);
	}

	private function processResult($result) {

		$data = array();

		foreach ($result as $key => $value) {
			$row = array();

			$row["category"] = $value->category;

			$temp = explode(" ", $value->category);
			$temp = join("-", $temp);
			$row["link"] = strtolower($temp);

			$data[] = $row;
		}

		return $data;
	}


	public function login() {

		$this->load->model("homeModel");
		$response = array("error" => false);

		$post = $this->input->raw_input_stream;

		$post = json_decode($post);
		$phone = $post->phone;

		$res = $this->homeModel->login($phone);

		if ($res != null) {
			
			$response['error'] = FALSE;
			$response['message'] = "Found";
		}
		else {

			$response['error'] = TRUE;
			$response['errorMessage'] = "Phone Number not recognised.";
		}

		echo json_encode($response);
	}


	public function register() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$post = $this->input->raw_input_stream;
		if (isset($post) && !empty($post)) {
			
			$data = json_decode($post)->data;

			$user = array(
				'phone' => $data->number,
				'email' => $data->email,
				'full_name' => $data->name);

			$res = $this->homeModel->addUser($user);

			if ($res) {
				$response['error'] = FALSE;
				$response['message'] = "Account Created";
			}
			else {
				$response['error'] = TRUE;
				$response['errorMessage'] = "Server error! Please try again.";
			}
		}

		echo json_encode($response);
	}



	public function book() {
		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$post = $this->input->raw_input_stream;
		$post = json_decode($post);
		$data = $post->data;

		$selectedServices = trim($data->services);
		$time = trim($data->time);
		$date = trim($data->date);
		$location = trim($data->location);

		// $selectedServices = "Half Arms Waxing (2), Half Legs Waxing (1)";
		// $time = "9am-10am";
		// $date = "2020-05-13";
		// $location = "Mumbai";


		// Get user
		$user = $this->homeModel->getUser(1);
		

		// Get all expert in this location
		$services = explode(",", $selectedServices); // Convert the string to array
		$availableExperts = array();
		$experts = $this->getExperts($location, $services);

		// Check if expert has not been previously booked
		if (!empty($experts)) {
			
			foreach ($experts as $key => $value) {
				$booked = $this->homeModel->checkExpertBookings($value->id, $date, $time);
				if ($booked == null) {
					$availableExperts[] = $value;
				}
			}

			// Check if experts are available
			if (!empty($availableExperts)) {

				// Book and send message to one of the available experts
				$app = array(
					'book_date' => $date,
					'book_time' => $time,
					'user_id' => $user->id,
					'expert_id' => $availableExperts[0]->id,
					'service' => join(",", $services));

				$this->homeModel->book($app);

				$response['error'] = FALSE;
				$response['message'] = "Appointment booked";
			}
			else {
				$response['error'] = TRUE;
				$response['errorMessage'] = "Non of our experts is available by $time on the this Date.";
			}
		}
		else {

			$response['error'] = TRUE;
			$response['errorMessage'] = "No Experts for your services.";
		}


		echo json_encode($response);
	}


	private function getExperts($location, $services) {

		$experts = array();
		foreach ($services as $key => $value) {
			$service = explode("(", $value)[0];

			$expert = $this->homeModel->getLocationExpertsWithService($location, trim($service));
			if ($expert != null) {
				$experts[] = $expert;
			}
		}

		return $experts;
	}

	public function addExpert() {

		$this->load->model('homeModel');
		$response = array("error" => FALSE);

		$post = $this->input->raw_input_stream;
		$post = json_decode($post);
		if ($post != null) {
			
			$data = $post->data;

			$expert = array(
				'full_name' => $data->name,
				'phone' => $data->phone,
				'city' => $data->city,
				'category' => "",
				'services' => "",
				'email' => $data->email);

			$this->homeModel->addExpert($expert);
			$response['error'] = FALSE;
			$response['message'] = "Account Created. Please wait.";
		}
		else {
			$response['error'] = TRUE;
			$response['errorMessage'] = "Data availability error.";
		}

		echo json_encode($response);
	}


	// Category is same as profession
	public function categoryDetails() {
		$this->load->model("homeModel");
		$response = array("error" => FALSE);

		$output = array();

		$post = $this->input->raw_input_stream;
		$post = json_decode($post);
		$category = $post->category;


		$packages = $this->homeModel->getPackagesInCategory($category);
		if ($packages != null) {
			foreach ($packages as $key => $value) {
				$subPackages = $this->homeModel->getSubpackagesInCategory($value->id);
				$services = array();

				if ($subPackages != null) {
					foreach ($subPackages as $key => $sub) {
						$services = $this->homeModel->getServicesInSubpackage($sub->id);
					}
				}

				$row['package'] = $value;
				$row['sub_packages'] = $subPackages;
				$row['services'] = $services;

				$output[] = $row;
			}
		}

		$response['packages'] = $packages;
		$response['data'] = $output;
		echo json_encode($response);
	}

	public function getPackages() {

		$this->load->model('homeModel');
		$response = array();

		$post = $this->input->raw_input_stream;
		$post = json_decode($post);
		$category = $post->category;

		$result = $this->homeModel->getPackages($category);

		if ($result != null) {
			
			$response['error'] = FALSE;
			$response['data'] = $result;
		}
		else {

			$response['error'] = TRUE;
			$response['errorMessage'] = "Not Found";
		}

		echo json_encode($response);
	}


	public function packageServices() {

		$this->load->model('homeModel');
		$response = array();

		$post = $this->input->raw_input_stream;
		$post = json_decode($post);
		$package = $post->package;

		$result = $this->homeModel->packageServices($package);

		if ($result != null) {
			
			$response['error'] = FALSE;
			$response['data'] = $result;
		}
		else {

			$response['error'] = TRUE;
			$response['errorMessage'] = "Not Found";
		}

		echo json_encode($response);
	}


	public function loginPartner() {

		$this->load->model('homeModel');
		$response = array();

		$post = $this->input->raw_input_stream;
		$post = json_decode($post);
		$data = $post->data;

		$email = trim($data->email);
		$password = trim($data->password);

		$res = $this->homeModel->loginPartner($email, $password);
		if ($res != null) {
			
			$response['error'] = FALSE;
			$response['message'] = "Login successful";
		}
		else {
			$response['error'] = TRUE;
			$response['errorMessage'] = "Email Or Password not correct";
		}

		echo json_encode($response);
	}

	public function setupService() {

		$this->load->model("homeModel");
		$response = array('error' => FALSE);

		$post = $this->input->raw_input_stream;
		$post = json_decode($post);
		$data = $post->data;

		$email = $data->email;
		$city = $data->city;
		$profession = $data->profession;
		$services = $data->services;
		$note = $data->note;

		$setup = array(
			'city' => $city,
			'category' => $profession,
			'services' => $services,
			'note' => $note,
			'setup' => 1);

		$res = $this->homeModel->setupService($email, $setup);

		if ($res) {
			$response['error'] = FALSE;
			$response['message'] = "Business is under review for approval.";
		}
		else {
			$response['error'] = TRUE;
			$response['errorMessage'] = "Operation not successful. try again";
			$response['data'] = $data;
		}

		echo json_encode($response);
	}


	public function checkStatus() {

		$this->load->model('homeModel');
		$response = array('status' => FALSE);
		$post = $this->input->raw_input_stream;

		$email = json_decode($post)->email;

		$res = $this->homeModel->checkStatus($email);
		if ($res->setup) {
			$response['status'] = TRUE;
		}
		else {
			$response['status'] = FALSE;
		}

		echo json_encode($response);
	}

	public function myBookings() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);
		$post = $this->input->raw_input_stream;

		$email = json_decode($post)->email;

		$expert = $this->homeModel->getExpertWithEmail($email);

		$bookings = $this->homeModel->myBookings($expert->id);
		if ($bookings != null) {
			
			$response['error'] = FALSE;
			$response['data'] = $bookings;
		}
		else {

			$response['error'] = TRUE;
			$response['errorMessage'] = "No bookings yet.";
		}

		echo json_encode($response);
	}

	public function professionsIn() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$post = json_decode($this->input->raw_input_stream);
		$location = $post->location;

		$professions = $this->homeModel->professionsIn($location);
		if ($professions != null) {
			
			$response['error'] = FALSE;
			$response['data'] = $this->processProfessionResult($professions);
		}
		else {

			$response['error'] = TRUE;
			$response['errorMessage'] = "Our services are not available in $location yet";
		}

		echo json_encode($response);
	}

	private function processProfessionResult($professions) {

		$data = array();
		foreach ($professions as $key => $value) {

			$row = array();
			$link = explode(" ", trim($value->profession));
			$link = join("-", $link);

			$row["id"] = $value->id;
			$row["city"] = $value->city;
			$row["profession"] = $value->profession;
			$row["link"] = strtolower($link);
			$row["logo"] = $value->logo;

			$data[] = $row;
		}

		return $data;
	}



























	public function complain() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$data = $this->input->raw_input_stream;

		if (isset($data) && !empty($data)) {
			
			$data = json_decode($data);
			$data = $data->data;

			$subject = $data->subject;
			$message = $data->message;
			$email   = $data->email;

			if ($email != null && $subject != null && $message != null) {

				$student = $this->homeModel->getUser($email);
				
				if ($student != null) {
					
					$comp = array(
						'student_id' => $student->id,
						'subject' => $subject,
						'message' => $message
					);

					if ($this->homeModel->addComplain($comp)) {
						
						$response['error'] = FALSE;
						$response['message'] = "Your Complain has been recieved.";
					}
					else {
						$response['error'] = TRUE;
						$response['errorMessage'] = "Error while processing complain. Please try again";
					}
				}
				else {

					$response['error'] = TRUE;
					$response['errorMessage'] = "User Email not recognised";
				}
			}
			else {
				$response['errorMessage'] = "Required data not present";
			}
		}

		echo json_encode($response);
	}


	public function apply() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$post = $this->input->raw_input_stream;
		if (isset($post) && !empty($post)) {
			
			$post = json_decode($post);
			$data = $post->data;

			$email = $data->email;
			if (!empty($email)) {

				$user = $this->homeModel->getUser($email);

				if ($user != null) {
					
					$id = $user->id;
					$father = $data->father;
					$mother = $data->mother;
					$fnumber = $data->fatherNumber;
					$occupation = $data->occupation;
					$sex = $data->sex;
					$address = $data->address;
					$religion = $data->religion;
					$jamb = $data->score;
					$department = $data->department;

					$olevel1 = $data->olevel1;
					$olevel2 = $data->olevel2;
					$olevel3 = $data->olevel3;
					$olevel4 = $data->olevel4;
					$olevel5 = $data->olevel5;
					$olevel6 = $data->olevel6;
					$olevel7 = $data->olevel7;
					$olevel8 = $data->olevel8;
					$olevel9 = $data->olevel9;

					$grade1 = strtolower($data->grade1);
					$grade2 = strtolower($data->grade2);
					$grade3 = strtolower($data->grade3);
					$grade4 = strtolower($data->grade4);
					$grade5 = strtolower($data->grade5);
					$grade6 = strtolower($data->grade6);
					$grade7 = strtolower($data->grade7);
					$grade8 = strtolower($data->grade8);
					$grade9 = strtolower($data->grade9);


					$olevels = array($olevel1, $olevel2, $olevel3, $olevel4, $olevel5, $olevel6, $olevel7, $olevel8, $olevel8, $olevel9);
					$grades  = array($grade1, $grade2, $grade3, $grade4, $grade5, $grade6, $grade7, $grade8, $grade9);

					$olevels = join(",", $olevels);
					$grades  = join(",", $grades);

					$gradesArray  = explode(",", $grades);

					$points = 0;
					foreach ($gradesArray as $key => $grade) {
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
						else if ($grade == 'e') {
							$points += 2;
						}
						else {
							$points += 0;
						}
					}

					$admission = array (
						'student_id' => $id,
						'father' => $father,
						'mother' => $mother,
						'phone' => $fnumber,
						'occupation' => $occupation,
						'sex' => $sex,
						'address' => $address,
						'religion' => $religion,
						'jamb' => $jamb,
						'olevels' => $olevels,
						'grades' => $grades,
						'department' => $department,
						'points' => $points
					);

					$this->homeModel->admission($admission);
					$response['error'] = FALSE;
					$response['message'] = "Your Application has been submited successfully";
				}
				else {
					$response['error'] = TRUE;
					$response['email'] = $email;
					$response['errorMessage'] = "No Record Found For Your Email. Please try again";
				}
			}
			else {
				$response['error'] = TRUE;
				$response['errorMessage'] = "Email not recognised";
			}
		}
		else {
			$response['error'] = TRUE;
			$response['errorMessage'] = "Required Data is OR are missing";
		}

		echo json_encode($response);
	}

	public function status() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$post = $this->input->raw_input_stream;
		if (isset($post) && !empty($post)) {
			
			$post = json_decode($post);
			$data = $post->data;

			$email = $data->email;

			$user = $this->homeModel->getUser($email);

			if ($user != null) {
				
				$apply = $this->homeModel->checkApply($user->id);
				if ($apply != null) {
					
					$response['error'] = FALSE;
					$response['status'] = $apply->status;
				}
				else {
					$response['error'] = TRUE;
					$response['errorMessage'] = "You have not apply";
				}

			}
			else {
				$response['error'] = TRUE;
				$response['errorMessage'] = "Your Email is not recognised";
			}
		}
		else {
			$response['error'] = TRUE;
			$response['errorMessage'] = "Required Data is Or are missing";
		}

		echo json_encode($response);
	}


	public function contact() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$data = $this->input->raw_input_stream;

		if (isset($data) && !empty($data)) {
			
			$data = json_decode($data);
			$data = $data->data;

			$subject = $data->subject;
			$message = $data->message;
			$email   = $data->email;

			if ($email != null && $subject != null && $message != null) {

				$student = $this->homeModel->getUser($email);
				
				if ($student != null) {
					
					$comp = array(
						'student_id' => $student->id,
						'subject' => $subject,
						'message' => $message
					);

					if ($this->homeModel->contact($comp)) {
						
						$response['error'] = FALSE;
						$response['message'] = "Your Message has been recieved.";
					}
					else {
						$response['error'] = TRUE;
						$response['errorMessage'] = "Error while processing Message. Please try again";
					}
				}
				else {

					$response['error'] = TRUE;
					$response['errorMessage'] = "User Email not recognised";
				}
			}
			else {
				$response['errorMessage'] = "Required data not present";
			}
		}

		echo json_encode($response);
	}


	public function allStudents() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);

		$data = $this->homeModel->allApplicants();
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

	public function applicant() {

		$this->load->model('homeModel');
		$response = array('error' => FALSE);
		$points = 0;

		$post = $this->input->raw_input_stream;

		if (isset($post) && !empty($post)) {

			$post = json_decode($post);
			$data = $post->data;
			$id = $data->id;

			$applicant = $this->homeModel->applicant($id);

			if ($applicant != null) {

				$grades = explode(",", $applicant->grades);

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
					else if ($grade == 'e') {
						$points += 3;
					}
				}

				$response['error'] = FALSE;
				$response['data'] = $applicant;
				$response['points'] = $points;
			}
			else {
				$response['error'] = TRUE;
				$response['errorMessage'] = "No Record Found";
			}
		}
		else {
			$response['error'] = TRUE;
			$response['errorMessage'] = "Applicant ID is missing";
		}

		echo json_encode($response);
	}
}