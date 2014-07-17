<?php

function init_svg($page_id) {
	$svg = new SimpleXMLElement("<svg></svg>");
	$svg->addAttribute("width", "800");
	$svg->addAttribute("height", "600");
	$svg->addAttribute("xml:id", $page_id);
}

