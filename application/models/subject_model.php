<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Subject_model extends CI_Model {
	
	var $id;
	var $client_id;
	var $cate_id;
	var $subject;
	var $query;
	var $inclusive;
	var $exclusive;
	var $latest_update;
	var $matching_status;
	var $latest_matching;
	var $from;
	var $to;
	var $bot_id;
	
	function __constuctor()
	{
		// Call the Model constructor
		parent::__construct();
	}
	
	function init($id=null)
	{
		$this->id = null;
		$this->client_id = null;
		$this->cate_id = null;
		$this->subject = null;
		$this->query = null;
		$this->inclusive = null;
		$this->exclusive = null;
		$this->latest_update  = null;
		$this->matching_status = 'queue';
		$this->latest_matching  = null;
		$this->from = '2012-01-01';
		$this->to = '2012-01-01';
		$this->bot_id = 0;
		
		if($id!=null)
		{
			$query = $this->db->get_where('subject',array('id'=>$id));
			
			if($query->num_rows())
			{
				$this->id = $query->row()->id;
				$this->client_id = $query->row()->client_id;
				$this->cate_id = $query->row()->cate_id;
				$this->subject = $query->row()->subject;
				$this->query = $query->row()->query;
				$this->inclusive = $query->row()->inclusive;
				$this->exclusive = $query->row()->exclusive;
				$this->latest_update  = $query->row()->latest_update;
				$this->matching_status = $query->row()->matching_status;
				$this->latest_matching  = $query->row()->latest_matching;
				$this->from = $query->row()->from;
				$this->to = $query->row()->to;
				$this->bot_id = $query->row()->bot_id;
			}
			return $this;
		}
	}
	
	function insert()
	{
		$this->db->insert('subject',$this);
		return $this->db->insert_id();
	}
	
	function update()
	{
		$res = $this->db->update('subject',$this,array('id'=>$this->id));
		return $res;
	}
	
	function delete()
	{
		$res = $this->db->delete('subject',array('id'=>$this->id));
		return $res;
	}
}
?>