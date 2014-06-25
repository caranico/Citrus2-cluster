<?php

namespace Citrus\Cluster;


class Message {
	private static $lstMsg = array();

	static function addInfo($titre, $content, $link = false)
	{
		self::$lstMsg[] = array(
			'date' => date("d/m/Y à H:i:s"),
			'type' => 'highlight',
			'title' => $titre,
			'content' => $content,
			'link' => $link
		);

	}

	static function addError($titre, $content, $link = false)
	{
		self::$lstMsg[] = array(
			'date' => date("d/m/Y à H:i:s"),
			'type' => 'error',
			'title' => $titre,
			'content' => $content,
			'link' => $link
		);
	}

	static function getAll()
	{
		$lst = self::$lstMsg;
		self::$lstMsg = array();
		return $lst;
	}

}