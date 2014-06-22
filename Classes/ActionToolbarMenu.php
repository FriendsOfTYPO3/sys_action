<?php
namespace TYPO3\CMS\SysAction;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Adds action links to the backend's toolbar
 *
 * @author Steffen Kamper <info@sk-typo3.de>
 */
class ActionToolbarMenu implements \TYPO3\CMS\Backend\Toolbar\ToolbarItemHookInterface {

	/**
	 * Reference back to the backend object
	 *
	 * @var \TYPO3\CMS\Backend\Controller\BackendController
	 */
	protected $backendReference;

	/**
	 * @var string
	 */
	protected $extensionKey = 'sys_action';

	/**
	 * Constructor
	 */
	public function __construct(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference = NULL) {
		$GLOBALS['LANG']->includeLLFile('EXT:sys_action/locallang.xlf');
		$this->backendReference = $backendReference;
	}

	/**
	 * Sets the backend reference
	 *
	 * @param \TYPO3\CMS\Backend\Controller\BackendController $backendReference Backend object reference
	 * @return void
	 */
	public function setBackend(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference) {
		$this->backendReference = $backendReference;
	}

	/**
	 * Renders the toolbar menu
	 *
	 * @return string The rendered backend menu
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function render() {
		$actionMenu = array();
		$actionEntries = $this->getActionEntries();
		if ($actionEntries) {
			$this->addJavascriptToBackend();
			$this->addCssToBackend();
			$title = $GLOBALS['LANG']->getLL('action_toolbaritem', TRUE);
			$actionMenu[] = '<a href="#" class="toolbar-item">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-toolbar-menu-actions', array('title' => $title)) . '</a>';
			$actionMenu[] = '<ul class="toolbar-item-menu" style="display: none;">';
			foreach ($actionEntries as $linkConf) {
				$actionMenu[] = '<li><a href="' . htmlspecialchars($linkConf[1]) . '" target="content">' . $linkConf[2] . htmlspecialchars($linkConf[0]) . '</a></li>';
			}
			$actionMenu[] = '</ul>';
			return implode(LF, $actionMenu);
		} else {
			return '';
		}
	}

	/**
	 * Gets the entries for the action menu
	 *
	 * @return array Array of action menu entries
	 * @author Steffen Kamper <info@sk-typo3.de>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	protected function getActionEntries() {
		$actions = array();
		if ($GLOBALS['BE_USER']->isAdmin()) {
			$queryResource = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_action', 'pid = 0 AND hidden=0', '', 'sys_action.sorting');
		} else {
			$groupList = 0;
			if ($GLOBALS['BE_USER']->groupList) {
				$groupList = $GLOBALS['BE_USER']->groupList;
			}
			$queryResource = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'sys_action.*',
				'sys_action',
				'sys_action_asgr_mm',
				'be_groups',
				' AND be_groups.uid IN (' . $groupList . ') AND sys_action.pid = 0 AND sys_action.hidden = 0',
				'sys_action.uid',
				'sys_action.sorting'
			);
		}

		if ($queryResource) {
			while ($actionRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($queryResource)) {
				$actions[] = array(
					$actionRow['title'],
					BackendUtility::getModuleUrl('user_task') . '&SET[mode]=tasks&SET[function]=sys_action.TYPO3\\CMS\\SysAction\\ActionTask&show=' . $actionRow['uid'],
					\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('sys_action', $actionRow)
				);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($queryResource);
		}
		return $actions;
	}

	/**
	 * Returns additional attributes for the list item in the toolbar
	 *
	 * @return string List item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return 'id="tx-sys-action-menu"';
	}

	/**
	 * Adds the necessary javascript ot the backend
	 *
	 * @return void
	 */
	protected function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile(
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->extensionKey) . 'toolbarmenu/tx_sysactions.js'
		);
	}

	/**
	 * Adds the necessary css ot the backend
	 *
	 * @return void
	 */
	protected function addCssToBackend() {
		$this->backendReference->addCssFile(
			'sysaction',
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->extensionKey) . 'toolbarmenu/tx_sysactions.css'
		);
	}

	/**
	 * Checks if user has access to the sys action menu
	 *
	 * @return boolean TRUE if the user has access, FALSE otherwise
	 */
	public function checkAccess() {
		// Taskcenter is enabled for everybody
		return TRUE;
	}

}
