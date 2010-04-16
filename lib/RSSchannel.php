<?php

class RSSException extends Exception {
}

class ItemRSSchannel {
	private $channel; // link to parent RSSchannel object
	/**
	 * The title of the item.
	 */
	public $title;
	/**
	 * The URL of the item.
	 */
	public $link;
	/**
	 * The item synopsis.
	 */
	public $description;
	/**
	 * Email address of the author of the item.
	 */
	public $author;
	/**
	 * Includes the item in one or more categories (array).
	 */
	private $categories = array();
	/**
	 * URL of a page for comments relating to the item.
	 */
	public $comments;
	/**
	 * Array, describes a media object that is attached to the item.
	 */
	private $enclosure;
	/**
	 * A string that uniquely identifies the item.
	 */
	private $guid;
	/**
	 * Indicates when the item was published.
	 */
	public $pub_date;
	/**
	 * The RSS channel that the item came from (PHP-timestamp
	 * or string in RFC822 - http://asg.web.cmu.edu/rfc/rfc822.html).
	 */
	public $source;

	function guid($url, $isPermaLink = true) {
		$this->guid = array('url'=>$url,'isPermaLink'=>$isPermaLink);
	}

	function add_category($category_name, $domain = null) {
		$this->categories[] = array('name'=>$category_name,'domain'=>$domain);
	}
	function ItemRSSchannel($channel) {
		$this->channel = $channel;
	}
	/**
	 * Describes a media object that is attached to the item.
	 * @param <b>url</b> - says where the enclosure is located
	 * @param <b>length</b> - length says how big it is in bytes
	 * @param <b>type</b> - says what its type is, a standard MIME type (e.g. "audio/mpeg")
	 */
	function enclosure($url, $length, $type) {
		$this->enclosure = array('url'=>$url, 'length'=>$length,'type'=>$type);
	}

	/**
	 * return string with object data in XML format
	 */
	function getXML() {
		$res = '<item>'."\n";

		$res.= $this->get_element('title','title','xmlentities');
		$res.= $this->get_element('link','link','xmlentities');
		$res.= $this->get_element('description','description','xmlentities');
		$res.= $this->get_element('author');

		foreach ($this->categories as $category)
			$res.= '<category'.((empty($category['domain']))?'':' domain="'.$this->channel->xmlentities($category['domain']).'"').'>'.
				   $category['name'].'</category>'."\n";

		$res.= $this->get_element('comments');

		if (!empty($this->enclosure['url']))
			$res.='<enclosure'.
				  ' url="'.$this->channel->xmlentities($this->enclosure['url']).'" '.
				  ' length="'.$this->enclosure['length'].'"'.
				  ' type="'.$this->enclosure['type'].'"'.
				  '/>'."\n";

		if (!empty($this->guid['url']))
			$res.= '<guid isPermaLink="'.(($this->guid['isPermaLink']==true)?'true':'false').'">'.
					$this->channel->xmlentities($this->guid['url']).'</guid>';

		$res.= $this->get_element('pub_date','pubDate','xmldate');
		$res.= $this->get_element('source');

		$res.= '</item>'."\n";

		return $res;
	}
	/**
	 * get element tag, if property not empty
	 */
	private function get_element($property_name, $element_name=null, $prepare_method_name = null) {
		if (empty($element_name)) $element_name = $property_name;
		if (empty($this->$property_name))
			$res = '';
		else {
			if (!empty($prepare_method_name))
				$val = $this->channel->$prepare_method_name($this->$property_name);
			else
				$val = $this->$property_name;

			$res = '<'.$element_name.'>'.$val.'</'.$element_name.'>'."\n";
		}

		return $res;
	}

}
class RSSchannel extends AbstractView {

	public $encoding = 'utf-8';
	/**
	 * The name of the channel. It's how people refer to your service.
	 * If you have an HTML website that contains the same information as your RSS file,
	 * the title of your channel should be the same as the title of your website.
	 */
	public $title = 'untitled channel';
	/**
	 * The publication date for the content in the channel.
	 * For example, the New York Times publishes on a daily basis, the publication date flips once every 24 hours.
	 * That's when the pubDate of the channel changes.
	 *
	 * PHP-timestamp or string in Date and Time Specification of RFC 822,
	 * with the exception that the year may be expressed with two characters or four characters (four preferred).
	 */
	public $pub_date;
	/**
	 * The last time the content of the channel changed.
	 */
	public $last_build_date;

	/**
	 * The URL to the HTML website corresponding to the channel.
	 */
	public $link;
	/**
	 * Phrase or sentence describing the channel.
	 */
	public $description;
	/**
	 * The language the channel is written in. This allows aggregators to group all Italian language sites,
	 * for example, on a single page. A list of allowable values for this element, as provided by Netscape,
	 * is here (http://blogs.law.harvard.edu/tech/stories/storyReader$15).
	 * You may also use values defined by the W3C.
	 */
	public $language = 'en-us';
	/**
	 * Copyright notice for content in the channel.
	 */
	public $copyright;
	/**
	 * Email address for person responsible for editorial content.
	 */
	public $managing_editor;
	/**
	 * Email address for person responsible for technical issues relating to channel.
	 */
	public $web_master;

	/**
	 * Specify one or more categories that the channel belongs to.
	 * Follows the same rules as the <item>-level category element.
	 */
	private $categories = array();

	/**
	 * ttl stands for time to live. It's a number of minutes that indicates how long a channel can be cached
	 * before refreshing from the source.
	 */
	public $ttl;

	/**
	 * Specifies a GIF, JPEG or PNG image that can be displayed with the channel (array).
	 */
	private $image;

	/**
	 * The PICS rating for the channel (http://www.w3.org/PICS/).
	 */
	public $rating;

	/**
	 * A hint for aggregators telling them which hours they can skip.
	 */
	public $skip_hours;
	/**
	 * A hint for aggregators telling them which days they can skip.
	 */
	public $skip_days;

	const DOCS = 'http://blogs.law.harvard.edu/tech/rss';
	const GENERATOR = 'Amodules3 RSS generator v.1.0';

	/**
	 * array of objects ItemRSSchannel
	 */
	private $items = array();

	function init() {
		parent::init();
		$this->last_build_date = time();
	}

	protected function send_headers() {
		header('Last-Modified: '.date('D, d M Y H:i:s O',$this->last_build_date));
		header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
		header('Cache-Control: pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
		header('Content-Type: text/xml; charset='.$this->encoding);
	}

	/**
	 * Specifies a GIF, JPEG or PNG image that can be displayed with the channel.
	 * @param <b>url</b> - is the URL of a GIF, JPEG or PNG image that represents the channel.
	 * @param <b>title</b> - describes the image, it's used in the ALT attribute of the HTML <img> tag when the channel is rendered in HTML.
	 * @param <b>description</b> - contains text that is included in the TITLE attribute of the link formed around the image in the HTML rendering.
	 * @param <b>link</b> - is the URL of the site, when the channel is rendered, the image is a link to the site. (Note, in practice the
	 * image <title> and <link> should have the same value as the channel's <title> and <link>
	 * @param <b>width, height</b> - Optional elements include <width> and <height>, numbers, indicating the width and height of the image in pixels.
	 * Maximum value for width is 144, default value is 88, Maximum value for height is 400, default value is 31.
	 */
	function image($url,$title,$description=null,$link=null,$width=88,$height=31) {
		if (($width) && ($width>144))
			throw new RSSException('Maximum value for image widht is 144!');
		if (($height) && ($height>144))
			throw new RSSException('Maximum value for image widht is 400!');

		$this->image = array(
								'url'=>$url, 'title'=>$title,
								'width'=>$width, 'height'=>$height
							 );

		if (!empty($link))
			$this->image['link'] = $link;
		if (!empty($description))
			$this->image['description'] = $description;
	}

	function newItem($title, $description, $pub_date) {
		$key = count($this->items)-1;
		$this->items[$key] = new ItemRSSchannel($this);
		$this->items[$key]->title = $title;
		$this->items[$key]->description = $description;
		$this->items[$key]->pub_date = $pub_date;

		return $this->items[$key];
	}

	/**
	 * escaping strings
	 */
	function xmlentities($string) {
		return htmlentities($string, ENT_NOQUOTES, $this->encoding);
	}
	/**
	 * return date in RFC822 format
	 */
	function xmldate($date_var) {
		if (is_numeric($date_var))
			return date('D, d M Y H:i:s O',$date_var);
		else
			return $date_var;
	}

	/**
	 * get element tag, if property not empty
	 */
	private function get_element($property_name, $element_name=null, $prepare_method_name = null) {
		if (empty($element_name)) $element_name = $property_name;
		if (empty($this->$property_name))
			$res = '';
		else {
			if (!empty($prepare_method_name))
				$val = $this->$prepare_method_name($this->$property_name);
			else
				$val = $this->$property_name;

			$res = '<'.$element_name.'>'.$val.'</'.$element_name.'>'."\n";
		}

		return $res;
	}
	function add_category($category_name, $domain = null) {
		$this->categories[] = array('name'=>$category_name,'domain'=>$domain);
	}
	/**
	 * return sting with XML in RSS 2.0 format
	 */
	function getXMl() {
		$res = '<?xml version="1.0" encoding="'.$this->encoding.'"?>'."\n";
		$res.= '<rss version="2.0">'."\n";
		$res.= '	<channel>'."\n";
		$res.= '		<title>'.$this->xmlentities($this->title).'</title>'."\n";

		$res .= $this->get_element('link','link','xmlentities');
		$res .= $this->get_element('description','description','xmlentities');

		$res.= '		<language>'.$this->language.'</language>'."\n";

		$res .= $this->get_element('copyright','copyright','xmlentities');

		$res .= $this->get_element('managing_editor','managingEditor');
		$res .= $this->get_element('web_master','webMaster');

		$res .= $this->get_element('pub_date','pubDate','xmldate');
		if (empty($this->last_build_date)) $this->last_build_date = time();

		$res.= '		<lastBuildDate>'.$this->xmldate($this->last_build_date).'</lastBuildDate>'."\n";

		foreach ($this->categories as $category)
			$res.= '<category'.((empty($category['domain']))?'':' domain="'.$this->xmlentities($category['domain']).'"').'>'.
				   $category['name'].'</category>'."\n";

		$res.= '		<generator>'.self::GENERATOR.'</generator>'."\n";
		$res.= '		<docs>'.self::DOCS.'</docs>'."\n";

		$res .= $this->get_element('ttl');

		if (!empty($this->image['url']))
			$res.='<image>'."\n".
				  '<title>'.$this->xmlentities($this->image['title']).'</title>'."\n".
				  '<url>'.$this->xmlentities($this->image['url']).'</url>'."\n".
				  ((empty($this->image['description']))?'':'<description>'.$this->xmlentities($this->image['description']).'</description>'."\n").
				  ((empty($this->image['link']))?'':'<link>'.$this->xmlentities($this->image['link']).'</link>'."\n").
				  '<width>'.$this->image['width'].'</width>'."\n".
				  '<height>'.$this->image['height'].'</height>'."\n".
				  '</image>'."\n";

		$res .= $this->get_element('rating');
		$res .= $this->get_element('skip_hours','skipHours');
		$res .= $this->get_element('skip_days','skipDays');


		foreach ($this->items as $item)
			$res.= $item->getXML();

		$res.= '	</channel>'."\n";
		$res.= '</rss>'."\n";

		return $res;
	}

	function echoXML() {
		$this->send_headers();
		echo $this->getXML();
	}

	function render(){
		$this->output('<pre>'.htmlentities($this->getXML()).'</pre>');
	}
}
