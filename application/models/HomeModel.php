<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class HomeModel extends CI_Model {

	public function setVerify($data) {
		$this->db->insert("verify", $data);
		return $this->db->insert_id();
	}

	public function getVerify($phone, $code) {
		$query = $this->db->get_where('verify', array('phone' => $phone, 'code' => $code));
		return $query->row();
	}

	public function addUser($data) {
		$this->db->insert('users', $data);
		return $this->db->insert_id();
	}

	public function deleteVerify($phone, $code) {
		$this->db->delete('verify', array('phone' => $phone, 'code' => $code));
		return $this->db->affected_rows();
	}

	public function login($phone) {
		$query = $this->db->get_where("users", array("phone" => $phone));
		return $query->row();
	}

	public function book($data) {
		$this->db->insert('bookings', $data);
		return $this->db->insert_id();
	}

	public function getServices($location) {

		$this->db->select("category");
		$this->db->where("city", $location);

		$query = $this->db->get("experts");
		return $query->result();
	}

	public function getCategories($category) {
		$this->db->select("category");
		$this->db->where("city", $location);

		$query = $this->db->get("experts");
		return $query->result();
	}

	public function getPackagesInCategory($category) {
		$query = $this->db->get_where("packages", array("profession" => $category));
		return $query->result();
	}

	public function getSubpackagesInCategory($category) {
		$query = $this->db->get_where("sub_packages", array("package_id" => $category));
		return $query->result();
	}

	public function getServicesInSubpackage($id) {
		$query = $this->db->get_where("services", array("sub_package_id" => $id));
		return $query->result();
	}


	public function getServicesInPackage($package) {
		$query = $this->db->get_where("services", array("package" => $package));
		return $query->result();
	}

	public function getPackages($category) {
		$query = $this->db->get_where("packages", array("profession" => $category));
		return $query->result();
	}

	public function packageServices($package) {
		$query = $this->db->get_where("services", array("package" => $package));
		return $query->result();
	}

	public function getLocationExpertsWithService($location, $service) {
		$sql = "SELECT * FROM experts WHERE services LIKE '%$service%' AND city = '$location'";
		$query = $this->db->query($sql);
		return $query->row();
	}

	public function checkExpertBookings($expertid, $date, $time) {
		$query = $this->db->get_where("bookings", array("expert_id" => $expertid, "book_date" => $date, "book_time" => $time));
		return $query->row();
	}

	public function getUser($id) {
		$query = $this->db->get_where('users', array('id' => $id));
		return $query->row();
	}

	public function addExpert($data) {
		$this->db->insert('experts', $data);
		return $this->db->insert_id();
	}

	public function loginPartner($email, $password) {
		$query = $this->db->get_where("experts", array("email" => $email, "password" => $password));
		return $query->row();
	}

	public function setupService($email, $data) {
		$query = $this->db->update('experts', $data, array('email' => $email));
		return $this->db->affected_rows();
	}

	public function checkStatus($email) {
		$this->db->select('setup');
		$this->db->where('email', $email);
		$query = $this->db->get('experts');

		return $query->row();
	} 

	public function getExpertWithEmail($email) {
		$query = $this->db->get_where("experts", array("email" => $email));
		return $query->row();
	}

	public function myBookings($id) {
		$query = $this->db->get_where("bookings", array("expert_id" => $id, 'status' => 0));
		return $query->result();
	}

	public function professionsIn($location) {
		$query = $this->db->get_where("availability", array("city" => $location, 'status' => 1));
		return $query->result();
	}
}