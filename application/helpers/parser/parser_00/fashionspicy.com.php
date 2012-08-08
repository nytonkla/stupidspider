<?PHP
	header ('Content-type: text/html; charset=utf-8');
	require("simple_html_dom_helper.php");
	require("date_decoder_th_helper.php");

	function parse_fashionspicy($fetch,$debug = false)
	{
		$html = str_get_html($fetch);
	
		$parsed_posts_count = 0;
		
		if($parsed_posts_count == 0){
			
			$main_content = $html->find('.subject',0);
			$post_title = trim($main_content->plaintext);

			$board_msg = $html->find('.subject',0);
			$board_msg = $board_msg->parent();
			$board_msg = $board_msg->next_sibling();
			$post_body = str_replace('รายละเอียด :                         &nbsp;','',trim($board_msg->plaintext));

			$author = $html->find('.poster span',0);
			$post_author = explode('(',trim($author->plaintext));
			$post_author = $post_author[0];

			$date_time = $html->find('td[id^=itemid-] span',0);
			$date = trim($date_time->plaintext);
			
			$date_time = $date_time->next_sibling();
			$time = trim($date_time->plaintext);
			
			$date = explode('/',$date);
			$d = $date[0];
			$m = $date[1];
			$y = $date[2];
			$t = substr($time,strlen($time)-5,5);	
			$post_date = $y.'-'.$m.'-'.$d.' '.$t;

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
				$post->body = $post_body;
				$post->post_date = $post_date;
				$post->parse_date = mdate('%Y-%m-%d %H:%i',time());
				$post->author_id = $post->get_author_id(trim($post_author));
				$post->insert();
				unset($post);
			}
		}
	
		$comments = $html->find('#webboard-comment div[id^=itemid-]');

			foreach($comments as $c){

					$comment_title = '';
					
					$c_body = $c->find('table tbody tr',0);
					$c_body = $c_body->next_sibling();
					$comment_body = str_replace('รายละเอียด :                         &nbsp;','',trim($c_body->plaintext));

					$c_author = $c->find('.poster span',0);
					$comment_author = explode('(',trim($c_author->plaintext));
					$comment_author = $comment_author[0];

					$c_date_time = $c->find('table tbody tr span',0);
					$date = $c_date_time->next_sibling();
					$time = $date->next_sibling();
					$date = trim($date->plaintext);	
					$time = trim($time->plaintext);	
								
					$date = explode('/',$date);
					$d = $date[0];
					$m = $date[1];
					$y = thYear_decoder($date[2]);
					$t = substr($time,strlen($time)-5,5);
					$comment_date = $y.'-'.$m.'-'.$d.' '.$t;

					if($debug)
					{
						echo "CommentTitle:".$comment_title;
						echo "<br/>";
						echo "CommentBody:".$comment_body;
						echo "<br/>";
						echo "CommentAuthor:".$comment_author;
						echo "<br>";
						echo "CommentDate:".$comment_date;
						echo "<br/>";
						echo "<hr/>";
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
		$html->clear();
		unset($html);
	}
	
	$url = "www.fashionspicy.com/store/webboard/view/%E0%B9%84%E0%B8%A1%E0%B9%88%E0%B9%83%E0%B8%8A%E0%B9%88%E0%B8%81%E0%B8%B2%E0%B8%A3%E0%B8%A5%E0%B8%94%E0%B8%99%E0%B9%89%E0%B8%B3%E0%B8%AB%E0%B8%99%E0%B8%B1%E0%B8%81_%E0%B9%84%E0%B8%A1%E0%B9%88%E0%B9%83%E0%B8%8A%E0%B9%88%E0%B8%81%E0%B8%B2%E0%B8%A3%E0%B8%A5%E0%B8%94%E0%B8%84%E0%B8%A7%E0%B8%B2%E0%B8%A1%E0%B8%AD%E0%B9%89%E0%B8%A7%E0%B8%99_%E0%B9%81%E0%B8%95%E0%B9%88%E0%B9%80%E0%B8%9B%E0%B9%87%E0%B8%99%E0%B8%81%E0%B8%B2%E0%B8%A3%E0%B8%97%E0%B8%B3%E0%B9%83%E0%B8%AB%E0%B9%89%E0%B8%95%E0%B8%B1%E0%B8%A7%E0%B9%80%E0%B8%AD%E0%B8%87%E0%B8%94%E0%B8%B9%E0%B8%94%E0%B8%B5_%E0%B8%9F%E0%B8%B4%E0%B8%95%E0%B9%81%E0%B8%A5-6362258-th.html";
	
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
	
	parse_fashionspicy($fetch,true);
?>