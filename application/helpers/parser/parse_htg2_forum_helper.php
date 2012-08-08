<?php
	function parse_htg2_forum($fetch,$page,$debug=false)
	{
		$log_unit_name = 'parse_htg2_forum';

		
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
				$main_content = $html->find('td[valign=middle] b');
				$post_title = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$main_content[0]->plaintext));

				$board_msg = $html->find('.post');
				$post_body = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$board_msg[0]->plaintext));

				$author = $html->find('.windowbg a');
				$post_author = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$author[0]->plaintext));

				$date_time = $html->find('.smalltext',7);
				$post_date = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$date_time->plaintext));

				$post_date = str_replace("  "," ",$post_date);
				$post_date = str_replace(",","",$post_date);
				$pdate = explode(" ",$post_date);
				//echo $pdate[4]."-".$pdate[3]."-".$pdate[2]."<br>";

				$p_view = $html->find('.titlebg td',1);
				$page_view = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$p_view->plaintext));

				$page_view = explode("(",$page_view);
				$view = explode (")",$page_view[1]);
				$page_view = $view[0];

				//$yy = thYear_decoder($pdate[2]);
				$mm = thMonth_decoder($pdate[3],'full');

				$post_date = $pdate[4]."-".$mm."-".$pdate[2]." ".$pdate[5];
				
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
			$comments = $html->find('td[class^=windowbg]');
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

				$c_title = $c->find('td[valign=middle] b',0);
				$comment_title = trim(iconv("tis-620","utf-8",$c_title->plaintext));
		
				$c_body = $c->find('.post',0);
				if($c_body == null) $c_body = $c->find('td.body',1);
				$comment_body = trim(iconv("tis-620","utf-8",$c_body->plaintext));
		
				$c_author = $c->find('a',0);
				$comment_author = trim(iconv("tis-620","utf-8",$c_author->plaintext));
				
				$c_date = $c->find('.smalltext',2);
				$comment_date = trim(iconv("tis-620","utf-8",$c_date->plaintext));
				
				//echo $comment_date."<br>";
				$cdate = str_replace("  "," ",$comment_date);
				$comment_date = str_replace(",","",$comment_date);
				$cdate = explode(" ",$comment_date);
				$mm = thMonth_decoder($cdate[5],'full');
				//echo $cdate[6]."-".$mm."-".$cdate[4]."<br>";
				
				$comment_date = $cdate[6]."-".$mm."-".$cdate[4]." ".$cdate[7];


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