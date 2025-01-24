<?php
function numeral($number, $hideIfOne = false) {
	if($hideIfOne === true and $number == 1) {
		return '';
	}
	
	$test = abs($number) % 10;
	$ext = ((abs($number) % 100 < 21 and abs($number) % 100 > 4) ? 'th' : (($test < 4) ? ($test < 3) ? ($test < 2) ? ($test < 1) ? 'th' : 'st' : 'nd' : 'rd' : 'th'));
	return $number . $ext;
}

function count_words($str) {
	return count(preg_split('/\s+/', strip_tags($str), null, PREG_SPLIT_NO_EMPTY));
}

function admin() {
	return user_authed() && user_authed_role() == 'administrator';
}

function get_description($html) {
	libxml_use_internal_errors(true); 
	$dom = new DOMDocument();
	@$dom->loadHTML('<!DOCTYPE html><meta charset="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
	libxml_clear_errors();
	$paragraphs = $dom->getElementsByTagName('p');

	if ($paragraphs->length > 0) {
		$first_paragraph = $paragraphs->item(0);
		$spans = $first_paragraph->getElementsByTagName('span');

		// Don't render footnotes on the main page 
		// (reverse order because otherwise the list changes)
		for ($i = $spans->length - 1; $i >= 0; $i--) {
			$span = $spans->item($i);
			if ($span->hasAttribute('class') && strpos($span->getAttribute('class'), 'sidenote-wrapper') !== false) {
				$span->parentNode->removeChild($span);
			}
		}
		return $dom->saveHTML($first_paragraph);
	} else {
		// Get the first paragraph
		$paragraphs = explode("\n", $html);
		$paragraphs = array_slice($paragraphs, 0, 1);
		return join("\n", $paragraphs);
	}
}

function split_content($content){
	$paragraphs = explode("\n", $content);
	$paragraphs = array_slice($paragraphs, 0, 1);
	return join("\n", $paragraphs);
}

function pluralise($amount, $str, $alt = '') {
	return intval($amount) === 1 ? $str : $str . ($alt !== '' ? $alt : 's');
}

function relative_time($date) {
	if(is_numeric($date)) $date = '@' . $date;

	$user_timezone = new DateTimeZone(Config::app('timezone'));
	$date = new DateTime($date, $user_timezone);

	// get current date in user timezone
	$now = new DateTime('now', $user_timezone);

	$elapsed = $now->format('U') - $date->format('U');

	if($elapsed <= 1) {
		return 'Just now';
	}

	$times = array(
		31104000 => 'year',
		2592000 => 'month',
		604800 => 'week',
		86400 => 'day',
		3600 => 'hour',
		60 => 'minute',
		1 => 'second'
	);

	foreach($times as $seconds => $title) {
		$rounded = $elapsed / $seconds;

		if($rounded > 1) {
			$rounded = round($rounded);
			return $rounded . ' ' . pluralise($rounded, $title) . ' ago';
		}
	}
}

function renderArticleLink($page, $item) {
	$itemDate = date('F j, Y', strtotime($item->created));
	$suffix = "";
	if ($item->status != 'published') {
		$suffix = " <span class='glyphicon' style='font-size:0.7em;'>&#xe033;</span>";
	}
	echo "<a class='articleLink' href='" . base_url($page->slug . '/' . $item->slug) . "' title='" . $item->title . "'><span class='name'>" . $item->title . "$suffix</span><span class='date'>{$itemDate}</span></a>";
}

function total_articles() {
	return Post::where(Base::table('posts.status'), '=', 'published')->count();
}