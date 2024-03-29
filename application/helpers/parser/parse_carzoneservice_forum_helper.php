<?php
	function parse_carzoneservice_forum($fetch,$page,$debug=false)
	{
		$log_unit_name = 'parse_carzoneservice_forum';

		
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
		
				$main_content = $html->find('.h_postPage .top',0);
				$post_title = trim($main_content->plaintext);
	
				$board_msg = $html->find('.post .txt',0);
				$post_body = trim($board_msg->plaintext);
	
				$author = $html->find('.post_det .author',0);
				$post_author = trim(str_replace('โพสโดย : ','',$author->plaintext));
	
				$date_time = $html->find('.post_det .date',0);
				$post_date = trim($date_time->plaintext);
			
				$date = explode(' ',$post_date);
				$d = $date[0];
				$m = thMonth_decoder($date[1],'cut');
				$y = thYear_decoder($date[2]);
				$t = $date[5];
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
			else { echo "(sub)"; }

			// Comments at 
			$comments = $html->find('.reply');
			//echo "CommentCount:".count($comments);
			log_message('info', $log_unit_name.' : found elements : '.count($comments));
			echo '(c='.count($comments).')';
			//echo "<hr>";
			
			$i=0;
			foreach($comments as $k=>$c)
			{
				//if($k==0) continue; // skip post entry
				if($i < $parsed_posts_count-1)
				{
					$i++;
					continue;
				}
				
				$comment_title = '';
				
				$c_body = $c->find('.txt',0);
				$comment_body = trim($c_body->plaintext);
	
				$c_author = $c->find('.reply_det .author',0);
				$comment_author = trim(str_replace('โพสโดย : ','',$c_author->plaintext));
				
				$c_date_time = $c->find('.reply_det .date',0);
				$comment_date = trim($c_date_time->plaintext);					
	
				$date = explode(' ',$comment_date);
				$d = $date[0];
				$m = thMonth_decoder($date[1],'cut');
				$y = thYear_decoder($date[2]);
				$t = $date[5];
				$comment_date = $y.'-'.$m.'-'.$d.' '.$t;
				
				
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