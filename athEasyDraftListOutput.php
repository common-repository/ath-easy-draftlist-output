<?php
/*
Plugin Name: Ath Easy Draftlist Output
Description: Output draft article's title by shortcord
Version: 1.0
Author: Athsear'J.S.
Author URI: https://profiles.wordpress.org/athsear

*/



$athEasyDraftListOutput = new AthEasyDraftListOutput();
$athEasyDraftListOutput->do_set_shortcord();


class AthEasyDraftListOutput {

	private $_limit;
	private $_cat;
	private $_tag;
	private $_class;
	
	public function do_set_shortcord() {
		$this->_set_short_code();
		
	}

	public function create_pre_post_data_list($options) {
		extract(
			shortcode_atts(
				array(
					'cat' => null,
					'limit' => 10,
					'tag' => 'li',
					'class' => null,
				),
				$options
			)
		);

		$this->_class = esc_html($this->_replace_s_q($class));
		$this->_tag = esc_html($this->_replace_s_q($tag));
		$this->_limit = $limit;
		$this->_cat = $cat;

		$pre_post_data = $this->_create_output_style($this->_getpre_post_data());

		return $pre_post_data;
	}

	// set shortcode
	private function _set_short_code() {
		add_shortcode('DraftPostList',array($this	,'create_pre_post_data_list'));
	}

	// get draft data from $wpdb
	private function _getpre_post_data() {
		global $wpdb;
		$cat_list = array();
		if(!empty($this->_cat)) {
			$cat_list = explode(',',$this->_cat);
		}

		$wp_term_in = '';
		$param = array();
		foreach($cat_list as $value) {
			if(!is_numeric($value)) {
				continue;
			}
			if(!empty($wp_term_in)) {
				$wp_term_in .= ',';
			}
			$wp_term_in .= '%d';
			$param[] = $value;
		}

		$sql = "SELECT p.post_content,p.post_title,p.post_modified "
				."FROM ". $wpdb->posts ." AS p ";
		if(!empty($wp_term_in)) {
			$sql .= "INNER JOIN "
					."(SELECT DISTINCT object_id "
					."FROM ". $wpdb->term_relationships ." AS w "
					."LEFT JOIN ". $wpdb->term_taxonomy ." t "
					."ON w.term_taxonomy_id = t.term_taxonomy_id "
					."WHERE t.term_id IN (". $wp_term_in .") AND taxonomy = 'category') "
					."r ON p.ID = r.object_id ";

		}
		$sql .= "WHERE p.post_status = 'draft' ";


		if(is_numeric($this->_limit) === true and $this->_limit > 0) {
			$sql .= 'Limit %d';
			$param[] = $this->_limit;
		}

		$pre_post_data = $wpdb->get_results($wpdb->prepare($sql,$param));
		return $pre_post_data;
	}

	private function _create_output_style($pre_post_data) {
		if($this->_tag === 'ol' or $this->_tag === 'ul') {
			$use_tag = 'li';
			$use_class = '';
			$pre_tag = '<'. $use_tag .'>';
		} else {
			$use_tag = $this->_tag;
			$use_class = $this->_class;
			$pre_tag = '<'. $use_tag . (!empty($use_class) ? ' class='.$use_class : '') .'>';
		}
		$post_tag = '</'. $use_tag .'>';
		$output_data = '';
		// create Output string

		// special:set tag 'dl',output with post_modified on dt tag
		foreach($pre_post_data as $value) {
			if($use_tag === 'dl') {
				$output_data .= '<dt>'. esc_html($value->post_title) . '</dt><dd>' . $value->post_modified . "</dd>\n";
			} else {
				$output_data .= $pre_tag . esc_html($value->post_title) . $post_tag . "\n";
			}
		}

		switch($this->_tag) {
			case 'ul':
			case 'ol':
				$output_data = '<'.$this->_tag .'>¥n'.$output_data .'</'.$this->_tag.'>';
				break;
			case 'dl':
				$output_data = $pre_tag ."\n". $output_data ."\n". $post_tag;
				break;
		}
		return $output_data;
	}

	/**
	* erase mendokusai character....
	*/
	private function _replace_s_q($subject) {
		$search = array(
			'"',
			' ',
			"'"
		);

		return str_replace($search,'',$subject);

	}
}
