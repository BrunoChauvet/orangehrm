<?php

require_once 'BaseMapper.php';
require_once 'MnoIdMap.php';

/**
* Map Connec Employee representation to/from OrangeHRM Employee
*/
class EmployeeMapper extends BaseMapper {
  private $_employeeService;
  private $_jobTitleService;

  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Employee';
    $this->local_entity_name = 'Employee';
    $this->connec_resource_name = 'employees';
    $this->connec_resource_endpoint = 'employees';

    $this->_employeeService = new EmployeeService();
    $this->_jobTitleService = new JobTitleService();
  }

  // Return the Employee local id
  protected function getId($employee) {
    return $employee->empNumber;
  }

  // Return a local Employee by id
  protected function loadModelById($local_id) {
    return $this->_employeeService->getEmployee($local_id);
  }

  // Match an EMployee by employee id
  protected function matchLocalModel($employee_hash) {
    if($employee_hash['employee_id'] != null) {
      $employee = $this->_employeeService->getEmployeeByEmployeeId($employee_hash['employee_id']);
      if($employee != null) { return $employee; }
    }
  }

  // Map the Connec resource attributes onto the OrangeHRM Employee
  protected function mapConnecResourceToModel($employee_hash, $employee) {
    // Map hash attributes to Employee
    if(!is_null($employee_hash['employee_id'])) { $employee->employeeId = $employee_hash['employee_id']; }
    if(!is_null($employee_hash['first_name'])) { $employee->firstName = $employee_hash['first_name']; }
    if(!is_null($employee_hash['last_name'])) { $employee->lastName = $employee_hash['last_name']; }
    if(!is_null($employee_hash['birth_date'])) { $employee->emp_birthday = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $employee_hash['birth_date'])->format("Y-m-d"); }
    if(!is_null($employee_hash['gender'])) { $employee->emp_gender = ($employee_hash['gender'] == 'M' ? Employee::GENDER_MALE : Employee::GENDER_FEMALE); }
    if(!is_null($employee_hash['social_security_number'])) { $employee->ssn = $employee_hash['social_security_number']; }

    // Address
    if(!is_null($employee_hash['address']) && !is_null($employee_hash['address']['billing'])) {
      if(!is_null($employee_hash['address']['billing']['line1'])) { $employee->street1 = $employee_hash['address']['billing']['line1']; }
      if(!is_null($employee_hash['address']['billing']['line2'])) { $employee->street2 = $employee_hash['address']['billing']['line2']; }
      if(!is_null($employee_hash['address']['billing']['city'])) { $employee->city = $employee_hash['address']['billing']['city']; }
      if(!is_null($employee_hash['address']['billing']['postal_code'])) { $employee->emp_zipcode = $employee_hash['address']['billing']['postal_code']; }
      if(!is_null($employee_hash['address']['billing']['country'])) { $employee->country = $employee_hash['address']['billing']['country']; }
      if(!is_null($employee_hash['address']['billing']['region'])) { $employee->province = $employee_hash['address']['billing']['region']; }
    }

    // Phone
    if(!is_null($employee_hash['telephone'])) {
      if(!is_null($employee_hash['telephone']['landline'])) { $employee->emp_hm_telephone = $employee_hash['telephone']['landline']; }
      if(!is_null($employee_hash['telephone']['landline2'])) { $employee->emp_work_telephone = $employee_hash['telephone']['landline2']; }
      if(!is_null($employee_hash['telephone']['mobile'])) { $employee->emp_mobile = $employee_hash['telephone']['mobile']; }
    }

    // Email
    if(!is_null($employee_hash['email'])) {
      if(!is_null($employee_hash['email']['address'])) { $employee->emp_work_email = $employee_hash['email']['address']; }
      if(!is_null($employee_hash['email']['address2'])) { $employee->emp_oth_email = $employee_hash['email']['address2']; }
    }

    // Job title is mapped to a JobTitle object
    if(!is_null($employee_hash['job_title'])) {
      $employee->jobTitle = $this->findOrCreateJobTitleByName($employee_hash['job_title']);
    }

    // Job details
    if(!is_null($employee_hash['hired_date'])) { $employee->joined_date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $employee_hash['hired_date'])->format("Y-m-d"); }
  }

  // Map the OrangeHRM Employee to a Connec resource hash
  protected function mapModelToConnecResource($employee) {
    $employee_hash = array();

    // Map Employee to Connec hash
    if(!is_null($employee->employeeId)) { $employee_hash['employee_id'] = $employee->employeeId; }
    if(!is_null($employee->firstName)) { $employee_hash['first_name'] = $employee->firstName; }
    if(!is_null($employee->lastName)) { $employee_hash['last_name'] = $employee->lastName; }
    if(!is_null($employee->emp_birthday)) { $employee_hash['birth_date'] = DateTime::createFromFormat('Y-m-d', $employee->emp_birthday)->format("Y-m-d\TH:i:s\Z"); }
    if(!is_null($employee->emp_gender)) { $employee_hash['gender'] = ($employee->emp_gender) == Employee::GENDER_MALE ? 'M' : 'F'; }
    if(!is_null($employee->ssn)) { $employee_hash['social_security_number'] = $employee->ssn; }

    // Address
    if(!is_null($employee->street1)) { $employee_hash['address']['billing']['line1'] = $employee->street1; }
    if(!is_null($employee->street2)) { $employee_hash['address']['billing']['line2'] = $employee->street2; }
    if(!is_null($employee->city)) { $employee_hash['address']['billing']['city'] = $employee->city; }
    if(!is_null($employee->emp_zipcode)) { $employee_hash['address']['billing']['postal_code'] = $employee->emp_zipcode; }
    if(!is_null($employee->country)) { $employee_hash['address']['billing']['country'] = $employee->country; }
    if(!is_null($employee->province)) { $employee_hash['address']['billing']['region'] = $employee->province; }

    // Phone
    if(!is_null($employee->emp_hm_telephone)) { $employee_hash['telephone']['landline'] = $employee->emp_hm_telephone; }
    if(!is_null($employee->emp_work_telephone)) { $employee_hash['telephone']['landline2'] = $employee->emp_work_telephone; }
    if(!is_null($employee->emp_mobile)) { $employee_hash['telephone']['mobile'] = $employee->emp_mobile; }

    // Email
    if(!is_null($employee->emp_work_email)) { $employee_hash['email']['address'] = $employee->emp_work_email; }
    if(!is_null($employee->emp_oth_email)) { $employee_hash['email']['address2'] = $employee->emp_oth_email; }

    // Job title
    if(!is_null($employee->jobTitle)) { $employee_hash['job_title'] = $employee->jobTitle->jobTitleName; }
    if(!is_null($employee->joined_date)) { $employee_hash['hired_date'] = DateTime::createFromFormat('Y-m-d', $employee->joined_date)->format("Y-m-d\TH:i:s\Z"); }

    return $employee_hash;
  }

  // Persist the OrangeHRM Employee
  protected function persistLocalModel($employee) {
    $this->_employeeService->saveEmployee($employee, false);
  }

  // Find or Create an OrangeHRM JobTitle object by its name
  private function findOrCreateJobTitleByName($jobTitleName) {
    $job_list = $this->_jobTitleService->getJobTitleList();
    foreach ($job_list as $job) {
      if($job->jobTitleName == $jobTitleName) { return $job; }
    }
    
    $job = new JobTitle();
    $job->jobTitleName = $jobTitleName;
    return $job->save();
  }
}
