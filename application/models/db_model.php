<?php  defined('BASEPATH') OR exit('No direct script access allowed');

class DB_Model extends CI_Model{

	 function __construct()
  {
    parent::__construct();
    //$this->load->database();
    // $this->output->enable_profiler(TRUE);   
  }

  // PARAMS 
  // $tblName     STRING    TABLE NAME
  // $flds        ARRAY     FIELDS TO FILTER DEFAULT * OR ALL
  // $where       ARRAY     FOR WHERE CLAUSE OPTIONAL
  // $order_by    VARIANT   SORTING FIELDS
  // $order_type  STRING    SORT TYPE DEFAULT ASC or ASCENDING
  // $limit       INT       LIMIT THE QUERY RESULTS DEFAULT 1000 RECORDS
  // RETURN       BOOLEAN OR ARRAY RESULT
  public function selectQuery(
      $tblName,
      $where='',
      $flds = '*',
      $limit=1000,
      $start=0,
      $order_by='',
      $order_type='ASC'
      ){

      $this->db->select($flds);
      if ($where){ $this->db->where($where); }
      if($order_by != ''){ $this->db->order_by($order_by, $order_type);}
      $this->db->limit($limit,$start);
      $result = $this->db->get($tblName)->result();

      return  ($result? $result : false);
  }

  // PARAMS tawg uli sir viber
  // $tblName STRING  TABLE NAME
  // $data    ARRAY   FIELDS WITH VALUES
  // RETURN   BOOLEAN
  public function batchInsert($tblName,$data=''){
    if ($data===''){return false;}
    $result = $this->db->insert_batch($tblName,$data);
    return  $result;
  }

  // PARAMS 
  // $tblName STRING  TABLE NAME
  // $data    ARRAY   FIELDS WITH VALUES
  // RETURN   BOOLEAN
  public function insertRecord($tblName,$data=''){ 
    if ($data===''){return false;}    
    $result = $this->db->insert($tblName,$data);              
    return  $result;
  }

  // PARAMS 
  // $tblName STRING  TABLE NAME
  // $data    ARRAY   FIELDS WITH VALUES
  // $where   ARRAY   WHERE CLAUSE
  // RETURN   BOOLEAN
  public function updateRecord($tblName,$fldSet,$where,$not_in = false){
      $this->db->set($fldSet);
      if($not_in){
        $this->db->where_not_in($where);
      }else{
        $this->db->where($where);
      }
      
      $this->db->update($tblName);
      //debug($this->db->last_query(),1);
      return ($this->db->affected_rows() > 0 ? true : false );  
  }

  // PARAMS 
  // $tblName STRING  TABLE NAME
  // $where   ARRAY   WHERE CLAUSE
  // RETURN   BOOLEAN
  public function deleteRecord($tblName,$where){
    $this->db->where($where);
    $this->db->delete($tblName);
    return ($this->db->affected_rows() > 0 ? true : false );
  }
 
   // PARAMS 
  // $tblName STRING  TABLE NAME
  // $where   ARRAY   WHERE CLAUSE
  // RETURN   BOOLEAN
  public function deleteRecordor($tblName,$where){
    $this->db->or_where($where);
    $this->db->delete($tblName);
    return ($this->db->affected_rows() > 0 ? true : false );
  }
  
//PARAMS
  // $sql   STRING 
  //RETURN  BOOLEAN OR RESULTS IN ARRAY
  public function queryBinder($sql){   
    $result = $this->db->query($sql);
    return ($result->num_rows() > 0?  $result->result(): false);              
  }
  
  public function count_rows($tblName,$where=''){
    if ($where){ $this->db->where($where); }
    return $this->db->get($tblName)->num_rows();
  }

/*  public function fetchRecord($table,$arrayFilter=''){
    //die($arrayFilter);
  if ($arrayFilter=='') {
      return $this->db->get($table)->result_array();
    }else{
      return $this->db->where($arrayFilter)->get($table)->result_array();
       //var_dump($this->db->last_query());die();
    }
  }*/
   public function audit_log($info)
 {
       $activity_data = array(   
        'SAL_SYSTEM'=>'MOP',           
        'SAL_USER_AGENT'=>$this->input->user_agent(),  
        'SAL_BY'=> $this->session->userdata('uservars')->USR_ID,
        'SAL_CREATE_DATETIME'=> date('Ymd H:i:s'),
        'SAL_IP_ADDRESS'=> getUserIpAddress()
        );
     
     $data = array_merge($info,$activity_data);
     $this->insertRecord('SS_ACTIVITY_LOG',$data);
 }
 
  public function query_generate($type, $tablename, $params=NULL){
     $this->db->reset_query();
    switch (strtoupper($type)) {
      case 'INSERT':
        # code...
        if(!isset($params)){debug('ERROR: {DATA} parameter is required',1);}
        $this->db->set($params);
        debug( $this->db->get_compiled_insert($tablename),1 );
        break;
      case 'UPDATE':
        # code...
        if(!isset($params['DATA'])){debug('ERROR: {DATA} parameter is required',1);}
        $this->db->set($params['DATA']);
        if(!isset($params['WHERE'])){debug('ERROR: {WHERE} parameter is required',1);}
        $this->db->where($params['WHERE']);
        debug( $this->db->get_compiled_update($tablename),1 );
        break;
      case 'DELETE':
        # code...
        if(!isset($params)){debug('WARNING: Not providing {DATA} parameter will delete all records',1 );}
        if(isset($params)){ $this->db->where($params); }
        debug( $this->db->get_compiled_delete($tablename),1 );
        break;      
      default://select
        # code...
        if(isset($params['FIELDS'])){ $this->db->select($params['FIELDS']); }
        //optional result order
        if(isset($params['ORDER']['BY'])){
          $order_type = ($params['ORDER']['TYPE']) ? $params['ORDER']['TYPE'] : 'ASC';
          $this->db->order_by($params['ORDER']['BY'],$order_type);
        }
        //optional result limit
        if(isset($params['LIMIT']['ROWS'])){
          $start = ($params['LIMIT']['OFFSET']) ? $params['LIMIT']['OFFSET'] : 0;
          $this->db->limit($params['LIMIT']['ROWS'],$start);
        }
        //optional result filter
        if(isset($params['WHERE'])){ $this->db->where($params['WHERE']); }
        
        debug( $this->db->get_compiled_select($tablename),1 );
        break;
    }
  }
  
  public function exec_sqlsrv_sp($spname,$spparams=array()){
    /* Array variable format:
     * $array_variable = array(
     *              array("Some value", SQLSRV_PARAM_IN),
     *              array($var_2, SQLSRV_PARAM_OUT),
     *              array($var_3, SQLSRV_PARAM_INOUT)
     *          );
     */
    
    $qmarks="?";
    for($c=0;$c<(count($spparams,0)-1);$c++) $qmarks= "?," . $qmarks; // generate '?' placeholders =no. of arguments
    $tsql_callSP = "{call " . $spname . "(" . $qmarks . ")}"; // the final SP to be executed
    //debug($tsql_callSP);debug($spparams);
    // Get CI DB Connection handler for direct query execution
    $q2 = sqlsrv_query($this->db->conn_id, $tsql_callSP, $spparams);

    if(!$q2){
      //log_message('error',"Stored Procedure execution failed" . sqlsrv_errors());
      return sqlsrv_errors();
    }else{ //successful execution of Stored Procedure
      $resarr=array();//array_push requires the type to be array to function correctly
      while($ta=sqlsrv_fetch_array ($q2,SQLSRV_FETCH_ASSOC)){
          array_push($resarr,$ta);
      };
      //$resarr=$resarr[0]; //eliminate parent array
      //sqlsrv_next_result($q2); //BUG in MS sqlsrv driver. This call is necesary to set the OUT variables
      return $resarr;
        
    }
  }

}

?>
