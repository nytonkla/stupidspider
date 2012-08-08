<?PHP
	header ('Content-type: text/html; charset=utf-8');
	require("simple_html_dom_helper.php");
	require("date_decoder_th_helper.php");

	function parse_momypedia($fetch,$debug = false){
		
		$html = str_get_html($fetch);
		
		//echo $html;
		
		$parsed_posts_count = 0;
		
		if($parsed_posts_count == 0){

			$main_content = $html->find('h1.txt16',0);
			$post_title = trim($main_content->plaintext);

			$board_msg = $html->find('.txt14',1);
			$post_body = trim($board_msg->plaintext);

			$author = $html->find('span[title^=IP:]',0);
			$post_author = trim($author->plaintext);

			$date_time = $html->find('table[width=644] .divLayout',0);
			$post_date = trim($date_time->plaintext);
			
			$date = explode(" ",$post_date);
						
			$time = @date("H:i", strtotime($date[4]." ".$date[5]));
			$post_date = thYear_decoder($date[3])."-". thMonth_decoder($date[2],"cut")."-".str_replace(",","",$date[1])." ".$time;
		

			if($debug)
			{
				echo "PostTitle:".$post_title;
				echo "<br/>";
				echo "PostBody:".$post_body;
				echo "<br/>";
				echo "PostAuthor:".$post_author;
				echo "<br/>";
				echo "PostDate:".$post_date;
				echo "<hr/>";
			}
			else
			{
				$post = new Post_model();
				$post->init();
				$post->page_id = $page->id;
				$post->type = "post";
				$post->title = $post_title;
				$post->body = $post_date;
				$post->post_date = $post_date;
				$post->parse_date = mdate('%Y-%m-%d %H:%i',time());
				$post->author_id = $post->get_author_id(trim($post_author));
				$post->insert();
				unset($post);
		
			}
		}
	

		$comments = $html->find('div.divLayout td[width=130]');

		$i = 0;
	
			foreach($comments as $c){ 
			
				if($i > 0){
					
					$next_date = $c->next_sibling()->next_sibling();
					$next_body = $c->parent()->next_sibling()->next_sibling();
					
			
					$comment_title = "";
				
					$c_body = $next_body->find('.txt14');
					$comment_body = trim($c_body[0]->plaintext);
					
					$c_author = $c->find('span[title^=IP:]',0);
					$comment_author = trim($c_author->plaintext);
					
					$c_date_time = $next_date->find('.divLayout',0);
					$comment_date = trim($c_date_time->plaintext);
					
				
					
		
					$cdate = explode(" ",$comment_date);
				
					$ctime = @date("H:i", strtotime($cdate[4]." ".$cdate[5]));
					$comment_date = thYear_decoder($cdate[3])."-". thMonth_decoder($cdate[2],"cut")."-".str_replace(",","",$cdate[1])." ".$ctime;
					
					if($debug)
					{	
						echo "CommentTitle:".$comment_title;
						echo "<br>";
						echo "CommentBody:".$comment_body;
						echo "<br>";
						echo "CommentAuthor:".$comment_author;
						echo "<br>";
						echo "CommentDate:".$comment_date;
						echo "<br>";
						echo "<hr>";
					}
					else
					{
						$post = new Post_model();
						$post->init();
						$post->page_id = $page->id;
						$post->type = "comment";
						$post->title = $comment_title;
						$post->body = trim($comment_body);
						$post->post_date = $comment_date;
						$post->parse_date = mdate('%Y-%m-%d %H:%i',time());
						$post->author_id = $post->get_author_id(trim($author));
						$post->insert();
						unset($post);
					}
				}
				
				$i++;
			}
		$html->clear();
		unset($html);
	}
	

	$url = "http://community.momypedia.com/webboard_topic.aspx?tid=577526";
	
	$options = array( 
	        CURLOPT_RETURNTRANSFER => true,         // return web page 
	        CURLOPT_HEADER         => false,        // don't return headers 
	        CURLOPT_FOLLOWLOCATION => true,         // follow redirects 
	        CURLOPT_ENCODING       => "",           // handle all encodings 
	        CURLOPT_USERAGENT      => "ThothSpider",// who am i 
	        CURLOPT_AUTOREFERER    => true,         // set referer on redirect 
	        CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect 
	        CURLOPT_TIMEOUT        => 120,          // timeout on response 
	        CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects 
	        CURLOPT_POST           => 0,            // i am sending post data 
	        CURLOPT_POSTFIELDS     => $curl_data,   // this are my post vars 
	        CURLOPT_SSL_VERIFYHOST => 0,            // don't verify ssl 
	        CURLOPT_SSL_VERIFYPEER => false,        // 
	        CURLOPT_VERBOSE        => 1 
	    );
	
	$ch = curl_init($url);
	curl_setopt_array($ch,$options);
	$fetch = curl_exec($ch);
	$err = curl_errno($ch);
	$errmsg = curl_error($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	
	parse_momypedia($fetch,true);
?>