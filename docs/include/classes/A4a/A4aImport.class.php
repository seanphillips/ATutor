<?php
/********************************************************************/
/* ATutor															*/
/********************************************************************/
/* Copyright (c) 2002-2008 by Greg Gay, Cindy Qi Li, & Harris Wong	*/
/* Adaptive Technology Resource Centre / University of Toronto		*/
/* http://atutor.ca													*/
/*																	*/
/* This program is free software. You can redistribute it and/or	*/
/* modify it under the terms of the GNU General Public License		*/
/* as published by the Free Software Foundation.					*/
/********************************************************************/
// $Id$

require_once(AT_INCLUDE_PATH.'classes/A4a/A4a.class.php');

/**
 * Accessforall Import  class.
 * Based on the specification at: 
 *		http://www.imsglobal.org/accessibility/index.html
 *
 * @date	Oct 9th, 2008
 * @author	Harris Wong
 */
class A4aImport extends A4a {
	//Constructor
	function A4aImport($cid){
		parent::A4a($cid);		//call its parent
	}

	/** 
	 * Import AccessForAll
	 * @param	array	XML items generated by the IMS import
	 */
	function importA4a($items){
		//use the items array data and insert it into the database.
		foreach ($items as $file_path => $a4a_resources){
			foreach ($a4a_resources as $resource){
				//If it has adaptation/alternative, this is a primary file.
				if (isset($resource['hasAdaptation']) && !empty($resource['hasAdaptation'])){
					//we only have one language in the table, [1], [2], etc will be the same
					$pri_lang = $resource['language'][0];	

					//insert primary resource
					$primary_id = $this->setPrimaryResource($this->cid, str_replace($this->relative_path, '', $file_path), $pri_lang);

					//get primary resource type
					$resources_attrs = $resource['access_stmt_originalAccessMode'];
					$attrs = $this->toResourceTypeId($resources_attrs);
					
					//insert primary resource type associations
					foreach ($attrs as $resource_type_id){
						$this->setPrimaryResourceType($primary_id, $resource_type_id);
					}

					//insert secondary resource
					$secondary_resources = $resource['hasAdaptation'];	//uri array

					foreach ($secondary_resources as $secondary_resource){
						$secondary_files = $items[$this->relative_path.$secondary_resource];
						//check if this secondary file is the adaptation of 
						// this primary file 

						foreach($secondary_files as $secondary_file){
							//isAdaptation is 1-to-1 mapping, save to use [0]
							if(($this->relative_path.$secondary_file['isAdaptationOf'][0]) == $file_path){
								$secondary_lang = $secondary_file['language'][0];

								$secondary_attr = $this->toResourceTypeId($secondary_file['access_stmt_originalAccessMode']);
								$secondary_id = $this->setSecondaryResource($primary_id, $secondary_resource, $secondary_lang);

								//insert secondary resource type associations
								foreach ($secondary_attr as $secondary_resource_type_id){
									$this->setSecondaryResourceType($secondary_id, $secondary_resource_type_id);
								}
								break;	//1-to-1 mapping, no need to go further
							}
						}
					} //foreach of secondary_resources
				}				
			} //foreach of resources
		} //foreach of item array
	}

	/**
	 * By the given attrs array, decide which resource type it is
	 *	auditory = type 1
	 *	textual	 = type 3
	 *	visual	 = type 4
	 * @param	array
	 * return type id array
	 */
	 function toResourceTypeId($resources_attrs){
		 $result = array();
		 if (in_array('auditory', $resources_attrs)){
			 $result[] = 1;
		 }
		 if (in_array('textual', $resources_attrs)){
			 $result[] = 3;
		 }
		 if (in_array('visual', $resources_attrs)){
			 $result[] = 4;
		 }
		 return $result;
	 }
}

?>