<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test extends CI_Controller {
	
	public function multi_fetch($domain_id = 3)
	{
		$sql = "SELECT count(id) as count from page where outdate = 0 and parse_child = 0 and parse_post = 0 and domain_id = $domain_id";
		$query = $this->db->query($sql);
		$rows_count = $query->row()->count;
		echo "TOTAL = ".$rows_count.PHP_EOL;
		
		$rows_per_thread = 1000;
		$threads_count = $rows_count / $rows_per_thread;
		
		echo "THREADS = ".$threads_count.PHP_EOL;
		
		$url_list = array();
		
		for($i=0;$i<$threads_count;$i++)
		{
			$offset = $rows_per_thread * $i;
			$url_list[$i] = "http://127.0.0.1/spider/index.php/fetch2/all/$domain_id/$rows_per_thread/$offset";
		}
		
		print_r($url_list);
		
		//$url = 'http://127.0.0.1/spider/index.php/fetch2/all/2/10/0';
		
		$options = array( 
		        CURLOPT_RETURNTRANSFER => true,         // return web page 
		        CURLOPT_HEADER         => false,        // don't return headers 
		        CURLOPT_FOLLOWLOCATION => true,         // follow redirects 
		        CURLOPT_ENCODING       => "",           // handle all encodings 
		        CURLOPT_USERAGENT      => "Googlebot",     // who am i 
		        CURLOPT_AUTOREFERER    => true,         // set referer on redirect 
		        CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect 
		        CURLOPT_TIMEOUT        => 300,          // timeout on response 
		        CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
		        CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl 
		        CURLOPT_SSL_VERIFYPEER => false,        // 
		        CURLOPT_VERBOSE        => false
		    ); 
		
		// build the multi-curl handle, adding both $ch
		echo "INITIATING....\n";
		$mh = curl_multi_init();
		$curl_array = array();
		foreach($url_list as $i=>$url)
		{
			$curl_array[$i] = curl_init($url); 
			curl_setopt_array($curl_array[$i], $options); 
			curl_multi_add_handle($mh, $curl_array[$i]);
		}
		
		
		echo "EXECUTING....\n";
		$running = NULL;
		do
		{ 
			usleep(10000); 
			curl_multi_exec($mh,$running); 
		} while($running > 0);
		
		echo "CLOSING....\n";
		foreach($url_list as $i => $url)
		{ 
			curl_multi_remove_handle($mh, $curl_array[$i]); 
		} 
		curl_multi_close($mh);
		
		$sql = "SELECT count(id) as count from page where outdate = 0 and parse_child = 0 and parse_post = 0 and domain_id = $domain_id";
		$query = $this->db->query($sql);
		$rows_count = $query->row()->count;
		echo "TOTAL = ".$rows_count.PHP_EOL;
		
		// $ch      = curl_init($url); 
		// curl_setopt_array($ch,$options); 
		// $content = curl_exec($ch); 
		// $err     = curl_errno($ch); 
		// $errmsg  = curl_error($ch) ; 
		// $header  = curl_getinfo($ch); 
		// curl_close($ch);
		// 
		// if($header["http_code"]!=200) echo "FAILED";
		// 
		// $size = $header["size_download"]/1024;
		// log_message('info',"page_model : fetched : ".$size." KB");
		// 
		// $fetch['content'] = $content;
		// $fetch['meta'] = $header;
		// $fetch['size'] = $size;
		// 
		// echo $fetch['content'];
	}
	
	public function url_exist($domain_id = 125)
	{
		$url = "/ประชาสัมพันธ์/";
		$this->db->select('url');
		$query = $this->db->get_where('page',array('domain_id'=>$domain_id));
		$array = array();
		foreach($query->result() as $row) {$array[] = $row->url;}
		echo 'url = '.$url.PHP_EOL;
		echo 'count = '.count($array).PHP_EOL;
//		var_dump($array);

		$options = array (
			'url' => $url,
			'domain_id' => $domain_id
			);
		
		
		// ----- Array ------ //
		$time = microtime(true);
		echo in_array($url,$array)?'true':'false'.PHP_EOL;
		$diff = microtime(true)-$time;
		echo ' - exec:'.$diff.'sec'.PHP_EOL;
		
		// ----- Query ----- //
		$time = microtime(true);
		$query = $this->db->get_where('page',$options);
		if($query->num_rows() > 0) echo $query->row()->id;
		$diff = microtime(true)-$time;
		echo ' - exec:'.$diff.'sec'.PHP_EOL;
	}
	
	public function memcache_getstats()
	{
		$memcache = new Memcache;
		$memcache->connect('localhost', 11211) or die ("Could not connect");
		
		$var = $memcache->getStats();
		$memcache->close();

		print_r ($var);
	}
	
	public function memcache()
	{
		$memcache = new Memcache;
		$memcache->connect('localhost', 11211) or die ("Could not connect");

		$version = $memcache->getVersion();
		echo "Server's version: ".$version."<br/>\n";

		$tmp_object = new stdClass;
		$tmp_object->str_attr = 'key';
		$tmp_object->int_attr = 123;

		echo '.';
		$tmp_object->str_attr .= '1';
		$memcache->add('key1', $tmp_object, false, 10) or die ("Failed to save data at the server");
		echo '.';
		$tmp_object->str_attr .= '2';
		$memcache->add('key2', $tmp_object, false, 10) or die ("Failed to save data at the server");
		echo '.';
		$tmp_object->str_attr .= '3';
		$memcache->add('key3', $tmp_object, false, 10) or die ("Failed to save data at the server");
		echo '.';
		$tmp_object->str_attr .= '4';
		$memcache->add('key4', $tmp_object, false, 10) or die ("Failed to save data at the server");
		echo '.';
		echo "Store data in the cache (data will expire in 10 seconds)<br/>\n";

		$get_result = $memcache->get('key2');
		echo "Data from the cache:<br/>\n";
		var_dump($get_result);
		
		$get_result = $memcache->get('key3');
		echo "Data from the cache:<br/>\n";
		var_dump($get_result);
		
		$get_result = $memcache->get('key2');
		echo "Data from the cache:<br/>\n";
		var_dump($get_result);
		
		$memcache->close();
	}
	
	public function directory()
	{
		$content = "testwertyuiovbnm,";
		
		//filename = latest_fetch + id
		$filename = mdate('%Y%m%d%H%i',time())."_test2";
		
		//file_folder = 8 digits Year+Month+Day
		$file_folder = mdate('%Y%m%d',time()).'/';
		
		$path = $this->config->item('fetch_file_path');
		
		$folder = substr($file_folder,0,-1);
		if(!file_exists($path.$folder)) mkdir($path.$folder);
		
//		echo $path.$file_folder.$filename;
		
		//write file
		if (!write_file($path.$file_folder.$filename, $content))
		{
			echo PHP_EOL."page_model : write_file error";
		}
		else
		{
			echo "page_model : write_file success :";
		}
	}
	
	public function date_range($post_id)
	{
		$from = '2012-05-01';
		$to = '2012-05-30';
		
		$post = new Post_model();
		$post->init($post_id);
		
		// if post_date is our of range then skip
		$post_date = strtotime($post->post_date);
		$date_from = strtotime($from);
		$date_to = strtotime($to);
		if($post_date < $date_from || $post_date > $date_to) $res = 'out of range';
		else $res =  "ok";
		
		echo 'from : '.mdate('%Y-%m-%d %H:%i:%s',$date_from).', to : '.$to.', post_date : '.$post->post_date.' '.$res;
	}
	public function daily()
	{
		$date = new Datetime('yesterday');
		// go back for 10 days
		$to_date = new Datetime('yesterday');;
		$to_date->sub(new DateInterval('P30D'));
//		$to_date = new Datetime('2012-06-01');
		
		while($date >= $to_date)
		{
			$date_format = $date->format('Y-m-d');
			echo 'date ='.$date_format;
			echo "\n";
			
			$daily = "SELECT d.id as id,d.name,c.count as post_count, c.date as post_date
			 		  FROM domain d
					  LEFT JOIN
			            (SELECT count(ps.id) as count, date(CASE WHEN ps.post_date IS NULL
						THEN ps.parse_date ELSE ps.post_date END) as date, pg.domain_id 
			             FROM post ps, page pg
						 WHERE ps.page_id = pg.id
						 AND date(ps.post_date) = '$date_format'
						 GROUP BY pg.domain_id) c 
						 ON c.domain_id = d.id";
			$query = $this->db->query($daily);
			foreach($query->result() as $row)
			{
				$option = array('date'=>$row->post_date,'domain_id'=>$row->id);
				$query = $this->db->get_where('daily_post_count',$option);
				$num_rows = $query->num_rows();
				if($num_rows > 0) $update = true;
				else $update = false;

				if(!$update)
				{
				$a = array('date'=>$row->post_date,'domain_id'=>$row->id,'post_count'=>$row->post_count);
					if($row->post_date == null)
					{
						if($row->post_count == null) $a = array('date'=>"$date_format",'domain_id'=>$row->id,'post_count'=>'0');
						else $a = array('date'=>"$date_format",'domain_id'=>$row->id,'post_count'=>$row->post_count);
					}
					
				$this->db->insert('daily_post_count',$a);
				}
				else /*update*/ 
				{
					$a = array('date'=>$row->post_date,'domain_id'=>$row->id,'post_count'=>$row->post_count);
					$this->db->update('daily_post_count',$a,array("id"=>$row->id));
				}
			}
			
			$date->sub(new DateInterval('P1D'));
		}
	}
	
	public function hilight()
	{
		$subject_id = 126;
		$post_id = 430359;

		$this->load->helper('mood');
		
		$post_body = $this->custom_model->get_value('post','body',$post_id);
		echo 'post_body : '.$post_body;
		echo '<br /><br />';
		echo 'result : '.get_hilight($subject_id,$post_body);
	}
	
	public function mb_ereg()
	{
		$str = " ไอเดีย อายุ : 15 ปี █ █ █ █ █ █ █ ███ █ █ ██ █ █ ██ █ █ █ █ ██ เอ่อ หลังจากโดนแขวนไป สาม สี่";
		
		echo $str."<br />";
		$str = mb_ereg_replace($pattern,'',$str);
		echo $str."<br />";
	}
	
	public function test_exec()
	{
		echo exec('pwd');
		
		error_reporting(E_ALL);

		/* Add redirection so we can get stderr. */
		$handle = popen('php index.php test test_test 2>&1', 'r');
		echo "'$handle'; " . gettype($handle) . "\n";
		$read = fread($handle, 2096);
		echo $read;
		pclose($handle);
	}

	public function test_child()
	{
		// $parent = "http://www.pantip.com/cafe/mbk/";
		// 		$url = array(
		// 			"/cafe/mbk/topic/T11451306/T11451306.html",
		// 			"/cafe/news/topic/NE11456948/NE11456948.html",
		// 			"http://www.pantip.com/cafe/jatujak/topic/J11306858/J11306858.html",
		// 			"listerT.php?subgroup=5"
		// 			);
		
		$parent = "http://www.blognone.com/";
		$url = array(
			"/news/28383/%E0%B9%82%E0%B8%AD%E0%B9%80%E0%B8%9B%E0%B8%AD%E0%B9%80%E0%B8%A3%E0%B9%80%E0%B8%95%E0%B8%AD%E0%B8%A3%E0%B9%8C%E0%B8%AA%E0%B8%A7%E0%B8%B4%E0%B8%AA%E0%B8%AB%E0%B8%A5%E0%B8%B8%E0%B8%94%E0%B8%82%E0%B9%89%E0%B8%AD%E0%B8%A1%E0%B8%B9%E0%B8%A5-nokia-lumia-900-%E0%B8%AD%E0%B8%AD%E0%B8%81%E0%B8%AA%E0%B8%B4%E0%B9%89%E0%B8%99%E0%B9%80%E0%B8%94%E0%B8%B7%E0%B8%AD%E0%B8%99-%E0%B8%81%E0%B8%9E-2012",
			"/news/28383/%E0%B9%82%E0%B8%AD%E0%B9%80%E0%B8%9B%E0%B8%AD%E0%B9%80%E0%B8%A3%E0%B9%80%E0%B8%95%E0%B8%AD%E0%B8%A3%E0%B9%8C%E0%B8%AA%E0%B8%A7%E0%B8%B4%E0%B8%AA%E0%B8%AB%E0%B8%A5%E0%B8%B8%E0%B8%94%E0%B8%82%E0%B9%89%E0%B8%AD%E0%B8%A1%E0%B8%B9%E0%B8%A5-nokia-lumia-900-%E0%B8%AD%E0%B8%AD%E0%B8%81%E0%B8%AA%E0%B8%B4%E0%B9%89%E0%B8%99%E0%B9%80%E0%B8%94%E0%B8%B7%E0%B8%AD%E0%B8%99-%E0%B8%81%E0%B8%9E-2012#comment-356699asd"
			);
			
		$pattern = "";
		
		foreach($url as $u)
		{
			if(strpos($u,"#") > 0) $u = substr($u,0,strpos($u,"#"));
			
			$pattern = '/\A\/news\/\d*\//';
			if (preg_match($pattern, $u)) {
    			echo $u."<br/>";
			}
		

			//$child = false;
			//if ($child) echo "child:";
			//echo $u."<br />";
		}
	}

	public function read($page_id,$utf8=0)
	{
		$page = new Page_model();
		$page->init($page_id);
		if($utf8===0)
		{
			echo trim(iconv("CP874","utf-8//TRANSLIT//IGNORE",$page->read_file()));
		}
		else
		{
			echo $page->read_file();
		}
	}

	public function search()
	{
		$this->load->helper('sphinxapi');
		$this->load->helper('mood');
		
		// Reset PHP Timeout to 5min
		set_time_limit(0);
		
		//////////////////////
		// parse command line
		//////////////////////

		// // for very old PHP versions, like at my home test server
		// if ( is_array($argv) && !isset($_SERVER["argv"]) )
		// 	$_SERVER["argv"] = $argv;
		// unset ( $_SERVER["argv"][0] );
		// 
		// // build query
		// if ( !is_array($_SERVER["argv"]) || empty($_SERVER["argv"]) )
		// {
		// 	print ( "Usage: php -f test.php [OPTIONS] query words\n\n" );
		// 	print ( "Options are:\n" );
		// 	print ( "-h, --host <HOST>\tconnect to searchd at host HOST\n" );
		// 	print ( "-p, --port\t\tconnect to searchd at port PORT\n" );
		// 	print ( "-i, --index <IDX>\tsearch through index(es) specified by IDX\n" );
		// 	print ( "-s, --sortby <CLAUSE>\tsort matches by 'CLAUSE' in sort_extended mode\n" );
		// 	print ( "-S, --sortexpr <EXPR>\tsort matches by 'EXPR' DESC in sort_expr mode\n" );
		// 	print ( "-a, --any\t\tuse 'match any word' matching mode\n" );
		// 	print ( "-b, --boolean\t\tuse 'boolean query' matching mode\n" );
		// 	print ( "-e, --extended\t\tuse 'extended query' matching mode\n" );
		// 	print ( "-ph,--phrase\t\tuse 'exact phrase' matching mode\n" );
		// 	print ( "-f, --filter <ATTR>\tfilter by attribute 'ATTR' (default is 'group_id')\n" );
		// 	print ( "-fr,--filterrange <ATTR> <MIN> <MAX>\n\t\t\tadd specified range filter\n" );
		// 	print ( "-v, --value <VAL>\tadd VAL to allowed 'group_id' values list\n" );
		// 	print ( "-g, --groupby <EXPR>\tgroup matches by 'EXPR'\n" );
		// 	print ( "-gs,--groupsort <EXPR>\tsort groups by 'EXPR'\n" );
		// 	print ( "-d, --distinct <ATTR>\tcount distinct values of 'ATTR''\n" );
		// 	print ( "-l, --limit <COUNT>\tretrieve COUNT matches (default: 20)\n" );
		// 	print ( "--select <EXPRLIST>\tuse 'EXPRLIST' as select-list (default: *)\n" );
		// 	exit;
		// }
		// 
		// $args = array();
		// foreach ( $_SERVER["argv"] as $arg )
		// 	$args[] = $arg;
		
		

		$cl = new SphinxClient ();

		$q = $this->input->post('query');
//		$q = "samsung & (\"สินค้า\" | \"ของ\"|\"ที่นี่\") & (\"หมด\" | \"ขาด\"|\"ไม่\"|\"ขาย\"|\"ต้องการ\"|\"เยอะ\"|\"ครบ\"|\"ตลาด\"|\"สต๊อก\" | \"มี\") | (\"ซื้อ\" |\"หา\" ) & (\"ได้\"|\"ไม่\"|\"เจอ\")";
		$sql = "";
		$mode = SPH_MATCH_EXTENDED;
		$host = "192.168.1.102";
		$port = 9312;
		$index = "*";
		$groupby = "";
		$groupsort = "@group desc";
		$filter = "group_id";
		$filtervals = array();
		$distinct = "";
		$sortby = "@id ASC";
		$sortexpr = "";
		$offset = 0;
		$limit = 1000;
		$ranker = SPH_RANK_PROXIMITY_BM25;
		$select = "";
		
		//Extract subject keyword from search string
		$keywords = get_keywords($q);
		
		
		// for ( $i=0; $i<count($args); $i++ )
		// {
		// 	$arg = $args[$i];
		// 
		// 	if ( $arg=="-h" || $arg=="--host" )				$host = $args[++$i];
		// 	else if ( $arg=="-p" || $arg=="--port" )		$port = (int)$args[++$i];
		// 	else if ( $arg=="-i" || $arg=="--index" )		$index = $args[++$i];
		// 	else if ( $arg=="-s" || $arg=="--sortby" )		{ $sortby = $args[++$i]; $sortexpr = ""; }
		// 	else if ( $arg=="-S" || $arg=="--sortexpr" )	{ $sortexpr = $args[++$i]; $sortby = ""; }
		// 	else if ( $arg=="-a" || $arg=="--any" )			$mode = SPH_MATCH_ANY;
		// 	else if ( $arg=="-b" || $arg=="--boolean" )		$mode = SPH_MATCH_BOOLEAN;
		// 	else if ( $arg=="-e" || $arg=="--extended" )	$mode = SPH_MATCH_EXTENDED;
		// 	else if ( $arg=="-e2" )							$mode = SPH_MATCH_EXTENDED2;
		// 	else if ( $arg=="-ph"|| $arg=="--phrase" )		$mode = SPH_MATCH_PHRASE;
		// 	else if ( $arg=="-f" || $arg=="--filter" )		$filter = $args[++$i];
		// 	else if ( $arg=="-v" || $arg=="--value" )		$filtervals[] = $args[++$i];
		// 	else if ( $arg=="-g" || $arg=="--groupby" )		$groupby = $args[++$i];
		// 	else if ( $arg=="-gs"|| $arg=="--groupsort" )	$groupsort = $args[++$i];
		// 	else if ( $arg=="-d" || $arg=="--distinct" )	$distinct = $args[++$i];
		// 	else if ( $arg=="-l" || $arg=="--limit" )		$limit = (int)$args[++$i];
		// 	else if ( $arg=="--select" )					$select = $args[++$i];
		// 	else if ( $arg=="-fr"|| $arg=="--filterrange" )	$cl->SetFilterRange ( $args[++$i], $args[++$i], $args[++$i] );
		// 	else if ( $arg=="-r" )
		// 	{
		// 		$arg = strtolower($args[++$i]);
		// 		if ( $arg=="bm25" )		$ranker = SPH_RANK_BM25;
		// 		if ( $arg=="none" )		$ranker = SPH_RANK_NONE;
		// 		if ( $arg=="wordcount" )$ranker = SPH_RANK_WORDCOUNT;
		// 		if ( $arg=="fieldmask" )$ranker = SPH_RANK_FIELDMASK;
		// 		if ( $arg=="sph04" )	$ranker = SPH_RANK_SPH04;
		// 	}
		// 	else
		// 		$q .= $args[$i] . " ";
		// }

		////////////
		// do query
		////////////

		$cl->SetServer ( $host, $port );
		$cl->SetConnectTimeout ( 1000 );
		$cl->SetMaxQueryTime( 0 );
		$cl->SetArrayResult ( true );
		$cl->SetWeights ( array ( 100, 1 ) );
		$cl->SetMatchMode ( $mode );
		// if ( count($filtervals) )	$cl->SetFilter ( $filter, $filtervals );
		// if ( $groupby )				$cl->SetGroupBy ( $groupby, SPH_GROUPBY_ATTR, $groupsort );
		if ( $sortby )				$cl->SetSortMode ( SPH_SORT_EXTENDED, $sortby );
		// if ( $sortexpr )			$cl->SetSortMode ( SPH_SORT_EXPR, $sortexpr );
		// if ( $distinct )			$cl->SetGroupDistinct ( $distinct );
		if ( $select )				$cl->SetSelect ( $select );
		if ( $limit )				$cl->SetLimits ( $offset, $limit, ( $limit>1000000 ) ? $limit : 1000000 );
		$cl->SetRankingMode ( $ranker );
		$res = $cl->Query ( $q, $index );

		////////////////
		// print me out
		////////////////
		
		$search = array(
			'name' => 'query',
			'id' => 'query',
			'value' => $q,
            'size' => '50',
            'style' => 'width:50%',
		);
		
		$this->load->helper('form');
		echo "<div>";
		echo form_open('test/search');
		echo "Search [Complex] :";
		echo form_input($search);
		echo form_submit('submit','GO!');
		echo form_close();
		echo "</div>";

		if ( $res===false )
		{
			echo "Query failed: " . $cl->GetLastError() . ".\n";

		} else
		{
			if ( $cl->GetLastWarning() )
				echo "WARNING: " . $cl->GetLastWarning() . "\n\n";

			echo "Query '$q' retrieved $res[total] of $res[total_found] matches in $res[time] sec.\n";
			// echo PHP_EOL.'Query stats:'.PHP_EOL;
			// if ( is_array($res["words"]) )
			// {
			// 	foreach ( $res["words"] as $word => $info )
			// 	{
			// 		echo "    '$word' found $info[hits] times in $info[docs] documents\n";
			// 	}
			// }
			echo PHP_EOL;

			if ( is_array($res["matches"]) )
			{
				$n = 1;
				echo 'Matches:'.count($res["matches"]);
				
				echo "<table border=1 width=960><thead><tr><td width=50>ID</td><td width=20>Mood</td><td width=100>Date</td><td width=200>Title</td><td>Body</td></tr></thead><tbody>";
				foreach ( $res["matches"] as $docinfo )
				{
//					var_dump($docinfo);
					//echo "$n. doc_id=$docinfo[id], weight=$docinfo[weight]";
					
					$post = new Post_model();
					$post->init($docinfo["id"]);
					
					echo "<tr><td>($n)$post->id<br/>weight=$docinfo[weight]</td>";
					
//					echo '<td>mood</td>';
					echo '<td>';
					printf("%2.2f",get_mood($post->body,$keywords));
					echo '</td>';
					// foreach ( $res["attrs"] as $attrname => $attrtype )
					// {
					// 	$value = $docinfo["attrs"][$attrname];
					// 	if ( $attrtype==SPH_ATTR_MULTI || $attrtype==SPH_ATTR_MULTI64 )
					// 	{
					// 		$value = "(" . join ( ",", $value ) .")";
					// 	} else
					// 	{
					// 		if ( $attrtype==SPH_ATTR_TIMESTAMP )
					// 			$value = date ( "Y-m-d H:i:s", $value );
					// 	}
					// 	echo ", $attrname=$value";
					// }
					echo "<td>$post->post_date</td><td>$post->title</td><td>$post->body</td></tr>";
					echo PHP_EOL;
					$n++;
				}
				echo "</tbody></table>";
				
			}
		}
	}
}