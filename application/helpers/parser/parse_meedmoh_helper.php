<?php
	function parse_meedmoh($fetch,$page,$debug=false)
	{
		$log_unit_name = 'parse_meedmoh';

		
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
				// Post Title at div.maincontent, 1st h1 element
				$main_content = $html->find('title');
				$post_title = trim($main_content[0]->plaintext);
				//$main_content = $html->find('div[class=maincontent] h1');
				//$post_title = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$main_content[0]->plaintext));

				// Post Body at div.boardmsg
				$board_msg = $html->find('div[class=post]');
				$post_body = trim($board_msg[0]->plaintext);
				//$board_msg = $html->find('div[class=boardmsg]');
				//$post_body = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$board_msg[0]->plaintext));

				// Post Meta at ul#ownerdetail
				$author = $html->find('div[class=poster] h4 a');
				$post_author = trim($author[0]->plaintext);
				//$author = $html->find('ul[id=ownerdetail] li b');
				//$post_author = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$author[0]->plaintext));

				$date_time = $html->find('div[class=smalltext]',0);
				$post_date = trim($date_time->plaintext);

				// View Count
				$page_info = $html->find('div[class=maincontent] p span strong');
				$page_view = trim($page_info[1]->plaintext);


				//« เมื่อ: พฤษภาคม 17, 2010, 11:07:28 AM »
				//2010,-17,-พฤษภาคม AM

				$str = explode(" ",$post_date);
				$yy = $str[5];
				$mm = thMonth_decoder($str[3],'full');
				$dd = $str[4];
				$tt = $str[6];
				$ampm = $str[7];
				
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
			$comments = $html->find('hr[class=post_separator] div[class^=windowbg]');
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
				$c_title = $c->find('div[class=smalltext]',0);
				$comment_title0 = trim($c_title->plaintext);
				$str= explode(" ",$comment_title0);
				$comment_title = $str[2];  
				$yy = $str[6];
				$mm = thMonth_decoder($str[4],'full');
				$dd = $str[5];
				$tt = $str[7];
				$ampm = $str[8];


				//2010,-19,-พฤษภาคม PM

				//$c_title = $c->find('div[class=listCommentHead]',0);
				//$comment_title = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$c_title->plaintext));

				//Comment Body as div.commentBox div.boardmsg
				$c_body = $c->find('div[class=post] div[class=inner]',0);
				$comment_body = trim($c_body->plaintext);

				//Comment Author as ui#ownerdetail li b
				$c_author = $c->find('div[class=poster] h4',0);
				$comment_author = trim($c_author->plaintext);

				//Comment Date ul#ownerdetail li
				$c_date_time = $c->find('div[class=postbody] p[class=author]',0);
				$comment_date = trim($c_date_time->plaintext);
				
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