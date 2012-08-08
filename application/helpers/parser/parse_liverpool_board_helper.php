<?php
	function parse_liverpool_board($fetch,$page,$debug=false)
	{
		$log_unit_name = 'parse_liverpool';

		
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
			if($parsed_posts_count == 0 && $page->sub_comment == 0) // No early post and not a sub comment page
			{
				$main_content = $html->find('div[id^=subject_] a',0);
				$post_title = trim($main_content->plaintext);
	
				$board_msg = $html->find('.post',0);
				$post_body = trim($board_msg->plaintext);
	
				$author = $html->find('a[title^=???????????????]',0);
				$post_author = trim($author->plaintext);
	
				$date_time = $html->find('div[id^=subject_]',0);
				$date_time = $date_time->next_sibling();
				$post_date = trim($date_time->plaintext);
			
				$date = explode(' ',$post_date);
				$d = str_replace(",","",$date[4]);
				$m = thMonth_decoder($date[3],'full');
				$y = str_replace(",","",$date[5]);
				$t = date("H:i:s", strtotime($date[6]." ".str_replace("�","",$date[7])));
		
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
			$comments = $html->find('.bordercolor tbody tr td[style=padding: 1px 1px 0 1px;]');
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
					$c_title = $c->find('div[id^=subject_] a',0);
					$comment_title = trim($c_title->plaintext);
					
					$c_body = $c->find('.post',0);
					$comment_body = trim($c_body->plaintext);
		
					$c_author = $c->find('a[title^=???????????????]',0);
					$comment_author = trim($c_author->plaintext);
					
					$c_date_time = $c->find('td[width=85%] .smalltext',0);
					$comment_date = trim($c_date_time->plaintext);					
					
					$date = explode(' ',$comment_date);
					$d = str_replace(",","",$date[5]);
					$m = thMonth_decoder(str_replace("[","",$date[4]),'full');
					$y = str_replace(",","",$date[6]);
					$t = date("H:i:s", strtotime($date[7]." ".str_replace("]","",$date[8])));
		
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