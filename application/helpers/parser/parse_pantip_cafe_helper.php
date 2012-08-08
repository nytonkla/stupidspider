<?php
	function parse_pantip_cafe($fetch,$page,$debug=false)
	{
		$log_unit_name = 'parse_pantip_cafe';
		
		$html = str_get_html($fetch);
		
		$parsed_posts_count = 0;
		$current_posts = $page->get_posts();
		if($current_posts) $parsed_posts_count = count($current_posts);
		log_message('info',' parsed_posts_count : '.$parsed_posts_count);
		
		
		$elements = $html->find('table[height=36]');
		$count=count($elements);
		log_message('info',' parse_pantip_cafe : found elements : '.$count);
		if($debug) echo ' parse_pantip_cafe : found elements : '.$count.'<br />';
		echo '(c='.$count.')';
		$i=0;
		foreach($elements as $e)
		{
			if($debug) $parsed_posts_count = 0;
			if($i < $parsed_posts_count) 
			{ 
				//log_message('info','skip : '.$i);
				$i++;
				continue; // skip post order if less than parsed post
			}
			
			// Check if it is a quote block skip
			$quote = str_get_html($e->outertext);
			$res = $quote->find('img[hspace=3]');
			$quote->clear();
			unset($quote);
			if(count($res) == 0) continue;
			
			$post = new Post_model();
			$post->init();
			
			$post->page_id = $page->id;
			if($i==0) $post->type = "post";
			else $post->type = "comment";
			
			if($debug) echo $post->type.'<br/>';
			
			$post->title = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$e->plaintext));
			if($debug) echo 'title:'.$post->title.'<br/>';
			
			$body = $e->next_sibling()->plaintext;
			$body1 = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$body));
			$body2 = null;

			// Search for form
			$form = str_get_html($e->next_sibling()->outertext);
			$res = $form->find('form');
			if(count($res) > 0) // if form found, read post body 2 and adjust meta location
			{
				$meta = $e->next_sibling()->next_sibling()->next_sibling()->next_sibling();
				$body = $e->next_sibling()->next_sibling()->next_sibling()->plaintext;
				$body2 = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$body));
			}
			else
			$meta = $e->next_sibling()->next_sibling();
			
			$form->clear();
			unset($form);
			
			$body = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$e->next_sibling()->plaintext));
			$post->body = $body1.$body2;
			if($debug) echo 'body1:'.$body1.'<br/>';
			if($debug) echo 'body2:'.$body2.'<br/>';
			
			// if post 
			if($i==0)
			{
				// check if meta 2
				$needle = "จากคุณ";
				$pos = false;
				while(!$pos)
				{
					if($meta == null)
					{
						$meta = $e->parent(); // RESET! end of tree.
					}
					else
					{
						$haystack = trim(iconv("tis-620","utf-8//TRANSLIT//IGNORE",$meta->plaintext));
						$pos = strstr($haystack,$needle);
						//echo "found:";
						if(!$pos)
						{
							if($meta->next_sibling() == null) $meta = $meta->parent(); // NEXT PARENT NODE. this tree node don't have it.
							else $meta = $meta->next_sibling();
						}
					}
				}
				$meta = $meta->first_child()->first_child();
				$author_name = str_replace(" ","",iconv("tis-620","utf-8//TRANSLIT//IGNORE",$meta->first_child()->next_sibling()->plaintext));
				$post_date = iconv("tis-620","utf-8//TRANSLIT//IGNORE",$meta->next_sibling()->first_child()->next_sibling()->plaintext);
				
			}
			else // else comment
			{
				//Find Meta with table cellspacing=1 from e->parent();
				$parent = $e->parent();
				$meta_element_pos = count($parent->find('table[cellspacing=1] tr td'));
				if($meta_element_pos == 0) continue;
				$author = $parent->find('table[cellspacing=1] tr td',$meta_element_pos-5);
				$author_name = str_replace(" ","",iconv("tis-620","utf-8//TRANSLIT//IGNORE",$author->plaintext));
				$date = $parent->find('table[cellspacing=1] tr td',$meta_element_pos-3);
				$post_date = iconv("tis-620","utf-8//TRANSLIT//IGNORE",$date->plaintext);
			}

			if($debug) echo 'author:'.$author_name.'<br />';
			$author_name = substr($author_name,1);
			$post->author_id = $post->get_author_id(trim($author_name));
			$post_date = substr($post_date,1);
			$post_date = trim($post_date);
			
			// if วันสิ้นปี, วันปีใหม่, replace back to normal
			$special_date = array('วันสิ้นปี' => '31 ธ.ค.','วันปีใหม่' => '1 ม.ค.', 'วันนักประดิษฐ์' => '2 ก.พ.', 'วันวาเลนไทน์' => '14 ก.พ.','วันเข้าพรรษา'=>'27 ก.ค.','วันแม่แห่งชาติ'=>'12 ส.ค.','วันเกิด PANTIP.COM'=>'7 ต.ค.','วันปิยมหาราช'=>'23 ต.ค.','วันลอยกระทง'=>'10 พ.ย.','วันพ่อแห่งชาติ'=>'5 ธ.ค.','วันจักรี'=>'6 เม.ย.','วันฉัตรมงคล'=>'5 พ.ค.','วันมาฆบูชา'=>'7 มี.ค.','วันสตรีสากล'=>'8 มี.ค.','วันรัฐธรรมนูญ'=>'10 ธ.ค.','วันมหาสงกรานต์'=>'13 เม.ย.','วันเนา'=>'14 เม.ย.','วันเถลิงศก'=>'15 เม.ย.','วันแรงงาน'=>'1 พ.ค.','วันวิสาขบูชา'=>'4 มิ.ย.','วันอาสาฬหบูชา'=>'15 ก.ค.','วันออกพรรษา'=>'12 ต.ค.','วันคริสต์มาส'=>'25 พ.ย.','วันครูแห่งชาติ'=>'16 ม.ค.','วันรพี'=>'7 ส.ค.','วันภาษาไทยแห่งชาติ'=>'29 ก.ค.','วันสุนทรภู่'=>'26 มิ.ย.','วันอนามัยโลก'=>'7 เม.ย.','วันพืชมงคล'=>'9 พ.ค.','วันคุ้มครองโลก'=>'22 เม.ย.','วันต่อต้านยาเสพติดโลก'=>'26 มิ.ย.','วันสิ่งแวดล้อมโลก'=>'5 มิ.ย.');
			mb_internal_encoding("UTF-8");
			foreach($special_date as $k=>$v)
			{
				$post_date = preg_replace('/'.$k.'/',$v,$post_date);
			}
			// end if
			
			
			$date = explode(" ",$post_date);
			
			$yy = thYear_decoder($date[2]);
			$mm = thMonth_decoder($date[1]);
			$dd = $date[0];
			$tt = $date[3];
			
			$post_date = $yy."-".$mm."-".$dd." ".$tt;
			if($debug) echo 'date'.$post_date.'<br />';
			
			$post->post_date = $post_date;
			$post->parse_date = mdate('%Y-%m-%d %H:%i',time());
			
			if($debug)
			{
				echo "<hr>";
			}
			else
			{
				$post->insert();
			} 
			unset($post);
			$i++;
		}
		
		$html->clear();
		unset($html);
	}
?>