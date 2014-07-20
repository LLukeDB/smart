<?php

function simplexml_insert_after(SimpleXMLElement $insert, SimpleXMLElement $target)
{
	$target_dom = dom_import_simplexml($target);
	$insert_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml($insert), true);
	if ($target_dom->nextSibling) {
		return $target_dom->parentNode->insertBefore($insert_dom, $target_dom->nextSibling);
	} else {
		return $target_dom->parentNode->appendChild($insert_dom);
	}
}

function simplexml_append_child(SimpleXMLElement $child, SimpleXMLElement $parent)
{
	$target_dom = dom_import_simplexml($parent);
	$child_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml($child), true);
	return $target_dom->appendChild($child_dom);
}
