<?php

abstract class file_generator {
	protected abstract function generate_xml();
	public abstract function save($dir);
	
}