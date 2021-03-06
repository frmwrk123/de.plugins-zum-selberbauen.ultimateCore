<?php
/**
 * Contains the ACPFirstTimeListener class.
 * 
 * LICENSE:
 * This file is part of the Ultimate Core.
 *
 * The Ultimate Core is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * The Ultimate Core is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with the Ultimate Core.  If not, see {@link http://www.gnu.org/licenses/}}.
 * 
 * @author		Jim Martens
 * @copyright	2011-2014 Jim Martens
 * @license		http://www.gnu.org/licenses/lgpl-3.0 GNU Lesser General Public License, version 3
 * @package		de.plugins-zum-selberbauen.ultimateCore
 * @subpackage	system.event.listener
 * @category	Ultimate Core
 */
namespace wcf\system\event\listener;
use wcf\data\application\ApplicationEditor;
use wcf\data\category\CategoryAction;
use wcf\system\cache\builder\ApplicationCacheBuilder;
use wcf\system\cache\builder\ObjectTypeCacheBuilder;
use wcf\system\category\CategoryHandler;
use wcf\system\event\IEventListener;
use wcf\system\io\File;

/**
 * Executes some functions on the first start of the ACP after installation.
 * 
 * @author		Jim Martens
 * @copyright	2011-2014 Jim Martens
 * @license		http://www.gnu.org/licenses/lgpl-3.0 GNU Lesser General Public License, version 3
 * @package		de.plugins-zum-selberbauen.ultimateCore
 * @subpackage	system.event.listener
 * @category	Ultimate Core
 */
class ACPFirstTimeListener implements IParameterizedEventListener {
	/**
	 * The name of the config file.
	 * @var	string
	 */
	const CONFIG_FILE = 'config.inc.php';
	
	/**
	 * The category id of the default link category.
	 * @var integer
	 */
	protected $categoryID = 0;
	
	/**
	 * Executes this listener.
	 * 
	 * @param	object	$eventObj
	 * @param	string	$className
	 * @param	string	$eventName
	 * @param   array   $parameters
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		// if we are in WCF ACP, don't execute the event listener
		if (PACKAGE_ID == 1) {
			return;
		}
		
		// if Ultimate CMS is not installed, don't execute event listener
		if (!defined('ULTIMATE_DIR')) {
			return;
		}
		
		// adds default link category
		require(ULTIMATE_DIR.'acp/'.self::CONFIG_FILE);
		if (!$initiatedDefaultLinkCategory) {
			$this->createDefaultLinkCategory();
			$this->updateConfigFile();
		}
	}
	
	/**
	 * Creates the default link category.
	 */
	protected function createDefaultLinkCategory() {
		ObjectTypeCacheBuilder::getInstance()->reset();
		$objectType = CategoryHandler::getInstance()->getObjectTypeByName('de.plugins-zum-selberbauen.ultimate.linkCategory');
		// until it's working, we have to do this
		if ($objectType === null) return;
		if (!isset($objectType->objectTypeID)) exit;
		
		$parameters = array(
			'data' => array(
				'objectTypeID' => $objectType->objectTypeID,
				'parentCategoryID' => 0,
				'showOrder' => 0,
				'title' => 'ultimate.link.category.title.category1',
				'time' => TIME_NOW
			)
		);
		$action = new CategoryAction(array(), 'create', $parameters);
		$returnValues = $action->executeAction();
		$this->categoryID = $returnValues['returnValues']->__get('categoryID');
	}
	
	/**
	 * Rewrites the config file.
	 */
	protected function updateConfigFile() {
		$file = new File(ULTIMATE_DIR.'acp/'.self::CONFIG_FILE);
		$content = '<?php'."\n";
		$content .= '/*'."\n".' * This file was automatically generated. To not modify it.'."\n".' */'."\n\n";
		$content .= '$initiatedDefaultLinkCategory = true;'."\n";
		$content .= '$categoryID = '.(string) $this->categoryID.';'."\n";
		$file->write($content);
		$file->close();
	}
}
