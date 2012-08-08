﻿<?php
	function parse_whatphonenokiagang_forum($fetch,$page,$debug=false)
	{
		$log_unit_name = 'parse_whatphonenokiagang_forum';

		
		$html = str_get_html($fetch);
		
		$memcache = new Memcache;
		$memcache->connect('localhost', 11211) or die ("Could not connect");
		
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

		$dead_page = $html->find('div.alert_info',0);
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
				$main_content = $html->find('div[id=page-body] h2');
				$post_title = trim($main_content[0]->plaintext);

				$board_msg = $html->find('div[class=content]');
				$post_body = trim($board_msg[0]->plaintext);

				$author = $html->find('p[class=author] strong');
				$post_author = trim($author[0]->plaintext);

				$date_time = $html->find('div[class=postbody] p[class=author]',0);
				$post_date = trim($date_time->plaintext);
				$post_date = str_replace("  "," ",$post_date);
				$post_date = str_replace(",","",$post_date);
				$post_date = explode("&raquo;",$post_date);
				$post_date = preg_split("/[\s]+/",trim($post_date[1]));

				if(preg_match("/^[a-zA-Z]/",$post_date[0])){
					if(trim($post_date[0]) == "Yesterday" || trim($post_date[0]) == "Today"){
						$post_date = dateThText($post_date[0])." ".$post_date[4];	
					}else{ 
						$dd = $post_date[1];
						if(strlen($post_date[2]) == 3)	
						$mm = enMonth_decoder($post_date[2],"cut");
						else
						$mm = enMonth_decoder($post_date[2],"full");
						$yy = $post_date[3];
						$tt = $post_date[4];
						$post_date = $yy."-".$mm."-".$dd." ".$tt;
					}
				}else{   
					if(trim($post_date[0]) == "เมื่อวานนี้" || trim($post_date[0]) == "วันนี้"){
						$post_date = dateThText($post_date[0])." ".$post_date[4];	
					}else{  
						$dd = $post_date[2];	
						if(strlen($post_date[1]) == 3)	
						$mm = enMonth_decoder($post_date[1],"cut");
						else
						$mm = enMonth_decoder($post_date[1],"full");
						$yy = $post_date[3];
						$tt = $post_date[4];
						$post_date = $yy."-".$mm."-".$dd." ".$tt;	
					}
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
			$comments = $html->find('hr[class=divider] div[class=inner]');
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
				$c_title = $c->find('div[class=postbody] h3',0);
				$comment_title = trim($c_title->plaintext);

				$c_body = $c->find('div[class=content]',0);
				$comment_body = trim($c_body->plaintext);

				$c_author = $c->find('p[class=author] strong',0);
				$comment_author = trim($c_author->plaintext);

				$c_date_time = $c->find('div[class=postbody] p[class=author]',0);
				$comment_date = trim($c_date_time->plaintext);
				$comment_date = str_replace(",","",$comment_date);
				$comment_date = explode("&raquo;",$comment_date);
				$comment_date = str_replace("  "," ",$comment_date);
				$comment_date = preg_split("/[\s]+/",trim($comment_date[1]));

				if(preg_match("/^[a-zA-Z]/",$comment_date[0])){
					if(trim($comment_date[0]) == "Yesterday" || trim($comment_date[0]) == "Today"){
						$comment_date = dateThText($comment_date[0])." ".$comment_date[4];	
					}else{ 
						$dd = $comment_date[1];	
						if(strlen($comment_date[2]) == 3)	
						$mm = enMonth_decoder($comment_date[2],"cut");
						else
						$mm = enMonth_decoder($comment_date[2],"full");
						$yy = $comment_date[3];
						$tt = $comment_date[4];
						$comment_date = $yy."-".$mm."-".$dd." ".$tt;
					}
				}else{   
					if(trim($comment_date[0]) == "เมื่อวานนี้" || trim($comment_date[0]) == "วันนี้"){
						$comment_date = dateThText($comment_date[0])." ".$comment_date[4];	
					}else{  
						$dd = $comment_date[2];	
						$mm = ehMonth_decoder($comment_date[1],"cut");
						$yy = $comment_date[3];
						$tt = $comment_date[4];
						$comment_date = $yy."-".$mm."-".$dd." ".$tt;	
					}
				}
				
				if(!empty($comment_author)){
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
					//$post->insert();
		
                                        // add obj to memcache
                                        $key = rand(1000,9999).'-'.microtime(true);
                                        $memcache->add($key, $post, false, 12*60*60) or die ("Failed to save OBJECT at the server");
                                        echo '.';
                                        unset($post);
				}
			//$i++;
		}
			}
		}
		
		$memcache->close();
		
		$html->clear();
		unset($html);
	}
?>