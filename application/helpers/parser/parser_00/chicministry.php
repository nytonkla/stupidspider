<?PHP
	header ('Content-type: text/html; charset=utf-8');
	require("simple_html_dom_helper.php");
	require("date_decoder_th_helper.php");

	function parse_chicministry($fetch,$debug)
	{
		$html = str_get_html($fetch);
		
		$parsed_posts_count = 0;
		
		if($parsed_posts_count == 0)
		{
			$main_content = $html->find('.gensmall div[style^=float: left]',0);
			$post_title = trim($main_content->plaintext);
			
			$title = explode(": ",$post_title);
			$post_title = $title[1];

			$board_msg = $html->find('.postbody',0);
			$post_body = strip_tags(trim($board_msg->plaintext));
			
			$author = $html->find('.postauthor',0);
			$post_author = trim($author->plaintext);	
						
			$date = $html->find('.gensmall div[style^=float: right]',0);
			$post_date = trim($date->plaintext);

			$post_date = trim(str_replace(array("Posted:",",","&nbsp;"),"",$post_date));
			$pdate = explode(" ",$post_date);

			$ptime = @date("H:i", strtotime($pdate[4]." ".$pdate[5]));
						
			$post_date = $pdate[3]."-".enMonth_decoder($pdate[1],"cut")."-".$pdate[2]." ".$ptime ;
			
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

		$comments = $html->find('#pagecontent .tablebg .profile table[width=150]');
		
			
		$i =0;
		foreach($comments as $k=>$c)
		{ 	
			
			if($i > 0){
						
				$cb = $c->parent()->parent();
				$ct = $cb->prev_sibling();
			
				$c_title = $ct->find('div',0);
				$comment_title = trim($c_title->plaintext);
				$comment_title = str_replace("&nbsp;Post subject: ","",$comment_title);

				$c_title = $cb->find('.postbody',0);
				$comment_body = trim($cb->plaintext);
				
				$c_author = $ct->find('.postauthor',0);
				$comment_author = trim($c_author->plaintext);
				
				$c_date = $ct->find('div',1);
				$comment_date = trim($c_date->plaintext);
				$comment_date = trim(str_replace(array("Posted:",",","&nbsp;"),"",$comment_date));
				$cdate = explode(" ",$comment_date); 

				$ctime = @date("H:i", strtotime($cdate[4]." ".$cdate[5]));
						
				$comment_date = $cdate[3]."-".enMonth_decoder($cdate[1],"cut")."-".$cdate[2]." ".$ctime ;
				
				if($debug)
				{
					echo "CommentTitle:".$comment_title;
					echo "<br>";
					echo "CommentBody:".$comment_body;
					echo "<br>";
					echo "CommentAuthor:".$comment_author;
					echo "<br>";
					echo "CommentDate:".$comment_date;
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
					$post->author_id = $post->get_author_id(trim($comment_author));
					$post->insert();
					unset($post);
				}
			}
			$i++;
			
		}
		$html->clear();
		unset($html);
	}
	
	$url = "http://www.chicministry.com/webboard/viewtopic.php?f=5&t=2042";
	
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

	parse_chicministry($fetch,true);
?>