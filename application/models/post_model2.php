<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Post_model2 extends CI_Model {
	
	var $id;
	var $post_date;
	var $parse_date;
	var $page_id;
	var $type;
	var $author_id;
	var $title;
	var $body;
	var $segmented;
	var $is_segmented;
	var $tweet_id;
	var $reach_calculation_state;
	var $facebook_id;
	
	function __constuctor()
	{
		// Call the Model constructor
		parent::__construct();
	}
	
	function init($id=null)
	{
		$this->id = null;
		$this->post_date = null;
		$this->parse_date = null;
		$this->page_id = null;
		$this->type = null;
		$this->author_id = null;
		$this->title = null;
		$this->body = null;
		$this->segmented = null;
		$this->is_segmented = 0;
		$this->tweet_id = null;
		$this->reach_calculation_state = 'ready';
		$this->facebook_id = null;
		
		if($id!=null)
		{
			$query = $this->db->get_where('post',array('id'=>$id));
			
			if($query->num_rows())
			{
				$this->id = $query->row()->id;
				$this->post_date = $query->row()->post_date;
				$this->parse_date = $query->row()->parse_date;
				$this->page_id = $query->row()->page_id;
				$this->type = $query->row()->type;
				$this->author_id = $query->row()->author_id;
				$this->title = $query->row()->title;
				$this->body = $query->row()->body;
				$this->segmented = $query->row()->segmented;
				$this->is_segmented = $query->row()->is_segmented;
				$this->tweet_id = $query->row()->tweet_id;
				$this->reach_calculation_state = $query->row()->reach_calculation_state;
				$this->facebook_id = $query->row()->facebook_id;
			}
			return $this;
		}
	}
	
	function insert()
	{
		$this->db->insert('post',$this);
		log_message('info',"post model : inserted.");
		return $this->db->insert_id();
	}
	
	function insert_cache($cache)
	{
		// add obj to memcache
		$key = rand(1000,9999).'-'.microtime(true);
		$cache->add($key, $this, false, 12*60*60) or die ("Failed to save OBJECT at the server");
		echo '.';
	}
	
	function update()
	{
		$res = $this->db->update('post',$this,array('id'=>$this->id));
		log_message('info',"post model [".$this->id."]: updated.");
		return $res;
	}
	
	function delete()
	{
		$res = $this->db->delete('post',array('id'=>$this->id));
		log_message('info',"post model [".$this->id."]: deleted.");
		return $res;
	}
	
	function add_ext_url($url=null)
	{
		if($url == null) return false;
		
		$ext_url_id = $this->is_ext_url_exist($url);
		if($ext_url_id === false) //if not found existing insert new ext_url and get it
		{   
			$ext_url = array('url' => $url);
			$this->db->insert('ext_url',$ext_url);
			$ext_url_id = $this->db->insert_id();
		}	
		
		// insert in post_ext_url table
		$post_ext_url = array('post_id'=> $this->id, 'ext_url_id'=>$ext_url_id);
		$this->db->insert('post_ext_url',$post_ext_url);
		$post_ext_url_id = $this->db->insert_id();
	}
	
	function is_ext_url_exist($url)
	{
		$option = array('url'=>$url);
		$query = $this->db->get_where('ext_url',$option);
		if($query->num_rows())
		{
			return $query->row()->id;
		}
		else
		{
			return false;
		}
	}
	
	function is_author_exist($str)
	{
		$author = array('username' => $str);
		$query = $this->db->get_where('author',$author);
		
		if($query->num_rows())
		{
			return $query->row()->id;
		}
		else
		{
			return false;
		}
	}
	
	function get_author_id($str=null)
	{
		if($str == null) return 0;
		
		$author_id = $this->is_author_exist($str);
		if($author_id == false) // if not found author create one
		{
			log_message('info',"post model : new author : ".$str);
			$author = array ('username' => $str);
			$this->db->insert('author',$author);
			$author_id = $this->db->insert_id();
		}
		
		return $author_id;
	}
	
	function get_author_name()
	{
		$author_name = $this->custom_model->get_value('author','username',$this->author);
		return $author_name;
	}
	
	function get_page()
	{
		$page = new Page_model();
		$page->init($this->page_id);
		return $page;
	}
	
	function get_post_website($post_id){
		$sql = "SELECT 	po.id as 'post_id',po.post_date,po.title,po.body,po.type,a.id as 'author_id',
				a.username as 'author',p.id as 'page_id',d.id as 'website_id',d.name as 'website_name',
				dc.id as 'website_cate_id',dc.name as 'website_cate',
				d.root_url,p.url,
				dt.id as 'website_type_id',dt.name as 'website_type'  
			FROM 	domain_type dt,domain_categories dc,page p,post po,author a,domain d
			WHERE 	d.domain_type_id = dt.id 
				AND dc.id = d.domain_cate_id 
				ANd d.id = p.domain_id 
				AND p.id = po.page_id
				AND po.author_id = a.id 
				AND po.id = ".$post_id." ";
				
		
		$query = $this->db->query($sql);
		return $query->row();	
	}
	function get_post_twitter($post_id){	
		$sql = "SELECT 	po.id as 'post_id',po.post_date,po.body,po.type,tweet_id,
				a.id as 'author_id',a.username as 'author'
			FROM 	post po,author a
			WHERE 	po.author_id = a.id 
				AND po.id = ".$post_id;
		$query = $this->db->query($sql);
		return $query->row();				
	}
	function get_post_facebook($post_id){	
		$sql = "SELECT 	po.id as 'post_id',po.post_date,po.type,a.id as 'author_id',
				a.username as 'author',body,
				po.facebook_id as 'facebook_id',
				pof.parent_post_id,pf.facebook_id as 'facebook_page_id',pf.name as 'facebook_page_name',
				pof.likes,pof.shares
			FROM 	post po,author a,post_facebook pof,page_facebook pf
			WHERE  po.author_id = a.id 
				ANd po.id = pof.post_id 
				AND pof.page_id = pf.facebook_id 
				AND po.id = ".$post_id;
		$query = $this->db->query($sql);
		return $query->row();
	}
	function get_subject($subject_id){
		$sql = "SELECT 	s.id as 'subject_id',s.subject as 'subject_name',s.client_id,
				c.cate_id as 'group_id',c.cate_name as 'group' 
			FROM 	subject s,categories c 
			WHERE 	s.cate_id = c.cate_id AND s.id = ".$subject_id;
		$query = $this->db->query($sql);
		return $query->row();
	}
	function insert_post_comment($page_id,$client_id,$thothconnect_db){
		
		$sql = "SELECT 	po.id as 'post_id',po.post_date,po.title,po.body,po.type,a.id as 'author_id',
				a.username as 'author',p.id as 'page_id',d.id as 'website_id',d.name as 'website_name',
				dc.id as 'website_cate_id',dc.name as 'website_cate',d.root_url,p.url,
				dt.id as 'website_type_id',dt.name as 'website_type'
			FROM 	domain_type dt,domain_categories dc,page p,post po,author a,categories c,domain d
			WHERE 	d.domain_type_id = dt.id 
				AND dc.id = d.domain_cate_id 
				ANd d.id = p.domain_id 
				AND p.id = po.page_id
				AND po.author_id = a.id 
				AND po.type IN('post','comment')
				AND po.page_id = ".$page_id."  ";
		
		$query_post = $this->db->query($sql);
		
		foreach($query_post->result_array() as $val){
			
			$sql = "SELECT post_id FROM website_post_comment_c".$client_id ." WHERE post_id = ".$val["post_id"]." ";
			$query2 = $thothconnect_db->query($sql);
					
			if($query2->num_rows() <= 0 ){  
						
				$data = array();
				
				$data["post_id"] = $val["post_id"];
				$data["post_date"] = $val["post_date"];
				$data["title"] = $val["title"];
				$data["body"] = $val["body"];
				$data["type"] = $val["type"];
				$data["author_id"] = $val["author_id"];
				$data["author"] = $val["author"];
				$data["website_id"] = $val["website_id"];
				$data["website_name"] = $val["website_name"];
				$data["website_cate_id"] = $val["website_cate_id"];
				$data["website_cate"] = $val["website_cate"];
				$data["website_type_id"] = $val["website_type_id"];
				$data["website_type"] = $val["website_type"];
				$data["url"] = substr($val["root_url"],0,-1)."".$val["url"];
				$data["page_id"] = $val["page_id"];
	
				$thothconnect_db->insert("website_post_comment_c".$client_id ."",$data);
			}
		}
	}
	function validate(){
		
		$pattern = '/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/';
		if(preg_match($pattern,$this->post_date)){
		    return true;
		}
		else{
		    return false;
		}
	}
}