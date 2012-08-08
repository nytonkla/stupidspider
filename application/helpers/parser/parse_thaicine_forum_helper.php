<?php
	function parse_thaicine_forum($fetch,$page,$debug=false)
	{
		$log_unit_name = 'parse_thaicine_forum';
		
		$html = str_get_html($fetch);
		
		if($debug)
		{
			$parsed_posts_count = 0;
		}
		else
		{
			$current_posts = $page->get_posts();
			if($current_posts) $parsed_posts_count = count($current_posts);
			else $parsed_posts_count = 0;
		}
		log_message('info',' parsed_posts_count : '.$parsed_posts_count);

		$dead_page = $html->find('div[class=blockrow restore]',0);
		if($dead_page != null) 
		{
			if($debug)
			{
				echo "Page is dead.";
				echo "<br/>";
			}
			else
			{
				// Page is dead
				$page->outdate = 1;
				$page->update();
			}
		}
		else
		{
			if($parsed_posts_count == 0 && $page->sub_comment == 0) 
			{
		
				$main_content = $html->find('h5[id^=subject_] a',0);
				$post_title = trim($main_content->plaintext);

				$board_msg = $html->find('.post div[id^=msg_]',0);
				$post_body = trim(str_replace('&nbsp;','',$board_msg->plaintext));

				$author = $html->find('a[title^=ดูรายละเอียดของ]',0);
				if(empty($author->plaintext)){
					$author = $html->find('.poster h4',0);
					$post_author = trim($author->plaintext);
				}
				else{
					$post_author = trim($author->plaintext);
				}

				$date_time = $html->find('.keyinfo .smalltext',0);
				$post_date = trim($date_time->plaintext);

				$date = explode(" ",str_replace(array("AM","PM"),"",$post_date));
				if(trim($date[3]) == "วันนี้" || trim($date[3]) == "เมื่อวานนี้"){
					$post_date = dateThText($date[3])." ".$date[5];	
				}else{
					$d = str_replace(",","",$date[3]);
					$m = thMonth_decoder($date[4],'full');
					if($m<10) $m = '0'.$m;
					$y = str_replace(",","",$date[5]);
					$post_date = $y."-".$m."-".$d." ".$date[6];
				}
				
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
			else { echo "(sub)"; }

			// Comments at 
			$comments = $html->find('div[class^=windowbg]');
			//echo "CommentCount:".count($comments);
			log_message('info', $log_unit_name.' : found elements : '.count($comments));
			echo '(c='.count($comments).')';
			//echo "<hr>";
			
			$i=0;
			foreach($comments as $k=>$c)
			{
				if($k==0) continue; // skip post entry
				if($i < $parsed_posts_count-1)
				{
					$i++;
					continue;
				}
				
				$comment_title = trim($c->find('h5[id^=subject_]',0)->plaintext);
				
				$c_body = $c->find('.post div[id^=msg_]',0);
				$comment_body = trim(str_replace('&nbsp;','',$c_body->plaintext));
	
				$c_author = $c->find('a[title^=ดูรายละเอียดของ]',0);
				if(empty($c_author->plaintext)){
					$c_author = $c->find('.poster h4',0);
					$comment_author = trim($c_author->plaintext);
				}
				else{
					$comment_author = trim($c_author->plaintext);
				}

				$c_date_time = $c->find('.keyinfo .smalltext',0);
				$comment_date = trim($c_date_time->plaintext);					

				$date = explode(" ",str_replace(array("AM","PM"),"",$comment_date));

				if(trim($date[4]) == "วันนี้" || trim($date[4]) == "เมื่อวานนี้"){
					$comment_date = dateThText($date[4])." ".$date[6];	
				}else{
					$d = $date[4];
					$m = thMonth_decoder($date[5],'full');
					$y = str_replace(",","",$date[6]);
					$comment_date = $y."-".$m."-".$d." ".$date[7];
				}
				
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
				$i++;
			}
		}
		$html->clear();
		unset($html);
	}
?>